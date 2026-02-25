<?php
// verify.php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['pin']) || empty($_POST['dob']) || empty($_POST['nin'])) {
    header("Location: index.php");
    exit;
}

$pin = trim($_POST['pin']);
$dob_provided = trim($_POST['dob']);
$nin_provided = trim($_POST['nin']);

try {
    // 1. Check if already consented
    $checkSql = "SELECT ConsentID FROM pencare_consent WHERE PIN = :pin";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([':pin' => $pin]);
    if ($checkStmt->fetch()) {
        $_SESSION['error'] = "You have already submitted your consent form.";
        header("Location: index.php");
        exit;
    }

    // 2. Fetch Employee Details
    $sql = "
    SELECT 
        PIN,
        FIRSTNAME,
        SURNAME,
        OTHERNAMES,
        DATE_OF_BIRTH,
        GENDER,
        EMAIL,
        MOBILE_PHONE,
        SSN,
        PERMANENT_ADDRESS
    FROM employees
    WHERE PIN = :pin 
      AND (DATE_OF_BIRTH = :dob1 OR CAST(DATE_OF_BIRTH AS DATE) = :dob2)
      AND SSN = :ssn
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':pin' => $pin,
        ':dob1' => $dob_provided,
        ':dob2' => $dob_provided,
        ':ssn' => $nin_provided
    ]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        $_SESSION['error'] = "Authentication failed. Please verify your PIN, Date of Birth, and NIN.";
        header("Location: validate.php");
        exit;
    }

    // Success - clear temp pin
    unset($_SESSION['temp_pin']);

} catch (PDOException $e) {
    error_log("Verify Error: " . $e->getMessage());
    $_SESSION['error'] = "A system error occurred. Please try again later. (" . $e->getMessage() . ")";
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Details | Retiree Pencare Consent</title>
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="container">
        <div class="card">
        <header>
            <h1>PENCARE HEALTHCARE ENROLMENT – CONSENT FORM (RETIREES)</h1>
            <p class="subtitle">
                This Consent Form authorises Norrenberger pension limited to share personal information with an approved Health Maintenance Organisation (HMO) solely for the purpose of enrolling you into the PenCare Healthcare Programme. Your information will only be processed to facilitate your healthcare enrolment and to enable the HMO provide health services and contact you when necessary.
            </p>
        </header>

            <div class="details-list">
                <?php
                // Safe value helper
                function getVal($arr, $key)
                {
                    return $arr[$key] ?? $arr[strtoupper($key)] ?? $arr[strtolower($key)] ?? '';
                }
                ?>
                <div class="detail-item">
                    <span class="detail-label">Full Name</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(getVal($employee, 'SURNAME') . ' ' . getVal($employee, 'FIRSTNAME') . ' ' . getVal($employee, 'OTHERNAMES')) ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">PIN Reference</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(getVal($employee, 'PIN')) ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date of Birth</span>
                    <span class="detail-value">
                        <?php
                        $dob = getVal($employee, 'DATE_OF_BIRTH');
                        echo $dob ? htmlspecialchars(date('d-F-Y', strtotime($dob))) : 'N/A';
                        ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Gender</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(getVal($employee, 'GENDER')) ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email Address</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(getVal($employee, 'EMAIL')) ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Mobile Number</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(getVal($employee, 'MOBILE_PHONE')) ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">NIN</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(getVal($employee, 'SSN')) ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Permanent Address</span>
                    <span class="detail-value" style="text-align: right; max-width: 60%;">
                        <?= htmlspecialchars(getVal($employee, 'PERMANENT_ADDRESS')) ?>
                    </span>
                </div>
            </div>

            <form action="submit.php" method="POST">
                <input type="hidden" name="pin" value="<?= htmlspecialchars(getVal($employee, 'PIN')) ?>">

                <label class="consent-group">
                    <input type="checkbox" name="consent" value="1" required>
                    <div class="consent-text">
                        I hereby by consent to Norrenberger pension limited processing and sharing my personal data with
                        an approved HMO solely to enrol me in the PenCare Healthcare Programme. I understand this
                        purpose and my rights under the Nigeria Data Protection Act (NDPA) 2023.
                    </div>
                </label>

                <button type="submit" class="btn">Confirm & Enroll</button>
                <a href="index.php" class="back-link">Cancel and return</a>
            </form>
        </div>
    </div>

</body>

</html>