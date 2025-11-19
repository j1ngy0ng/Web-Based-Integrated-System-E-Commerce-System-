<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $id   = req('id');
    $unit = req('unit');
    update_cart($id, $unit);
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
$max_units = min($p->stock, 10);

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
                    <?= $p->stock > 0 ? $p->stock . ' in stock' : 'Out of Stock' ?>
                </div>
            </div>
            
            <div class="cart-section">
                <h3>Add to Cart</h3>
                <form method="post" class="quantity-selector" id="cartForm">
                    <?= html_hidden('id') ?>
                    <div class="select-group">
                        <select name="unit" id="unit" <?= $p->stock == 0 ? 'disabled' : '' ?>>
                            <option value="">Select quantity</option>
                            <?php for ($i = 1; $i <= $max_units; $i++): ?>
                                <option value="<?= $i ?>" <?= $unit == $i ? 'selected' : '' ?>>
                                    <?= $i ?> unit<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="cart-buttons">
                        <button type="button" class="btn btn-secondary" id="clearBtn" <?= $p->stock == 0 ? 'disabled' : '' ?>>
                            Clear
                        </button>
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
            e.target.form.submit();
        }
    });

    $(document).on('click', '#clearBtn', function() {
        $('#unit').val('');
        $('#cartForm').submit();
    });

    $(document).ready(function() {
        initImageModal();
    });
</script>

<?php
include '../footer.php';