<?php
include '../_base.php';
// ----------------------------------------------------------------------------
// Authenticated users
auth();

// Use the logged-in user's ID
$user_id = $_user->id;

// Fetch latest phone verification token for the logged-in user
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
    } else if (isset($tokenRow['verified']) && $tokenRow['verified']) {
        $_err['code'] = 'Phone already verified';
    } else if (strtotime($tokenRow['expires_at']) < time()) {
        $_err['code'] = 'Verification code expired. Please request a new one.';
    } else if ($tokenRow['token'] != $code) {
        $_err['code'] = 'Incorrect code';
    } 
    
    // Check for errors before updating
    if (!$_err) {
        // Mark phone verified in the 'user' table
        $stm = $_db->prepare("UPDATE user SET phone_verified=1 WHERE id=?");
        $stm->execute([$user_id]);

        // Mark phone verified in the 'phone_verifications' table
        $stm = $_db->prepare("UPDATE phone_verifications SET verified=1 WHERE id=?");
        $stm->execute([$tokenRow['id']]);

        // Update the global user session object immediately
        $_user->phone_verified = 1;
        $_SESSION['user'] = $_user; 
        
        temp('info', 'Phone verified successfully.');
        redirect('/user/profile.php');
        exit();
    }
}

// ----------------------------------------------------------------------------
$_title = 'Verify Phone';
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($_title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 0; }
        .container { max-width: 400px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin-bottom: 20px; }
        .demo-token { margin-bottom: 15px; color: #3498db; font-size: 0.9rem; text-align: center; }
        form label { display: block; font-weight: 600; margin-bottom: 5px; }
        form input[type="text"] { width: 95%; padding: 10px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 1rem; }
        .err-msg { color: #e74c3c; margin-bottom: 10px; font-size: 0.85rem; }
        form button { width: 100%; padding: 12px; background: #007BFF; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; }
        form button:hover { background: #0056b3; }
        .back-login { text-align: center; margin-top: 15px; font-size: 0.85rem; }
        .back-login a { color: #4285f4; text-decoration: none; }
        .back-login a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Phone Number</h2>

        <?php 
        // Display any temporary info messages (like "code sent")
        $info = temp('info'); 
        if ($info): ?>
            <div style="color:#007BFF; text-align:center; margin-bottom:15px;"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <?php if ($tokenRow): ?>
            <div class="demo-token">
                <strong>Demo Token:</strong> <?= htmlspecialchars($tokenRow['token']) ?>
                <div style="font-size:0.75rem; color:#e67e22;">Expires at: <?= date('g:i A', strtotime($tokenRow['expires_at'])) ?></div>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="code">Enter Verification Code:</label>
            <input type="text" id="code" name="code" maxlength="6" value="<?= htmlspecialchars($code ?? '') ?>">

            <?php if(isset($_err['code'])): ?>
                <div class="err-msg"><?= $_err['code'] ?></div>
            <?php endif; ?>

            <button type="submit">Verify</button>
        </form>

        <div class="back-login">
            <a href="/user/resend_phone_code.php">Resend Code</a> | 
            <a href="/user/profile.php">Back to Profile</a>
        </div>
    </div>
</body>
</html>