<?php
require_once '_base.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

// Debug logging
function debug_log($message) {
    $log_file = __DIR__ . '/login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

debug_log("=== LOGIN PAGE LOADED ===");
debug_log("Request method: " . $_SERVER['REQUEST_METHOD']);

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

                // --- Check email verification ---
                if ($user['email_verified'] == 0) {
                    debug_log("Email not verified, resending token");

                    $_SESSION['pre_verify_user_id'] = $user['id'];

                    // Check last token to avoid abuse (optional)
                    $stm = $_db->prepare("SELECT * FROM email_verifications WHERE userID = ? ORDER BY expires_at DESC LIMIT 1");
                    $stm->execute([$user['id']]);
                    $lastToken = $stm->fetch(PDO::FETCH_ASSOC);
                    if (!$lastToken || strtotime($lastToken['expires_at']) < time() - 300) {
                        $token = bin2hex(random_bytes(16));
                        $expires = date('Y-m-d H:i:s', time() + 3600);

                        $stm = $_db->prepare("INSERT INTO email_verifications (userID, token, expires_at) VALUES (?, ?, ?)");
                        $stm->execute([$user['id'], $token, $expires]);

                        $verify_link = "http://localhost:8000/user/verify_email.php?token=$token";

                        // Send email
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'njywbisdemo@gmail.com';
                            $mail->Password   = 'cjoj ubfn swhp nqri';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            $mail->setFrom('njywbisdemo@gmail.com', 'Chillax');
                            $mail->addAddress($user['email'], $user['name']);
                            $mail->isHTML(true);
                            $mail->Subject = 'Verify your email';
                            $mail->Body    = "
                                Hello {$user['name']},<br><br>
                                Please verify your email by clicking the link below:<br>
                                <a href='$verify_link'>$verify_link</a><br><br>
                                This link will expire in 1 hour.
                            ";
                            $mail->send();
                        } catch (Exception $e) {
                            debug_log("Failed to send verification email: " . $mail->ErrorInfo);
                        }
                    }

                    temp('info', 'Please verify your email first. A verification email has been sent.');
                    redirect('/user/email_verification_notice.php');
                    exit();
                }

                // --- Check phone verification ---
                if ($user['phone_verified'] == 0) {
                    debug_log("Phone not verified, sending token");

                    $user_id = $user['id'];
                    $phone = $user['phone_number'];

                    // Delete old tokens
                    $stm = $_db->prepare("DELETE FROM phone_verifications WHERE user_id = ?");
                    $stm->execute([$user_id]);

                    // Generate new token
                    $token = random_int(100000, 999999);
                    $expires = date('Y-m-d H:i:s', time() + 600);

                    $stm = $_db->prepare("INSERT INTO phone_verifications (user_id, phone_number, token, expires_at) VALUES (?, ?, ?, ?)");
                    $stm->execute([$user_id, $phone, $token, $expires]);

                    $_SESSION['pre_verify_user_id'] = $user_id;
                    temp('info', "Verification code sent: $token");
                    redirect('/user/verify_phone.php');
                    exit();
                }

                debug_log("Email and phone verified. Logging in");

                // Reset failed attempts
                $stm = $_db->prepare("UPDATE user SET failed_attempts = 0, lock_until = NULL WHERE id = ?");
                $stm->execute([$user['id']]);

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
<html>

<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; background: #01A7C2; margin:0; padding:0; }
        .box { width:50%; padding:25px; background:white; margin:80px auto; border-radius:40px; border-style:groove; border-width:20px; box-shadow:0 4px 20px #A3BAC3; }
        input { width:95%; padding:10px; border:1px solid #bbb; border-radius:5px; margin-top:5px; }
        button { width:90%; background:#4285f4; border:none; padding:12px; color:white; border-radius:5px; cursor:pointer; font-size:16px; display:block; margin:0 auto; }
        button:hover { background:#2f6bd9; }
        a { text-decoration:none; color:#4285f4; }
        .error { color:#d9534f; font-size:13px; }
        label.remember { display:inline-flex; align-items:center; cursor:pointer; }
        label.remember input { width:auto; margin-right:5px; }
        .success { color:#5cb85c; background:#dff0d8; padding:10px; border-radius:5px; margin:10px 0; text-align:center; }
        .warning { color:#8a6d3b; background:#fcf8e3; padding:10px; border-radius:5px; margin:10px 0; text-align:center; }
    </style>
</head>

<body>
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
        <br><br>

        <button type="submit">Login</button>

        <p style="text-align:center; margin-top:15px;">
            <a href="/user/reset.php">Forgot Password?</a>
        </p>
        <p style="text-align:center; margin-top:15px;">
            <a href="/user/register.php">New User? Register Here</a>
        </p>
    </form>
</div>
</body>
</html>
