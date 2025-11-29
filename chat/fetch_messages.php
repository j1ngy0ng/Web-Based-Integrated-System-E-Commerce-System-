<?php
include '../_base.php';
auth();

$roomID = req('roomID');

$stm = $_db->prepare("
    SELECT msgID, roomID, senderID, message, created_at
    FROM chat_message
    WHERE roomID = ?
    ORDER BY msgID ASC
");
$stm->execute([$roomID]);

echo json_encode($stm->fetchAll());
?>
