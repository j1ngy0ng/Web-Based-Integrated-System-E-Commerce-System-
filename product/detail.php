<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $id   = req('id');
    $unit = req('unit');
    
    // Handle delete cart action
    $action = req('action');
    if ($action === 'delete') {
        update_cart($id, 0); // Set to 0 to remove from cart
    } else {
        update_cart($id, $unit);
    }
    redirect();
}

$id  = req('id');
$stm = $_db->prepare('SELECT * FROM product WHERE productID = ?');
$stm->execute([$id]);
$p = $stm->fetch();
if (!$p) redirect('list.php');

$main_photo = get_main_photo($id);
$additional_photos = get_additional_photos($id);

$cart = get_cart();
$unit = $cart[$id] ?? 0;

// Determine max units based on stock (limit to 2 if stock is 2 or less)
if ($p->stock <= 2) {
    $max_units = $p->stock;
} else {
    $max_units = min($p->stock, 10);
}

// ----------------------------------------------------------------------------

$_title = 'Product | Detail';
include '../navbar.php';
?>

<div class="product-detail-container">
    <div class="product-detail-grid">
        <div class="product-gallery">
            <?php if (!empty($main_photo)): ?>
                <div class="main-photo">
                    <img src="/products/<?= $main_photo ?>" 
                         alt="<?= $main_photo ?>" 
                         id="mainProductImage"
                         class="clickable-main"
                         data-fullsize="/products/<?= $main_photo ?>"
                         data-index="0">
                </div>
            <?php else: ?>
                <div class="photo-placeholder">
                    No Image Available
                </div>
            <?php endif; ?>
            
            <?php if ($additional_photos): ?>
                <div class="additional-photos">
                    <?php foreach ($additional_photos as $index => $photo): ?>
                        <img src="/products/<?= $photo->photoURL ?>" 
                             alt="<?= $photo->photoURL ?>" 
                             class="thumbnail"
                             data-fullsize="/products/<?= $photo->photoURL ?>"
                             data-index="<?= $index + 1 ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="product-info-detail">
            <h1 class="product-title"><?= $p->name ?></h1>
            
            <div class="product-description">
                <?= !empty($p->description) ? nl2br(htmlspecialchars($p->description)) : 'No description available' ?>
            </div>
            
            <div class="price-section">
                <div class="current-price">RM <?= number_format($p->price, 2) ?></div>
                <div class="stock-status <?= $p->stock > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <?php if ($p->stock > 0): ?>
                        <?php if ($p->stock <= 10): ?>
                            <span style="color: #f39c12; font-weight: 600;"> ⚠️ Low Stock: Only <?= $p->stock ?> left</span>
                        <?php else: ?>
                            In Stock
                        <?php endif; ?>
                    <?php else: ?>
                        Out of Stock
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="cart-section">
                <h3>Add to Cart</h3>
                <form method="post" class="quantity-selector" id="cartForm">
                    <?= html_hidden('id') ?>
                    <?= html_hidden('action', '') ?>
                    
                    <?php
                    // Generate stock options based on available stock
                    $stock_options = ['' => 'Select quantity'];
                    
                    // Only generate options if stock is available
                    if ($p->stock > 0) {
                        for ($i = 1; $i <= $max_units; $i++) {
                            $stock_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
                        }
                    } else {
                        $stock_options = ['' => 'Out of Stock'];
                    }
                    ?>
                    
                    <div class="select-group">
                        <?= html_select('unit', $stock_options, '', $unit, $p->stock) ?>
                    </div>
                    
                    <div class="cart-buttons">
                        <?php if ($unit > 0): ?>
                            <button type="button" class="btn btn-danger" id="deleteBtn">
                                Delete from Cart
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($unit): ?>
                        <div class="cart-message">
                            ✅ <?= $unit ?> unit<?= $unit > 1 ? 's' : '' ?> in cart
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="imageModal" class="image-modal">
    <span class="modal-close">&times;</span>
    <div class="modal-nav">
        <button class="nav-arrow prev-arrow" id="prevBtn">&#10094;</button>
        <button class="nav-arrow next-arrow" id="nextBtn">&#10095;</button>
    </div>
    <img class="modal-content" id="modalImage">
    <div class="modal-caption"></div>
    <div class="image-counter" id="imageCounter"></div>
</div>

<p style="text-align: center; margin: 30px 0;">
    <button data-get="list.php" class="btn btn-outline">Back to List</button>
</p>

<script>
    $('select').on('change', function(e) {
        if (this.value !== '') {
            $('#action').val('');
            e.target.form.submit();
        }
    });

    $(document).on('click', '#clearBtn', function() {
        $('#unit').val('');
        $('#action').val('');
        $('#cartForm').submit();
    });
    
    $(document).on('click', '#deleteBtn', function() {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            $('#action').val('delete');
            $('#cartForm').submit();
        }
    });

    $(document).ready(function() {
        initImageModal();
    });
</script>

<style>
.btn-danger {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 10px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
}

.btn-danger:hover {
    background-color: #c0392b;
}

.cart-buttons {
    display: flex;
    gap: 10px;
}

.btn-outline {
    background-color: #006989;
    width: 10%;
    border-radius: 30px;
    color: white;
    border: none;
    padding: 20px 10px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
    justify-content: center;
}
</style>

<?php
include '../footer.php';
?>