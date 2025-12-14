<?php
include '../_base.php';
auth("Admin");

// --------------------------------------------------------------------
// Search
$q = trim(req('q', ''));
$search = "%$q%";

// --------------------------------------------------------------------
// Sorting
$sort = req('sort', 'categoryID');
$dir  = req('dir', 'asc');
$dir  = $dir == 'desc' ? 'desc' : 'asc';

$allowedSort = ['categoryID', 'categoryName', 'description'];
if (!in_array($sort, $allowedSort)) $sort = 'categoryID';

// --------------------------------------------------------------------
// Pagination (DEFINE FIRST!)
$page  = max(1, intval(req('page', 1)));
$limit = 10;
$offset = ($page - 1) * $limit;

// --------------------------------------------------------------------
// Count total rows
$countSql = "SELECT COUNT(*) FROM productCategory";
$params = [];

if ($q !== '') {
    $countSql .= " WHERE categoryName LIKE ? OR description LIKE ?";
    $params = [$search, $search];
}

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();

$totalPages = ceil($totalRows / $limit);

$sql = "SELECT * FROM productCategory";

if ($q !== '') {
    $sql .= " WHERE categoryName LIKE ? OR description LIKE ?";
}

$sql .= " ORDER BY $sort $dir LIMIT $limit OFFSET $offset";

$stm = $_db->prepare($sql);
$stm->execute($params);
$data = $stm->fetchAll();


// --------------------------------------------------------------------
$_title = "Category | List";
include '../navbar.php';

function sort_link($label, $column, $currentSort, $currentDir, $q, $page = 1) {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

    $class = '';
    if ($currentSort === $column) {
        $class = $currentDir;
    }

    $url = "?sort=$column&dir=$dir&page=$page&q=" . urlencode($q);

    return "<a href=\"$url\" class=\"$class\">$label</a>";
}

?>

<style>
    table {
        width: 90%;
        margin: 30px auto;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    th,
    td {
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

    .actions {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
    }

    .btn {
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
    }

    .btn-primary {
        background: #007BFF;
        color: #fff;
    }

    .btn-secondary {
        background: #6c757d;
        color: #fff;
    }

    .actions {
        display: flex;
        justify-content: flex-end;
        /* Align all buttons to the right */
        gap: 10px;
        /* Space between buttons */
        margin-bottom: 20px;
        flex-wrap: wrap;
        /* Wrap on small screens */
    }

    .btn {
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
    }

    .btn-success {
        background: #28a745;
        color: #fff;
    }

    .btn-secondary {
        background: #6c757d;
        color: #fff;
    }

    .btn-danger {
        background: #dc3545;
        color: #fff;
    }


    .btn-success {
        background: #28a745;
        color: #fff;
    }

    .btn-primary {
        background: #007BFF;
        color: #fff;
    }

    .btn-secondary {
        background: #6c757d;
        color: #fff;
    }

    .btn-danger {
        background: #dc3545;
        color: #fff;
    }

    .top-bar {
        width: 90%;
        margin: 20px auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    /* Search */
    .search-bar {
        display: flex;
        gap: 10px;
    }

    .search-bar input {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        min-width: 220px;
    }

    /* Buttons */
    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    mark {
    background: #ffe066;
    padding: 0 3px;
    border-radius: 3px;
}

</style>

<h2 style="text-align:center; margin-top:30px;">Category List</h2>

<div class="top-bar">

    <!-- SEARCH (GET) -->
    <form method="get" class="search-bar">
        <input type="text" name="q"
            placeholder="Search category..."
            value="<?= htmlspecialchars($q) ?>">

        <input type="hidden" name="sort" value="<?= $sort ?>">
        <input type="hidden" name="dir" value="<?= $dir ?>">

        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- ACTION BUTTONS -->
    <div class="actions">
        <a href="categoryDetail.php" class="btn btn-success">+ Add Category</a>
        <a href="category_batch.php" class="btn btn-secondary">⬆ Batch Insertion</a>
        <a href="category_update_batch.php" class="btn btn-secondary">Batch Update</a>
    </div>

</div>

<form method="post" action="category_delete_batch.php" id="batchDeleteForm">
    <table>
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
<th>ID</th>
<th><?= sort_link('Name', 'categoryName', $sort, $dir, $q, $page) ?></th>
<th><?= sort_link('Description', 'description', $sort, $dir, $q, $page) ?></th>
<th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$data): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:20px; color:#888;">No categories found.</td>
                </tr>
            <?php else: ?>
                <?php $rowNum = ($page - 1) * $limit + 1; ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><input type="checkbox" name="deleteIDs[]" value="<?= $row->categoryID ?>"></td>
                        <td><?= $rowNum ?></td>
<td><?= highlight($row->categoryName, $q) ?></td>
<td><?= highlight($row->description, $q) ?></td>

                        <td class="action-btns">
                            <a class="edit-btn" href="categoryDetail.php?categoryID=<?= $row->categoryID ?>">Edit</a>
                            <a class="delete-btn" href="deleteCategory.php?categoryID=<?= $row->categoryID ?>"
                                onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php $rowNum++;
                endforeach; ?>
            <?php endif ?>
        </tbody>
    </table>
    <div style="width:90%; margin:20px auto; text-align:right;">
        <button type="submit" class="btn btn-danger"
            onclick="return confirm('Are you sure you want to delete selected categories?');">
            Delete Selected
        </button>
    </div>

</form>
<div style="width:90%; margin:20px auto; text-align:center;">

    <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">

            <!-- Prev -->
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&dir=<?= $dir ?>&q=<?= urlencode($q) ?>"
                    class="btn btn-primary">◀ Prev</a>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&sort=<?= $sort ?>&dir=<?= $dir ?>&q=<?= urlencode($q) ?>"
                    class="btn <?= $i == $page ? 'btn-secondary' : 'btn-primary' ?>">
                    <?= $i ?>
                </a>

            <?php endfor; ?>

            <!-- Next -->
            <?php if ($page < $totalPages): ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&dir=<?= $dir ?>&q=<?= urlencode($q) ?>"
                        class="btn btn-primary">Next ▶</a>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    <?php endif; ?>

</div>

<script>
    // Select All checkbox functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('input[name="deleteIDs[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

<?php include '../footer.php'; ?>