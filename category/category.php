<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin");

// --------------------------------------------------------------------
// Sorting
$sort = req('sort', 'categoryID');     // default column
$dir  = req('dir', 'asc');     // default order
$dir  = $dir == 'desc' ? 'desc' : 'asc'; // safety

$allowedSort = ['categoryID', 'categoryName', 'description'];
if (!in_array($sort, $allowedSort)) {
    $sort = 'categoryID';
}

// --------------------------------------------------------------------
// Fetch categories
$stm = $_db->prepare("SELECT * FROM productCategory ORDER BY $sort $dir");
$stm->execute();
$data = $stm->fetchAll();

// --------------------------------------------------------------------
$_title = "Category | List";
include '../navbar.php';
?>

<style>
table {
    width: 90%;
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

th a {
    text-decoration: none;
    color: #333;
}

th a.asc::after {
    content: " ▲";
}

th a.desc::after {
    content: " ▼";
}

tr:hover {
    background: #fafafa;
}

.action-btns a {
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
}

.edit-btn {
    background: #007bff;
    color: #fff;
}

.delete-btn {
    background: #dc3545;
    color: #fff;
}
</style>

<h2 style="text-align:center; margin-top:30px;">Category List</h2>

<div style="width:90%; margin: 20px auto; text-align:right;">
    <a href="categoryDetail.php" 
       style="display:inline-block; padding:10px 20px; background:#28a745; color:white; border-radius:6px; text-decoration:none; font-weight:600;">
       + Add Category
    </a>
</div>

<table>
    <thead>
        <tr>
            <?php
            table_headers(
                [
                    'id'          => 'ID',
                    'name'        => 'Name',
                    'description' => 'Description',
                ],
                $sort,
                $dir
            );
            ?>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php if (!$data): ?>
            <tr>
                <td colspan="4" style="text-align:center; padding:20px; color:#888;">
                    No categories found.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= $row->categoryID ?></td>
                    <td><?= htmlspecialchars($row->categoryName) ?></td>
                    <td><?= htmlspecialchars($row->description) ?></td>
                    <td class="action-btns">
                        <a class="edit-btn" href="categoryDetail.php?categoryID=<?= $row->categoryID ?>">Edit</a>
                        <a class="delete-btn" href="deleteCategory.php?categoryID=<?= $row->categoryID ?>"
                           onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>

<?php include '../footer.php';
