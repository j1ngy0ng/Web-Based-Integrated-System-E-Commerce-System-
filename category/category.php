<?php
include '../_base.php';
auth("Admin");

// Sorting
$sort = req('sort', 'categoryID');
$dir  = req('dir', 'asc');
$dir  = $dir == 'desc' ? 'desc' : 'asc';
$allowedSort = ['categoryID', 'categoryName', 'description'];
if (!in_array($sort, $allowedSort)) $sort = 'categoryID';

// Fetch categories
$stm = $_db->prepare("SELECT * FROM productCategory ORDER BY $sort $dir");
$stm->execute();
$data = $stm->fetchAll();

$_title = "Category | List";
include '../navbar.php';
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
    justify-content: flex-end; /* Align all buttons to the right */
    gap: 10px;                 /* Space between buttons */
    margin-bottom: 20px;
    flex-wrap: wrap;           /* Wrap on small screens */
}

.btn {
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
}

.btn-success { background: #28a745; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }


.btn-success { background: #28a745; color: #fff; }
.btn-primary { background: #007BFF; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
</style>

<h2 style="text-align:center; margin-top:30px;">Category List</h2>

<!-- Form wrapping everything -->
<form method="post" action="category_delete_batch.php" id="batchDeleteForm">

<div style="width:90%; margin: 20px auto; text-align:right;" class="actions">
    <a href="categoryDetail.php" class="btn btn-success">+ Add Category</a>
    <a href="category_batch.php" class="btn btn-secondary">⬆ Batch Insertion</a>
    <a href="category_update_batch.php" class="btn btn-secondary">Batch Update</a>
    <button type="submit" class="btn btn-danger"
            onclick="return confirm('Are you sure you want to delete selected categories?');">
        Delete Selected
    </button>
</div>

<table>
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$data): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:20px; color:#888;">No categories found.</td>
            </tr>
        <?php else: ?>
            <?php $rowNum = 1; ?>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><input type="checkbox" name="deleteIDs[]" value="<?= $row->categoryID ?>"></td>
                <td><?= $rowNum ?></td>
                <td><?= htmlspecialchars($row->categoryName) ?></td>
                <td><?= htmlspecialchars($row->description) ?></td>
                <td class="action-btns">
                    <a class="edit-btn" href="categoryDetail.php?categoryID=<?= $row->categoryID ?>">Edit</a>
                    <a class="delete-btn" href="deleteCategory.php?categoryID=<?= $row->categoryID ?>"
                       onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php $rowNum++; endforeach; ?>
        <?php endif ?>
    </tbody>
</table>

</form>

<script>
// Select All checkbox functionality
document.getElementById('selectAll').addEventListener('change', function() {
    let checkboxes = document.querySelectorAll('input[name="deleteIDs[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php include '../footer.php'; ?>
