<?php
// check_eligibility.php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['pin'])) {
    header("Location: index.php");
    exit;
}

$pin = trim($_POST['pin']);

try {
    // 1. Check eligibility in exit_process_master
    $sql = "SELECT pin FROM exit_process_master WHERE pin = :pin";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':pin' => $pin]);
    $isEligible = $stmt->fetch();

    if (!$isEligible) {
        $_SESSION['error'] = "User not eligible for Pencare HMO enrollment.";
        header("Location: index.php");
        exit;
    }

    // 2. Check if already consented
    $checkSql = "SELECT ConsentID FROM pencare_consent WHERE PIN = :pin";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([':pin' => $pin]);
    if ($checkStmt->fetch()) {
        $_SESSION['error'] = "You have already submitted your consent form.";
        header("Location: index.php");
        exit;
    }

    // 3. Eligible - store pin in session and proceed to validation
    $_SESSION['temp_pin'] = $pin;
    header("Location: validate.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "A system error occurred. Please try again later. (" . $e->getMessage() . ")";
    header("Location: index.php");
    exit;
}
?>