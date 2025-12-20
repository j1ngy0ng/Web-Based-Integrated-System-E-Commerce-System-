<?php
include '../../_base.php';
auth('Admin','Superadmin');

// Fetch latest unread messages per room
$sqlUnreadRooms = "
    SELECT cr.roomID, u.name,
           (SELECT message FROM chat_message 
            WHERE roomID = cr.roomID 
              AND senderID = cr.userID 
              AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
            ORDER BY msgID DESC LIMIT 1) AS last_msg,
           (SELECT COUNT(*) 
            FROM chat_message 
            WHERE roomID = cr.roomID 
              AND senderID = cr.userID
              AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
           ) AS unread_count
    FROM chat_room cr
    JOIN user u ON cr.userID = u.id
    HAVING unread_count > 0
    ORDER BY unread_count DESC, cr.roomID ASC
";
$unreadRooms = $_db->query($sqlUnreadRooms)->fetchAll(PDO::FETCH_OBJ);

// Total unread messages
$totalUnreadChats = 0;
foreach ($unreadRooms as $r) $totalUnreadChats += $r->unread_count;

echo json_encode([
    'total' => $totalUnreadChats,
    'rooms' => $unreadRooms
]);
