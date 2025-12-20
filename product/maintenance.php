<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin","Superadmin");

// ====================================================================
// ENABLE/DISABLE LOGIC HERE
// ====================================================================
if (is_post()) {
    $action = req('action_type');
    $pid    = req('productID');

    if ($action === 'toggle_status' && $pid) {
        // Toggle the is_active status (0 -> 1, or 1 -> 0)
        $stm = $_db->prepare("UPDATE product SET is_active = NOT is_active WHERE productID = ?");
        $stm->execute([$pid]);
        
        temp('info', 'Product status updated successfully.');
        redirect('maintenance.php'); // Reload page
    }
}
// ====================================================================

// --------------------------------------------------------------------
// Sorting
$sort = req('sort', 'productID');
$dir  = req('dir', 'asc');
$dir  = ($dir === 'desc') ? 'desc' : 'asc';

$allowedSort = ['productID', 'name', 'price', 'stock', 'rating_avg', 'categoryName'];
if (!in_array($sort, $allowedSort)) {
    $sort = 'productID';
}

// ====================================================================
// SEARCH LOGIC
// ====================================================================
$search = req('search');
$search_sql = "";
$params = [];

if ($search) {
    $search_sql = "WHERE p.productID LIKE ? OR p.name LIKE ? OR c.categoryName LIKE ?";
    $pattern = "%$search%";
    $params = [$pattern, $pattern, $pattern];
}

// ====================================================================
// FIX: Initialize $extraParams for table_headers()
// ====================================================================
$extraParams = [];
if ($search) {
    // If a search is active, pass the search term as an extra parameter
    // so it is preserved when sorting is changed.
    $extraParams['search'] = $search;
}
// ====================================================================

// ====================================================================
// PAGINATION
// ====================================================================
$page  = max(1, (int) req('page', 1));
$limit = 10; // products per page

// Count total products (apply SAME search condition)
$countSql = "
    SELECT COUNT(*) FROM (
        SELECT p.productID
        FROM product p
        LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
        LEFT JOIN order_item oi ON p.productID = oi.productID
        LEFT JOIN productRating pr ON oi.order_item_id = pr.order_item_id
        $search_sql
        GROUP BY p.productID
    ) x
";


$stm = $_db->prepare($countSql);
$stm->execute($params);
$totalRows = $stm->fetchColumn();

// Get pagination info
$pagination = paginate($totalRows, $page, $limit);


// --------------------------------------------------------------------
// Fetch Products with Average Rating
$sql = "
    SELECT
        p.*,
        c.categoryName,
        ph.photoURL AS mainPhoto,
        p.model3d, 
        AVG(pr.rating) AS rating_avg
    FROM product p
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    LEFT JOIN productPhoto ph ON p.productID = ph.productID AND ph.is_main = 1
    LEFT JOIN order_item oi ON p.productID = oi.productID
    LEFT JOIN productRating pr ON oi.order_item_id = pr.order_item_id
    $search_sql
    GROUP BY p.productID
    ORDER BY $sort $dir
    LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}
";

$stm = $_db->prepare($sql);
$stm->execute($params);
$data = $stm->fetchAll();

$paginationParams = [
    'sort' => $sort,
    'dir'  => $dir,
];

if ($search) {
    $paginationParams['search'] = $search;
}


$_title = "Product | List";
include '../navbar.php';
?>

<style>
/* Base Font */
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }

