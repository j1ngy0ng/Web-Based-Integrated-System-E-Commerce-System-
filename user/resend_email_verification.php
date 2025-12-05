<?php
require_once '../_base.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php'; // PHPMailer autoload

$email = req('email');

if (!$email) {
    $_SESSION['error'] = "Please enter your email.";
    redirect('resend_email_verification_form.php');
}

// Find user
$stm = $_db->prepare("SELECT * FROM user WHERE email = ?");
$stm->execute([$email]);
$user = $stm->fetch(PDO::FETCH_OBJ);

if (!$user) {
    $_SESSION['error'] = "No account found with that email.";
    redirect('resend_email_verification_form.php');
}

// Generate new token
$token = bin2hex(random_bytes(16));
$expires = date('Y-m-d H:i:s', time() + 3600);

// Insert into email_verifications table
$stm = $_db->prepare("
    INSERT INTO email_verifications (userID, token, expires_at)
    VALUES (?, ?, ?)
");
$stm->execute([$user->id, $token, $expires]);

// Prepare verification link
$verify_link = "http://localhost:8000/user/verify_email.php?token=$token";

// Send email using PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'njywbisdemo@gmail.com';
    $mail->Password   = 'cjoj ubfn swhp nqri'; // App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('njywbisdemo@gmail.com', 'Chillax');
    $mail->addAddress($email, $user->name);

    $mail->isHTML(true);
    $mail->Subject = 'Resend Email Verification';
    $mail->Body    = "
        Hello {$user->name},<br><br>
        Please verify your email by clicking the link below:<br>
        <a href='$verify_link'>$verify_link</a><br><br>
        This link will expire in 1 hour.
    ";

    $mail->send();
    $_SESSION['success'] = "A new verification email has been sent.";
} catch (Exception $e) {
    $_SESSION['error'] = "Mailer Error: {$mail->ErrorInfo}";
}

// Redirect to notice page
redirect('email_verification_notice.php');
