<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// 1. AUTHENTICATION
// ----------------------------------------------------------------------------
auth('Member');

// ----------------------------------------------------------------------------
// 2. HANDLE ACTIONS (Remove)
// ----------------------------------------------------------------------------
if (is_post()) {
    $action = req('action');
    $pid    = req('productID');

    // Handle "Remove Favorite" locally on this page
    if ($action == 'remove' && $pid) {
        $stm = $_db->prepare("DELETE FROM favorite WHERE userID = ? AND productID = ?");
        $stm->execute([$_user->id, $pid]);
        temp('info', 'Item removed from favorites.');
        redirect();
    }
}

// ----------------------------------------------------------------------------
// 3. FETCH FAVORITES
// ----------------------------------------------------------------------------
$stm = $_db->prepare("
    SELECT p.*, c.categoryName, ph.photoURL
    FROM favorite f
    JOIN product p ON f.productID = p.productID
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    LEFT JOIN productPhoto ph ON p.productID = ph.productID AND ph.is_main = 1
    WHERE f.userID = ?
    ORDER BY f.created_at DESC
");
$stm->execute([$_user->id]);
$favorites = $stm->fetchAll();

$_title = 'My Favorites';
include '../navbar.php';
?>

<style>
    /* ... (Keep your existing styles) ... */
    .page-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; min-height: 70vh; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .page-header { text-align: center; margin-bottom: 40px; }
    .page-header h1 { color: #2d3748; font-size: 2.5em; margin-bottom: 10px; background: linear-gradient(135deg, #dc3545, #ff6b6b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .page-header p { color: #718096; font-size: 1.1em; }
    
    .fav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
    .fav-card { background: white; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid #edf2f7; position: relative; display: flex; flex-direction: column; }
    .fav-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-color: #cbd5e0; }

    /* Disabled State */
    .fav-card.disabled-card { opacity: 0.75; background: #fafafa; }
    .fav-card.disabled-card .fav-img-wrap img { filter: grayscale(100%); opacity: 0.6; }
    .unavailable-badge { background: #e2e8f0; color: #718096; } 

    /* Remove Button */
    .btn-remove-fav { position: absolute; top: 15px; right: 15px; background: rgba(255, 255, 255, 0.9); border: none; width: 35px; height: 35px; border-radius: 50%; color: #dc3545; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: 0.2s; z-index: 2; }
    .btn-remove-fav:hover { background: #dc3545; color: white; transform: scale(1.1); }

    /* Image */
    .fav-img-wrap { height: 220px; width: 100%; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
    .fav-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
    .fav-card:hover .fav-img-wrap img { transform: scale(1.05); }
    .no-image { color: #a0aec0; font-size: 0.9em; }

    /* Content */
    .fav-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
    .fav-category { color: #718096; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .fav-title { font-size: 1.1em; font-weight: 700; color: #2d3748; margin-bottom: 10px; text-decoration: none; line-height: 1.4; }
    .fav-title:hover { color: #006989; }
    .fav-price { font-size: 1.25em; color: #006989; font-weight: 800; margin-bottom: 15px; }

    /* Status Badges */
    .stock-badge { display: inline-block; font-size: 0.8em; padding: 3px 8px; border-radius: 4px; margin-bottom: 15px; font-weight: 600; }
    .in-stock { background: #c6f6d5; color: #22543d; }
    .low-stock { background: #feebc8; color: #744210; }
    .out-stock { background: #fed7d7; color: #742a2a; }

    /* Actions */
    .fav-actions { margin-top: auto; }
    .btn-cart { display: block; width: 100%; text-align: center; background: #006989; color: white; padding: 10px 0; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; }
    .btn-cart:hover { background: #005a73; }
    .btn-disabled { background: #cbd5e0; cursor: not-allowed; display: block; width: 100%; text-align: center; color: white; padding: 10px 0; border-radius: 8px; font-weight: 600; }

    /* Empty State */
    .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .empty-icon { font-size: 4em; color: #cbd5e0; margin-bottom: 20px; }
    .btn-go-shop { display: inline-block; margin-top: 20px; padding: 12px 30px; background: #006989; color: white; text-decoration: none; border-radius: 50px; font-weight: 600; transition: 0.2s; }
    .btn-go-shop:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 105, 137, 0.3); }
</style>

<div class="page-container">
    <div class="page-header">
        <h1>❤️ My Favorites</h1>
        <p>Your curated list of items to buy later</p>
    </div>

    <?php if ($favorites): ?>
        <div class="fav-grid">
            <?php foreach ($favorites as $p): ?>
                
                <?php 
                    // Status Logic
                    $isDisabled = ($p->is_active == 0);
                    $cardClass = $isDisabled ? 'disabled-card' : '';
                ?>

                <div class="fav-card <?= $cardClass ?>">
                    <form method="post" onsubmit="return confirm('Remove this item from favorites?');">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="productID" value="<?= $p->productID ?>">
                        <button type="submit" class="btn-remove-fav" title="Remove">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>

                    <?php if ($isDisabled): ?>
                        <div class="fav-img-wrap">
                    <?php else: ?>
                        <a href="/product/detail.php?id=<?= $p->productID ?>" class="fav-img-wrap">
                    <?php endif; ?>
                    
                        <?php if ($p->photoURL && file_exists("../products/" . $p->photoURL)): ?>
                            <img src="../products/<?= $p->photoURL ?>" alt="<?= htmlspecialchars($p->name) ?>">
                        <?php else: ?>
                            <div class="no-image">No Image</div>
                        <?php endif; ?>
                    
                    <?php if ($isDisabled): ?>
                        </div>
                    <?php else: ?>
                        </a>
                    <?php endif; ?>

                    <div class="fav-content">
                        <div class="fav-category"><?= htmlspecialchars($p->categoryName ?? 'Product') ?></div>
                        
                        <?php if ($isDisabled): ?>
                            <span class="fav-title" style="color: #999;"><?= htmlspecialchars($p->name) ?></span>
                        <?php else: ?>
                            <a href="/product/detail.php?id=<?= $p->productID ?>" class="fav-title">
                                <?= htmlspecialchars($p->name) ?>
                            </a>
                        <?php endif; ?>
                        
                        <div class="fav-price" style="<?= $isDisabled ? 'color:#999;' : '' ?>">RM <?= number_format($p->price, 2) ?></div>

                        <div>
                            <?php if ($isDisabled): ?>
                                <span class="stock-badge unavailable-badge">Unavailable</span>
                            <?php elseif ($p->stock <= 0): ?>
                                <span class="stock-badge out-stock">Out of Stock</span>
                            <?php elseif ($p->stock <= 5): ?>
                                <span class="stock-badge low-stock">Low Stock: <?= $p->stock ?> left</span>
                            <?php else: ?>
                                <span class="stock-badge in-stock">In Stock</span>
                            <?php endif; ?>
                        </div>

                        <div class="fav-actions">
                            <?php if ($isDisabled): ?>
                                <span class="btn-disabled">Item Unavailable</span>
                            <?php elseif ($p->stock <= 0): ?>
                                <span class="btn-disabled">Out of Stock</span>
                            <?php else: ?>
                                <form method="post" action="/order/cart.php">
                                    <input type="hidden" name="btn" value="update"> 
                                    
                                    <input type="hidden" name="id" value="<?= $p->productID ?>">
                                    <input type="hidden" name="unit" value="1">
                                    <button type="submit" class="btn-cart">
                                        Add to Cart 🛒
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa fa-heart-o"></i></div>
            <h3 style="color:#4a5568;">Your wishlist is empty</h3>
            <p style="color:#718096;">Looks like you haven't added any products to your favorites yet.</p>
            <a href="/product/list.php" class="btn-go-shop">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>