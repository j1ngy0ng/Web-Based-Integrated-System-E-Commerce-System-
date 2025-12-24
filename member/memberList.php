<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin", "Superadmin");
$currentUserRole = $_SESSION['user']->role ?? '';

// --------------------------------------------------------------------
// Sorting
$sort = req('sort', 'id'); // default column
$dir  = req('dir', 'asc');
$dir  = $dir == 'desc' ? 'desc' : 'asc';

$allowedSort = [
    'id',
    'email',
    'name',
    'role',
    'phone_number',
    'phone_verified',
    'is_active',
    'failed_attempts',
    'lock_until',
    'reward_points'
];

// --------------------------------------------------------------------
// Filters
$roleFilter     = req('role_filter', '');
$verifiedFilter = req('verified_filter', '');
$statusFilter = req('status_filter', '');
$failedFilter   = req('failed_filter', '');

// --------------------------------------------------------------------
// Search term
$q = req('q', '');
$searchTerm = "%$q%";

// --------------------------------------------------------------------
// Build dynamic WHERE conditions
$where = [];
$params = [];

// Default: show only ACTIVE members
if ($statusFilter === '') {
    $where[] = "is_active = 1";
}


if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}
if ($verifiedFilter !== '') {
    $where[] = "phone_verified = ?";
    $params[] = $verifiedFilter;
}
if ($statusFilter !== '') {
    $where[] = "is_active = ?";
    $params[] = $statusFilter;
}

if ($failedFilter !== '') {
    $where[] = "failed_attempts = ?";
    $params[] = $failedFilter;
}

if ($q) {
    $where[] = "(id LIKE ? OR email LIKE ? OR name LIKE ? OR role LIKE ? OR phone_number LIKE ? OR failed_attempts LIKE ? OR phone_verified LIKE ? OR is_active LIKE ? OR reward_points LIKE ?)";
    $params = array_merge($params, array_fill(0, 9, $searchTerm));
}

$page  = max(1, intval(req('page', 1)));
$limit = 10;

$countSql = "SELECT COUNT(*) FROM user";
if ($where) $countSql .= " WHERE " . implode(" AND ", $where);

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();

$paging = paginate($totalRows, $page, $limit);

$page       = $paging['page'];
$limit      = $paging['limit'];
$offset     = $paging['offset'];
$totalPages = $paging['totalPages'];


// --------------------------------------------------------------------
// Build final SQL
$countSql = "SELECT COUNT(*) FROM user";
if ($where) $countSql .= " WHERE " . implode(" AND ", $where);

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();



$sql = "SELECT * FROM user";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY $sort $dir LIMIT $offset, $limit";

$stm = $_db->prepare($sql);
$stm->execute($params);
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

    .action-btns {
        display: flex;
        gap: 6px;
        flex-wrap: nowrap;
    }

    th:last-child,
    td:last-child {
        min-width: 140px;
    }


    .edit-btn {
        background: #007bff;
        color: #fff;
    }

    .delete-btn {
        background: #dc3545;
        color: #fff;
    }

    th select {
        margin-left: 5px;
        padding: 2px 5px;
        font-size: 0.85rem;
    }
</style>



<h2 style="text-align:center; margin-top:30px;">Member List</h2>

