<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin","Superadmin");

// --------------------------------------------------------------------
// Search
$q = trim(req('q', '')); // GET parameter
$search = "%$q%";

// --------------------------------------------------------------------
// Status filter (from table header dropdown)
$statusFilter = req('status', ''); // '' = all


// --------------------------------------------------------------------
// Sorting
$sort = req('sort', 'orderID'); // default column
$dir  = req('dir', 'asc');      // default order
$dir  = $dir == 'desc' ? 'desc' : 'asc'; // safety

$allowedSort = ['orderID', 'userID', 'status', 'TotalAmount', 'OrderDate'];
if (!in_array($sort, $allowedSort)) {
    $sort = 'orderID';
}

// --------------------------------------------------------------------
// Fetch Orders
// --------------------------------------------------------------------
// Pagination setup
$page  = max(1, intval(req('page', 1))); // Current page from GET
$limit = 10; // Rows per page

// Count total orders
$countSql = "SELECT COUNT(*) FROM `order` WHERE 1";
$params = [];

if ($q !== '') {
    $countSql .= " AND (orderID LIKE ? OR userID LIKE ? OR status LIKE ?)";
    array_push($params, $search, $search, $search);
}

if ($statusFilter !== '') {
    $countSql .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();


// Get pagination info
$pagination = paginate($totalRows, $page, $limit);
$limit  = (int)$pagination['limit'];
$offset = (int)$pagination['offset'];

// --------------------------------------------------------------------
// Fetch paginated orders
$sql = "SELECT * FROM `order` WHERE 1";

if ($q !== '') {
    $sql .= " AND (orderID LIKE ? OR userID LIKE ? OR status LIKE ?)";
}

if ($statusFilter !== '') {
    $sql .= " AND status = ?";
}

$sql .= " ORDER BY $sort $dir LIMIT $limit OFFSET $offset";

$stm = $_db->prepare($sql);
$stm->execute($params);
$data = $stm->fetchAll();




// --------------------------------------------------------------------
$_title = "Order | List";
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

<h2 style="text-align:center; margin-top:30px;">Order List</h2>

<div style="width:90%; margin: 20px auto; display:flex; justify-content:flex-start; gap:10px; flex-wrap:wrap;">
    <form method="get" style="display:flex; gap:10px;">
        <input type="text" name="q" placeholder="Search orders..."
               value="<?= htmlspecialchars($q) ?>"
               style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">

        <button type="submit" class="btn btn-primary">Search</button>

        <input type="hidden" name="sort" value="<?= $sort ?>">
        <input type="hidden" name="dir" value="<?= $dir ?>">
    </form>
</div>


<table>
<thead>
<tr>
    <!-- Order ID -->
    <th>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'orderID','dir'=>$sort=='orderID' && $dir=='asc'?'desc':'asc'])) ?>">
            Order ID
        </a>
    </th>

    <!-- User ID -->
    <th>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'userID','dir'=>$sort=='userID' && $dir=='asc'?'desc':'asc'])) ?>">
            User ID
        </a>
    </th>

    <!-- Status FILTER -->
    <th>
        Status<br>
        <form method="get">
            <?php foreach ($_GET as $k => $v): ?>
                <?php if ($k !== 'status' && $k !== 'page'): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
                <?php endif ?>
            <?php endforeach ?>

            <select name="status" onchange="this.form.submit()" style="width:100%;">
                <option value="">All</option>
                <option value="Pending"   <?= $statusFilter=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Paid"      <?= $statusFilter=='Paid'?'selected':'' ?>>Paid</option>
                <option value="Shipped"   <?= $statusFilter=='Shipped'?'selected':'' ?>>Shipped</option>
                <option value="Completed" <?= $statusFilter=='Completed'?'selected':'' ?>>Completed</option>
                <option value="Cancelled" <?= $statusFilter=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
        </form>
    </th>

    <!-- Total -->
    <th>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'TotalAmount','dir'=>$sort=='TotalAmount' && $dir=='asc'?'desc':'asc'])) ?>">
            Total (RM)
        </a>
    </th>

    <!-- Order Date -->
    <th>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'OrderDate','dir'=>$sort=='OrderDate' && $dir=='asc'?'desc':'asc'])) ?>">
            Order Date
        </a>
    </th>

    <th>Action</th>
</tr>
</thead>


    <tbody>
        <?php if (!$data): ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:20px; color:#888;">
                    No orders found.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= highlight($row->orderID, $q) ?></td>
                    <td><?= highlight($row->userID, $q) ?></td>
                    <td><?= highlight($row->status, $q) ?></td>
                    <td>RM <?= number_format($row->TotalAmount, 2) ?></td>
                    <td><?= $row->OrderDate ?></td>

                    <td class="action-btns">
                        <a class="edit-btn" href="detail.php?orderID=<?= $row->orderID ?>">Edit</a>
                        <a class="delete-btn" href="deleteOrder.php?orderID=<?= $row->orderID ?>"
                           onclick="return confirm('Are you sure you want to delete this order?');">
                           Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>
<div style="width:90%; margin:20px auto; text-align:center;">
    <?php
render_pagination($pagination['page'], $pagination['totalPages'], [
    'sort'   => $sort,
    'dir'    => $dir,
    'q'      => $q,
    'status' => $statusFilter
]);



    ?>
    
</div>

<?php include '../footer.php'; ?>
