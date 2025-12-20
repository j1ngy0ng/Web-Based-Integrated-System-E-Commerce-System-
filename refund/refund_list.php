<?php
include '../_base.php';
auth("Admin","Superadmin");

// --------------------------------------------------------------------
// Search
$q = trim(req('q', '')); // GET parameter
$search = "%$q%";

// --------------------------------------------------------------------
// Status filter (table header dropdown)
$statusFilter = req('status', '');


// --------------------------------------------------------------------
// Fetch refunds with user info and order info
$stm = $_db->prepare('
    SELECT r.*, u.name, o.TotalAmount, o.OrderDate
    FROM refund r
    JOIN `order` o ON r.orderID = o.orderID
    JOIN user u ON r.userID = u.id
    ORDER BY r.refund_date DESC
');
$stm->execute();
$refunds = $stm->fetchAll();

// --------------------------------------------------------------------
// Pagination
$page  = max(1, intval(req('page', 1))); // current page
$limit = 10;                             // rows per page

// Count total refunds
$countSql = "
    SELECT COUNT(*) 
    FROM refund r
    JOIN `order` o ON r.orderID = o.orderID
    JOIN user u ON r.userID = u.id
    WHERE 1
";
$params = [];

if ($q !== '') {
    $countSql .= " AND (r.refundID LIKE ? OR r.orderID LIKE ? OR u.name LIKE ? OR r.status LIKE ?)";
    array_push($params, $search, $search, $search, $search);
}

if ($statusFilter !== '') {
    $countSql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();


// Use your base.php helper
$pagination = paginate($totalRows, $page, $limit);

$sql = "
    SELECT r.*, u.name, o.TotalAmount, o.OrderDate
    FROM refund r
    JOIN `order` o ON r.orderID = o.orderID
    JOIN user u ON r.userID = u.id
    WHERE 1
";

if ($q !== '') {
    $sql .= " AND (r.refundID LIKE ? OR r.orderID LIKE ? OR u.name LIKE ? OR r.status LIKE ?)";
}

if ($statusFilter !== '') {
    $sql .= " AND r.status = ?";
}

$sql .= " ORDER BY r.refund_date DESC 
          LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";

$stm = $_db->prepare($sql);
$stm->execute($params);
$refunds = $stm->fetchAll();




// --------------------------------------------------------------------
$_title = "Refund | List";
include '../navbar.php';
?>

<style>
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
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }

    th {
        background: #f4f6f9;
        font-weight: 700;
    }

    tr:hover {
        background: #fafafa;
    }

    .action-btns form {
        display: inline;
    }

    .action-btns button {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        margin-right: 5px;
    }

    .complete-btn {
        background: #28a745;
        color: #fff;
    }

    .failed-btn {
        background: #dc3545;
        color: #fff;
    }
</style>

<h2 style="text-align:center; margin-top:30px;">Refund List</h2>
<div style="width:90%; margin:20px auto; display:flex; justify-content:flex-start; gap:10px; flex-wrap:wrap;">
    <form method="get" style="display:flex; gap:10px;">
        <input type="text" name="q" placeholder="Search refunds..."
            value="<?= htmlspecialchars($q) ?>"
            style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Refund ID</th>
            <th>Order ID</th>
            <th>User</th>
            <th>Amount (RM)</th>
            <th>
                Status<br>
                <form method="get">
                    <?php foreach ($_GET as $k => $v): ?>
                        <?php if ($k !== 'status' && $k !== 'page'): ?>
                            <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
                        <?php endif ?>
                    <?php endforeach ?>

                    <select name="status" onchange="this.form.submit()" style="width:100%;">
                        <option value="">All</option>
                        <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Completed" <?= $statusFilter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Failed" <?= $statusFilter == 'Failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </form>
            </th>

            <th>Refund Date</th>
            <th>Reason</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$refunds): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px; color:#888;">
                    No refunds found.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($refunds as $r): ?>
                <tr>
                    <td><?= highlight($r->refundID, $q) ?></td>
                    <td><?= highlight($r->orderID, $q) ?></td>
                    <td><?= highlight($r->name, $q) ?></td>
                    <td>RM <?= number_format($r->amount, 2) ?></td>
                    <td><?= highlight($r->status, $q) ?></td>
                    <td><?= $r->refund_date ?></td>
                    <td><?= highlight($r->reason, $q) ?></td>
                    <td class="action-btns">
                        <form method="post" action="process_refund.php" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="refundID" value="<?= $r->refundID ?>">
                            <input type="hidden" name="status" value="Completed">
                            <button type="submit" class="complete-btn">Complete</button>
                        </form>
                        <form method="post" action="process_refund.php" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="refundID" value="<?= $r->refundID ?>">
                            <input type="hidden" name="status" value="Failed">
                            <button type="submit" class="failed-btn">Failed</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>
<div style="width:90%; margin:20px auto; text-align:center;">
    <?php render_pagination(
        $pagination['page'],
        $pagination['totalPages'],
        [
            'q'      => $q,
            'status' => $statusFilter
        ]
    );
    ?>
</div>

<?php include '../footer.php'; ?>