<div style="width:90%; margin: 20px auto; display:flex; justify-content:space-between; align-items:center;">
    <!-- Search Form -->
    <?php
    $placeholder = 'Search by name or email';
    include '../member/search_bar.php';
    ?>



    <!-- Add Member Button -->
    <a href="memberDetail.php"
        style="display:inline-block; padding:10px 20px; background:#28a745; color:white; border-radius:6px; text-decoration:none; font-weight:600;">
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
                    'phone_number'   => 'Phone',
                    'reward_points'  => 'Reward Points',
                    'lock_until'     => 'Locked Until'
                ],
                $sort,
                $dir
            );
            ?>

            <!-- Columns with filters -->
            <th>
                <a href="?sort=role&dir=<?= $sort == 'role' && $dir == 'asc' ? 'desc' : 'asc' ?>">
                    Role <?= $sort == 'role' ? ($dir == 'asc' ? '▲' : '▼') : '' ?>
                </a>
                <form method="get" style="display:inline;">
                    <input type="hidden" name="sort" value="<?= $sort ?>">
                    <input type="hidden" name="dir" value="<?= $dir ?>">
                    <select name="role_filter" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="Admin" <?= $roleFilter == 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Member" <?= $roleFilter == 'Member' ? 'selected' : '' ?>>Member</option>
                    </select>
                </form>
            </th>


            <th>
                <a href="?sort=role&dir=<?= $sort == 'role' && $dir == 'asc' ? 'desc' : 'asc' ?>">
                    Verified <?= $sort == 'role' ? ($dir == 'asc' ? '▲' : '▼') : '' ?>
                </a>
                <form method="get" style="display:inline;">
                    <select name="verified_filter" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="1" <?= $verifiedFilter == '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= $verifiedFilter == '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </form>
            </th>

            <th>
                <a href="?sort=role&dir=<?= $sort == 'role' && $dir == 'asc' ? 'desc' : 'asc' ?>">
                    Active <?= $sort == 'role' ? ($dir == 'asc' ? '▲' : '▼') : '' ?>
                </a>
                <form method="get" style="display:inline;">
                    <select name="status_filter" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Blocked</option>
                        <option value="-1" <?= $statusFilter === '-1' ? 'selected' : '' ?>>Inactive</option>
                    </select>


                </form>
            </th>

            <th>
                <a href="?sort=role&dir=<?= $sort == 'role' && $dir == 'asc' ? 'desc' : 'asc' ?>">
                    Failed Attempts <?= $sort == 'role' ? ($dir == 'asc' ? '▲' : '▼') : '' ?>
                </a>

                <form method="get" style="display:inline;">
                    <select name="failed_filter" onchange="this.form.submit()">
                        <option value="">All</option>
                        <?php for ($i = 0; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $failedFilter == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </th>

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
                    <td><?= highlight($row->id, $q) ?></td>
                    <td><?= highlight($row->email, $q) ?></td>
                    <td><?= highlight($row->name, $q) ?></td>
                    <td><?= $row->phone_number ? highlight($row->phone_number, $q) : '-' ?></td>
                    <td><?= highlight($row->reward_points, $q) ?></td>
                    <td><?= $row->lock_until ?: '-' ?></td>

                    <td><?= highlight($row->role, $q) ?></td>

                    <td>
                        <?php $pv = $row->phone_verified ? '✔ Yes' : '✖ No';
                        echo highlight($pv, $q); ?>
                    </td>

                    <td>
                        <?php
                        if ($row->is_active == 1) {
                            $act = 'Active';
                        } elseif ($row->is_active == 0) {
                            $act = 'Blocked';
                        } elseif ($row->is_active == -1) {
                            $act = 'Inactive';
                        } else {
                            $act = 'Unknown';
                        }
                        echo highlight($act, $q);
                        ?>


                    </td>

                    <td><?= highlight($row->failed_attempts, $q) ?></td>

                    <td>
                        <?php if ($row->photo): ?>
                            <img src="../photos/<?= htmlspecialchars($row->photo) ?>"
                                style="width:50px; height:50px; border-radius:50%;">
                            <?php else: ?>No photo<?php endif; ?>
                    </td>

                    <td class="action-btns">
                        <?php
                        // Only show Edit for Superadmin accounts if current user is Superadmin
                        $canEdit = true;
                        if ($row->role == 'Superadmin' && $currentUserRole != 'Superadmin') {
                            $canEdit = false;
                        }

                        if ($canEdit): ?>
                            <a class="edit-btn" href="memberDetail.php?id=<?= $row->id ?>">Edit</a>
                        <?php endif; ?>

                        <?php if ($row->role != 'Superadmin' || $currentUserRole == 'Superadmin'): ?>
                            <a class="delete-btn" href="deleteMember.php?id=<?= $row->id ?>"
                                onclick="return confirm('Set this member to inactive?');">
                                Delete
                            </a>

                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>

<div style="width:90%; margin:20px auto; text-align:center;">

    <?php
    render_pagination(
        $page,
        $totalPages,
        [
            'sort' => $sort,
            'dir'  => $dir,
            'q'    => $q,
            'role_filter'     => $roleFilter,
            'verified_filter' => $verifiedFilter,
            'status_filter'   => $statusFilter,
            'failed_filter'   => $failedFilter
        ]
    );
    ?>

</div>


<?php include '../footer.php'; ?>