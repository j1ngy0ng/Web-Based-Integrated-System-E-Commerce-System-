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

// --------------------------------------------------------------------
// Fetch Products (with category + main photo)
$sql = "
    SELECT
        p.*,
        c.categoryName,
        ph.photoURL AS mainPhoto
    FROM product p
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    LEFT JOIN productPhoto ph ON p.productID = ph.productID AND ph.is_main = 1
    ORDER BY $sort $dir
";

$stm = $_db->prepare($sql);
$stm->execute();
$data = $stm->fetchAll();

// --------------------------------------------------------------------
$_title = "Product | List";
include '../navbar.php';
?>

<style>
table {
    width: 95%;
    margin: 30px auto;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

th, td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    background: #f4f6f9;
    font-weight: 700;
}

th a { text-decoration: none; color: #333; }
th a.asc::after { content: " ▲"; }
th a.desc::after { content: " ▼"; }

tr:hover { background: #fafafa; }

/* Styles for the delete form button */
.delete-form {
    display: inline-block; /* Allows buttons to stay on one line */
    margin: 0;
    padding: 0;
}
.delete-form button {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    text-decoration: none;
    background: #dc3545; /* Red delete button */
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}
.delete-form button:hover {
    background: #c82333; /* Darker red on hover */
}

/* Original action buttons */
.action-btns a {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-block; /* Ensure it respects margin/padding */
    margin-right: 5px; /* Add some space between edit and delete */
}

.edit-btn { background: #007bff; color: #fff; }
.edit-btn:hover { background: #0056b3; } /* Darker blue on hover */

.product-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}
</style>

<h2 style="text-align:center; margin-top:30px;">Product List</h2>

<div style="width:95%; margin: 20px auto; text-align:right;">
    <a href="detail_admin.php"
        style="padding:10px 20px; background:#28a745; color:white; border-radius:6px; text-decoration:none; font-weight:600;">
        + Add Product
    </a>
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
                $sort,
                $dir
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
                    No products found.
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