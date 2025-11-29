<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $btn = req('btn');
    if ($btn == 'clear') {
        clear_cart();
        redirect('?');
    }

    // Handle individual item deletion
    if ($btn == 'delete') {
        $id = req('id');
        $cart = get_cart();
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        redirect();
    }

    $id   = req('id');
    $unit = req('unit');
    update_cart($id, $unit);
    redirect();
}

// ----------------------------------------------------------------------------
$isCartPage = true;
$_title = 'Order | Shopping Cart';
include '../navbar.php';

$cart = get_cart();
$count = 0;
$total = 0;
?>

<style>
.cart-page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
    min-height: 70vh;
}

.cart-header {
    text-align: center;
    margin-bottom: 40px;
}

.cart-header h1 {
    color: #2d3748;
    font-size: 2.5em;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #006989, #00a8cc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.cart-header p {
    color: #718096;
    font-size: 1.1em;
}

.cart-summary {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 105, 137, 0.15);
    overflow: hidden;
    border: 1px solid #e8f4f8;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    background: linear-gradient(#006989);
    color: white;
    padding: 20px 15px;
    text-align: left;
    font-weight: 600;
    font-size: 1em;
}

.cart-table th:first-child {
    border-radius: 20px 0 0 0;
}

.cart-table th:last-child {
    border-radius: 0 20px 0 0;
}

.cart-table td {
    padding: 25px 15px;
    border-bottom: 1px solid #f1f8fc;
    vertical-align: middle;
    transition: background-color 0.3s ease;
}

.cart-table tr:hover td {
    background-color: #f8fcfd;
}

.cart-table tr:last-child td {
    border-bottom: none;
}

.cart-item-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    background: #f8fafc;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.cart-item-image:hover {
    transform: scale(1.05);
}

.cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 1.1em;
    margin-bottom: 5px;
}

.cart-item-category {
    color: #718096;
    font-size: 0.9em;
}

.cart-item-price {
    color: #006989;
    font-weight: 700;
    font-size: 1.2em;
}

.quantity-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.quantity-form select {
    padding: 10px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    background: white;
    font-size: 0.95em;
    min-width: 120px;
    transition: all 0.3s ease;
}

.quantity-form select:focus {
    border-color: #006989;
    box-shadow: 0 0 0 3px rgba(0, 105, 137, 0.1);
    outline: none;
}

.delete-btn {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9em;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.delete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
}

.cart-subtotal {
    color: #2d3748;
    font-weight: 700;
    font-size: 1.2em;
}

.cart-total {
    background: linear-gradient(135deg, #f8fafc, #e8f4f8);
    padding: 30px;
    text-align: right;
    font-size: 1.4em;
    font-weight: 800;
    color: #006989;
    border-top: 2px solid #e8f4f8;
}

.cart-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 40px;
    flex-wrap: wrap;
}

.btn {
    padding: 15px 35px;
    border: none;
    border-radius: 12px;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #006989, #0088aa);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 105, 137, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #005672, #006989);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 105, 137, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    color: white;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #c53030, #a31818);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
}

.btn-outline {
    background: transparent;
    color: #006989;
    border: 2px solid #006989;
}

.btn-outline:hover {
    background: #006989;
    color: white;
    transform: translateY(-3px);
}

.empty-cart {
    text-align: center;
    padding: 80px 20px;
    color: #718096;
}

.empty-cart-icon {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.7;
}

.empty-cart h3 {
    font-size: 1.8em;
    margin-bottom: 15px;
    color: #2d3748;
}

.empty-cart p {
    font-size: 1.1em;
    margin-bottom: 30px;
    color: #718096;
}

.stock-warning {
    color: #e67e22;
    font-size: 0.85em;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.low-stock {
    color: #e74c3c;
    font-weight: 600;
}

@media (max-width: 768px) {
    .cart-table {
        display: block;
        overflow-x: auto;
    }
    
    .cart-table th,
    .cart-table td {
        padding: 15px 10px;
    }
    
    .btn {
        padding: 12px 25px;
        font-size: 1em;
    }
    
    .quantity-form {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $unit): 
                        $stm = $_db->prepare('SELECT p.*, c.categoryName FROM product p LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID WHERE p.productID = ?');
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
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f8fafc, #e8f4f8); display: flex; align-items: center; justify-content: center; font-size: 0.8em; color: #718096;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="cart-item-name"><?= $p->name ?></div>
                                    <div class="cart-item-category"><?= $p->categoryName ?? 'Uncategorized' ?></div>
                                    <?php if ($p->stock < $unit): ?>
                                        <div class="stock-warning low-stock">
                                            ⚠️ Only <?= $p->stock ?> in stock
                                        </div>
                                    <?php elseif ($p->stock <= 5): ?>
                                        <div class="stock-warning">
                                            ⚠️ Low stock
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="cart-item-price">RM <?= number_format($p->price, 2) ?></td>
                        <td>
                            <div class="quantity-form">
                                <form method="post" style="display: inline;">
                                    <?= html_hidden('id', "value='$p->productID'") ?>
                                    <?= html_select('unit', $units_options, '', $unit) ?>
                                </form>
                            </div>
                        </td>
                        <td class="cart-subtotal">RM <?= number_format($subtotal, 2) ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?= html_hidden('id', "value='$p->productID'") ?>
                                <?= html_hidden('btn', "value='delete'") ?>
                                <button type="submit" class="delete-btn" onclick="return confirm('Remove this item from cart?')">
                                    🗑️ Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-total">
                Total (<?= $count ?> items): RM <?= number_format($total, 2) ?>
            </div>
        </div>
        
        <div class="cart-actions">
            <button class="btn btn-secondary" data-post="?btn=clear">
                🗑️ Clear Cart
            </button>
            <a href="../product/list.php" class="btn btn-outline">
                ← Continue Shopping
            </a>
            <?php if ($_user?->role == 'Member'): ?>
                <button class="btn btn-primary" data-get="../payment/payment.php">
                    🛍️ Proceed to Checkout
                </button>
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
    // Enhanced cart functionality
    $(document).ready(function() {
        // Handle quantity changes
        $('select[name="unit"]').on('change', function(e) {
            if (this.value !== '') {
                $(this).closest('form').submit();
            }
        });

        // Add smooth animations
        $('.delete-btn, .btn').hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );

        // Add confirmation for clear cart
        $('[data-post]').on('click', function(e) {
            const message = $(this).text().includes('Clear Cart') ? 'Clear entire cart?' : 'Proceed with action?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Add item removal animation
        $('.delete-btn').on('click', function(e) {
            if (!confirm('Remove this item from cart?')) {
                e.preventDefault();
                return;
            }
            
            const row = $(this).closest('tr');
            row.css({
                'background-color': '#fee',
                'transition': 'all 0.3s ease'
            });
            setTimeout(() => {
                row.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 100);
        });
    });

    // Update cart count in real-time (if you have a cart counter elsewhere)
    function updateCartCount() {
        const itemCount = <?= $count ?>;
        // Update any cart counter elements on the page
        $('.cart-count').text(itemCount);
    }

    // Initialize
    updateCartCount();
</script>

<?php
include '../footer.php';