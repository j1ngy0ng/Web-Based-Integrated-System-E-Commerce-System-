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
$searchQuery = req('search') ?? '';

$allProductsStm = $_db->query('SELECT COUNT(*) FROM product');
$allProductsCount = $allProductsStm->fetchColumn();

// Build query with optional category filter
$params = [];
$conditions = [];

if ($searchQuery) {
    $conditions[] = 'p.name LIKE ?';
    $params[] = "%$searchQuery%";
}

if ($currentCategory) {
    $conditions[] = 'p.categoryID = ?';
    $params[] = $currentCategory;
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
// ----------------------------------------------------------------------------

$_title = 'Product | List';
include '../navbar.php';
?>

<style>
.product.out-of-stock {
    opacity: 0.6;
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.product.out-of-stock .product-image img {
    filter: grayscale(100%);
}

.product.out-of-stock .product-image {
    pointer-events: none;
}

.stock-alert {
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 5px;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
}

.stock-alert.in-stock {
    color: #27ae60;
    background-color: #e8f8f0;
}

.stock-alert.low-stock {
    color: #f39c12;
    background-color: #fef5e7;
}

.stock-alert.out-of-stock {
    color: #95a5a6;
    background-color: #ecf0f1;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    margin: 40px 0;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    width: 100%;
}

.empty-state-icon {
    font-size: 5rem;
    margin-bottom: 25px;
}

.empty-state h2 {
    color: #495057;
    margin-bottom: 15px;
    font-weight: 600;
}

.empty-state-message {
    color: #6c757d;
    font-size: 1.2rem;
    margin-bottom: 30px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.empty-state-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-primary {
    background: #006989;
    color: white;
}

.btn-primary:hover {
    background: #005a73;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}
</style>

<div class="container">
    <?php 
        // Initialize variables BEFORE the loop
        $hasProducts = !empty($arr);
        $currentCategoryName = '';
        
        // Get current category name if a category is selected
        if ($currentCategory && isset($categories)) {
            foreach ($categories as $cat) {
                if ($cat->categoryID == $currentCategory) {
                    $currentCategoryName = $cat->categoryName;
                    break;
                }
            }
        }
        
        // First, check if there are products to display
        if ($hasProducts): 
        ?>
        <div id="products">
        
            <?php foreach ($arr as $p): ?>
                <?php
                $cart = get_cart();
                $id   = $p->productID;
                $unit = $cart[$p->productID] ?? 0;

                $stock_options = ['' => 'Select quantity'];
                $max_units = min($p->stock, 10);
                
                // Only generate options if stock is available
                if ($p->stock > 0) {
                    for ($i = 1; $i <= $max_units; $i++) {
                        $stock_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
                    }
                } else {
                    $stock_options = ['' => 'Out of Stock'];
                }

                $main_photo = get_main_photo($p->productID);
                
                // Determine stock status
                $stock_class = '';
                $stock_alert_class = '';
                $stock_message = '';
                
                if ($p->stock == 0) {
                    $stock_class = 'out-of-stock';
                    $stock_alert_class = 'out-of-stock';
                    $stock_message = 'Out of Stock';
                } elseif ($p->stock <= 5) {
                    $stock_alert_class = 'low-stock';
                    $stock_message = 'Only ' . $p->stock . ' left!';
                } else {
                    $stock_alert_class = 'in-stock';
                    $stock_message = 'In Stock';
                }
                ?>
                <div class="product <?= $stock_class ?>">
                    <div class="product-image">
                        <?php if (!empty($main_photo)): ?>
                            <img src="/products/<?= $main_photo ?>" 
                                <?php if ($p->stock > 0): ?>
                                    data-get="/product/detail.php?id=<?= $p->productID ?>"
                                <?php endif; ?>
                                alt="<?= $p->name ?>">
                        <?php else: ?>
                            <div class="photo-placeholder">No Image</div>
                        <?php endif; ?>
                        <?php if ($p->stock > 0): ?>
                            <div class="product-badge">Hot</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?= $p->name ?></div>
                        <div class="product-price">RM <?= number_format($p->price, 2) ?></div>
                        <div class="stock-alert <?= $stock_alert_class ?>">
                            <?= $stock_message ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <!-- NO PRODUCTS FOUND - EMPTY STATE -->
        <div class="empty-state">
            <div class="empty-state-icon">😔</div>
            <h2>No Products Available</h2>
            <p class="empty-state-message">
                <?php if ($currentCategoryName): ?>
                    We couldn't find any products in the <strong>"<?= htmlspecialchars($currentCategoryName) ?>"</strong> category.
                <?php elseif ($searchQuery): ?>
                    No products found for "<strong><?= htmlspecialchars($searchQuery) ?></strong>".
                <?php else: ?>
                    There are currently no products available.
                <?php endif; ?>
            </p>
            <div class="empty-state-actions">
                <a href="/product/list.php" class="btn btn-primary">
                    Browse All Products
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
include '../footer.php';
?>