<?php
require_once '_base.php';

// Debug logging function
function debug_log($message) {
    $log_file = __DIR__ . '/login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

// Start debugging
debug_log("=== LOGIN PAGE LOADED ===");
debug_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Initialize variables
$email = post('email');
$password = post('password');
$remember = post('remember');
$GLOBALS['remember'] = $remember;

debug_log("Email received: " . ($email ?: 'empty'));
debug_log("Remember received: " . ($remember ? 'YES' : 'NO'));

// Process form submission
if (is_post()) {
    debug_log("Form submitted via POST method");

    // Validate inputs
    if ($email == '') {
        $_err['email'] = 'Email is required';
        debug_log("Email validation failed: empty");
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
        debug_log("Email validation failed: invalid format");
    }

    if ($password == '') {
        $_err['password'] = 'Password is required';
        debug_log("Password validation failed: empty");
    }

    // Proceed if no errors
    if (!$_err) {
        debug_log("No validation errors, checking database");
        
        $stm = $_db->prepare("SELECT * FROM user WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            debug_log("User found in database: " . $user['email']);
            debug_log("User ID: " . $user['id']);
            debug_log("Phone verified: " . $user['phone_verified']);
            debug_log("Is active: " . $user['is_active']);

            // Check account status
            if (!$user['is_active']) {
                $_err['email'] = 'Account is blocked. Contact admin.';
                debug_log("Account blocked - not active");
            } else if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
                $remaining = ceil((strtotime($user['lock_until']) - time()) / 60);
                $_err['password'] = "Account temporarily locked. Try again in $remaining minutes.";
                debug_log("Account temporarily locked");
            } else if ($user['password'] === sha1($password)) {
                debug_log("Password verification: SUCCESS");

                // Check phone verification
                if ($user['phone_verified'] == 0) {
                    $_err['email'] = 'Please verify your phone number before logging in';
                    debug_log("Login failed: phone not verified");
                } else {
                    debug_log("Phone verification: SUCCESS");

                    // Reset failed attempts
                    $stm = $_db->prepare("UPDATE user SET failed_attempts = 0, lock_until = NULL WHERE id = ?");
                    $stm->execute([$user['id']]);
                    debug_log("Reset failed attempts for user: " . $user['id']);

                    // Login user
                    login((object)$user);
                    temp('info', 'Login successfully');
                    debug_log("User logged in successfully");

                    // Handle Remember Me functionality
                    if (!empty($remember)) {
                        debug_log("Remember me requested - starting token creation");
                        
                        $selector = bin2hex(random_bytes(6));
                        $validator = bin2hex(random_bytes(32));
                        $hashedValidator = hash('sha256', $validator);
                        $expires = date('Y-m-d H:i:s', time() + (86400 * 30));

                        debug_log("Token details:");
                        debug_log("  - Selector: $selector");
                        debug_log("  - Hashed validator: $hashedValidator");
                        debug_log("  - Expires: $expires");
                        debug_log("  - User ID: " . $user['id']);

                        try {
                            $stm = $_db->prepare("
                                INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) 
                                VALUES (?, ?, ?, ?)
                            ");
                            debug_log("Executing SQL insert...");
                            
                            $result = $stm->execute([$user['id'], $selector, $hashedValidator, $expires]);
                            
                            if ($result) {
                                $rows = $stm->rowCount();
                                debug_log("SUCCESS: Remember token inserted. Rows affected: $rows");
                                
                                setcookie(
                                    "remember_me",
                                    $selector . ':' . $validator,
                                    time() + (86400 * 30),
                                    "/",
                                    "",
                                    false,
                                    true
                                );
                                debug_log("Cookie set successfully");
                            } else {
                                debug_log("FAILED: Insert execution returned false");
                                $errorInfo = $stm->errorInfo();
                                debug_log("PDO Error info: " . print_r($errorInfo, true));
                            }
                        } catch (PDOException $e) {
                            debug_log('PDO EXCEPTION: ' . $e->getMessage());
                            debug_log('Error Code: ' . $e->getCode());
                        } catch (Exception $e) {
                            debug_log('GENERAL EXCEPTION: ' . $e->getMessage());
                        }
                    } else {
                        debug_log("Remember me NOT requested");
                    }

                    debug_log("Redirecting to homepage...");
                    redirect('/');
                    exit();
                }
            } else {
                debug_log("Password verification: FAILED");
                
                // Handle failed login attempts
                $failed_attempts = $user['failed_attempts'] + 1;
                $lock_until = null;
                
                if ($failed_attempts >= 3) {
                    $lock_until = date('Y-m-d H:i:s', time() + 300);
                    $failed_attempts = 0;
                    debug_log("Account locked for 5 minutes due to 3 failed attempts");
                }
                
                $stm = $_db->prepare("UPDATE user SET failed_attempts = ?, lock_until = ? WHERE id = ?");
                $stm->execute([$failed_attempts, $lock_until, $user['id']]);

                $_err['password'] = 'Incorrect password';
                debug_log("Updated failed attempts to: $failed_attempts");
            }
        } else {
            debug_log("User NOT found in database for email: " . $email);
            $_err['email'] = 'Email not registered';
        }
    } else {
        debug_log("Validation errors present: " . count($_err));
    }
} else {
    debug_log("Page loaded via GET method");
}

debug_log("=== LOGIN PROCESS COMPLETED ===");
?>


<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial;
            background: #01A7C2;
            margin: 0;
            padding: 0;
        }

        .box {
            width: 50%;
            padding: 25px;
            background: white;
            margin: 80px auto;
            border-radius: 40px;
            border-style: groove;
            border-width: 20px;
            box-shadow: 0 4px 20px #A3BAC3;
        }

        input {
            width: 95%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            margin-top: 5px;
        }

        button {
            width: 90%;
            background: #4285f4;
            border: none;
            padding: 12px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 0 auto;
        }

        button:hover {
            background: #2f6bd9;
        }

        a {
            text-decoration: none;
            color: #4285f4;
        }

        .error {
            color: #d9534f;
            font-size: 13px;
        }

        label.remember {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        label.remember input {
            width: auto;
            margin-right: 5px;
        }
        
        .success {
            color: #5cb85c;
            background: #dff0d8;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }
        
        .warning {
            color: #8a6d3b;
            background: #fcf8e3;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2 style="text-align:center;">Login</h2>
        
        <?php if (temp('info')): ?>
            <div class="success"><?= temp('info') ?></div>
        <?php endif; ?>
        
        <?php if (temp('error')): ?>
            <div class="warning"><?= temp('error') ?></div>
        <?php endif; ?>
        
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