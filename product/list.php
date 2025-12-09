<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// 1. HANDLE POST REQUESTS (Cart & Favorites)
// ----------------------------------------------------------------------------

if (is_post()) {
    $id = req('id');
    $action = req('action');

    // --- FAVORITE TOGGLE ---
    if ($action == 'toggle_fav') {
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

    // --- CART UPDATE ---
    $unit = req('unit');
    if ($id && $unit) {
        // Double check if active before adding
        $check = $_db->prepare("SELECT is_active FROM product WHERE productID = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() == 0) {
            temp('info', 'Product unavailable.');
            redirect();
        }

        update_cart($id, $unit);
        redirect();
    }
}

// ----------------------------------------------------------------------------
// 2. FETCH DATA
// ----------------------------------------------------------------------------

$categoryStm = $_db->query('SELECT * FROM ProductCategory ORDER BY categoryName');
$categories = $categoryStm->fetchAll();

$minPrice = req('min_price') ?? 0;
$maxPrice = req('max_price') ?? 1000;

$currentCategory = req('category') ?? '';
$searchQuery = req('search') ?? '';

$allProductsStm = $_db->query('SELECT COUNT(*) FROM product');
$allProductsCount = $allProductsStm->fetchColumn();

// --- BUILD QUERY ---
$params = [];
$conditions = [];

// Note: Removed "is_active = 1" so we can fetch disabled items and show them grayed out

if ($searchQuery) {
    $conditions[] = 'p.name LIKE ?';
    $params[] = "%$searchQuery%";
}

if ($currentCategory) {
    $conditions[] = 'p.categoryID = ?';
    $params[] = $currentCategory;
}

if ($minPrice > 0 || $maxPrice < 1000) {
    $conditions[] = 'p.price BETWEEN ? AND ?';
    $params[] = $minPrice;
    $params[] = $maxPrice;
}

$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

$stm = $_db->prepare("
    SELECT p.*, c.categoryName 
    FROM product p 
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID 
    $whereClause 
    ORDER BY p.productID DESC
");
$stm->execute($params);
$arr = $stm->fetchAll();

$currentCatName = '';
if ($currentCategory) {
    foreach ($categories as $cat) {
        if ($cat->categoryID == $currentCategory) {
            $currentCatName = $cat->categoryName;
            break;
        }
    }
}

$user_favorites = [];
if ($_user) {
    $fav_stm = $_db->prepare("SELECT productID FROM favorite WHERE userID = ?");
    $fav_stm->execute([$_user->id]);
    $user_favorites = $fav_stm->fetchAll(PDO::FETCH_COLUMN);
}

$_title = 'Product | List';
include '../navbar.php';
?>

<style>
/* --- PRODUCT STYLES --- */
#products {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* Force 4 columns side by side */
    gap: 25px;
    padding: 20px;
    min-height: 60vh;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.product_container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

.product {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 105, 137, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    border: 1px solid #e8f4f8;
}

.product:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 105, 137, 0.15);
}

.product-image {
    width: 100%;
    height: 200px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product:hover .product-image img {
    transform: scale(1.05);
}

.product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #01A7C2;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
}

.product-info {
    padding: 20px;
}

.product-name {
    font-size: 1.1em;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    line-height: 1.4;
}

.product-price {
    font-size: 1.3em;
    font-weight: 700;
    color: #006989;
    margin-bottom: 12px;
}

.product-stock {
    font-size: 0.9em;
    margin-bottom: 15px;
}

.in-stock {
    color: #28a745;
    font-weight: 600;
}

.out-of-stock {
    color: #dc3545;
    font-weight: 600;
}

.product-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.product-form select {
    flex: 1;
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    font-size: 0.9em;
    transition: border-color 0.3s ease;
}

.product-form select:focus {
    outline: none;
    border-color: #01A7C2;
}

.cart-indicator {
    color: #28a745;
    font-weight: 600;
    font-size: 0.9em;
}

