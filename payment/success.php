<?php
include '../_base.php';

$id = req('id');
if (!$id) redirect('cart.php');

$stm = $_db->prepare('
    SELECT o.*, p.stripe_payment_intent_id 
    FROM `order` o 
    LEFT JOIN payment p ON o.orderID = p.orderID 
    WHERE o.orderID = ?
');
$stm->execute([$id]);
$order = $stm->fetch();

if (!$order) redirect('cart.php');

$_title = 'Payment Successful';
include '../navbar.php';
?>

<div class="container">
    <div class="success-container text-center">
        <div class="success-icon">✅</div>
        <h2>Payment Successful!</h2>
        
        <div class="order-details">
            <p><strong>Order ID:</strong> #<?= $order->orderID ?></p>
            <p><strong>Amount Paid:</strong> RM <?= number_format($order->TotalAmount, 2) ?></p>
            <p><strong>Payment Date:</strong> <?= date('F j, Y g:i A', strtotime($order->OrderDate)) ?></p>
            <?php if ($order->stripe_payment_intent_id): ?>
                <p><strong>Transaction ID:</strong> <?= $order->stripe_payment_intent_id ?></p>
            <?php endif; ?>
        </div>

        <div class="success-actions">
            <a href="../product/list.php" class="btn btn-outline">Continue Shopping</a>
            <a href="../order/detail.php?orderID=<?= $order->orderID ?>" class="btn btn-primary">View Order Details</a>        </div>
    </div>
</div>

<style>
.success-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 30px;
}

.success-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.order-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: left;
}

.success-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}
</style>

<?php
include '../footer.php';