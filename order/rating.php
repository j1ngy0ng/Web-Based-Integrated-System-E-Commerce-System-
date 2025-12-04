<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();
$role = $_user->role;

// --------------------------------------------------------------------
// Get order_item_id
$orderItemID = req('order_item_id');
if (!$orderItemID) redirect('history.php');

// --------------------------------------------------------------------
// Fetch order item and product info
$stm = $_db->prepare('
    SELECT oi.*, p.name, oi.orderID
    FROM order_item oi
    JOIN product p ON oi.productID = p.productID
    WHERE oi.order_item_id = ?
');
$stm->execute([$orderItemID]);
$item = $stm->fetch();
if (!$item) redirect('history.php');

// --------------------------------------------------------------------
// Fetch order info
$stm = $_db->prepare('SELECT o.userID, o.status FROM `order` o WHERE o.orderID = ?');
$stm->execute([$item->orderID]);
$order = $stm->fetch();
if (!$order) redirect('history.php');

// --------------------------------------------------------------------
// Members can only rate their own completed orders
if ($role === 'Member' && ($order->userID != $_user->id || $order->status !== 'Completed')) {
    redirect('history.php');
}

// --------------------------------------------------------------------
// Fetch existing rating (if any)
$stm = $_db->prepare('SELECT * FROM productRating WHERE order_item_id = ?');
$stm->execute([$orderItemID]);
$rating = $stm->fetch();

$_title = "Rate Product | " . htmlspecialchars($item->name);
include '../navbar.php';
?>

<style>
/* Same styling as orderDetail.php */
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
form.form select, form.form textarea {
    font-size: 1rem;
}
form.form button {
    margin-top: 15px;
    padding: 10px 20px;
    background: #007bff;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}
form.form button:hover {
    background: #0056b3;
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

@media screen and (max-width: 768px) {
    form.form {
        width: 95%;
    }
}
</style>

<h2 style="text-align:center; margin-top:20px;">Rate Product: <?= htmlspecialchars($item->name) ?></h2>

<?php if ($rating): ?>
    <p style="text-align:center; font-weight:bold; color:green;">
        Rating: <?= $rating->rating ?> / 5
        <?php if ($rating->comment) echo " - " . htmlspecialchars($rating->comment); ?>
    </p>
<?php endif; ?>

<?php if ($role === 'Member' && !$rating): ?>
<form class="form" method="post" action="save_rating.php">
    <input type="hidden" name="order_item_id" value="<?= $orderItemID ?>">

    <label>Rating (1-5)</label>
    <select name="rating" required>
        <?php for ($i=1; $i<=5; $i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
    </select>

    <label>Comment (optional)</label>
    <textarea name="comment" rows="4" placeholder="Write your feedback..."></textarea>

    <button type="submit">Submit Rating</button>
</form>
<?php endif; ?>

<p style="text-align:center; margin-top:20px;">
    <button class="back-btn" onclick="window.location.href='detail.php?orderID=<?= $item->orderID ?>'">Back to Order</button>
</p>

<?php include '../footer.php'; ?>
