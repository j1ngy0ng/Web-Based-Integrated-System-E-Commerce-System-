<?php
include '../_base.php';
auth();

$roomID  = req('roomID');
$message = req('message');
$sender  = $_user->id;

if (!$roomID || !$message) exit;

$stm = $_db->prepare("
    INSERT INTO chat_message (roomID, senderID, message)
    VALUES (?, ?, ?)
");
$stm->execute([$roomID, $sender, $message]);
?>
