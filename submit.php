<?php
// submit.php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Validate inputs
if (!isset($_POST['consent']) || empty($_POST['pin'])) {
    $_SESSION['error'] = "Consent is required to proceed.";
    header("Location: index.php");
    exit;
}

$pin = trim($_POST['pin']);

try {
    // Double check for duplicate before inserting (Race condition mitigation)
    $checkSql = "SELECT ConsentID FROM pencare_consent WHERE PIN = :pin";
    $stmt = $conn->prepare($checkSql);
    $stmt->execute([':pin' => $pin]);
    if ($stmt->fetch()) {
        $_SESSION['success'] = "You have already completed the enrollment."; // Treat as success to avoid confusion
        header("Location: index.php");
        exit;
    }

    // Insert from Employees table
    $sql = "
    INSERT INTO pencare_consent (
        PIN,
        FirstName,
        Surname,
        OtherNames,
        DateOfBirth,
        Gender,
        Email,
        MobileNumber,
        NIN,
        Address,
        ConsentGiven,
        IPAddress,
        UserAgent
    )
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
        PERMANENT_ADDRESS,
        1,
        :ip,
        :agent
    FROM employees
    WHERE PIN = :pin
    ";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':pin' => $pin,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':agent' => $_SERVER['HTTP_USER_AGENT']
    ]);

    // Check if any row was inserted (i.e. if the PIN actually existed in employees)
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Consent recorded successfully! You are now enrolled.";
    } else {
        $_SESSION['error'] = "Failed to record consent. Employee record not found.";
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate)
        $_SESSION['success'] = "You were already enrolled.";
    } else {
        $_SESSION['error'] = "A database error occurred during submission: " . $e->getMessage();
    }
}

header("Location: index.php");
exit;
?>