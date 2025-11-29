<?php
include '../_base.php';
auth("Admin");

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
$_title = "Refund | List";
include '../navbar.php';
?>

<style>
table {
    width: 90%;
    margin: 30px auto;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

th, td {
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

<table>
    <thead>
        <tr>
            <th>Refund ID</th>
            <th>Order ID</th>
            <th>User</th>
            <th>Amount (RM)</th>
            <th>Status</th>
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
                    <td><?= $r->refundID ?></td>
                    <td><?= $r->orderID ?></td>
                    <td><?= htmlspecialchars($r->name) ?></td>
                    <td>RM <?= number_format($r->amount, 2) ?></td>
                    <td><?= $r->status ?></td>
                    <td><?= $r->refund_date ?></td>
                    <td><?= htmlspecialchars($r->reason) ?></td>
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

<?php include '../footer.php'; ?>
