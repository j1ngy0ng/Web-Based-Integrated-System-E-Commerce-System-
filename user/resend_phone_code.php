<?php
include '../_base.php';
auth();

$user_id = $_user->id;
$phone = $_user->phone_number;

if (!$phone) {
    temp('error', 'No phone number set.');
    redirect('/user/profile.php');
}

// Generate new token
$token = random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', time() + 600); // 10 min

// Insert into phone_verifications
$stm = $_db->prepare("
    INSERT INTO phone_verifications (user_id, phone_number, token, expires_at) 
    VALUES (?, ?, ?, ?)
");
$stm->execute([$user_id, $phone, $token, $expires]);

// TODO: Integrate actual SMS API here
temp('info', "Verification code sent: $token (for demo, displayed here)");

redirect('/user/verify_phone_session.php');