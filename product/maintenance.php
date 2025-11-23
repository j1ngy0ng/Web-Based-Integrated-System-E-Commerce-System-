<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin");

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
$search = req('search'); // Get search term from URL (e.g., ?search=phone)
$search_sql = "";
$params = [];

// If a search term exists, build the WHERE clause
if ($search) {
    // We search across Product ID, Product Name, and Category Name
    $search_sql = "WHERE p.productID LIKE ? OR p.name LIKE ? OR c.categoryName LIKE ?";
    // Add the search pattern (with wildcards %) to the parameters array three times
    $pattern = "%$search%";
    $params = [$pattern, $pattern, $pattern];
}
// ====================================================================


// --------------------------------------------------------------------
// Fetch Products (with category + main photo)
// We inject the $search_sql clause into the query
$sql = "
    SELECT
        p.*,
        c.categoryName,
        ph.photoURL AS mainPhoto
    FROM product p
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    LEFT JOIN productPhoto ph ON p.productID = ph.productID AND ph.is_main = 1
    $search_sql
    ORDER BY $sort $dir
";

$stm = $_db->prepare($sql);
// Pass the params array to execute if searching, otherwise pass nothing
$stm->execute($params);
$data = $stm->fetchAll();

// --------------------------------------------------------------------
$_title = "Product | List";
include '../navbar.php';
?>

<style>
/* Your existing table and action button styles */
table { width: 95%; margin: 30px auto; border-collapse: collapse; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
th, td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; }
th { background: #f4f6f9; font-weight: 700; }
th a { text-decoration: none; color: #333; }
th a.asc::after { content: " ▲"; }
th a.desc::after { content: " ▼"; }
tr:hover { background: #fafafa; }
.delete-form { display: inline-block; margin: 0; padding: 0; }
.delete-form button { padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; text-decoration: none; background: #dc3545; color: #fff; border: none; cursor: pointer; transition: background 0.2s; }
.delete-form button:hover { background: #c82333; }
.action-btns a { padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; text-decoration: none; display: inline-block; margin-right: 5px; }
.edit-btn { background: #007bff; color: #fff; }
.edit-btn:hover { background: #0056b3; }
.product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }


/* NEW / MODIFIED STYLES FOR SEARCH AND ADD BUTTON */
.table-controls-row {
    width: 95%;
    margin: 20px auto 15px auto; /* Margin above table */
    display: flex;
    justify-content: space-between; /* Pushes search to left, add to right */
    align-items: center;
    flex-wrap: wrap; /* Allow wrapping on smaller screens */
    gap: 15px; /* Space between elements if they wrap */
}

.search-form-flex {
    flex-grow: 1; /* Allows search form to take available space */
    max-width: 600px; /* Max width for the search input to not be excessively long */
    position: relative; /* For icon/clear button positioning */
    display: flex; /* To align input and clear button within the form */
    gap: 5px; /* Small gap between input and clear button */
}

.search-form-flex .search-input {
    flex-grow: 1; /* Input takes all available space within its container */
    padding: 12px 20px 12px 45px; /* Left padding for icon */
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-size: 1rem;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.search-form-flex .search-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    outline: none;
}

/* Icon for the search bar */
.search-form-flex .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
    font-size: 1.1rem;
    pointer-events: none;
}

/* Hide the actual search button, as pressing Enter in input submits the form */
.search-form-flex .search-btn {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap; /* Keep button on one line */
    transition: background 0.2s;
}

.search-form-flex .search-btn:hover {
    background: #0056b3;
}

/* Clear button styling and positioning */
.search-form-flex .clear-btn {
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap; /* Keep button on one line */
    transition: background 0.2s;
    /* Removed absolute positioning, now flex item */
}
.search-form-flex .clear-btn:hover {
    background: #5a6268;
}

/* Style for the Add Product Button */
.add-product-btn {
    padding: 10px 20px;
    background:#28a745;
    color:white;
    border-radius:6px;
    text-decoration:none;
    font-weight:600;
    white-space: nowrap; /* Prevent button text from wrapping */
    transition: background 0.2s;
}
.add-product-btn:hover {
    background:#218838;
}
</style>

<h2 style="text-align:center; margin-top:30px;">Product List</h2>

<div class="table-controls-row">
    <form method="get" class="search-form-flex">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

        <i class="fa fa-search search-icon" aria-hidden="true"></i>
        <input type="text" name="search" class="search-input"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Search products by ID, Name, or Category...">
        <button type="submit" class="search-btn">Search</button> <?php if ($search): /* Clear button is now part of the form, next to input */ ?>
            <a href="maintenance.php?sort=<?= htmlspecialchars($sort) ?>&dir=<?= htmlspecialchars($dir) ?>" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>

    <a href="detail_admin.php" class="add-product-btn">
        + Add Product
    </a>
</div>

<table>
    <thead>
        <tr>
            <?php
            // Update headers to include search param in links so sorting doesn't clear search
            $extraParams = $search ? "&search=" . urlencode($search) : "";
            table_headers(
                [
                    'productID'   => 'ID',
                    'categoryName'=> 'Category',
                    'name'        => 'Product Name',
                    'price'       => 'Price (RM)',
                    'stock'       => 'Stock',
                    'rating_avg'  => 'Rating',
                ],
                $sort,
                $dir,
                $extraParams
            );
            ?>
            <th>Main Photo</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php if (!$data): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px; color:#888;">
                    <?php if ($search): ?>
                        No products found matching "<strong><?= htmlspecialchars($search) ?></strong>".
                    <?php else: ?>
                        No products found.
                    <?php endif; ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $p): ?>
                <tr>
                    <td><?= $p->productID ?></td>
                    <td><?= htmlspecialchars($p->categoryName) ?></td>
                    <td><?= htmlspecialchars($p->name) ?></td>
                    <td>RM <?= number_format($p->price, 2) ?></td>
                    <td><?= $p->stock ?></td>
                    <td><?= $p->rating_avg ?> ⭐</td>

                    <td>
                        <?php if ($p->mainPhoto): ?>
                            <img src="../products/<?= $p->mainPhoto ?>" class="product-img">
                        <?php else: ?>
                            <span style="color:#888;">No Photo</span>
                        <?php endif; ?>
                    </td>

                    <td class="action-btns">
                        <a class="edit-btn" href="detail_admin.php?productID=<?= $p->productID ?>">Edit</a>

                        <form class="delete-form" action="detail_admin.php" method="post"
                              onsubmit="return confirm('WARNING: Are you sure you want to PERMANENTLY delete <?= htmlspecialchars($p->name) ?> and ALL its associated photos? This action cannot be undone.');">
                            <input type="hidden" name="productID" value="<?= $p->productID ?>">
                            <input type="hidden" name="action_type" value="delete">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>

<?php include '../footer.php'; ?>