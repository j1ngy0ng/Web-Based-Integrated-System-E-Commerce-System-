<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// 1. HANDLE POST REQUESTS (Cart & Favorites)
// ----------------------------------------------------------------------------

if (is_post()) {
    $id = req('id');
    $action = req('action');

    // --- A. FAVORITE TOGGLE LOGIC ---
    if ($action === 'toggle_fav') {
        if (!$_user) {
            temp('info', 'Please login to add favorites');
            redirect('/login.php');
        }
        
        $stm = $_db->prepare("SELECT * FROM favorite WHERE userID = ? AND productID = ?");
        $stm->execute([$_user->id, $id]);
        
        if ($stm->fetch()) {
            $stm = $_db->prepare("DELETE FROM favorite WHERE userID = ? AND productID = ?");
            $stm->execute([$_user->id, $id]);
        } else {
            $stm = $_db->prepare("INSERT INTO favorite (userID, productID) VALUES (?, ?)");
            $stm->execute([$_user->id, $id]);
        }
        redirect();
    }
    
    // --- B. CART LOGIC ---
    $unit = req('unit');
    if ($action === 'delete') {
        update_cart($id, 0); 
    } else {
        update_cart($id, $unit);
    }
    redirect();
}

// ----------------------------------------------------------------------------
// 2. FETCH PRODUCT DATA
// ----------------------------------------------------------------------------

$id  = req('id');
$stm = $_db->prepare('SELECT * FROM product WHERE productID = ?');
$stm->execute([$id]);
$p = $stm->fetch();

if (!$p) redirect('list.php');

$main_photo = get_main_photo($id);
$additional_photos = get_additional_photos($id);

// Cart Data
$cart = get_cart();
$unit = $cart[$id] ?? 0;

// Stock Limits
$max_units = ($p->stock <= 2) ? $p->stock : min($p->stock, 10);

// Check if currently Favorited
$isFav = false;
if ($_user) {
    $stm = $_db->prepare("SELECT COUNT(*) FROM favorite WHERE userID = ? AND productID = ?");
    $stm->execute([$_user->id, $id]);
    $isFav = $stm->fetchColumn() > 0;
}

// ----------------------------------------------------------------------------
// 3. FETCH RATINGS FOR THIS PRODUCT
// ----------------------------------------------------------------------------

$sql_ratings = "
    SELECT 
        pr.rating,
        pr.comment,
        pr.rating_date,
        o.userID
    FROM productRating pr
    INNER JOIN order_item oi ON pr.order_item_id = oi.order_item_id
    INNER JOIN `order` o ON oi.orderID = o.orderID
    WHERE oi.productID = ?
    ORDER BY pr.rating_date DESC
";
$stm_ratings = $_db->prepare($sql_ratings);
$stm_ratings->execute([$p->productID]);
$ratings = $stm_ratings->fetchAll();

// ----------------------------------------------------------------------------

$_title = 'Product | Detail';
include '../navbar.php';
?>

<div class="product-detail-container">
    <div class="product-detail-grid">
        
        <!-- Product Gallery -->
        <div class="product-gallery">
            <?php if (!empty($main_photo)): ?>
                <div class="main-photo">
                    <img src="/products/<?= $main_photo ?>" alt="<?= $main_photo ?>" id="mainProductImage" class="clickable-main" data-fullsize="/products/<?= $main_photo ?>" data-index="0">
                </div>
            <?php else: ?>
                <div class="photo-placeholder">No Image Available</div>
            <?php endif; ?>
            
            <?php if ($additional_photos): ?>
                <div class="additional-photos">
                    <?php foreach ($additional_photos as $index => $photo): ?>
                        <img src="/products/<?= $photo->photoURL ?>" alt="<?= $photo->photoURL ?>" class="thumbnail" data-fullsize="/products/<?= $photo->photoURL ?>" data-index="<?= $index + 1 ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Info -->
        <div class="product-info-detail">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <h1 class="product-title" style="margin: 0; line-height: 1.2;"><?= $p->name ?></h1>
                
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="id" value="<?= $p->productID ?>">
                    <input type="hidden" name="action" value="toggle_fav">
                    
                    <button type="submit" class="btn-fav" title="<?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                        <i class="fa <?= $isFav ? 'fa-heart' : 'fa-heart-o' ?>"></i>
                    </button>
                </form>
            </div>
            
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
                    <?= html_hidden('id', "value='$p->productID'") ?>
                    <?= html_hidden('action', "value=''") ?>
                    
                    <?php
                    $stock_options = ['' => 'Select quantity'];
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
                                🗑️
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

<!-- Ratings Section -->
<div class="ratings-section" style="margin: 40px auto; max-width: 800px;">
    <h2>Customer Ratings</h2>

    <?php if (!$ratings): ?>
        <p style="color:#888;">No ratings yet for this product.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse: collapse; box-shadow: 0 3px 8px rgba(0,0,0,0.1); border-radius: 6px; overflow: hidden;">
            <thead style="background:#f4f6f9;">
                <tr>
                    <th style="padding: 10px; border-bottom:1px solid #ddd;">UserID</th>
                    <th style="padding: 10px; border-bottom:1px solid #ddd;">Rating</th>
                    <th style="padding: 10px; border-bottom:1px solid #ddd;">Comment</th>
                    <th style="padding: 10px; border-bottom:1px solid #ddd;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ratings as $r): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:8px;"><?= htmlspecialchars($r->userID) ?></td>
                        <td style="padding:8px;"><?= str_repeat('⭐', $r->rating) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($r->comment ?: '-') ?></td>
                        <td style="padding:8px;"><?= date('Y-m-d H:i', strtotime($r->rating_date)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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
$('select[name="unit"]').on('change', function(e) {
    if (this.value !== '') {
        $('#cartForm input[name="action"]').val('');
        e.target.form.submit();
    }
});

$(document).on('click', '#deleteBtn', function() {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        $('#cartForm input[name="action"]').val('delete');
        $('#cartForm').submit();
    }
});

$(document).ready(function() {
    if (typeof initImageModal === "function") { 
        initImageModal(); 
    }
});
</script>

<style>
/* Favorite Button Style */
.btn-fav { background: none; border: none; cursor: pointer; font-size: 1.8rem; color: #dc3545; transition: transform 0.2s; padding: 5px; line-height:1; }
.btn-fav:hover { transform: scale(1.15); }
.btn-fav i.fa-heart-o { color: #ccc; }
.btn-fav:hover i.fa-heart-o { color: #dc3545; }

/* Cart Buttons */
.btn-danger { background-color: #e74c3c; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background-color 0.3s; }
.btn-danger:hover { background-color: #c0392b; }
.cart-buttons { display: flex; gap: 10px; margin-top: 10px; }

.btn-outline { background-color: #006989; min-width: 120px; border-radius: 30px; color: white; border: none; padding: 12px 20px; cursor: pointer; font-size: 14px; transition: background-color 0.3s; display: inline-block; }
.btn-outline:hover { background-color: #005a73; }
</style>

<?php include '../footer.php'; ?>
