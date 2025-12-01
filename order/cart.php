<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// 1. PRE-CALCULATE TOTALS & FETCH DATA
// ----------------------------------------------------------------------------
$cart = get_cart();
$cart_products = [];
$subtotal = 0;
$count = 0;

if ($cart) {
    foreach ($cart as $id => $unit) {
        $stm = $_db->prepare('SELECT p.*, c.categoryName FROM product p LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID WHERE p.productID = ?');
        $stm->execute([$id]);
        $p = $stm->fetch();
        
        if ($p) {
            $p->unit = $unit;
            $p->line_total = $p->price * $unit;
            $cart_products[] = $p;
            $subtotal += $p->line_total;
            $count += $unit;
        }
    }
}

// --- FETCH AVAILABLE VOUCHERS ---
$stm = $_db->prepare("SELECT * FROM vouchers WHERE expiry_date >= CURDATE() ORDER BY min_spend ASC");
$stm->execute();
$available_vouchers = $stm->fetchAll();

// ----------------------------------------------------------------------------
// 2. HANDLE POST REQUESTS
// ----------------------------------------------------------------------------

if (is_post()) {
    $btn = req('btn');

    if ($btn == 'clear') {
        clear_cart();
        unset($_SESSION['voucher']);
        redirect('?');
    }
    if ($btn == 'delete') {
        $id = req('id');
        $cart = get_cart();
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        if (empty($cart)) unset($_SESSION['voucher']);
        redirect();
    }

    if ($btn == 'apply_voucher') {
        $code = strtoupper(trim(req('voucher_code')));
        $stm = $_db->prepare("SELECT * FROM vouchers WHERE voucher_code = ? AND expiry_date >= CURDATE()");
        $stm->execute([$code]);
        $voucher = $stm->fetch();

        if (!$voucher) {
            temp('info', 'Invalid or expired voucher code.');
        } elseif ($subtotal < $voucher->min_spend) {
            temp('info', "Minimum spend of RM " . number_format($voucher->min_spend, 2) . " required.");
        } else {
            $_SESSION['voucher'] = $voucher;
            temp('info', 'Voucher applied successfully!');
        }
        redirect();
    }

    if ($btn == 'remove_voucher') {
        unset($_SESSION['voucher']);
        temp('info', 'Voucher removed.');
        redirect();
    }

    $id = req('id'); $unit = req('unit');
    if ($id && $unit) { update_cart($id, $unit); }
    redirect();
}

// ----------------------------------------------------------------------------
// 3. CALCULATE DISCOUNT
// ----------------------------------------------------------------------------

$discount_amount = 0;
$applied_voucher = $_SESSION['voucher'] ?? null;

if ($applied_voucher) {
    if ($subtotal < $applied_voucher->min_spend) {
        unset($_SESSION['voucher']);
        $applied_voucher = null;
    } else {
        if ($applied_voucher->discount_type == 'fixed') {
            $discount_amount = $applied_voucher->discount_value;
        } else {
            $discount_amount = $subtotal * ($applied_voucher->discount_value / 100);
        }
        if ($discount_amount > $subtotal) $discount_amount = $subtotal;
    }
}

$grand_total = $subtotal - $discount_amount;

// ----------------------------------------------------------------------------
$isCartPage = true;
$_title = 'Order | Shopping Cart';
include '../navbar.php';
?>

