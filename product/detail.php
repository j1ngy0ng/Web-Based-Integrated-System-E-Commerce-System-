<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// 1. HANDLE POST REQUESTS (Cart & Favorites)
// ----------------------------------------------------------------------------

if (is_post()) {
    $id = req('id');
    $action = req('action');

    // --- A. FAVORITE TOGGLE ---
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
    // Security Check: Is product active?
    $checkStm = $_db->prepare("SELECT is_active FROM product WHERE productID = ?");
    $checkStm->execute([$id]);
    $isActive = $checkStm->fetchColumn();

    if ($isActive == 0) {
        temp('info', 'This product is currently unavailable.');
        redirect();
    }

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

// CHANGED: Allow fetching disabled products to show details (but prevent buying)
$stm = $_db->prepare('SELECT * FROM product WHERE productID = ?');
$stm->execute([$id]);
$p = $stm->fetch();

if (!$p) {
    temp('info', 'Product not found.');
    redirect('list.php');
}

$isDisabled = ($p->is_active == 0);

$main_photo = get_main_photo($id);
$additional_photos = get_additional_photos($id);

// Cart Data
$cart = get_cart();
$unit = $cart[$id] ?? 0;

// Stock Limits
$max_units = ($p->stock <= 2) ? $p->stock : min($p->stock, 10);

// Check Favorite
$isFav = false;
if ($_user) {
    $stm = $_db->prepare("SELECT COUNT(*) FROM favorite WHERE userID = ? AND productID = ?");
    $stm->execute([$_user->id, $id]);
    $isFav = $stm->fetchColumn() > 0;
}

// ----------------------------------------------------------------------------
// 3. FETCH RATINGS
// ----------------------------------------------------------------------------

// Join with User table if you have names, otherwise use ID
$sql_ratings = "
    SELECT pr.rating, pr.comment, pr.rating_date, u.name as userName, o.userID
    FROM productRating pr
    JOIN order_item oi ON pr.order_item_id = oi.order_item_id
    JOIN `order` o ON oi.orderID = o.orderID
    LEFT JOIN user u ON o.userID = u.id
    WHERE oi.productID = ?
    ORDER BY pr.rating_date DESC
";
$stm_ratings = $_db->prepare($sql_ratings);
$stm_ratings->execute([$p->productID]);
$ratings = $stm_ratings->fetchAll();

$_title = 'Product | Detail';
include '../navbar.php';
?>

<style>
/* ... (Your Existing Styles) ... */

/* --- NEW RATINGS UI --- */
.ratings-container {
    max-width: 900px;
    margin: 60px auto;
    padding: 0 20px;
}

.ratings-header {
    border-bottom: 2px solid #f1f1f1;
    padding-bottom: 15px;
    margin-bottom: 30px;
}

.ratings-header h2 {
    margin: 0;
    color: #2d3748;
    font-size: 1.8rem;
}

.review-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.review-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #006989;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.review-content {
    flex-grow: 1;
}

.review-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    align-items: center;
}

.review-author {
    font-weight: 700;
    color: #2d3748;
    font-size: 1rem;
}

.review-date {
    color: #a0aec0;
    font-size: 0.85rem;
}