table { width: 95%; margin: 30px auto; border-collapse: collapse; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
th, td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; }
th { background: #f4f6f9; font-weight: 700; }
th a { text-decoration: none; color: #333; }
th a.asc::after { content: " ▲"; }
th a.desc::after { content: " ▼"; }
tr:hover { background: #fafafa; }

/* Flex layout for action buttons */
td.action-btns {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

/* --- ACTION BUTTONS --- */
td.action-btns {
    display: flex;
    align-items: center;
    gap: 5px; /* Consistent gap */
    white-space: nowrap;
    height: 100%; /* Ensure it fills the cell height */
    padding: 30px 15px; /* Match standard cell padding */
}

/* Reset form margins completely */
.status-form { 
    margin: 0; 
    padding: 0; 
    display: flex; /* Makes the button inside behave like a flex item */
}

/* Unified Button Style */
.action-btns a, 
.status-form button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    
    /* Strict Height Control */
    height: 34px; 
    min-width: 70px; /* Optional: ensures buttons are roughly same width */
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    font-family: inherit; /* Inherit font from body */
    text-decoration: none;
    border: none;
    cursor: pointer;
    color: #fff;
    line-height: 1; /* Reset line height */
    transition: opacity 0.2s;
    box-sizing: border-box; /* Include padding in height calculation */
}

.action-btns a:hover, 
.status-form button:hover {
    opacity: 0.9;
}

/* Colors */
.edit-btn { background: #007bff; }    /* Blue */
.btn-disable { background: #dc3545; } /* Red */
.btn-enable { background: #28a745; }  /* Green */

/* Media Styles */
.product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
.product-video-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; background: #000; cursor: pointer; }

/* Search Bar */
.table-controls-row { width: 95%; margin: 20px auto 15px auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.search-form-flex { flex-grow: 1; max-width: 600px; position: relative; display: flex; gap: 5px; }
.search-form-flex .search-input { flex-grow: 1; padding: 12px 20px 12px 45px; border: 1px solid #ced4da; border-radius: 8px; font-size: 1rem; font-family: inherit; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); transition: all 0.3s ease; }
.search-form-flex .search-input:focus { border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.1); outline: none; }
.search-form-flex .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-size: 1.1rem; pointer-events: none; }
.search-form-flex .search-btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 6px; text-decoration: none; font-family: inherit; font-weight: 600; white-space: nowrap; transition: background 0.2s; }
.search-form-flex .search-btn:hover { background: #0056b3; }
.search-form-flex .clear-btn { padding: 15px 15px; background: #6c757d; color: white; border: none; border-radius: 6px; text-decoration: none; font-weight: 600; white-space: nowrap; transition: background 0.2s; font-family: inherit; }
.search-form-flex .clear-btn:hover { background: #5a6268; }
.add-product-btn { padding: 10px 20px; background:#28a745; color:white; border-radius:6px; text-decoration:none; font-weight:600; white-space: nowrap; transition: background 0.2s; font-family: inherit; }
.add-product-btn:hover { background:#218838; }

/* Disabled Row */
tr.disabled-row {
    background-color: #f8f9fa;
    color: #adb5bd;
}
tr.disabled-row img, 
tr.disabled-row video {
    filter: grayscale(100%);
    opacity: 0.5;
}
tr.disabled-row .action-btns {
    opacity: 1 !important; 
}

/* Low Stock Warning */
.stock-warning {
    color: #dc3545;
    font-weight: 700;
    font-size: 0.85rem;
    display: block;
    margin-top: 2px;
}
.stock-ok {
    color: #28a745;
    font-weight: 600;
}
</style>

<h2 style="text-align:center; margin-top:30px;">Product List</h2>

<div class="table-controls-row">
    <form method="get" class="search-form-flex">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
        <i class="fa fa-search search-icon" aria-hidden="true"></i>
        <input type="text" name="search" class="search-input" value="<?= htmlspecialchars($search) ?>" placeholder="Search products by ID, Name, or Category...">
        <button type="submit" class="search-btn">Search</button> 
        <?php if ($search): ?>
            <a href="maintenance.php?sort=<?= htmlspecialchars($sort) ?>&dir=<?= htmlspecialchars($dir) ?>" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>
    <a href="detail_admin.php" class="add-product-btn">+ Add Product</a>
</div>

<table>
    <thead>
        <tr>
            <?php
            table_headers(
                [
                    'productID'   => 'ID',
                    'categoryName'=> 'Category',
                    'name'        => 'Product Name',
                    'price'       => 'Price (RM)',
                    'stock'       => 'Stock',
                    'rating_avg'  => 'Rating',
                ],
                $sort, $dir, $extraParams
            );
            ?>
            <th>Main Photo</th>
            <th>Video</th>
            <th>3D Model</th> 
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$data): ?>
            <tr><td colspan="9" style="text-align:center; padding:20px; color:#888;">No products found.</td></tr>
        <?php else: ?>
            <?php foreach ($data as $p): ?>
                
                <?php 
                    $rowClass = $p->is_active == 0 ? 'disabled-row' : '';
                    $lowStockThreshold = 10; 
                ?>

                <tr class="<?= $rowClass ?>">
                    <td><?= $p->productID ?></td>
                    <td><?= htmlspecialchars($p->categoryName) ?></td>
                    <td>
                        <?= htmlspecialchars($p->name) ?>
                        <?php if($p->is_active == 0): ?>
                            <span style="font-size:0.7rem; background:#666; color:white; padding:2px 5px; border-radius:3px; margin-left:5px; vertical-align:middle;">INACTIVE</span>
                        <?php endif; ?>
                    </td>
                    <td>RM <?= number_format($p->price, 2) ?></td>
                    <td>
                        <?= $p->stock ?>
                        <?php if ($p->stock <= $lowStockThreshold): ?>
                            <span class="stock-warning">
                                <i class="fa fa-exclamation-circle"></i> Low Stock
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= $p->rating_avg ? number_format($p->rating_avg, 2) . " ⭐" : '-' ?></td>
                    <td>
                        <?php if ($p->mainPhoto): ?>
                            <img src="../products/<?= $p->mainPhoto ?>" class="product-img">
                        <?php else: ?>
                            <span style="color:#888; font-size:0.8rem;">No Photo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($p->video_url) && file_exists("../products/" . $p->video_url)): ?>
                            <video src="../products/<?= $p->video_url ?>" class="product-video-thumb" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>
                        <?php else: ?>
                            <span style="color:#888; font-size:0.8rem;">No Video</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($p->model3d) && file_exists("../products/" . $p->model3d)): ?>
                            <i class="fa fa-check" style="color: #28a745;"></i> Yes
                        <?php else: ?>
                            <span style="color:#888; font-size:0.8rem;">No</span>
                        <?php endif; ?>
                    </td>

                    <td class="action-btns">
                    <a class="edit-btn" href="detail_admin.php?productID=<?= $p->productID ?>">Edit</a>

                    <form class="status-form" method="post">
                        <input type="hidden" name="productID" value="<?= $p->productID ?>">
                        <input type="hidden" name="action_type" value="toggle_status">
                        
                        <?php if ($p->is_active == 1): ?>
                            <button type="submit" class="btn-disable" onclick="return confirm('Disable this product?');">Disable</button>
                        <?php else: ?>
                            <button type="submit" class="btn-enable" onclick="return confirm('Activate this product?');">Enable</button>
                        <?php endif; ?>
                    </form>
                </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>

<div style="width:95%; margin:25px auto; text-align:center;">
    <?php render_pagination(
        $pagination['page'],
        $pagination['totalPages'],
        $paginationParams
    ); ?>
</div>

<?php include '../footer.php'; ?>