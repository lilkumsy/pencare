<?php
// validate.php
session_start();
if (!isset($_SESSION['temp_pin'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity Validation | Retiree Pencare Consent</title>
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="container">
        <div class="card">
            <header>
                <h1>Identity Validation</h1>
                <p class="subtitle">Please provide your details to proceed</p>
            </header>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error">
                    <?= htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="verify.php" method="POST">
                <input type="hidden" name="pin" value="<?= htmlspecialchars($_SESSION['temp_pin']) ?>">

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required style="cursor: pointer;">
                    <small
                        style="display: block; margin-top: 0.5rem; color: var(--text-muted); font-size: 0.75rem;">Confirm
                        your birth date as registered in PENCOM record.</small>
                </div>

                <div class="form-group">
                    <label for="nin">NIN (National Identity Number)</label>
                    <input type="text" id="nin" name="nin" placeholder="Enter your 11-digit NIN" required
                        autocomplete="off">
                </div>

                <button type="submit" class="btn">Proceed to Consent</button>
                <a href="index.php" class="back-link">Return to Start</a>
            </form>
        </div>
    </div>

</body>

</html>