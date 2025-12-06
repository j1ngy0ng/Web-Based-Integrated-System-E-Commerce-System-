<?php
include '../_base.php';
auth();

$roomID = req('roomID');
if (!$roomID) {
    http_response_code(400);
    echo json_encode(['error' => 'roomID is required']);
    exit;
}

// Fetch messages along with sender name
$stm = $_db->prepare("
    SELECT 
        cm.msgID, 
        cm.roomID, 
        cm.senderID, 
        cm.message, 
        cm.created_at,
        u.name AS sender_name
    FROM chat_message cm
    JOIN user u ON cm.senderID = u.id
    WHERE cm.roomID = ?
    ORDER BY cm.msgID ASC
");
$stm->execute([$roomID]);

$messages = $stm->fetchAll(PDO::FETCH_ASSOC);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($messages);
