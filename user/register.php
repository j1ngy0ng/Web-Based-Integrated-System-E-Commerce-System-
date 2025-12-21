<?php
require_once '../_base.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php'; // Adjust path if needed

if (is_post()) {

    $email    = req('email');
    $password = req('password');
    $confirm  = req('confirm');
    $name     = req('name');
    $phone    = req('phone'); // phone number
    $f        = get_file('photo');

    // --- Validation ---
    if ($name == '') $_err['name'] = 'Name is required';

    if ($email == '') $_err['email'] = 'Email is required';
    else if (!is_email($email)) $_err['email'] = 'Invalid email format';
    else if (!is_unique($email, 'user', 'email')) $_err['email'] = 'Email already exists';

    if ($phone == '') {
        $_err['phone'] = 'Phone number is required';
    } else if (!is_phone($phone)) {
        $_err['phone'] = 'Invalid Malaysian phone number';
    }

    if ($password == '') $_err['password'] = 'Password is required';
    else if (strlen($password) < 4) $_err['password'] = 'Password must be at least 4 characters';

    if ($confirm != $password) $_err['confirm'] = 'Passwords do not match';

    if (!$f) $_err['photo'] = 'Required';
    else if (!str_starts_with($f->type, 'image/')) $_err['photo'] = 'Must be image';
    else if ($f->size > 1 * 1024 * 1024) $_err['photo'] = 'Maximum 1MB';

    if (!$_err) {
        // --- Save photo ---
        $photo = save_photo($f, '/photos');

        // --- Insert user ---
        $stm = $_db->prepare('
            INSERT INTO user (email, password, name, photo, role, failed_attempts, lock_until, is_active, phone_number, phone_verified, email_verified)
            VALUES (?, SHA1(?), ?, ?, "Member", 0, NULL, 1, ?, 0, 0)
        ');
        $stm->execute([$email, $password, $name, $photo, $phone]);

        $user_id = $_db->lastInsertId();

        // --- Generate phone verification token ---
        $phone_token = random_int(100000, 999999);
        $phone_expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

        $stm = $_db->prepare("
            INSERT INTO phone_verifications (user_id, phone_number, token, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stm->execute([$user_id, $phone, $phone_token, $phone_expires]);

        // --- Generate email verification token ---
        $email_token = bin2hex(random_bytes(16));
        $email_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $stm = $_db->prepare("
            INSERT INTO email_verifications (userID, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stm->execute([$user_id, $email_token, $email_expires]);

        // --- Send email using PHPMailer ---
        $verify_link = "http://localhost:8000/user/verify_email.php?token=$email_token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'njywbisdemo@gmail.com';
            $mail->Password   = 'cjoj ubfn swhp nqri'; // Use App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('njywbisdemo@gmail.com', 'Chillax');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Verify your email';
            $mail->Body    = "
                Hello $name,<br><br>
                Please verify your email by clicking the link below:<br>
                <a href='$verify_link'>$verify_link</a><br><br>
                This link will expire in 1 hour.
            ";

            $mail->send();

            // --- Redirect to email notice page ---
            $_SESSION['pre_verify_user_id'] = $user_id;
            redirect('/user/email_verification_notice.php');
            exit();
        } catch (Exception $e) {
            temp('error', "Mailer Error: {$mail->ErrorInfo}");
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
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
            background: #34a853;
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
            background: #2a8f45;
        }

        a {
            text-decoration: none;
            color: #4285f4;
        }

        .success {
            color: #2a8f45;
            text-align: center;
            font-size: 14px;
            margin-bottom: 10px;
        }

        label.upload img {
            display: block;
            border: 1px solid #333;
            width: 200px;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }

        .success {
            color: #2a8f45;
            text-align: center;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .warning,
        .err {
            color: #e74c3c;
            text-align: left;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2 style="text-align:center;">Register</h2>

        <?php if (temp('success')): ?>
            <div class="success"><?= temp('success') ?></div>
        <?php endif; ?>
        <?php if (temp('info')): ?>
            <div class="success"><?= temp('info') ?></div>
        <?php endif; ?>
        <?php if (temp('error')): ?>
            <div class="warning"><?= temp('error') ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

            <label for="name">Name</label>
            <?php html_text('name'); ?>
            <?php err('name'); ?>
            <br><br>

            <label for="email">Email</label>
            <?php html_text('email'); ?>
            <?php err('email'); ?>
            <br><br>

            <label for="phone">Phone Number</label>
            <?php html_text('phone'); ?>
            <?php err('phone'); ?>
            <br><br>

            <label for="password">Password</label>
            <?php html_password('password'); ?>
            <?php err('password'); ?>
            <br><br>

            <label for="confirm">Confirm Password</label>
            <?php html_password('confirm'); ?>
            <?php err('confirm'); ?>
            <br><br>

            <label for="photo">Photo</label>
            <label class="upload" tabindex="0">
                <?= html_file('photo', 'image/*', 'hidden') ?>
                <img src="/images/photo.jpg">
            </label>
            <?= err('photo') ?>
            <br><br>

            <button type="submit">Register</button>

            <p style="text-align:center; margin-top:15px;">
                <a href="../login.php">Already have an account? Login</a>
            </p>
        </form>
    </div>
</body>

</html>