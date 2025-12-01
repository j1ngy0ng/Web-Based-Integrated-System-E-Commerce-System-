<?php
require_once '../_base.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['token'])) {
    temp('error', 'Invalid verification link.');
    redirect('../login.php');
    exit();
}

$token = req('token');

// Fetch the verification record
$stm = $_db->prepare("SELECT * FROM email_verifications WHERE token = ?");
$stm->execute([$token]);
$verify = $stm->fetch(PDO::FETCH_ASSOC);

if (!$verify) {
    temp('error', 'Invalid or expired verification link.');
    redirect('../login.php');
    exit();
}

// Check expiration
if (strtotime($verify['expires_at']) < time()) {
    temp('error', 'Verification link has expired.');
    redirect('../login.php');
    exit();
}

// Mark email as verified
$stm = $_db->prepare("UPDATE user SET email_verified = 1 WHERE id = ?");
$stm->execute([$verify['userID']]);

// Delete the verification record
$stm = $_db->prepare("DELETE FROM email_verifications WHERE token = ?");
$stm->execute([$token]);

// Fetch updated user record
$stm = $_db->prepare("SELECT * FROM user WHERE id = ?");
$stm->execute([$verify['userID']]);
$user = $stm->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    temp('error', 'User not found.');
    redirect('../login.php');
    exit();
}

// Check phone verification
if ($user['phone_verified'] == 0) {
    // Phone not verified, redirect to phone verification
    $_SESSION['pre_verify_user_id'] = $user['id'];
    temp('info', 'Email verified. Please verify your phone number to complete login.');
    redirect('/user/verify_phone.php');
    exit();
}

// Both email and phone verified -> log in
login((object)$user);
temp('info', 'Email and phone verified. You are now logged in.');
redirect('/');
exit();
?>