/* Disabled Style: Gray Background & Grayscale Image */
.product.unavailable { opacity: 0.75; background-color: #f9f9f9; }
.product.unavailable .product-image img { filter: grayscale(100%); opacity: 0.6; }
.product.unavailable .product-name, 
.product.unavailable .product-price { color: #999; }

/* Alert Badges */
.stock-alert { font-size: 0.85rem; font-weight: 600; margin-top: 5px; padding: 4px 8px; border-radius: 4px; display: inline-block; }
.stock-alert.in-stock { color: #27ae60; background-color: #e8f8f0; }
.stock-alert.low-stock { color: #f39c12; background-color: #fef5e7; }
.stock-alert.out-of-stock { color: #95a5a6; background-color: #ecf0f1; }
.stock-alert.unavailable-alert { color: #718096; background-color: #e2e8f0; } /* Gray Badge */

/* ... (Your other styles) ... */
.empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 20px; margin: 40px 0; box-shadow: 0 5px 25px rgba(0,0,0,0.08); width: 100%; }
.empty-state-icon { font-size: 5rem; margin-bottom: 25px; }
.empty-state h2 { color: #495057; margin-bottom: 15px; font-weight: 600; }
.empty-state-message { color: #6c757d; font-size: 1.2rem; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6; }
.empty-state-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }

.btn { padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-block; }
.btn-primary { background: #006989; color: white; }
.btn-primary:hover { background: #005a73; transform: translateY(-2px); }

.product-image { position: relative; }

/* Badges */
.badge-top-left {
    position: absolute; top: 10px; left: 10px; z-index: 10;
    width: auto; display: inline-block; padding: 4px 10px;
    border-radius: 20px; font-size: 0.75rem; font-weight: 700;
    color: white; text-transform: uppercase;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2); pointer-events: none;
}
.bg-green { background-color: #27ae60; }
.bg-red   { background-color: #e74c3c; }
.bg-gray  { background-color: #718096; }
</style>

<div class="container">
    <?php if (!empty($arr)): ?>
        <div id="products">
            <?php foreach ($arr as $p): ?>
                <?php
                $cart = get_cart();
                $id   = $p->productID;
                $unit = $cart[$p->productID] ?? 0;
                $isFav = in_array($p->productID, $user_favorites);
                $main_photo = get_main_photo($p->productID);
                
                // --- RESET VARIABLES ---
                $stock_class = ''; 
                $stock_alert_class = ''; 
                $stock_message = '';
                $badgeHTML = '';
                $isDisabled = ($p->is_active == 0);
                
                // --- DETERMINE STATUS ---
                if ($isDisabled) {
                    $stock_class = 'unavailable'; 
                    $stock_alert_class = 'unavailable-alert'; 
                    $stock_message = 'Unavailable';
                    $badgeHTML = '<div class="badge-top-left bg-gray">Unavailable</div>';
                } elseif ($p->stock == 0) {
                    $stock_class = 'out-of-stock'; 
                    $stock_alert_class = 'out-of-stock'; 
                    $stock_message = 'Out of Stock';
                } else {
                    $stock_alert_class = 'in-stock'; 
                    $stock_message = 'In Stock';
                    if ($p->productID > 50) { 
                        $badgeHTML = '<div class="badge-top-left bg-green">New</div>';
                    }
                }
                ?>
                
                <div class="product <?= $stock_class ?>">
                    
                    <div class="product-image">
                        <?php if ($isDisabled): ?>
                            <div style="display:block; width:100%; height:100%;">
                        <?php else: ?>
                            <a href="/product/detail.php?id=<?= $p->productID ?>" style="display:block; width:100%; height:100%;">
                        <?php endif; ?>

                            <?php if (!empty($main_photo)): ?>
                                <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                            <?php else: ?>
                                <div class="photo-placeholder">No Image</div>
                            <?php endif; ?>
                        
                        <?php if ($isDisabled): ?>
                            </div>
                        <?php else: ?>
                            </a>
                        <?php endif; ?>

                        <?= $badgeHTML ?>

                        <form method="post" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $p->productID ?>">
                            <input type="hidden" name="action" value="toggle_fav">
                            <button type="submit" class="fav-btn-floating" title="<?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                                <i class="fa <?= $isFav ? 'fa-heart' : 'fa-heart-o' ?>"></i>
                            </button>
                        </form>
                    </div>

                    <div class="product-info">
                        <div class="product-name">
                            <?= $p->name ?>
                        </div>
                        <div class="product-price">
                            RM <?= number_format($p->price, 2) ?>
                        </div>
                        <div class="stock-alert <?= $stock_alert_class ?>">
                            <?= $stock_message ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">😔</div>
            <h2>No Products Available</h2>
            <div class="empty-state-actions">
                <a href="/product/list.php" class="btn btn-primary">Browse All</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>