.review-stars {
    color: #f6c23e;
    font-size: 0.95rem;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

.review-text {
    color: #4a5568;
    line-height: 1.6;
    font-size: 0.95rem;
}

.empty-ratings {
    text-align: center;
    padding: 40px;
    color: #718096;
    background: #f8fafc;
    border-radius: 12px;
}
</style>

<div class="product-detail-container">
    <div class="product-detail-grid">
        
        <div class="product-gallery">
            <?php if (!empty($main_photo)): ?>
                <div class="main-photo">
                    <img src="/products/<?= $main_photo ?>" alt="<?= $main_photo ?>" id="mainProductImage" class="clickable-main" data-fullsize="/products/<?= $main_photo ?>" data-index="0" style="<?= $isDisabled ? 'filter:grayscale(100%); opacity:0.8;' : '' ?>">
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
        
        <div class="product-info-detail">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <h1 class="product-title" style="margin: 0; line-height: 1.2;">
                    <?= $p->name ?>
                    <?php if ($isDisabled): ?>
                        <span style="font-size:0.5em; background:#ccc; color:#fff; padding:3px 8px; border-radius:4px; vertical-align:middle;">UNAVAILABLE</span>
                    <?php endif; ?>
                </h1>
                
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
                <div class="current-price" style="<?= $isDisabled ? 'color:#999;' : '' ?>">RM <?= number_format($p->price, 2) ?></div>
                
                <div class="stock-status">
                    <?php if ($isDisabled): ?>
                        <span style="color: #718096; font-weight: 600; background:#edf2f7; padding:5px 10px; border-radius:4px;">
                            🚫 Currently Unavailable
                        </span>
                    <?php elseif ($p->stock > 0): ?>
                        <?php if ($p->stock <= 10): ?>
                            <span style="color: #f39c12; font-weight: 600;"> ⚠️ Low Stock: Only <?= $p->stock ?> left</span>
                        <?php else: ?>
                            <span style="color: #27ae60; font-weight: 600;">In Stock</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #e74c3c; font-weight: 600;">Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="cart-section">
                <?php if ($isDisabled): ?>
                    <div style="padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; text-align: center; color: #6c757d;">
                        This product is currently disabled and cannot be purchased.
                    </div>
                <?php else: ?>
                    <h3>Add to Cart</h3>
                    <form method="post" class="quantity-selector" id="cartForm">
                        <input type="hidden" name="id" value="<?= $p->productID ?>">
                        <input type="hidden" name="action" value="">
                        
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
                        
                        <?php if ($unit): ?>
                            <div class="cart-message">
                                ✅ <?= $unit ?> unit<?= $unit > 1 ? 's' : '' ?> in cart
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="ratings-container">
    <div class="ratings-header">
        <h2>Customer Reviews (<?= count($ratings) ?>)</h2>
    </div>

    <?php if (!$ratings): ?>
        <div class="empty-ratings">
            <i class="fa fa-star-o" style="font-size: 3rem; margin-bottom: 10px;"></i>
            <p>No ratings yet. Be the first to review this product!</p>
        </div>
    <?php else: ?>
        <div class="review-list">
            <?php foreach ($ratings as $r): ?>
                <?php 
                    // Display Name (Fallback to ID if name not found)
                    $displayName = $r->userName ?? 'User #' . $r->userID;
                    $initial = strtoupper(substr($displayName, 0, 1));
                ?>
                <div class="review-card">
                    <div class="review-avatar"><?= $initial ?></div>
                    <div class="review-content">
                        <div class="review-meta">
                            <span class="review-author"><?= htmlspecialchars($displayName) ?></span>
                            <span class="review-date"><?= date('d M Y', strtotime($r->rating_date)) ?></span>
                        </div>
                        <div class="review-stars">
                            <?= str_repeat('★', $r->rating) . str_repeat('☆', 5 - $r->rating) ?>
                        </div>
                        <div class="review-text">
                            <?= htmlspecialchars($r->comment ?: 'No written review.') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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
.btn-fav { background: none; border: none; cursor: pointer; font-size: 1.8rem; color: #dc3545; transition: transform 0.2s; padding: 5px; line-height:1; }
.btn-fav:hover { transform: scale(1.15); }
.btn-fav i.fa-heart-o { color: #ccc; }
.btn-fav:hover i.fa-heart-o { color: #dc3545; }

.btn-danger { background-color: #e74c3c; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background-color 0.3s; }
.btn-danger:hover { background-color: #c0392b; }
.cart-buttons { display: flex; gap: 10px; margin-top: 10px; }

.btn-outline { background-color: #006989; min-width: 120px; border-radius: 30px; color: white; border: none; padding: 12px 20px; cursor: pointer; font-size: 14px; transition: background-color 0.3s; display: inline-block; }
.btn-outline:hover { background-color: #005a73; }
</style>

<?php include '../footer.php'; ?>