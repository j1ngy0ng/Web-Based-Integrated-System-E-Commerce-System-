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

$allProductsStm = $_db->query('SELECT COUNT(*) FROM product');
$allProductsCount = $allProductsStm->fetchColumn();

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

<style>
    /* Category Filter Header Styles */
.category-filter-section {
    background-color: #006989;
    border-radius: 0 0 30px 30px;
    margin-bottom: 30px;
    color: white;
    overflow: hidden;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.filter-header:hover {
    background: rgba(255, 255, 255, 0.1);
}

.filter-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filter-title h4 {
    margin: 0;
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
}

.toggle-arrow {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
    color: rgba(255, 255, 255, 0.8);
}

.category-filter-section.expanded .toggle-arrow {
    transform: rotate(180deg);
}

.clear-filter-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.clear-filter-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.filter-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease;
    padding: 0 20px;
}

.category-filter-section.expanded .filter-content {
    max-height: 500px; /* Adjust based on your content */
}

.category-filter-container {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px 0 20px 0;
}

/* Custom scrollbar for category filter */
.category-filter-container::-webkit-scrollbar {
    width: 6px;
}

.category-filter-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.category-filter-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.category-filter-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.category-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 15px;
    margin-bottom: 5px;
}

.category-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 10px 5px;
    text-align: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 2px solid transparent;
}

.category-card:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-3px);
    border-color: rgba(255, 255, 255, 0.3);
}

.category-card.active {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.category-link {
    text-decoration: none;
    color: white;
    display: block;
}

.category-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.category-name {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.product-count {
    display: block;
    font-size: 0.8rem;
    opacity: 0.8;
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 10px;
    margin-top: 5px;
}

.active-filter-indicator {
    background: rgba(255, 255, 255, 0.15);
    padding: 15px 20px;
    border-radius: 10px;
    text-align: center;
    backdrop-filter: blur(10px);
    border-left: 4px solid #ffd700;
    margin-bottom: 20px;
}

.filtered-count {
    opacity: 0.9;
    font-size: 0.9rem;
}

/* No Products State */
.no-products {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 15px;
    margin: 30px 0;
}

.no-products-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.no-products h3 {
    color: #6c757d;
    margin-bottom: 10px;
}

.no-products p {
    color: #868e96;
    margin-bottom: 20px;
}

</style>

<div class="container">
    <div class="category-filter-section">
        <div class="filter-header" id="filterToggle">
            <div class="filter-title">
                <h4>Filter by Category</h4>
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
                            <span class="product-count"><?= $allProductsCount ?></span>
                        </a>
                    </div>
                    
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        // Count products in this category (pre-calculated)
                        $countStm = $_db->prepare('SELECT COUNT(*) FROM product WHERE categoryID = ?');
                        $countStm->execute([$cat->categoryID]);
                        $productCount = $countStm->fetchColumn();
                        ?>
                        <div class="category-card <?= $currentCategory == $cat->categoryID ? 'active' : '' ?>">
                            <a href="list.php?category=<?= $cat->categoryID ?>" class="category-link">
                                <div class="category-icon">
                                    <?php 
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
                        <?= html_select('unit', $stock_options, $unit ? $unit : '') ?>
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
    // Category filter toggle functionality
    const filterToggle = document.getElementById('filterToggle');
    const filterContent = document.getElementById('filterContent');
    const filterSection = document.querySelector('.category-filter-section');

    if (filterToggle && filterContent) {
        filterToggle.addEventListener('click', function(e) {
            // Don't toggle if clicking on the clear filter link
            if (e.target.closest('.clear-filter-btn')) {
                return;
            }
            filterSection.classList.toggle('expanded');
        });
        
        // Keep expanded state after page reload if it was expanded before
        const wasExpanded = sessionStorage.getItem('filterExpanded');
        if (wasExpanded === 'true') {
            filterSection.classList.add('expanded');
        }
        
        // Save state before page unload
        filterSection.addEventListener('click', function() {
            sessionStorage.setItem('filterExpanded', filterSection.classList.contains('expanded'));
        });
    }
    $('select').on('change', e => e.target.form.submit());
</script>

<?php
include '../footer.php';