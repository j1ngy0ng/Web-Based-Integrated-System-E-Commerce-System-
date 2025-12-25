<?php
include '_base.php';

// ----------------------------------------------------------------------------
// 1. HANDLE FAVORITE TOGGLE
// ----------------------------------------------------------------------------
if (is_post()) {
    $id = req('id');
    $action = req('action');

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
}

// ----------------------------------------------------------------------------
// 2. FETCH DATA
// ----------------------------------------------------------------------------

$user_favorites = [];
if ($_user) {
    $fav_stm = $_db->prepare("SELECT productID FROM favorite WHERE userID = ?");
    $fav_stm->execute([$_user->id]);
    $user_favorites = $fav_stm->fetchAll(PDO::FETCH_COLUMN);
}

// Banners
$banners = [
    ['image' => '/photos/ad1.jpg', 'title' => 'Summer Sale', 'subtitle' => 'Up to 50% Off', 'link' => '/product/list.php'],
    ['image' => '/photos/ad2.jpg', 'title' => 'New Arrivals', 'subtitle' => 'Check Out Latest Products', 'link' => '/product/list.php'],
    ['image' => '/photos/ad3.jpg', 'title' => 'Special Deals', 'subtitle' => 'Limited Time Offers', 'link' => '/product/list.php']
];

