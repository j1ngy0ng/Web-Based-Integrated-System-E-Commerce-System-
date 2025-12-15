<?php
include '../../_base.php';
auth("Admin");

// Fetch all chat rooms with last message info and unread count
$stm = $_db->query("
    SELECT 
        cr.roomID, 
        u.name AS member_name,
        -- Last message text
        (SELECT message 
         FROM chat_message 
         WHERE roomID = cr.roomID 
         ORDER BY msgID DESC 
         LIMIT 1) AS last_msg,
        -- Last message time
        (SELECT created_at 
         FROM chat_message 
         WHERE roomID = cr.roomID 
         ORDER BY msgID DESC 
         LIMIT 1) AS last_time,
        -- Count of unread messages from member
        (SELECT COUNT(*) 
         FROM chat_message 
         WHERE roomID = cr.roomID
           AND senderID = cr.userID
           AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
        ) AS unread_count
    FROM chat_room cr
    JOIN user u ON cr.userID = u.id
    ORDER BY last_time DESC
");



// Current page and rows per page
$page  = max(1, intval(req('page', 1)));
$limit = 10;

// Count total chat rooms
$totalRows = $_db->query("SELECT COUNT(*) FROM chat_room")->fetchColumn();

// Get pagination details
$pagination = paginate($totalRows, $page, $limit);

$sql = "
    SELECT 
        cr.roomID, 
        u.name AS member_name,
        (SELECT message 
         FROM chat_message 
         WHERE roomID = cr.roomID 
         ORDER BY msgID DESC 
         LIMIT 1) AS last_msg,
        (SELECT created_at 
         FROM chat_message 
         WHERE roomID = cr.roomID 
         ORDER BY msgID DESC 
         LIMIT 1) AS last_time,
        (SELECT COUNT(*) 
         FROM chat_message 
         WHERE roomID = cr.roomID
           AND senderID = cr.userID
           AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
        ) AS unread_count
    FROM chat_room cr
    JOIN user u ON cr.userID = u.id
    ORDER BY last_time DESC
    LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}
";

$rooms = $_db->query($sql)->fetchAll(PDO::FETCH_OBJ);


$_title = "Support Chat Rooms";
include '../../navbar.php';
?>

<style>
body { font-family: Arial, sans-serif; background: #fff; padding: 30px; margin-bottom: 30px; }
h2 { margin-bottom: 20px; text-align:center; }
table { width: 90%; margin: 30px auto; border-collapse: collapse; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; }
th, td { padding: 12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#F4F6F9; }
tr:hover { background:#f5f5f5; }
.btn-open { padding:8px 12px; background:#007bff; color:white; border-radius:5px; text-decoration:none; }
.btn-open:hover { background:#0056b3; }
.badge { background:red; color:white; font-weight:bold; padding:2px 6px; border-radius:8px; margin-left:5px; font-size:12px; }
</style>

<h2>Support Chat Rooms</h2>

<table>
    <tr>
        <th>Member</th>
        <th>Last Message</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    <?php foreach ($rooms as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r->member_name) ?></td>
            <td>
                <?= htmlspecialchars($r->last_msg ?? "No messages yet") ?>
                <?php if ($r->unread_count > 0): ?>
                    <span class="badge"><?= $r->unread_count ?> new</span>
                <?php endif; ?>
            </td>
            <td><?= $r->last_time ?? "-" ?></td>
            <td>
                <a href="chat.php?roomID=<?= $r->roomID ?>" class="btn-open">Open Chat</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<div style="width:90%; margin:20px auto; text-align:center;">
    <?php render_pagination($pagination['page'], $pagination['totalPages']); ?>
</div>

<?php include '../../footer.php'; ?>
