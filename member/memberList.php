<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();

// --------------------------------------------------------------------
// Sorting
$sort = req('sort', 'id'); // default column
$dir  = req('dir', 'asc');
$dir  = $dir == 'desc' ? 'desc' : 'asc';

$allowedSort = [
    'id', 'email', 'name', 'role',
    'phone_number', 'phone_verified',
    'is_active', 'failed_attempts', 'lock_until'
];


// --------------------------------------------------------------------
// Fetch members
$q = req('q', '');
if ($q) {
    $stm = $_db->prepare("SELECT * FROM user 
                          WHERE 
                                id LIKE ?
                                OR email LIKE ?
                                OR name LIKE ?
                                OR role LIKE ?
                                OR phone_number LIKE ?
                                OR failed_attempts LIKE ?
                                OR phone_verified LIKE ?
                                OR is_active LIKE ?
                                OR lock_until LIKE ?

                          ORDER BY $sort $dir");

$searchTerm = "%$q%";
$stm->execute([
    $searchTerm, $searchTerm, $searchTerm, $searchTerm,
    $searchTerm, $searchTerm, $searchTerm, $searchTerm,
    $searchTerm
]);
} else {
    $stm = $_db->prepare("SELECT * FROM user ORDER BY $sort $dir");
    $stm->execute();
}

$data = $stm->fetchAll();

// --------------------------------------------------------------------
$_title = "Member | List";
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

<h2 style="text-align:center; margin-top:30px;">Member List</h2>

<div style="width:90%; margin: 20px auto; text-align:right;">
    <form method="get" style="display:flex; justify-content:flex-end; gap:10px;">
        <input type="text" name="q" placeholder="Search by name or email..." 
               value="<?= htmlspecialchars(req('q', '')) ?>" 
               style="padding:6px 12px; border-radius:6px; border:1px solid #ccc;">
        <button type="submit" style="padding:6px 12px; border:none; background:#007bff; color:white; border-radius:6px;">Search</button>
    </form>

    <a href="memberDetail.php" 
       style="display:inline-block; padding:10px 20px; background:#28a745; color:white; border-radius:6px; text-decoration:none; font-weight:600; margin-top:10px;">
       + Add Member
    </a>
</div>

<table>
<thead>
    <tr>
        <?php
        table_headers(
            [
                'id'             => 'ID',
                'email'          => 'Email',
                'name'           => 'Name',
                'role'           => 'Role',
                'phone_number'   => 'Phone',
                'phone_verified' => 'Verified',
                'is_active'      => 'Active',
                'failed_attempts'=> 'Failed Attempts',
                'lock_until'     => 'Locked Until'
            ],
            $sort,
            $dir
        );
        ?>
        <th>Photo</th>
        <th>Action</th>
    </tr>
</thead>

<tbody>
<?php if (!$data): ?>
    <tr>
        <td colspan="12" style="text-align:center; padding:20px; color:#888;">
            No members found.
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($data as $row): ?>
        <tr>
            <td><?= $row->id ?></td>
            <td><?= htmlspecialchars($row->email) ?></td>
            <td><?= htmlspecialchars($row->name) ?></td>
            <td><?= htmlspecialchars($row->role) ?></td>

            <td><?= htmlspecialchars($row->phone_number ?: '-') ?></td>

            <td>
                <?= $row->phone_verified ? '✔ Yes' : '✖ No' ?>
            </td>

            <td>
                <?= $row->is_active ? 'Active' : 'Blocked' ?>
            </td>

            <td><?= $row->failed_attempts ?></td>

            <td><?= $row->lock_until ?: '-' ?></td>

            <td>
                <?php if ($row->photo): ?>
                    <img src="../photos/<?= htmlspecialchars($row->photo) ?>"
                         style="width:50px; height:50px; border-radius:50%;">
                <?php else: ?>
                    No photo
                <?php endif; ?>
            </td>

            <td class="action-btns">
                <a class="edit-btn" href="memberDetail.php?id=<?= $row->id ?>">Edit</a>
                <a class="delete-btn" href="deleteMember.php?id=<?= $row->id ?>"
                   onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
    <?php endforeach ?>
<?php endif ?>
</tbody>
</table>

<?php include '../footer.php'; ?>
