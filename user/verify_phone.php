<?php
include '../_base.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------
// Ensure user ID exists for pre-login verification
if (!isset($_SESSION['pre_verify_user_id'])) {
    redirect('/login.php'); // nothing to verify
}

$user_id = $_SESSION['pre_verify_user_id'];

// Fetch latest phone verification token
$stm = $_db->prepare("
    SELECT * FROM phone_verifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stm->execute([$user_id]);
$tokenRow = $stm->fetch(PDO::FETCH_ASSOC);

$code = post('code');

if (is_post()) {
    if (!$code) {
        $_err['code'] = 'Please enter verification code';
    } else if (!$tokenRow) {
        $_err['code'] = 'No verification code found. Please request a new one.';
    } else if ($tokenRow['verified']) {
        $_err['code'] = 'Phone already verified';
    } else if (strtotime($tokenRow['expires_at']) < time()) {
        $_err['code'] = 'Verification code expired. Please request a new one.';
    } else if ($tokenRow['token'] != $code) {
        $_err['code'] = 'Incorrect code';
    } else {
        // Mark phone verified
        $stm = $_db->prepare("UPDATE user SET phone_verified=1 WHERE id=?");
        $stm->execute([$user_id]);

        $stm = $_db->prepare("UPDATE phone_verifications SET verified=1 WHERE id=?");
        $stm->execute([$tokenRow['id']]);

        unset($_SESSION['pre_verify_user_id']); // remove temporary session

        temp('info', 'Phone verified. You can now login.');
        redirect('/login.php');
        exit();
    }
}

// ----------------------------------------------------------------------------
$_title = 'Verify Phone';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Phone</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .demo-token {
            margin-bottom: 15px;
            color: #3498db;
            font-size: 0.9rem;
            text-align: center;
        }
        form label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        form input[type="text"] {
            width: 95%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        .err-msg {
            color: #e74c3c;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        form button {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }
        form button:hover {
            background: #0056b3;
        }
        .back-login {
            text-align: center;
            margin-top: 15px;
            font-size: 0.85rem;
        }
        .back-login a {
            color: #4285f4;
            text-decoration: none;
        }
        .back-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Phone Number</h2>

        <?php if ($tokenRow): ?>
            <div class="demo-token">
                <strong>Demo Token:</strong> <?= htmlspecialchars($tokenRow['token']) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="code">Enter Verification Code:</label>
            <input type="text" id="code" name="code" maxlength="6">

            <?php if(isset($_err['code'])): ?>
                <div class="err-msg"><?= $_err['code'] ?></div>
            <?php endif; ?>

            <button type="submit">Verify</button>
        </form>

        <div class="back-login">
            <a href="/login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
