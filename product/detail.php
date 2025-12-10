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

$video_url = $p->video_url;

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
/* ===== PRODUCT DETAIL STYLES ===== */
.product-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
    min-height: 70vh;
}

.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: start;
}

.product-gallery {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.main-photo {
    width: 100%;
    height: 400px;
    border-radius: 15px;
    overflow: hidden;
    background: #f8fafc;
}

.main-photo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.photo-placeholder {
    width: 100%;
    height: 400px;
    background: linear-gradient(135deg, #f8fafc, #e8f4f8);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #006989;
    font-size: 1.1em;
}

.additional-photos {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 10px 3px;
}

.additional-photos img,
.additional-photos .video-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.additional-photos .video-thumb video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.additional-photos img:hover {
    border-color: #01A7C2;
}

.video-thumb {
    position: relative;
    overflow: hidden;
}

.video-thumb video {
    pointer-events: none;
}

#modalVideo {
    border-radius: 8px;
}

.product-info-detail {
    padding: 20px 0;
}

.product-title {
    font-size: 2em;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 15px;
}

.product-id {
    color: #718096;
    font-size: 0.9em;
    margin-bottom: 20px;
}

.product-description {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 25px;
    font-size: 1.05em;
    margin-right: 80px;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.current-price {
    font-size: 2em;
    font-weight: 700;
    color: #006989;
}

.stock-status {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9em;
}

.cart-section {
    background: linear-gradient(135deg, #f8fafc 0%, #e8f4f8 100%);
    padding: 20px 30px 30px;
    border-radius: 15px;
    margin-top: 30px;
    border: 2px solid #e2e8f0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.cart-section h3 {
    margin-bottom: 20px;
    color: #006989;
    font-size: 1.3em;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.cart-section h3 i {
    font-size: 1.2em;
}

.disabled-notice {
    text-align: center;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    border: 2px dashed #cbd5e0;
}

.disabled-notice i {
    font-size: 3em;
    color: #a0aec0;
    margin-bottom: 15px;
    display: block;
}

.disabled-notice p {
    color: #718096;
    font-size: 1.05em;
    margin: 0;
    font-weight: 500;
}

.quantity-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.quantity-wrapper label {
    font-weight: 600;
    color: #2d3748;
    font-size: 1em;
    min-width: 80px;
}

.quantity-selector {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.quantity-selector select {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #cbd5e0;
    border-radius: 10px;
    background: white;
    font-size: 1em;
    color: #2d3748;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.quantity-selector select:hover {
    border-color: #01A7C2;
}

.quantity-selector select:focus {
    outline: none;
    border-color: #006989;
    box-shadow: 0 0 0 3px rgba(1, 167, 194, 0.1);
}

.cart-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    background: white;
    border-radius: 10px;
    border: 2px solid #48bb78;
    animation: slideIn 0.3s ease;
}

.cart-message i.fa-check-circle {
    color: #48bb78;
    font-size: 1.2em;
}

.cart-message span {
    color: #2d3748;
    font-weight: 600;
    flex: 1;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Image Modal Styles */
.image-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.7);
    animation: fadeIn 0.3s;
    padding: 0;
    margin: 0;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 700px;
    max-height: 80vh;
    object-fit: contain;
    animation: zoom 0.3s;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

@keyframes zoom {
    from { transform: scale(0.8); }
    to { transform: scale(1); }
}

.product-modal-close {
    position: fixed;
    top: 15px;
    right: 35px;
    border-radius: 5px;
    background-color: rgba(0, 0, 0, 0.9);
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    z-index: 9999;
    pointer-events: auto;
}

.product-modal-close:hover {
    color: #01A7C2;
    transform: scale(1.1);
}

/* Navigation Arrows */
.modal-nav {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.nav-arrow {
    pointer-events: auto;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    font-size: 24px;
    padding: 15px 20px;
    cursor: pointer;
    border-radius: 5px;
    transition: all 0.3s ease;
    z-index: 1001;
    pointer-events: auto;
}

.nav-arrow:hover {
    background: rgba(0, 0, 0, 0.8);
    color: #01A7C2;
}

.prev-arrow {
    left: 20px;
}

.next-arrow {
    right: 20px;
}

/* Image Counter */
.image-counter {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    font-size: 16px;
    background: rgba(0, 0, 0, 0.7);
    padding: 8px 16px;
    border-radius: 20px;
    z-index: 1001;
}

.modal-caption {
    position: absolute;
    bottom: 60px; /* Position above the counter */
    left: 50%;
    transform: translateX(-50%);
    color: #ccc;
    text-align: center;
    padding: 10px 0;
    width: 80%;
    max-width: 700px;
    z-index: 1001;
}

/* Make main image clickable */
.clickable-main {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.clickable-main:hover {
    transform: scale(1.02);
}

.thumbnail {
    cursor: pointer;
    transition: transform 0.3s ease, border-color 0.3s ease;
}

.thumbnail:hover {
    transform: scale(1.05);
    border-color: #01A7C2 !important;
}

.thumbnail.active-thumbnail {
    border-color: #01A7C2 !important;
    border-width: 3px;
}

/* Make main image clickable too */
.main-photo img {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.main-photo img:hover {
    transform: scale(1.02);
}

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

#mainProductVideo {
    cursor: pointer;
}
</style>

<div class="product-detail-container">
    <div class="product-detail-grid">
        
        <div class="product-gallery">
            <?php if (!empty($main_photo)): ?>
                <div class="main-photo">
                    <?php if (!empty($video_url)): ?>
                            <video id="mainProductVideo" controls controlsList="nofullscreen" style="width:100%; height:100%; object-fit:cover; <?= $isDisabled ? 'filter:grayscale(100%); opacity:0.8;' : '' ?>">
                                <source src="/products/<?= $video_url ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                    <?php elseif (!empty($main_photo)): ?>
                        <img src="/products/<?= $main_photo ?>" alt="<?= $main_photo ?>" id="mainProductImage" class="clickable-main" data-fullsize="/products/<?= $main_photo ?>" data-index="0" style="width:100%; height:100%; object-fit:cover; <?= $isDisabled ? 'filter:grayscale(100%); opacity:0.8;' : '' ?>">
                    <?php else: ?>
                        <div class="photo-placeholder">No Image Available</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="photo-placeholder">No Image Available</div>
            <?php endif; ?>

            <?php if (!empty($p->model3d) && file_exists("../products/".$p->model3d)): ?>
            <div class="product-3d-model" style="margin:20px 0;">
                <model-viewer src="/products/<?= $p->model3d ?>"
                            alt="<?= htmlspecialchars($p->name) ?>"
                            auto-rotate
                            camera-controls
                            style="width:100%; height:400px;">
                </model-viewer>
            </div>
            <?php endif; ?>
        
            <!-- Show all photos as thumbnails (main + additional) -->
            <div class="additional-photos">
                <?php if (!empty($video_url)): ?>
                    <!-- Video thumbnail -->
                    <div class="thumbnail video-thumb active-thumbnail" data-type="video" data-src="/products/<?= $video_url ?>" data-index="0" style="position:relative; background:#000;">
                        <video style="width:100%; height:100%; object-fit:cover;" muted>
                            <source src="/products/<?= $video_url ?>" type="video/mp4">
                        </video>
                        <i class="fa fa-play-circle" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:white; font-size:24px; pointer-events:none;"></i>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($main_photo)): ?>
                    <img src="/products/<?= $main_photo ?>" alt="Main Photo" class="thumbnail <?= empty($video_url) ? 'active-thumbnail' : '' ?>" data-type="image" data-fullsize="/products/<?= $main_photo ?>" data-index="<?= empty($video_url) ? 0 : 1 ?>">
                <?php endif; ?>
                
                <?php if ($additional_photos): ?>
                    <?php 
                    $photoStartIndex = 1; // Start from 1 if no video
                    if (!empty($video_url)) $photoStartIndex = 2; // Start from 2 if video exists
                    elseif (!empty($main_photo)) $photoStartIndex = 1; // Start from 1 if main photo exists
                    ?>
                    <?php foreach ($additional_photos as $index => $photo): ?>
                        <img src="/products/<?= $photo->photoURL ?>" alt="<?= $photo->photoURL ?>" class="thumbnail" data-type="image" data-fullsize="/products/<?= $photo->photoURL ?>" data-index="<?= $photoStartIndex + $index ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-info-detail">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; position:relative;">
                <h1 class="product-title" style="margin: 0; line-height: 1.2;">
                    <?= $p->name ?>
                    <?php if ($isDisabled): ?>
                        <span style="font-size:0.5em; background:#ccc; color:#fff; padding:3px 8px; border-radius:4px; vertical-align:middle;">UNAVAILABLE</span>
                    <?php endif; ?>
                </h1>
                
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="id" value="<?= $p->productID ?>">
                    <input type="hidden" name="action" value="toggle_fav">
                    <button type="submit" class="fav-btn-floating" title="<?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
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
                    <div class="disabled-notice">
                        <i class="fa fa-ban"></i>
                        <p>This product is currently unavailable and cannot be purchased.</p>
                    </div>
                <?php else: ?>
                    <h3><i class="fa fa-shopping-cart"></i> Add to Cart</h3>
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
                        
                        <div class="quantity-wrapper">
                            <label for="unit-select">Quantity:</label>
                            <?= html_select('unit', $stock_options, '', $unit, $p->stock) ?>
                        </div>
                        
                        <?php if ($unit): ?>
                            <div class="cart-message">
                                <i class="fa fa-check-circle"></i>
                                <span><?= $unit ?> unit<?= $unit > 1 ? 's' : '' ?> in cart</span>
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
    <button class="product-modal-close">&times;</button>
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

$('.thumbnail').on('click', function() {
    const type = $(this).data('type');
    const newIndex = $(this).data('index');
    
    if (type === 'video') {
        const videoSrc = $(this).data('src');
        $('#mainProductImage').hide();
        
        const mainVideo = $('#mainProductVideo').get(0);
        if (mainVideo) {
            $(mainVideo).show();
        } else {
        }
    } else {
        const newSrc = $(this).attr('src');
        const fullsizeSrc = $(this).data('fullsize');
        
        $('#mainProductVideo').hide();
        
        if ($('#mainProductImage').length === 0) {
            $('.main-photo').prepend(`<img src="${newSrc}" id="mainProductImage" class="clickable-main" data-fullsize="${fullsizeSrc}" data-index="${newIndex}" style="cursor:pointer;">`);
        } else {
            $('#mainProductImage')
                .attr('src', newSrc)
                .attr('data-fullsize', fullsizeSrc)
                .attr('data-index', newIndex)
                .show();
        }
        
        // Re-bind click event using global modal data
        $('#mainProductImage').off('click').on('click', function() {
            $('#mainProductVideo').get(0)?.pause();
            if (window.modalData) {
                window.modalData.currentIndex = parseInt($(this).attr('data-index'));
                window.modalData.showMedia();
                window.modalData.modal.style.display = 'block';
            }
        });
    }
    
    $('.thumbnail').removeClass('active-thumbnail');
    $(this).addClass('active-thumbnail');
});

function initImageModal() {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const closeBtn = document.querySelector('.product-modal-close');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    let mediaItems = [];
    let currentIndex = 0;
    let savedVideoTime = 0; // Track video progress globally
    
    // Make these available globally for thumbnail clicks
    window.modalData = {
        mediaItems: mediaItems,
        get currentIndex() { return currentIndex; },
        set currentIndex(val) { currentIndex = val; },
        showMedia: null,
        modal: modal
    };
    
    // Collect all media (video + images)
    if ($('.video-thumb').length > 0) {
        mediaItems.push({
            type: 'video',
            src: $('.video-thumb').data('src')
        });
    }
    
    $('.thumbnail[data-type="image"]').each(function() {
        mediaItems.push({
            type: 'image',
            src: $(this).data('fullsize')
        });
    });
    
    // Open modal when clicking main photo
    $(document).on('click', '#mainProductImage', function() {
        const mainVideo = $('#mainProductVideo').get(0);
        if (mainVideo) {
            savedVideoTime = mainVideo.currentTime; // Save main video time
            mainVideo.pause();
        }
        const mainIndex = parseInt($(this).attr('data-index'));
        currentIndex = mainIndex;
        showMedia();
        modal.style.display = 'block';
    });
    
    // Open modal when clicking main video (but not controls)
    $(document).on('click', '#mainProductVideo', function(e) {
        // Allow normal video controls to work
        const video = $(this).get(0);
        const rect = video.getBoundingClientRect();
        const clickY = e.clientY - rect.top;
        const controlsHeight = 50; // Approximate height of controls bar
        
        // If clicking in controls area, don't open modal
        if (clickY > rect.height - controlsHeight) {
            return;
        }
        
        // Otherwise open modal
        savedVideoTime = video.currentTime;
        video.pause();
        currentIndex = 0;
        showMedia();
        modal.style.display = 'block';
    });
    
    function showMedia() {
        const currentMedia = mediaItems[currentIndex];
        
        if (currentMedia.type === 'video') {
            if ($('#modalVideo').length === 0) {
                modalImg.style.display = 'none';
                $(modalImg).after(`
                    <video id="modalVideo" controls style="margin: auto; display: block; width: 80%; max-width: 700px; max-height: 80vh; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <source src="${currentMedia.src}" type="video/mp4">
                    </video>
                `);
                const modalVideo = $('#modalVideo').get(0);
                modalVideo.currentTime = savedVideoTime;
                modalVideo.play();
                
                // Sync time changes back to main video
                modalVideo.addEventListener('timeupdate', function() {
                    savedVideoTime = this.currentTime;
                });
            } else {
                modalImg.style.display = 'none';
                $('#modalVideo').show();
                const modalVideo = $('#modalVideo').get(0);
                modalVideo.play();
            }
        } else {
            const modalVideo = $('#modalVideo').get(0);
            if (modalVideo) {
                modalVideo.pause();
            }
            $('#modalVideo').hide();
            modalImg.src = currentMedia.src;
            modalImg.style.display = 'block';
        }
        
        $('#imageCounter').text((currentIndex + 1) + ' / ' + mediaItems.length);
    }
    
    // Make showMedia available globally
    window.modalData.showMedia = showMedia;
    
    // Close modal
    closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeModal();
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    function closeModal() {
        modal.style.display = 'none';
        const modalVideo = $('#modalVideo').get(0);
        const mainVideo = $('#mainProductVideo').get(0);
        
        if (modalVideo) {
            savedVideoTime = modalVideo.currentTime;
            modalVideo.pause();
        }
        
        // Sync time back to main video
        if (mainVideo) {
            mainVideo.currentTime = savedVideoTime;
        }
    }
    
    prevBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const modalVideo = $('#modalVideo').get(0);
        if (modalVideo && currentIndex === 0) {
            savedVideoTime = modalVideo.currentTime;
        }
        
        currentIndex = (currentIndex - 1 + mediaItems.length) % mediaItems.length;
        showMedia();
    });
    
    nextBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const modalVideo = $('#modalVideo').get(0);
        if (modalVideo && currentIndex === 0) {
            savedVideoTime = modalVideo.currentTime;
        }
        
        currentIndex = (currentIndex + 1) % mediaItems.length;
        showMedia();
    });
    
    document.addEventListener('keydown', function(e) {
        if (modal.style.display === 'block') {
            if (e.key === 'Escape') {
                closeModal();
            }
            if (e.key === 'ArrowLeft') prevBtn.click();
            if (e.key === 'ArrowRight') nextBtn.click();
        }
    });
}
</script>

<script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>

<?php include '../footer.php'; ?>