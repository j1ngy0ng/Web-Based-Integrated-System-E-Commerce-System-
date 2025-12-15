<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin");

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
$totalRows = $_db->query("SELECT COUNT(*) FROM `order`")->fetchColumn();

// Get pagination info
$pagination = paginate($totalRows, $page, $limit);
$limit  = (int)$pagination['limit'];
$offset = (int)$pagination['offset'];

// --------------------------------------------------------------------
// Fetch paginated orders
$sql = "SELECT * FROM `order` ORDER BY $sort $dir LIMIT $limit OFFSET $offset";
$stm = $_db->query($sql); // No execute() needed
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

<table>
    <thead>
        <tr>
            <?php
            table_headers(
                [
                    'orderID'     => 'Order ID',
                    'userID'      => 'User ID',
                    'status'      => 'Status',
                    'TotalAmount' => 'Total (RM)',
                    'OrderDate'   => 'Order Date',
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
                <td colspan="6" style="text-align:center; padding:20px; color:#888;">
                    No orders found.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= $row->orderID ?></td>
                    <td><?= $row->userID ?></td>
                    <td><?= htmlspecialchars($row->status) ?></td>
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
        'sort' => $sort,
        'dir'  => $dir
    ]);
    $params = [
    'sort' => $sort,
    'dir'  => $dir,
    'q'    => $q ?? ''
];

    ?>
    
</div>

<?php include '../footer.php'; ?>
