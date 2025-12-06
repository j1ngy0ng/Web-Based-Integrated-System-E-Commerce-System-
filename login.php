<?php
require_once '_base.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'vendor/autoload.php';

// --- Debug Logging ---
function debug_log($message) {
    $log_file = __DIR__ . '/login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

debug_log("=== LOGIN PAGE LOADED ===");
debug_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// --- Handle POST ---
$email = post('email');
$password = post('password');
$remember = post('remember');
$GLOBALS['remember'] = $remember;

debug_log("Email received: " . ($email ?: 'empty'));
debug_log("Remember received: " . ($remember ? 'YES' : 'NO'));

if (is_post()) {
    debug_log("Form submitted via POST");

    // --- Validation ---
    if ($email == '') $_err['email'] = 'Email is required';
    else if (!is_email($email)) $_err['email'] = 'Invalid email format';

    if ($password == '') $_err['password'] = 'Password is required';

    if (!$_err) {
        $stm = $_db->prepare("SELECT * FROM user WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            debug_log("User found: {$user['email']} (ID: {$user['id']})");

            if (!$user['is_active']) {
                $_err['email'] = 'Account is blocked. Contact admin.';
                debug_log("Account blocked");
            } else if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
                $remaining = ceil((strtotime($user['lock_until']) - time()) / 60);
                $_err['password'] = "Account temporarily locked. Try again in $remaining minutes.";
                debug_log("Account temporarily locked");
            } else if ($user['password'] === sha1($password)) {
                debug_log("Password verification SUCCESS");

                // Email & phone verification logic...
                // (keep your existing verification code here)

                login((object)$user);
                temp('info', 'Login successfully');

                // Remember Me
                if (!empty($remember)) {
                    $selector = bin2hex(random_bytes(6));
                    $validator = bin2hex(random_bytes(32));
                    $hashedValidator = hash('sha256', $validator);
                    $expires = date('Y-m-d H:i:s', time() + (86400 * 30));

                    try {
                        $stm = $_db->prepare("INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
                        $stm->execute([$user['id'], $selector, $hashedValidator, $expires]);
                        setcookie("remember_me", $selector . ':' . $validator, time() + (86400 * 30), "/", "", false, true);
                    } catch (Exception $e) {
                        debug_log("Remember me token error: " . $e->getMessage());
                    }
                }

                redirect('/');
                exit();

            } else {
                debug_log("Password verification FAILED");
                $failed_attempts = $user['failed_attempts'] + 1;
                $lock_until = null;
                if ($failed_attempts >= 3) {
                    $lock_until = date('Y-m-d H:i:s', time() + 300);
                    $failed_attempts = 0;
                    debug_log("Account locked 5 mins due to 3 failed attempts");
                }
                $stm = $_db->prepare("UPDATE user SET failed_attempts = ?, lock_until = ? WHERE id = ?");
                $stm->execute([$failed_attempts, $lock_until, $user['id']]);
                $_err['password'] = 'Incorrect password';
            }
        } else {
            $_err['email'] = 'Email not registered';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chillax - Login</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; }
        .container { display:flex; min-height:100vh; }
        /* Left: Product Info */
        .hero { flex:1; background:#01A7C2; color:white; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:50px; text-align:center; }
        .hero h1 { font-size:50px; margin-bottom:20px; }
        .hero p { font-size:20px; margin-bottom:20px; }
        .hero ul { list-style: none; padding:0; font-size:18px; }
        .hero ul li { margin:10px 0; }
        /* Right: Login Form */
        .login { flex:1; display:flex; justify-content:center; align-items:center; background:#f5f5f5; }
        .box { width:80%; max-width:400px; background:white; padding:40px; border-radius:20px; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
        input { width:100%; padding:10px; border:1px solid #bbb; border-radius:5px; margin-top:5px; }
        button { width:100%; background:#4285f4; border:none; padding:12px; color:white; border-radius:5px; cursor:pointer; font-size:16px; margin-top:15px; }
        button:hover { background:#2f6bd9; }
        a { text-decoration:none; color:#4285f4; }
        .error { color:#d9534f; font-size:13px; }
        label.remember { display:inline-flex; align-items:center; cursor:pointer; margin-top:10px; }
        label.remember input { width:auto; margin-right:5px; }
    </style>
</head>
<body>
<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <img id="logo" src="/images/logo.png" alt="Logo" style="height:150px; margin-bottom:20px;">
        <h1>Welcome to Chillax!</h1>
        <p>Born from Wellness – The Art of Relaxation</p>
        <ul>
            <li>Chillax was founded with a simple mission: to bring harmony between healthcare and beauty. We believe that true beauty comes from within, and wellness is the foundation of confidence.</li>
        </ul>
    </div>

    <!-- Login Form -->
    <div class="login">
        <div class="box">
            <h2 style="text-align:center;">Login</h2>
            <form method="post">
                <label for="email">Email</label>
                <?php html_text('email'); ?>
                <?php err('email'); ?>
                <br><br>

                <label for="password">Password</label>
                <?php html_password('password'); ?>
                <?php err('password'); ?>
                <br><br>

                <label class="remember">
                    <?php html_checkbox('remember', 'Remember Me'); ?>
                </label>
                <br>

                <button type="submit">Login</button>

                <p style="text-align:center; margin-top:15px;">
                    <a href="/user/reset.php">Forgot Password?</a>
                </p>
                <p style="text-align:center; margin-top:15px;">
                    <a href="/user/register.php">New User? Register Here</a>
                </p>
            </form>
        </div>
    </div>
</div>
</body>
</html>