// Top 8 Selling Products (RESTRICTED TO TOP 8)
$topSellingStm = $_db->query('
    SELECT p.*, COALESCE(SUM(oi.quantity), 0) as total_sold, c.categoryName
    FROM product p
    LEFT JOIN order_item oi ON p.productID = oi.productID
    LEFT JOIN productcategory c ON p.categoryID = c.categoryID
    WHERE p.stock > 0 
    GROUP BY p.productID
    ORDER BY total_sold DESC
    LIMIT 8
');
$topSelling = $topSellingStm->fetchAll();

// New Arrivals
$newArrivalsStm = $_db->query('
    SELECT p.*, c.categoryName
    FROM product p
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    WHERE p.stock > 0
    ORDER BY p.productID DESC
    LIMIT 8
');
$newArrivals = $newArrivalsStm->fetchAll();

// "You May Like" - Based on User's Order History
if ($_user) {
    // Get categories from user's previous orders (ALL order items)
    $userCategoriesStm = $_db->prepare('
        SELECT DISTINCT p.categoryID
        FROM order_item oi
        INNER JOIN product p ON oi.productID = p.productID
        INNER JOIN `order` o ON oi.orderID = o.orderID
        WHERE o.userID = ?
    ');
    $userCategoriesStm->execute([$_user->id]);
    $userCategories = $userCategoriesStm->fetchAll(PDO::FETCH_COLUMN);

    // Get ALL products the user has ordered (from all order items)
    $orderedProductsStm = $_db->prepare('
        SELECT DISTINCT oi.productID
        FROM order_item oi
        INNER JOIN `order` o ON oi.orderID = o.orderID
        WHERE o.userID = ?
    ');
    $orderedProductsStm->execute([$_user->id]);
    $orderedProducts = $orderedProductsStm->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userCategories)) {
        // Check what products exist in these categories
        $placeholders = str_repeat('?,', count($userCategories) - 1) . '?';
        $checkStm = $_db->prepare("
            SELECT p.productID, p.name, p.categoryID, p.stock, p.is_active
            FROM product p
            WHERE p.categoryID IN ($placeholders)
        ");
        $checkStm->execute($userCategories);
        $allInCategory = $checkStm->fetchAll();

        // FIRST TRY: Get products from same categories, excluding ordered ones
        $query = "
            SELECT p.*, c.categoryName
            FROM product p
            LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
            WHERE p.stock > 0 
            AND p.is_active = 1
            AND p.categoryID IN ($placeholders)
        ";

        $params = $userCategories;

        if (!empty($orderedProducts)) {
            $excludePlaceholders = str_repeat('?,', count($orderedProducts) - 1) . '?';
            $query .= " AND p.productID NOT IN ($excludePlaceholders)";
            $params = array_merge($userCategories, $orderedProducts);
        }

        $query .= " ORDER BY RAND() LIMIT 8";

        $recommendedStm = $_db->prepare($query);
        $recommendedStm->execute($params);
        $recommended = $recommendedStm->fetchAll();

        // FALLBACK 1: If no products found, include already ordered products
        if (empty($recommended)) {
            $query = "
                SELECT p.*, c.categoryName
                FROM product p
                LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
                WHERE p.stock > 0 
                AND p.is_active = 1
                AND p.categoryID IN ($placeholders)
                ORDER BY RAND() 
                LIMIT 8
            ";
            $recommendedStm = $_db->prepare($query);
            $recommendedStm->execute($userCategories);
            $recommended = $recommendedStm->fetchAll();
        }

        if (empty($recommended)) {
            $recommendedStm = $_db->query('
                SELECT p.*, c.categoryName
                FROM product p
                LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
                WHERE p.stock > 0
                AND p.is_active = 1
                ORDER BY RAND()
                LIMIT 8
            ');
            $recommended = $recommendedStm->fetchAll();
        }
    } else {
        $recommendedStm = $_db->query('
            SELECT p.*, c.categoryName
            FROM product p
            LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
            WHERE p.stock > 0
            AND p.is_active = 1
            ORDER BY RAND()
            LIMIT 8
        ');
        $recommended = $recommendedStm->fetchAll();
    }
} else {
    // For guests, show random products
    $recommendedStm = $_db->query('
        SELECT p.*, c.categoryName
        FROM product p
        LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
        WHERE p.stock > 0
        AND p.is_active = 1
        ORDER BY RAND()
        LIMIT 8
    ');
    $recommended = $recommendedStm->fetchAll();
}

// Categories
$categoriesStm = $_db->query('SELECT * FROM ProductCategory ORDER BY categoryName');
$categories = $categoriesStm->fetchAll();


// Fetch upcoming events for the month
$year  = req('year') ?: date('Y');
$month = req('month') ?: date('n');

$a = new DateTime("$year-$month");
$b = new DateTime("last day of $year-$month");

if ($a->format('N') != 1) $a->modify('previous monday');
if ($b->format('N') != 7) $b->modify('next sunday');

/* Fetch events with products */
$stm = $_db->prepare("
    SELECT e.*, GROUP_CONCAT(pe.product_id) AS product_ids
    FROM product_event e
    LEFT JOIN product_event_item pe ON e.id = pe.event_id
    WHERE e.event_date BETWEEN ? AND ?
    GROUP BY e.id
");
$stm->execute([$a->format('Y-m-d'), $b->format('Y-m-d')]);
$events = $stm->fetchAll(PDO::FETCH_OBJ);

/* Attach products */
$data = [];
foreach ($events as $e) {
    $e->products = [];
    if ($e->product_ids) {
        $ids = explode(',', $e->product_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stm2 = $_db->prepare("SELECT productID, name FROM product WHERE productID IN ($placeholders)");
        $stm2->execute($ids);
        $e->products = $stm2->fetchAll(PDO::FETCH_OBJ);
    }
    $data[$e->event_date][] = $e;
}

$_title = 'Home - Welcome';
include 'navbar.php';
?>

<style>
    /* ... (Your existing slider/section styles) ... */
    .hero-slider {
        position: relative;
        width: 100%;
        height: 500px;
        overflow: hidden;
        margin-bottom: 40px;
    }

    .hero-slide {
        display: none;
        width: 100%;
        height: 100%;
        position: relative;
        animation: fadeIn 0.5s;
    }

    .hero-slide.active {
        display: block;
    }

    .hero-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.3) 100%);
        display: flex;
        align-items: center;
        padding: 0 80px;
    }

    .hero-content {
        color: white;
        max-width: 600px;
    }

    .hero-content h1 {
        font-size: 3.5rem;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .hero-content p {
        font-size: 1.5rem;
        margin-bottom: 25px;
    }

    .hero-btn {
        background: #006989;
        color: white;
        padding: 15px 40px;
        text-decoration: none;
        border-radius: 5px;
        font-size: 1.1rem;
        display: inline-block;
        transition: all 0.3s;
    }

    .hero-btn:hover {
        background: #005a73;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 105, 137, 0.3);
    }

    .slider-controls {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 10;
    }

    .slider-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .slider-dot.active {
        background: white;
        width: 30px;
        border-radius: 6px;
    }

    .slider-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.3);
        color: white;
        border: none;
        font-size: 2rem;
        padding: 15px 20px;
        cursor: pointer;
        transition: all 0.3s;
        z-index: 10;
    }

    .slider-arrow:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .slider-arrow.prev {
        left: 20px;
    }

    .slider-arrow.next {
        right: 20px;
    }

    .container {
        margin: 0 200px;
    }

    .section {
        margin: 60px 0;
    }

    .section-header {
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-right: 45px;
        padding-bottom: 15px;
        border-bottom: 2px solid #006989;
    }

    #section-flex {
        display: flex;
    }

    .section-header h2 {
        font-size: 2rem;
        color: #333;
    }

    .section-controls {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        /* enable horizontal scroll */
        white-space: nowrap;
        /* keep buttons in one line */
        padding-bottom: 5px;
        /* space for scrollbar */
        height: 60px;
        width: 104%;
    }

    .section-controls::-webkit-scrollbar {
        height: 7px;
    }

    .section-controls::-webkit-scrollbar-thumb {
        background: #006989;
        border-radius: 10px;
    }

    .section-controls::-webkit-scrollbar-track {
        background: #e0e0e0;
    }

    .filter-btn {
        padding: 8px 20px;
        border: 1px solid #006989;
        background: white;
        color: #006989;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .filter-btn.active {
        background: #006989;
        color: white;
    }

    .filter-btn:hover {
        background: #006989;
        color: white;
    }

    /* --- PRODUCT CARDS --- */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
    }

    .product-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
        min-width: 0;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .product-card-image {
        width: 100%;
        height: 250px;
        overflow: hidden;
        position: relative;
    }

    .product-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .product-card:hover .product-card-image img {
        transform: scale(1.1);
    }

    .product-card-content {
        padding: 20px;
    }

    .product-card-title {
        font-size: 1.1rem;
        margin-bottom: 10px;
        color: #333;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-card-category {
        color: #666;
        font-size: 0.85rem;
        margin-bottom: 10px;
    }

    .product-card-price {
        font-size: 1.3rem;
        color: #006989;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .product-card-stock {
        font-size: 0.85rem;
        padding: 5px 10px;
        border-radius: 5px;
        display: inline-block;
    }

    .product-card-stock.in-stock {
        background: #e8f8f0;
        color: #27ae60;
    }

    .product-card-stock.low-stock {
        background: #fef5e7;
        color: #f39c12;
    }

    .product-card-stock.unavailable {
        background: #e2e8f0;
        color: #718096;
    }

    .product-sales-info {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
        font-size: 0.85rem;
        color: #666;
    }

    /* Slider */
    .product-slider-container {
        position: relative;
        overflow: hidden;
    }

    .product-slider {
        display: flex;
        gap: 25px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 10px 0;
        scrollbar-width: none;
    }

    .product-slider::-webkit-scrollbar {
        display: none;
    }

    .product-slide {
        flex: 0 0 280px;
        min-width: 0;
    }

    .slider-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: 1px solid #ddd;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        z-index: 10;
        transition: all 0.3s;
    }

    .slider-nav:hover {
        background: #006989;
        color: white;
    }

    .slider-nav.prev-slide {
        left: -1px;
    }

    .slider-nav.next-slide {
        right: -1px;
    }

    /* Badges */
    .product-ranking {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #f39c12;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 700;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 15;
    }

    .badge-top-left {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 10;
        width: auto;
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        color: white;
        text-transform: uppercase;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        pointer-events: none;
    }

    .bg-green {
        background-color: #006989;
    }

    .bg-red {
        background-color: #e74c3c;
    }

    .bg-gray {
        background-color: #718096;
    }

    .bg-purple {
        background-color: #9b59b6;
    }

    .product-card.disabled-card {
        opacity: 0.75;
        background: #f8f9fa;
    }

    .product-card.disabled-card .product-card-image img {
        filter: grayscale(100%);
        opacity: 0.7;
    }

    @media (max-width: 768px) {
        .hero-slider {
            height: 300px;
        }

        .hero-content h1 {
            font-size: 2rem;
        }

        .hero-content p {
            font-size: 1rem;
        }

        .hero-overlay {
            padding: 0 20px;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 10px;
        margin: 20px 0;
    }

    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .empty-state h2 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .empty-state-actions {
        margin-top: 25px;
    }

    .btn {
        padding: 12px 30px;
        text-decoration: none;
        border-radius: 5px;
        display: inline-block;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #006989;
        color: white;
    }

    .btn-primary:hover {
        background: #005a73;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 105, 137, 0.3);
    }
</style>

<div class="hero-slider">
    <?php foreach ($banners as $index => $banner): ?>
        <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
            <img src="<?= $banner['image'] ?>" alt="<?= $banner['title'] ?>">
            <div class="hero-overlay">
                <div class="hero-content">
                    <h1><?= $banner['title'] ?></h1>
                    <p><?= $banner['subtitle'] ?></p>
                    <a href="<?= $banner['link'] ?>" class="hero-btn">Shop Now</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <button class="slider-arrow prev" onclick="changeSlide(-1)">‹</button>
    <button class="slider-arrow next" onclick="changeSlide(1)">›</button>
    <div class="slider-controls">
        <?php foreach ($banners as $index => $banner): ?>
            <button class="slider-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></button>
        <?php endforeach; ?>
    </div>
</div>

<div class="container">

    <div class="section">
        <div class="section-header" id="section-flex">
            <h2>You May Like It</h2>
            <a href="/product/list.php" style="color: #006989; text-decoration: none;">View All →</a>
        </div>
        <div class="product-slider-container">
            <button class="slider-nav prev-slide" onclick="slideProducts('recommended', -1)">‹</button>
            <div class="product-slider" id="recommended">
                <?php foreach ($recommended as $p): ?>
                    <?php
                    $main_photo = get_main_photo($p->productID);
                    $isFav = in_array($p->productID, $user_favorites);

                    $isDisabled = ($p->is_active == 0);

                    if ($isDisabled) {
                        $stock_status = 'unavailable';
                        $stock_text = 'Unavailable';
                        $badgeHTML = '<div class="badge-top-left bg-gray">Unavailable</div>';
                    } else {
                        $stock_status = ($p->stock <= 5) ? 'low-stock' : 'in-stock';
                        $stock_text = ($p->stock <= 5) ? "Only {$p->stock} left!" : "In Stock";
                        $badgeHTML = '<div class="badge-top-left bg-purple">For You</div>';
                    }

                    $cardClass = $isDisabled ? 'disabled-card' : '';
                    ?>
                    <div class="product-slide">
                        <div class="product-card <?= $cardClass ?>">
                            <div class="product-card-image">
                                <?php if ($isDisabled): ?>
                                    <div style="display:block; width:100%; height:100%;">
                                    <?php else: ?>
                                        <a href="/product/detail.php?id=<?= $p->productID ?>" style="display:block; width:100%; height:100%;">
                                        <?php endif; ?>

                                        <?php if (!empty($main_photo)): ?>
                                            <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background: #f0f0f0;"></div>
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
                                    <button type="submit" class="fav-btn-floating">
                                        <i class="fa <?= $isFav ? 'fa-heart' : 'fa-heart-o' ?>"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="product-card-content">
                                <div class="product-card-title" style="<?= $isDisabled ? 'color:#999' : '' ?>"><?= $p->name ?></div>
                                <div class="product-card-category"><?= $p->categoryName ?></div>
                                <div class="product-card-price" style="<?= $isDisabled ? 'color:#999' : '' ?>">RM <?= number_format($p->price, 2) ?></div>
                                <div class="product-card-stock <?= $stock_status ?>"><?= $stock_text ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="slider-nav next-slide" onclick="slideProducts('recommended', 1)">›</button>
        </div>
    </div>
    <div class="section">
        <div class="section-header">
            <h2>Top 8 Selling Products</h2>
            <div class="section-controls">
                <button class="filter-btn active" onclick="filterTopSelling('all')">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" onclick="filterTopSelling('<?= $cat->categoryID ?>')"><?= $cat->categoryName ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-state-icon">😔</div>
            <h2>No Products Available</h2>
        </div>
        <div class="products-grid" id="topSellingGrid">
            <?php foreach ($topSelling as $index => $p): ?>
                <?php
                $main_photo = get_main_photo($p->productID);
                $isFav = in_array($p->productID, $user_favorites);

                $isDisabled = ($p->is_active == 0);

                if ($isDisabled) {
                    $stock_status = 'unavailable';
                    $stock_text = 'Unavailable';
                } else {
                    $stock_status = ($p->stock <= 5) ? 'low-stock' : 'in-stock';
                    $stock_text = ($p->stock <= 5) ? "Only {$p->stock} left!" : "In Stock";
                }

                $cardClass = $isDisabled ? 'disabled-card' : '';
                ?>
                <div class="product-card <?= $cardClass ?>" data-category="<?= $p->categoryID ?>">
                    <div class="product-card-image">
                        <?php if ($isDisabled): ?>
                            <div style="display:block; width:100%; height:100%;">
                            <?php else: ?>
                                <a href="/product/detail.php?id=<?= $p->productID ?>" style="display:block; width:100%; height:100%;">
                                <?php endif; ?>

                                <?php if (!empty($main_photo)): ?>
                                    <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #f0f0f0;"></div>
                                <?php endif; ?>

                                <?php if ($isDisabled): ?>
                            </div>
                        <?php else: ?>
                            </a>
                        <?php endif; ?>

                        <div class="product-ranking">#<?= $index + 1 ?></div>

                        <form method="post" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $p->productID ?>">
                            <input type="hidden" name="action" value="toggle_fav">
                            <button type="submit" class="fav-btn-floating" title="<?= $isFav ? 'Remove Fav' : 'Add Fav' ?>">
                                <i class="fa <?= $isFav ? 'fa-heart' : 'fa-heart-o' ?>"></i>
                            </button>
                        </form>
                    </div>

                    <div class="product-card-content">
                        <div class="product-card-title" style="<?= $isDisabled ? 'color:#999' : '' ?>"><?= $p->name ?></div>
                        <div class="product-card-category"><?= $p->categoryName ?></div>
                        <div class="product-card-price" style="<?= $isDisabled ? 'color:#999' : '' ?>">RM <?= number_format($p->price, 2) ?></div>
                        <div class="product-card-stock <?= $stock_status ?>"><?= $stock_text ?></div>
                        <div class="product-sales-info">📦 <?= $p->total_sold ?> sold</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-header" id="section-flex">
            <h2>New Arrivals</h2>
            <a href="/product/list.php" style="color: #006989; text-decoration: none;">View All →</a>
        </div>
        <div class="product-slider-container">
            <button class="slider-nav prev-slide" onclick="slideProducts('newArrivals', -1)">‹</button>
            <div class="product-slider" id="newArrivals">
                <?php foreach ($newArrivals as $p): ?>
                    <?php
                    $main_photo = get_main_photo($p->productID);
                    $isFav = in_array($p->productID, $user_favorites);

                    $isDisabled = ($p->is_active == 0);

                    if ($isDisabled) {
                        $stock_status = 'unavailable';
                        $stock_text = 'Unavailable';
                        $badgeHTML = '<div class="badge-top-left bg-gray">Unavailable</div>';
                    } else {
                        $stock_status = ($p->stock <= 5) ? 'low-stock' : 'in-stock';
                        $stock_text = ($p->stock <= 5) ? "Only {$p->stock} left!" : "In Stock";
                        $badgeHTML = '<div class="badge-top-left bg-green">New</div>';
                    }

                    $cardClass = $isDisabled ? 'disabled-card' : '';
                    ?>
                    <div class="product-slide">
                        <div class="product-card <?= $cardClass ?>">
                            <div class="product-card-image">
                                <?php if ($isDisabled): ?>
                                    <div style="display:block; width:100%; height:100%;">
                                    <?php else: ?>
                                        <a href="/product/detail.php?id=<?= $p->productID ?>" style="display:block; width:100%; height:100%;">
                                        <?php endif; ?>

                                        <?php if (!empty($main_photo)): ?>
                                            <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background: #f0f0f0;"></div>
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
                                    <button type="submit" class="fav-btn-floating">
                                        <i class="fa <?= $isFav ? 'fa-heart' : 'fa-heart-o' ?>"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="product-card-content">
                                <div class="product-card-title" style="<?= $isDisabled ? 'color:#999' : '' ?>"><?= $p->name ?></div>
                                <div class="product-card-category"><?= $p->categoryName ?></div>
                                <div class="product-card-price" style="<?= $isDisabled ? 'color:#999' : '' ?>">RM <?= number_format($p->price, 2) ?></div>
                                <div class="product-card-stock <?= $stock_status ?>"><?= $stock_text ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="slider-nav next-slide" onclick="slideProducts('newArrivals', 1)">›</button>
        </div>
    </div>
</div>

<script>
    // Hero Slider
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slider-dot');

    function showSlide(n) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        if (n >= slides.length) currentSlide = 0;
        if (n < 0) currentSlide = slides.length - 1;
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function changeSlide(n) {
        currentSlide += n;
        showSlide(currentSlide);
    }

    function goToSlide(n) {
        currentSlide = n;
        showSlide(currentSlide);
    }
    setInterval(() => {
        currentSlide++;
        showSlide(currentSlide);
    }, 5000);

    // Product Slider
    function slideProducts(sliderId, direction) {
        const slider = document.getElementById(sliderId);
        const slideWidth = 305;
        slider.scrollLeft += slideWidth * direction;
    }

    // Filter Logic
    function filterTopSelling(category) {
        const products = document.querySelectorAll('#topSellingGrid .product-card');
        const buttons = document.querySelectorAll('.section-controls .filter-btn');
        const emptyState = document.getElementById('emptyState');

        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        let visibleCount = 0;

        products.forEach(product => {
            if (category === 'all' || product.dataset.category === category) {
                product.style.display = 'block';
                visibleCount++;
            } else {
                product.style.display = 'none';
            }
        });

        // Show/hide empty state based on visible products
        if (visibleCount === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    }
</script>

<!-- Calendar -->
<h3 class="calendar-title">This Month’s Events</h3>
<div class="cal">
    <h3>Mon</h3>
    <h3>Tue</h3>
    <h3>Wed</h3>
    <h3>Thu</h3>
    <h3>Fri</h3>
    <h3>Sat</h3>
    <h3>Sun</h3>

    <?php
    for ($d = clone $a; $d <= $b; $d->modify('+1 day')) {
        $date = $d->format('Y-m-d');
        $x = $d->format('n') != $month ? 'x' : '';
        echo "<div class='day $x' data-date='$date'>";
        echo "<b>{$d->format('d')}</b>";

        foreach ($data[$date] ?? [] as $e) {
            $eventData = htmlspecialchars(json_encode([
                'id' => $e->id,
                'name' => $e->name,
                'description' => $e->description,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'is_promo' => $e->is_promo,
                'products' => $e->products ?? [],
            ]), ENT_QUOTES);

            echo "<div class='event' data-event='$eventData'>"
                . htmlspecialchars($e->name)
                . "</div>";
        }


        echo "</div>";
    }
    ?>
</div>


<!-- Event modal -->
<div id="eventModal" class="modal">
    <div class="box">
        <h4 id="modalTitle"></h4>
        <p id="modalTime"></p>
        <p id="modalDesc"></p>
        <div id="modalProducts"></div>
        <button type="button" onclick="closeModal()" class="btn-save">Close</button>
    </div>
</div>


<style>
    .calendar-compact {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        font-size: 14px;
        margin-top: 20px;
    }

    .day {
        background: #f9f9f9;
        padding: 4px;
        min-height: 80px;
        border-radius: 4px;
    }

    .day.other-month {
        background: #f0f0f0;
        color: #aaa;
    }

    .day .date {
        font-weight: bold;
        margin-bottom: 4px;
    }

    .event {
        background: #36b9cc;
        color: white;
        padding: 3px 6px;
        border-radius: 4px;
        margin-bottom: 2px;
        cursor: pointer;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event.promo {
        background: #f39c12 !important;
    }

    .calendar-title {
        margin: 0 80px 0px;
        font-size: 22px;
        color: #4e73df;
    }


    .cal {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px;
        margin: 0 80px 40px 80px;
    }

    .cal h3 {
        background: #4e73df;
        color: #fff;
        text-align: center;
        padding: 6px;
    }

    .day {
        background: #fff;
        padding: 6px;
        min-height: 90px;
        cursor: default;
        border-radius: 6px;
    }

    .day.x {
        background: #f0f0f0;
        color: #999;
    }

    .event {
        background: #36b9cc;
        color: #fff;
        font-size: 12px;
        padding: 2px 5px;
        margin-top: 3px;
        border-radius: 4px;
    }

    .event.promo {
        background: #f39c12;
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        justify-content: center;
        align-items: center;
    }

    .box {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        width: 480px;
        max-width: 90%;
    }

    .btn-add {
        background: #4e73df;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 4px 8px;
        text-decoration: none;
        display: inline-block;
        margin: 2px 0;
    }
</style>

<script>
    const modal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalTime = document.getElementById('modalTime');
    const modalDesc = document.getElementById('modalDesc');
    const modalProducts = document.getElementById('modalProducts');

    document.querySelectorAll('.event').forEach(ev => {
        ev.addEventListener('click', () => {
            const data = JSON.parse(ev.dataset.event);

            modalTitle.textContent = data.name;
            modalTime.textContent = "Time: " + data.event_time;
            modalDesc.textContent = data.description;

            modalProducts.innerHTML = '';
            if (data.is_promo && data.products.length > 0) {
                data.products.forEach(p => {
                    modalProducts.insertAdjacentHTML('beforeend',
                        `<a href="/product/detail.php?id=${p.productID}" class="btn-add" target="_blank">${p.name}</a><br>`
                    );
                });
            }

            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
    }

    window.onclick = e => {
        if (e.target == modal) closeModal();
    };
</script>

<?php include 'footer.php'; ?>