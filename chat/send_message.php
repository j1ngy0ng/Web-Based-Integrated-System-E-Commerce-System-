<?php
include '../_base.php';
auth();  // works for both Admin and Member

$roomID  = req('roomID');
$message = req('message');
$sender  = $_user->id;

if (!$roomID || !$message) exit;

// Insert message
$stm = $_db->prepare("
    INSERT INTO chat_message (roomID, senderID, message)
    VALUES (?, ?, ?)
");
$stm->execute([$roomID, $sender, $message]);

// --- Update last_read_at if sender is admin ---
if ($_user->role === 'Admin') {
    $stm = $_db->prepare("
        UPDATE chat_room
        SET last_read_at = NOW()
        WHERE roomID = ?
    ");
    $stm->execute([$roomID]);
}
?>
