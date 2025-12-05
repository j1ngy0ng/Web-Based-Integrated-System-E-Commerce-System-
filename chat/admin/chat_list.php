<?php
include '../../_base.php';
auth("Admin");

$stm = $_db->query("
    SELECT cr.roomID, u.name,
           (SELECT message FROM chat_message WHERE roomID = cr.roomID ORDER BY msgID DESC LIMIT 1) AS last_msg,
           (SELECT created_at FROM chat_message WHERE roomID = cr.roomID ORDER BY msgID DESC LIMIT 1) AS last_time
    FROM chat_room cr
    JOIN user u ON cr.userID = u.id
    ORDER BY last_time DESC
");

$rooms = $stm->fetchAll();

$_title = "Chat Rooms";
include '../../navbar.php';
?>

<style>
    body {
        font-family: Arial, sans-serif;
        background: #fff;
        padding: 30px;
        margin-bottom: 30px
    }

    h2 {
        margin-bottom: 20px;
    }

    table {
        width: 90%;
        margin: 30px auto;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    th,
    td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }

    th {
        background: #F4F6F9;
        color: black;
    }

    tr:hover {
        background: #f5f5f5;
    }

    .btn-open {
        padding: 8px 12px;
        background: #007bff;
        color: white;
        border-radius: 5px;
        text-decoration: none;
    }

    .btn-open:hover {
        background: #0056b3;
    }
</style>

<h2 style="text-align:center; margin:20px;">Support Chat Rooms</h2>

<table class="table" style="width:90%; margin:50px auto;">
    <tr>
        <th>Member</th>
        <th>Last Message</th>
        <th>Time</th>
        <th>Action</th>
    </tr>

    <?php foreach ($rooms as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r->name) ?></td>
            <td><?= htmlspecialchars($r->last_msg ?? "No messages yet") ?></td>
            <td><?= $r->last_time ?></td>
            <td>
                <a href="chat.php?roomID=<?= $r->roomID ?>" class="btn btn-primary btn-sm">Open Chat</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php include '../../footer.php'; ?>