<?php
include '../../_base.php';
auth("Admin");

// --------------------------------------------------------------------
// Search
$q = trim(req('q', '')); 
$search = "%$q%";

// --------------------------------------------------------------------
// Pagination
$page  = max(1, intval(req('page', 1)));
$limit = 10;

// Count total chat rooms with search
$countSql = "SELECT COUNT(*) 
             FROM chat_room cr
             JOIN user u ON cr.userID = u.id";
$params = [];

if ($q !== '') {
    $countSql .= " WHERE u.name LIKE ?";
    $params[] = $search;
}

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();

// Pagination helper
$pagination = paginate($totalRows, $page, $limit);

// --------------------------------------------------------------------
// Fetch paginated chat rooms with search
$sql = "
    SELECT 
        cr.roomID, 
        u.name AS member_name,
        (SELECT message 
         FROM chat_message 
         WHERE roomID = cr.roomID 
         ORDER BY msgID DESC LIMIT 1) AS last_msg,
        (SELECT created_at 
         FROM chat_message 
         WHERE roomID = cr.roomID 
         ORDER BY msgID DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) 
         FROM chat_message 
         WHERE roomID = cr.roomID
           AND senderID = cr.userID
           AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
        ) AS unread_count
    FROM chat_room cr
    JOIN user u ON cr.userID = u.id
";

if ($q !== '') {
    $sql .= " WHERE u.name LIKE ?";
}

$sql .= " ORDER BY last_time DESC LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";

$stm = $_db->prepare($sql);
$stm->execute($params);
$rooms = $stm->fetchAll(PDO::FETCH_OBJ);

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

<div style="width:90%; margin:20px auto; display:flex; justify-content:flex-start; gap:10px; flex-wrap:wrap;">
    <form method="get" style="display:flex; gap:10px;">
        <input type="text" name="q" placeholder="Search member..."
               value="<?= htmlspecialchars($q) ?>"
               style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>


<table>
    <tr>
        <th>Member</th>
        <th>Last Message</th>
        <th>Time</th>
        <th>Action</th>
    </tr>
    <?php foreach ($rooms as $r): ?>
        <tr>
            <td><?= highlight($r->member_name, $q) ?></td>
            <td>
                <?= highlight($r->last_msg ?? "No messages yet", $q) ?>
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
    <?php render_pagination($pagination['page'], $pagination['totalPages'], ['q' => $q]); ?>
</div>


<?php include '../../footer.php'; ?>
