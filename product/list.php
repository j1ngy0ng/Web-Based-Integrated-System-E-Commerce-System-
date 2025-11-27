<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $id   = req('id');
    $unit = req('unit');
    update_cart($id, $unit);
    redirect();
}

// Get categories for filter
$categoryStm = $_db->query('SELECT * FROM ProductCategory ORDER BY categoryName');
$categories = $categoryStm->fetchAll();

// Get current category filter
$currentCategory = req('category') ?? '';

// Build query with optional category filter
$whereClause = '';
$params = [];

if ($currentCategory) {
    $whereClause = 'WHERE p.categoryID = ?';
    $params[] = $currentCategory;
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

// ----------------------------------------------------------------------------

$_title = 'Product | List';
include '../navbar.php';
?>

<div class="container">
    <div class="category-filter-section">
        <div class="filter-header" id="filterToggle">
            <div class="filter-title">
                <h2>Filter by Category</h2>
                <span class="toggle-arrow">▼</span>
            </div>
            <?php if ($currentCategory): ?>
                <a href="list.php" class="clear-filter-btn">
                    Clear Filter
                </a>
            <?php endif; ?>
        </div>
        
        <div class="filter-content" id="filterContent">
            <div class="category-filter-container">
                <div class="category-filter-grid">
                    <div class="category-card <?= !$currentCategory ? 'active' : '' ?>">
                        <a href="list.php" class="category-link">
                            <div class="category-icon">📦</div>
                            <span class="category-name">All Products</span>
                            <span class="product-count"><?= count($arr) ?></span>
                        </a>
                    </div>
                    
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        // Count products in this category
                        $countStm = $_db->prepare('SELECT COUNT(*) FROM product WHERE categoryID = ?');
                        $countStm->execute([$cat->categoryID]);
                        $productCount = $countStm->fetchColumn();
                        ?>
                        <div class="category-card <?= $currentCategory == $cat->categoryID ? 'active' : '' ?>">
                            <a href="list.php?category=<?= $cat->categoryID ?>" class="category-link">
                                <div class="category-icon">
                                    <?php 
                                    // Simple icon mapping based on category name
                                    $icons = [
                                        'Skincare' => '✨',
                                        'Hair Care' => '💇',
                                        'Body Care' => '🧴',
                                        'Health Supplements' => '💊',
                                        'Medical Devices' => '🏥',
                                        'Personal Hygiene' => '🧼',
                                        'Fragrances' => '🌸',
                                        'Makeup' => '💄',
                                        'Men\'s Grooming' => '🧔',
                                        'Wellness Essentials' => '🌿'
                                    ];
                                    echo $icons[$cat->categoryName] ?? '📁';
                                    ?>
                                </div>
                                <span class="category-name"><?= htmlspecialchars($cat->categoryName) ?></span>
                                <span class="product-count"><?= $productCount ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($currentCategory): ?>
                <?php 
                $currentCatName = '';
                foreach ($categories as $cat) {
                    if ($cat->categoryID == $currentCategory) {
                        $currentCatName = $cat->categoryName;
                        break;
                    }
                }
                ?>
                <div class="active-filter-indicator">
                    📍 Currently viewing: <strong><?= htmlspecialchars($currentCatName) ?></strong>
                    <span class="filtered-count">(<?= count($arr) ?> product<?= count($arr) !== 1 ? 's' : '' ?>)</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="products">
        <?php foreach ($arr as $p): ?>
            <?php
            $cart = get_cart();
            $id   = $p->productID;
            $unit = $cart[$p->productID] ?? 0;

            $max_units = min($p->stock, 10);
            for ($i = 1; $i <= $max_units; $i++) {
                $stock_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
            }
            
            if ($p->stock == 0) {
                $stock_options = ['' => 'Out of Stock'];
            }

            $main_photo = get_main_photo($p->productID);
            ?>
            <div class="product">
                <div class="product-image">
                    <?php if (!empty($main_photo)): ?>
                        <img src="/products/<?= $main_photo ?>" 
                            data-get="/product/detail.php?id=<?= $p->productID ?>" 
                            alt="<?= $p->name ?>">
                    <?php else: ?>
                        <div class="photo-placeholder">No Image</div>
                    <?php endif; ?>
                    <div class="product-badge">Hot</div>
                </div>
                <div class="product-info">
                    <div class="product-name"><?= $p->name ?></div>
                    <div class="product-price">RM <?= number_format($p->price, 2) ?></div>
                    <div class="product-stock <?= $p->stock > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $p->stock > 0 ? 'In Stock' : 'Out of Stock' ?>
                    </div>
                    <form method="post" class="product-form">
                        <?= html_hidden('id', "value='$p->productID'") ?>
                        <?= html_select('unit', $stock_options, '') ?>
                        <?php if ($unit): ?>
                            <span class="cart-indicator">✅ <?= $unit ?> in cart</span>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>

<script>
    $('select').on('change', e => e.target.form.submit());
</script>

<?php
include '../footer.php';