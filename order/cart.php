<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $btn = req('btn');
    if ($btn == 'clear') {
        set_cart();
        redirect('?');
    }

    $id   = req('id');
    $unit = req('unit');
    update_cart($id, $unit);
    redirect();
}

// ----------------------------------------------------------------------------

$_title = 'Order | Shopping Cart';
include '../navbar.php';

$cart = get_cart();
$count = 0;
$total = 0;
?>

<div class="cart-page-container">
    <div class="cart-header">
        <h1>🛒 Shopping Cart</h1>
        <p>Review your items and proceed to checkout</p>
    </div>
    
    <?php if ($cart): ?>
        <div class="cart-summary">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $unit): 
                        $stm = $_db->prepare('SELECT * FROM product WHERE productID = ?');
                        $stm->execute([$id]);
                        $p = $stm->fetch();
                        if (!$p) continue;
                        
                        $subtotal = $p->price * $unit;
                        $count += $unit;
                        $total += $subtotal; 

                        // Generate quantity options
                        $units_options = ['' => 'Select quantity'];
                        $max_units = min($p->stock, 10);
                        for ($i = 1; $i <= $max_units; $i++) {
                            $units_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
                        }
                        
                        $main_photo = get_main_photo($id);
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="cart-item-image">
                                    <?php if (!empty($main_photo)): ?>
                                        <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 0.8em;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-name"><?= $p->name ?></div>
                            </div>
                        </td>
                        <td class="cart-item-price">RM <?= number_format($p->price, 2) ?></td>
                        <td>
                            <form method="post" class="quantity-form">
                                <?= html_hidden('id', "value='$p->productID'") ?>
                                <?= html_select('unit', $units_options, '', $unit) ?>
                            </form>
                        </td>
                        <td class="cart-subtotal">RM <?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-total">
                Total (<?= $count ?> items): RM <?= number_format($total, 2) ?>
            </div>
        </div>
        
        <div class="cart-actions">
            <button class="btn btn-secondary" data-post="?btn=clear">Clear Cart</button>
            <?php if ($_user?->role == 'Member'): ?>
                <button class="btn btn-primary" data-post="checkout.php">Proceed to Checkout</button>
            <?php else: ?>
                <a href="/login.php" class="btn btn-outline">Login to Checkout</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-cart">
            <h3>Your cart is empty</h3>
            <p>Browse our products and add some items to your cart!</p>
            <a href="../product/list.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<script>
    $('select').on('change', function(e) {
        if (this.value !== '') {
            e.target.form.submit();
        }
    });
</script>

<?php
include '../footer.php';