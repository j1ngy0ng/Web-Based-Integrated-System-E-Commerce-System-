<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();
$role = $_user->role;

// --------------------------------------------------------------------
// Get orderID
$orderID = req('orderID');
if (!$orderID) redirect($role === 'Admin' ? 'order.php' : 'history.php');

// --------------------------------------------------------------------
// Fetch order
$stm = $_db->prepare('SELECT * FROM `order` WHERE orderID = ?');
$stm->execute([$orderID]);
$order = $stm->fetch();
if (!$order) redirect($role === 'Admin' ? 'order.php' : 'history.php');

// Members can only view their own orders
if ($role === 'Member' && $order->userID != $_user->id) redirect('history.php');

// --------------------------------------------------------------------
// Fetch order items + main photo + rating
$stm = $_db->prepare('
    SELECT oi.*, p.name, p.price AS product_price, pp.photoURL, pr.rating, pr.comment
    FROM order_item oi
    JOIN product p ON oi.productID = p.productID
    LEFT JOIN productPhoto pp ON p.productID = pp.productID AND pp.is_main = 1
    LEFT JOIN productRating pr ON oi.order_item_id = pr.order_item_id
    WHERE oi.orderID = ?
');
$stm->execute([$orderID]);
$items = $stm->fetchAll();

// --------------------------------------------------------------------
// Fetch refund info (if any)
$stm = $_db->prepare('SELECT * FROM refund WHERE orderID = ?');
$stm->execute([$orderID]);
$refund = $stm->fetch();

// Refund color safe lookup
$refundColors = [
    'Pending' => 'orange',
    'Completed' => 'green',
    'Failed' => 'red',
];
$refundColor = $refund ? ($refundColors[$refund->status] ?? 'black') : '';

$_title = 'Order | Detail';
include '../navbar.php';
?>

<style>
/* Form styling */
form.form {
    width: 90%;
    margin: 20px auto;
    background: #fff;
    padding: 20px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
form.form label {
    display: block;
    font-weight: 600;
    margin-top: 10px;
}
form.form div, form.form select, form.form textarea {
    margin-top: 5px;
    padding: 8px 10px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
}
form.form button {
    margin-top: 15px;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}
form.form button:hover {
    background: #0056b3;
}

/* Table styling */
table.table {
    width: 90%;
    margin: 20px auto 40px;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}
table.table th, table.table td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}
table.table th {
    background: #f4f6f9;
    font-weight: 700;
}
table.table tr:hover {
    background: #fafafa;
}
table.table td.right {
    text-align: right;
}
.popup img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border: 1px solid #333;
    border-radius: 5px;
}
button.back-btn {
    margin: 0 5%;
    padding: 10px 20px;
    background: #28a745;
    color: white;
    border-radius: 6px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
}
button.back-btn:hover {
    background: #1e7e34;
}
.cancel-btn {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}
.cancel-btn:hover {
    background: #a71d2a;
}
.rate-btn {
    display: inline-block;
    padding: 6px 12px;
    background: #ffc107; /* Yellow like Bootstrap warning */
    color: #000;
    font-weight: 600;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.3s;
}

.rate-btn:hover {
    background: #e0a800;
}

@media screen and (max-width: 768px) {
    .popup img { width: 40px; height: 40px; }
    form.form, table.table { width: 95%; }
}
</style>

<h2 style="text-align:center; margin-top:20px;">Order Detail</h2>

<!-- MAIN FORM (Admin editing only) -->
<form class="form" method="post" action="saveOrder.php">
    <input type="hidden" name="orderID" value="<?= $order->orderID ?>">

    <label>Order ID</label>
    <div><?= $order->orderID ?></div>

    <label>Date</label>
    <div><?= $order->OrderDate ?></div>

    <label>Status</label>
    <?php if ($role === 'Admin'): ?>
        <select name="status">
            <?php foreach (['Pending','Paid','Shipped','Completed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $order->status == $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <div><?= htmlspecialchars($order->status) ?></div>
    <?php endif; ?>

    <?php if ($refund): ?>
        <label>Refund Status</label>
        <div style="color:<?= $refundColor ?>; font-weight:bold;">
            <?= htmlspecialchars($refund->status) ?>
            <?php if ($refund->reason) echo " ({$refund->reason})"; ?>
        </div>
    <?php endif; ?>

    <label>Shipping Address</label>
    <?php if ($role === 'Admin'): ?>
        <textarea name="ShippingAddress" rows="3"><?= htmlspecialchars($order->ShippingAddress) ?></textarea>
    <?php else: ?>
        <div><?= nl2br(htmlspecialchars($order->ShippingAddress)) ?></div>
    <?php endif; ?>

    <label>Total Amount</label>
    <div>RM <?= number_format($order->TotalAmount, 2) ?></div>

    <?php if ($role === 'Admin'): ?>
        <button type="submit">Save Changes</button>
    <?php endif; ?>
</form>

<!-- CANCEL ORDER FORM FOR MEMBER -->
<?php if ($role === 'Member' && in_array($order->status, ['Pending','Paid']) && !$refund): ?>
<form method="post" action="cancel_order.php" style="width:90%; margin:10px auto;">
    <input type="hidden" name="orderID" value="<?= $order->orderID ?>">
    <button type="submit" class="cancel-btn">Cancel Order</button>
</form>
<?php endif; ?>

<p style="width:90%; margin:10px auto; font-weight:600;"><?= count($items) ?> item(s)</p>

<table class="table">
    <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Price (RM)</th>
        <th>Quantity</th>
        <th>Subtotal (RM)</th>
        <th>Photo</th>
        <th>Rating</th>
    </tr>

    <?php foreach ($items as $item): ?>
    <tr>
        <td><?= $item->productID ?></td>
        <td><?= htmlspecialchars($item->name) ?></td>
        <td class="right"><?= number_format($item->product_price, 2) ?></td>
        <td class="right"><?= $item->quantity ?></td>
        <td class="right"><?= number_format($item->product_price * $item->quantity, 2) ?></td>
        <td>
            <?php if ($item->photoURL): ?>
                <div class="popup">
                    <img src="/products/<?= $item->photoURL ?>">
                </div>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($item->rating): ?>
                <?= $item->rating ?> / 5
                <?php if ($item->comment) echo "<br><small>".htmlspecialchars($item->comment)."</small>"; ?>
            <?php elseif ($role==='Member' && $order->status==='Completed'): ?>
                <a href="rating.php?order_item_id=<?= $item->order_item_id ?>" class="rate-btn">Rate</a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>

    <tr>
        <th colspan="3"></th>
        <th class="right"><?= array_sum(array_map(fn($i) => $i->quantity, $items)) ?></th>
        <th class="right"><?= number_format($order->TotalAmount, 2) ?></th>
        <th colspan="2"></th>
    </tr>
</table>

<p>
    <button class="back-btn" data-get="<?= $role === 'Admin' ? 'maintenance.php' : 'history.php' ?>">Back</button>
</p>

<script>
document.querySelector('.back-btn').onclick = function() {
    window.location.href = this.getAttribute('data-get');
};
</script>

<?php include '../footer.php'; ?>
