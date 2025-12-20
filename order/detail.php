<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();
$role = $_user->role;

// --------------------------------------------------------------------
// Get orderID
$orderID = req('orderID');
if (!$orderID) redirect(($role === 'Admin' || $role === 'Superadmin') ? 'maintenance.php' : 'history.php');

// --------------------------------------------------------------------
// Fetch order
$stm = $_db->prepare('SELECT * FROM `order` WHERE orderID = ?');
$stm->execute([$orderID]);
$order = $stm->fetch();
if (!$order) redirect(($role === 'Admin' || $role === 'Superadmin')? 'maintenance.php' : 'history.php');

// Members can only view their own orders
if ($role === 'Member' && $order->userID != $_user->id) redirect('history.php');

// --------------------------------------------------------------------
// Fetch shipping address details
$address = null;
if ($order->address_id) {
    $stm = $_db->prepare('SELECT * FROM shipping_addresses WHERE address_id = ?');
    $stm->execute([$order->address_id]);
    $address = $stm->fetch();
}

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

$_title = 'Order | Detail';
include '../navbar.php';
?>

<style>
/* --- LAYOUT --- */
.page-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.page-header { text-align: center; margin-bottom: 40px; }
.page-header h1 { font-size: 2.2em; margin-bottom: 10px; background: linear-gradient(135deg, #006989, #009eb3); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.page-header p { color: #718096; }

/* --- GRID SYSTEM --- */
.detail-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
@media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }

/* --- CARDS --- */
.info-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0, 105, 137, 0.08); border: 1px solid #e8f4f8; }
.card-title { font-size: 1.2rem; font-weight: 700; color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }

