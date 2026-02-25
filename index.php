<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retiree Pencare Consent Enrollment</title>
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="container">
        <div class="card">
            <header>
                <h1>RETIREE PENCARE CONSENT</h1>
                <p class="subtitle">Secure Enrollment Portal for Retirees</p>
            </header>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error">
                    <?= htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="message success">
                    <?= htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="check_eligibility.php" method="POST">
                <div class="form-group">
                    <label for="pin">Retiree PIN</label>
                    <input type="text" id="pin" name="pin" placeholder="Enter your PIN" required autofocus
                        autocomplete="off">
                </div>

                <button type="submit" class="btn">Verify Eligibility</button>
            </form>

            <p
                style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted); opacity: 0.6;">
                <a href="reports.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold;">[
                    ADMIN: VIEW REPORTS ]</a>
            </p>
        </div>
    </div>

</body>

</html>