<style>
/* ... (Standard Layout Styles) ... */
.cart-page-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; min-height: 70vh; }
.cart-header { text-align: center; margin-bottom: 40px; }
.cart-header h1 { color: #2d3748; font-size: 2.5em; margin-bottom: 10px; background: linear-gradient(135deg, #006989, #00a8cc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.cart-header p { color: #718096; font-size: 1.1em; }
.cart-summary { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 105, 137, 0.15); overflow: hidden; border: 1px solid #e8f4f8; }
.cart-table { width: 100%; border-collapse: collapse; }
.cart-table th { background: linear-gradient(#006989); color: white; padding: 20px 15px; text-align: left; font-weight: 600; font-size: 1em; }
.cart-table th:first-child { border-radius: 20px 0 0 0; }
.cart-table th:last-child { border-radius: 0 20px 0 0; }
.cart-table td { padding: 25px 15px; border-bottom: 1px solid #f1f8fc; vertical-align: middle; transition: background-color 0.3s ease; }
.cart-table tr:hover td { background-color: #f8fcfd; }
.cart-table tr:last-child td { border-bottom: none; }
.cart-item-image { width: 80px; height: 80px; border-radius: 12px; overflow: hidden; background: #f8fafc; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
.cart-item-image:hover { transform: scale(1.05); }
.cart-item-image img { width: 100%; height: 100%; object-fit: cover; }
.cart-item-name { font-weight: 600; color: #2d3748; font-size: 1.1em; margin-bottom: 5px; }
.cart-item-category { color: #718096; font-size: 0.9em; }
.cart-item-price { color: #006989; font-weight: 700; font-size: 1.2em; }
.quantity-form { display: flex; align-items: center; gap: 10px; }
.quantity-form select { padding: 10px 15px; border: 2px solid #e2e8f0; border-radius: 10px; background: white; font-size: 0.95em; min-width: 120px; transition: all 0.3s ease; }
.quantity-form select:focus { border-color: #006989; box-shadow: 0 0 0 3px rgba(0, 105, 137, 0.1); outline: none; }
.delete-btn { background: linear-gradient(135deg, #e53e3e, #c53030); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.9em; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px; }
.delete-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3); }
.cart-subtotal { color: #2d3748; font-weight: 700; font-size: 1.2em; }

/* --- UPDATED CONSISTENT THEME STYLES --- */
.totals-section {
    background: linear-gradient(135deg, #f8fafc, #e8f4f8);
    padding: 30px;
    border-top: 2px solid #e8f4f8;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 30px;
}

/* Voucher Area */
.voucher-box { flex: 1; min-width: 300px; }
.voucher-header { font-weight: 700; color: #006989; margin-bottom: 15px; display: block; font-size: 1.1em; }

/* Manual Input */
.voucher-form { display: flex; gap: 10px; margin-bottom: 20px; }
.voucher-input { flex: 1; padding: 12px; border: 2px solid #cbd5e0; border-radius: 10px; outline: none; }
.voucher-input:focus { border-color: #006989; box-shadow: 0 0 0 3px rgba(0, 105, 137, 0.1); }

/* Apply Button - Matches Primary Theme */
.btn-apply { 
    background: linear-gradient(135deg, #006989, #0088aa); 
    color: white; 
    border: none; 
    padding: 0 25px; 
    border-radius: 10px; 
    cursor: pointer; 
    font-weight: 600; 
    transition: all 0.2s;
}
.btn-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 105, 137, 0.2);
}

/* Voucher List */
.voucher-list { display: flex; flex-direction: column; gap: 10px; max-height: 250px; overflow-y: auto; padding-right: 5px; }
.voucher-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border: 1px solid #e2e8f0;
    padding: 15px;
    border-radius: 12px;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}
.voucher-card:hover { border-color: #006989; box-shadow: 0 4px 12px rgba(0, 105, 137, 0.08); }

/* Left border accent for visual consistency */
.voucher-card::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #006989;
}

.voucher-info strong { color: #006989; font-size: 1.1em; display: block; margin-bottom: 2px; }
.voucher-info p { margin: 0; color: #718096; font-size: 0.85em; }

/* Claim Button - Consistent Blue Outline Style */
.voucher-btn {
    background: transparent; 
    color: #006989; 
    border: 2px solid #006989; 
    padding: 6px 16px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 700; 
    font-size: 0.85em;
    transition: all 0.2s;
}
.voucher-btn:hover { 
    background: #006989; 
    color: white; 
}

/* Disabled State */
.voucher-card.disabled { opacity: 0.6; background: #f8fafc; border-color: #e2e8f0; }
.voucher-card.disabled::before { background: #cbd5e0; }
.voucher-card.disabled .voucher-btn { 
    border-color: #cbd5e0; color: #a0aec0; cursor: not-allowed; 
}
.voucher-card.disabled .voucher-btn:hover { background: transparent; color: #a0aec0; }

/* Applied State - Theme Consistent */
.applied-voucher-tag { 
    margin-top: 10px; 
    display: inline-flex; 
    align-items: center; 
    gap: 10px; 
    background: #e0f2f5; /* Light version of your teal */
    color: #006989; 
    padding: 12px 20px; 
    border-radius: 12px; 
    font-size: 1em; 
    font-weight: 600; 
    border: 1px solid #bce3eb; 
    width: 100%; 
    box-sizing: border-box; 
    justify-content: space-between; 
}
.remove-voucher { 
    background: white; 
    border: 1px solid #e53e3e; 
    color: #e53e3e; 
    cursor: pointer; 
    font-weight: bold; 
    border-radius: 50%; 
    width: 26px; height: 26px; 
    display: flex; align-items: center; justify-content: center; 
    font-size: 1.1em; line-height: 1; transition: 0.2s; 
}
.remove-voucher:hover { background: #e53e3e; color: white; }

/* Totals Area */
.totals-box { flex: 1; min-width: 250px; text-align: right; }
.totals-row { display: flex; justify-content: flex-end; gap: 30px; margin-bottom: 10px; color: #4a5568; font-size: 1.1em; }
.totals-row.final { font-size: 1.5em; font-weight: 800; color: #006989; margin-top: 15px; padding-top: 15px; border-top: 1px solid #cbd5e0; }
.discount-text { color: #e53e3e; }

/* ... (Rest of existing styles) ... */
.cart-actions { display: flex; gap: 20px; justify-content: center; margin-top: 40px; flex-wrap: wrap; }
.btn { padding: 15px 35px; border: none; border-radius: 12px; font-size: 1.1em; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; text-align: center; }
.btn-primary { background: linear-gradient(135deg, #006989, #0088aa); color: white; box-shadow: 0 4px 15px rgba(0, 105, 137, 0.3); }
.btn-primary:hover { background: linear-gradient(135deg, #005672, #006989); transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0, 105, 137, 0.4); }
.btn-secondary { background: linear-gradient(135deg, #e53e3e, #c53030); color: white; box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3); }
.btn-secondary:hover { background: linear-gradient(135deg, #c53030, #a31818); transform: translateY(-3px); box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4); }
.btn-outline { background: transparent; color: #006989; border: 2px solid #006989; }
.btn-outline:hover { background: #006989; color: white; transform: translateY(-3px); }
.empty-cart { text-align: center; padding: 80px 20px; color: #718096; }
.empty-cart-icon { font-size: 4em; margin-bottom: 20px; opacity: 0.7; }
.empty-cart h3 { font-size: 1.8em; margin-bottom: 15px; color: #2d3748; }
.empty-cart p { font-size: 1.1em; margin-bottom: 30px; color: #718096; }
.stock-warning { color: #e67e22; font-size: 0.85em; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
.low-stock { color: #e74c3c; font-weight: 600; }
@media (max-width: 768px) { .totals-section { flex-direction: column-reverse; } .totals-box, .voucher-box { width: 100%; text-align: left; } .totals-row { justify-content: space-between; } .cart-table { display: block; overflow-x: auto; } .cart-table th, .cart-table td { padding: 15px 10px; } .btn { padding: 12px 25px; font-size: 1em; } .quantity-form { flex-direction: column; gap: 8px; } }
</style>

<div class="cart-page-container">
    <div class="cart-header">
        <h1>🛒 Shopping Cart</h1>
        <p>Review your items and proceed to checkout</p>
    </div>
    
    <?php if ($cart_products): ?>
        <div class="cart-summary">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_products as $p): 
                        $units_options = ['' => 'Select quantity'];
                        $max_units = min($p->stock, 10);
                        for ($i = 1; $i <= $max_units; $i++) { $units_options[$i] = $i . ' unit' . ($i > 1 ? 's' : ''); }
                        $main_photo = get_main_photo($p->productID);
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="cart-item-image">
                                    <?php if (!empty($main_photo)): ?>
                                        <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f8fafc, #e8f4f8); display: flex; align-items: center; justify-content: center; font-size: 0.8em; color: #718096;">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="cart-item-name"><?= $p->name ?></div>
                                    <div class="cart-item-category"><?= $p->categoryName ?? 'Uncategorized' ?></div>
                                    <?php if ($p->stock < $p->unit): ?>
                                        <div class="stock-warning low-stock">⚠️ Only <?= $p->stock ?> in stock</div>
                                    <?php elseif ($p->stock <= 5): ?>
                                        <div class="stock-warning">⚠️ Low stock</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="cart-item-price">RM <?= number_format($p->price, 2) ?></td>
                        <td>
                            <div class="quantity-form">
                                <form method="post" style="display: inline;">
                                    <?= html_hidden('id', "value='$p->productID'") ?>
                                    <?= html_select('unit', $units_options, '', $p->unit) ?>
                                </form>
                            </div>
                        </td>
                        <td class="cart-subtotal">RM <?= number_format($p->line_total, 2) ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?= html_hidden('id', "value='$p->productID'") ?>
                                <?= html_hidden('btn', "value='delete'") ?>
                                <button type="submit" class="delete-btn" onclick="return confirm('Remove this item from cart?')">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="totals-section">
                
                <div class="voucher-box">
                    <span class="voucher-header">Available Vouchers</span>
                    
                    <?php if (!$applied_voucher): ?>
                        
                        <form method="post" class="voucher-form">
                            <input type="text" name="voucher_code" class="voucher-input" placeholder="Enter code (e.g. SAVE10)" required>
                            <button type="submit" name="btn" value="apply_voucher" class="btn-apply">Apply</button>
                        </form>

                        <?php if ($available_vouchers): ?>
                            <div class="voucher-list">
                                <?php foreach ($available_vouchers as $v): 
                                    $min_met = $subtotal >= $v->min_spend;
                                    $desc = ($v->discount_type == 'fixed') 
                                            ? "RM " . number_format($v->discount_value, 0) . " OFF" 
                                            : number_format($v->discount_value, 0) . "% OFF";
                                ?>
                                <div class="voucher-card <?= $min_met ? '' : 'disabled' ?>">
                                    <div class="voucher-info">
                                        <strong><?= htmlspecialchars($v->voucher_code) ?></strong>
                                        <p><?= $desc ?> (Min spend: RM<?= $v->min_spend ?>)</p>
                                    </div>
                                    <form method="post">
                                        <input type="hidden" name="voucher_code" value="<?= $v->voucher_code ?>">
                                        <button type="submit" name="btn" value="apply_voucher" class="voucher-btn" <?= $min_met ? '' : 'disabled title="Spend more to unlock"' ?>>
                                            <?= $min_met ? 'Claim' : 'Locked' ?>
                                        </button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="applied-voucher-tag">
                            <div>
                                <span style="display:block; font-size:0.8em; opacity:0.8;">Applied Voucher:</span>
                                <span>🏷️ <b><?= htmlspecialchars($applied_voucher->voucher_code) ?></b></span>
                            </div>
                            <form method="post" style="display:inline;">
                                <button type="submit" name="btn" value="remove_voucher" class="remove-voucher" title="Remove Voucher">&times;</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="totals-box">
                    <div class="totals-row">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if ($discount_amount > 0): ?>
                    <div class="totals-row discount-text">
                        <span>Discount:</span>
                        <span>- RM <?= number_format($discount_amount, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="totals-row final">
                        <span>Grand Total:</span>
                        <span>RM <?= number_format($grand_total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="cart-actions">
            <button class="btn btn-secondary" data-post="?btn=clear">🗑️ Clear Cart</button>
            <a href="../product/list.php" class="btn btn-outline">← Continue Shopping</a>
            <?php if ($_user?->role == 'Member'): ?>
                <button class="btn btn-primary" data-get="../payment/payment.php">🛍️ Proceed to Checkout</button>
            <?php else: ?>
                <a href="/login.php" class="btn btn-outline">🔐 Login to Checkout</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-cart">
            <h3>Your cart is empty</h3>
            <a href="../product/list.php" class="btn btn-primary">🛍️ Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function() {
        $('select[name="unit"]').on('change', function(e) {
            if (this.value !== '') { $(this).closest('form').submit(); }
        });

        // Add smooth animations
        $('.delete-btn, .btn').hover(
            function() { $(this).css('transform', 'translateY(-2px)'); },
            function() { $(this).css('transform', 'translateY(0)'); }
        );

        $('[data-post]').on('click', function(e) {
            const message = $(this).text().includes('Clear Cart') ? 'Clear entire cart?' : 'Proceed with action?';
            if (!confirm(message)) { e.preventDefault(); return false; }
        });

        $('.delete-btn').on('click', function(e) {
            if (!confirm('Remove this item from cart?')) { e.preventDefault(); return; }
            const row = $(this).closest('tr');
            row.css({ 'background-color': '#fee', 'transition': 'all 0.3s ease' });
            setTimeout(() => { row.fadeOut(300, function() { $(this).remove(); }); }, 100);
        });
    });

    function updateCartCount() {
        const itemCount = <?= $count ?>;
        $('.cart-count').text(itemCount);
    }
    updateCartCount();
</script>

<?php
include '../footer.php';
?>