/* --- FORM ELEMENTS --- */
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 0.85rem; color: #718096; margin-bottom: 5px; font-weight: 600; text-transform: uppercase; }
.form-value { font-size: 1rem; color: #2d3748; font-weight: 500; }
.form-control { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; transition: 0.3s; }
.form-control:focus { border-color: #006989; outline: none; }

/* --- ITEM LIST --- */
.item-row { display: flex; gap: 15px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid #f1f8fc; }
.item-row:last-child { border-bottom: none; }
.item-thumb { width: 70px; height: 70px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; background: #f8fafc; }
.item-details { flex-grow: 1; }
.item-name { font-weight: 600; color: #2d3748; display: block; text-decoration: none; font-size: 1.05rem; }
.item-meta { font-size: 0.9rem; color: #718096; margin-top: 4px; }
.meta-id { color: #006989; font-weight: 600; }
.item-price { text-align: right; min-width: 100px; }
.price-total { font-weight: 700; color: #006989; font-size: 1.1rem; }
.price-unit { font-size: 0.85rem; color: #a0aec0; }

/* --- REVIEW BUBBLE STYLE --- */
.review-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-top: 10px; position: relative; display: inline-block; min-width: 200px; }
.review-stars { color: #f6c23e; font-size: 0.9em; margin-bottom: 4px; display: block; letter-spacing: 1px; }
.review-text { font-size: 0.9em; color: #4a5568; line-height: 1.4; font-style: italic; display: block; }
.review-text::before { content: "“"; font-size: 1.4em; line-height: 0; position: relative; top: 5px; margin-right: 2px; color: #cbd5e0; }

/* --- TOTALS --- */
.total-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid #e8f4f8; text-align: right; }
.total-label { font-size: 1rem; color: #718096; margin-right: 15px; }
.total-amount { font-size: 1.8rem; font-weight: 800; color: #006989; }

/* --- BADGES --- */
.status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: inline-block; }
.st-pending { background: #fffaf0; color: #d69e2e; border: 1px solid #fbd38d; }
.st-paid { background: #ebf8ff; color: #3182ce; border: 1px solid #bee3f8; }
.st-shipped { background: #e9d8fd; color: #805ad5; border: 1px solid #d6bcfa; }
.st-completed { background: #f0fff4; color: #38a169; border: 1px solid #c6f6d5; }
.st-cancelled { background: #fff5f5; color: #e53e3e; border: 1px solid #fed7d7; }

/* --- BUTTONS --- */
.btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; width: 100%; display: block; text-align: center; text-decoration: none; }
.btn-primary { background: #006989; color: white; margin-top: 15px; }
.btn-primary:hover { background: #005a73; }
.btn-danger { background: #fff5f5; color: #e53e3e; border: 1px solid #e53e3e; margin-top: 10px; }
.btn-danger:hover { background: #e53e3e; color: white; }
.btn-back { background: transparent; border: 2px solid #cbd5e0; color: #718096; display: inline-block; width: auto; margin: 30px auto; }
.btn-back:hover { border-color: #006989; color: #006989; }
.btn-rate { background: #f6c23e; color: #fff; padding: 6px 15px; font-size: 0.85rem; border-radius: 6px; display: inline-block; width: auto; margin-top: 8px; text-decoration: none; }
.btn-rate:hover { background: #d4a72c; }

/* --- REFUND BOX --- */
.refund-box { background: #fff5f5; border-left: 4px solid #e53e3e; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
.refund-title { font-weight: bold; color: #c53030; }
</style>

<div class="page-container">
    <div class="page-header">
        <h1>Order #<?= str_pad($order->orderID, 6, '0', STR_PAD_LEFT) ?></h1>
        <p>Placed on <?= date('d M Y, h:i A', strtotime($order->OrderDate)) ?></p>
    </div>

    <div class="detail-grid">
        <div class="left-col">
            <?php if ($refund): ?>
                <div class="refund-box">
                    <div class="refund-title">Refund Status: <?= htmlspecialchars($refund->status) ?></div>
                    <?php if ($refund->reason) echo "<small>Reason: ".htmlspecialchars($refund->reason)."</small>"; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="saveOrder.php" class="info-card">
                <div class="card-title">Order Information</div>
                <input type="hidden" name="orderID" value="<?= $order->orderID ?>">

                <!-- STATUS -->
                <div class="form-group">
                    <label class="form-label">Order Status</label>
                    <?php if (($role === 'Admin') || $role === 'Superadmin'): ?>
                        <select name="status" class="form-control" <?= $order->status === 'Completed' ? 'disabled' : '' ?>>
                            <?php foreach (['Pending','Paid','Shipped','Completed','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order->status == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($order->status === 'Completed'): ?>
                            <small style="color:#718096;">Completed orders cannot be modified.</small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="status-badge st-<?= strtolower($order->status) ?>">
                            <?= htmlspecialchars($order->status) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- SHIPPING ADDRESS -->
                <div class="form-group">
                    <label class="form-label">Shipping Address</label>
                    <?php if ($role === 'Admin' && $address): ?>
                        <input type="hidden" name="address_id" value="<?= $address->address_id ?>">
                        <div class="form-group">
                            <label class="form-label">Recipient Name</label>
                            <input type="text" name="recipient_name" class="form-control" value="<?= htmlspecialchars($address->recipient_name) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($address->phone_number) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="address_line1" class="form-control" value="<?= htmlspecialchars($address->address_line1) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address_line2" class="form-control" value="<?= htmlspecialchars($address->address_line2) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($address->city) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($address->state) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($address->postal_code) ?>">
                        </div>
                    <?php elseif ($address): ?>
                        <div class="form-value" style="line-height: 1.5; background: #f8fafc; padding: 10px; border-radius: 8px;">
                            <?= htmlspecialchars($address->recipient_name) ?><br>
                            <?= htmlspecialchars($address->phone_number) ?><br>
                            <?= htmlspecialchars($address->address_line1) ?><br>
                            <?= !empty($address->address_line2) ? htmlspecialchars($address->address_line2)."<br>" : "" ?>
                            <?= htmlspecialchars($address->postal_code) ?> <?= htmlspecialchars($address->city) ?><br>
                            <?= htmlspecialchars($address->state) ?>
                        </div>
                    <?php else: ?>
                        <div class="form-value" style="color:#e53e3e;">
                            No shipping address found (address_id = <?= htmlspecialchars($order->address_id) ?>)
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($role === 'Admin' && $order->status !== 'Completed'): ?>
                    <button type="submit" class="btn btn-primary">Update Order</button>
                <?php endif; ?>
            </form>

            <?php if ($role === 'Member' && in_array($order->status, ['Pending','Paid']) && !$refund): ?>
                <form method="post" action="cancel_order.php">
                    <input type="hidden" name="orderID" value="<?= $order->orderID ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">
                        Cancel Order
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="right-col">
            <div class="info-card">
                <div class="card-title">Items Ordered (<?= count($items) ?>)</div>
                
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <?php if ($item->photoURL): ?>
                                <img src="/products/<?= $item->photoURL ?>" class="item-thumb" alt="Product">
                            <?php else: ?>
                                <div class="item-thumb" style="display:flex;align-items:center;justify-content:center;font-size:0.7em;color:#999;">No Img</div>
                            <?php endif; ?>

                            <div class="item-details">
                                <a href="/product/detail.php?id=<?= $item->productID ?>" class="item-name">
                                    <?= htmlspecialchars($item->name) ?>
                                </a>
                                
                                <div class="item-meta">
                                    <span class="meta-id">ID: #<?= $item->productID ?></span>
                                    &nbsp;|&nbsp; 
                                    Qty: <?= $item->quantity ?>
                                </div>
                                
                                <?php if ($item->rating): ?>
                                    <div class="review-box">
                                        <span class="review-stars">
                                            <?= str_repeat('★', $item->rating) . str_repeat('☆', 5 - $item->rating) ?>
                                        </span>
                                        <?php if (!empty($item->comment)): ?>
                                            <span class="review-text"><?= htmlspecialchars($item->comment) ?></span>
                                        <?php else: ?>
                                            <span style="font-size:0.8em; color:#a0aec0; font-style:italic;">(No comment)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($role === 'Member' && $order->status === 'Completed'): ?>
                                    <a href="rating.php?order_item_id=<?= $item->order_item_id ?>" class="btn-rate">
                                        Rate Item
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="item-price">
                                <div class="price-total">RM <?= number_format($item->product_price * $item->quantity, 2) ?></div>
                                <div class="price-unit">RM <?= number_format($item->product_price, 2) ?> / unit</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-section">
                    <?php if ((!empty($order->discount_amount) && $order->discount_amount > 0) || (!empty($order->redeemed_points) && $order->redeemed_points > 0)): ?>
                        <?php if (!empty($order->discount_amount) && $order->discount_amount > 0): ?>
                            <div style="font-size: 0.95em; color: #e53e3e; margin-bottom: 5px;">
                                Discount (Voucher: <?= htmlspecialchars($order->voucher_code) ?>): <b>- RM <?= number_format($order->discount_amount, 2) ?></b>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($order->redeemed_points) && $order->redeemed_points > 0): ?>
                            <div style="font-size: 0.95em; color: #e53e3e; margin-bottom: 5px;">
                                Discount (Reward Points): <b>- RM <?= number_format($order->redeemed_points / 100, 2) ?></b>
                                <!-- Assuming 1 point = RM0.01 -->
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <span class="total-label">Grand Total</span>
                    <span class="total-amount">RM <?= number_format($order->TotalAmount, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center;">
        <button class="btn btn-back" onclick="window.location.href='<?= $role === 'Admin' || $role === 'Superadmin'? '../order/maintenance.php' : 'history.php' ?>'">
            &larr; Back to List
        </button>
    </div>
</div>

<?php include '../footer.php'; ?>
