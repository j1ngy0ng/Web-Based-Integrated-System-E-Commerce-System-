<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// Authenticated users
auth('Admin','Superadmin');

// ----------------------------------------------------------------------------
// Search
$q = trim(req('q', ''));
$search = "%$q%";


// ----------------------------------------------------------------------------
// DELETE LOGIC
if (is_post() && req('action') == 'delete') {
    $code = req('voucher_code');
    $stm = $_db->prepare("DELETE FROM vouchers WHERE voucher_code = ?");
    $stm->execute([$code]);
    temp('info', "Voucher '$code' deleted successfully.");
    redirect('voucher.php');
}

// ----------------------------------------------------------------------------
// FETCH VOUCHERS
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
// Pagination setup
$page  = max(1, intval(req('page', 1))); // current page from GET
$limit = 10; // rows per page

// Get total number of vouchers
$countSql = "SELECT COUNT(*) FROM vouchers";
$params = [];

if ($q !== '') {
    $countSql .= " WHERE voucher_code LIKE ? OR discount_type LIKE ?";
    $params = [$search, $search];
}

$stmCount = $_db->prepare($countSql);
$stmCount->execute($params);
$totalRows = $stmCount->fetchColumn();


// Use your base.php function to get pagination info
$pagination = paginate($totalRows, $page, $limit);

$limit  = (int)$pagination['limit'];
$offset = (int)$pagination['offset'];

// ----------------------------------------------------------------------------
// Fetch vouchers with limit/offset
$sql = "SELECT * FROM vouchers";

if ($q !== '') {
    $sql .= " WHERE voucher_code LIKE ? OR discount_type LIKE ?";
}

$sql .= " ORDER BY expiry_date DESC LIMIT $limit OFFSET $offset";

$stm = $_db->prepare($sql);
$stm->execute($params);
$vouchers = $stm->fetchAll();

$_title = 'Manage Vouchers';
include '../navbar.php';
?>

<style>
    /* Reuse consistent styles from maintenance.php */
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    
    .page-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
    
    /* Header & Add Button */
    .table-controls-row {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
    }
    
    .add-btn {
        padding: 10px 20px; background: #28a745; color: white; border-radius: 6px; 
        text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;
        transition: 0.2s;
    }
    .add-btn:hover { background: #218838; transform: translateY(-2px); }

    /* Table Styles */
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
    th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #f4f6f9; font-weight: 700; color: #333; }
    tr:hover { background: #fafafa; }

    /* Badge Styles */
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600; }
    .badge-fixed { background: #e3f2fd; color: #0d47a1; }
    .badge-percent { background: #e8f5e9; color: #1b5e20; }
    .badge-expired { background: #ffebee; color: #c62828; }
    .badge-active { background: #e8f5e9; color: #2e7d32; }

    /* Action Buttons */
    td.action-btns { display: flex; gap: 8px; }
    .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; text-decoration: none; color: white; border: none; cursor: pointer; transition: 0.2s; }
    .btn-edit { background: #007bff; }
    .btn-edit:hover { background: #0056b3; }
    .btn-delete { background: #dc3545; }
    .btn-delete:hover { background: #c82333; }
</style>

<div class="page-container">
<h2 style="margin-bottom:15px;">Voucher List</h2>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <!-- Search -->
    <form method="get" style="display:flex; gap:10px;">
        <input type="text" name="q"
               placeholder="Search voucher..."
               value="<?= htmlspecialchars($q) ?>"
               style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">

        <button type="submit" class="btn-action btn-edit">Search</button>
    </form>

    <!-- Add button -->
    <a href="voucher_details.php" class="add-btn">
        <i class="fa fa-plus"></i> Add Voucher
    </a>
</div>



    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Min Spend</th>
                <th>Expiry Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$vouchers): ?>
                <tr><td colspan="7" style="text-align:center; color:#888;">No vouchers found.</td></tr>
            <?php else: ?>
                <?php foreach ($vouchers as $v): 
                    $isExpired = strtotime($v->expiry_date) < time();
                ?>
                <tr style="<?= $isExpired ? 'opacity:0.6;' : '' ?>">
                    <td><strong><?= highlight($v->voucher_code, $q) ?></strong></td>
                    <td>
                        <span class="badge <?= $v->discount_type == 'fixed' ? 'badge-fixed' : 'badge-percent' ?>">
                            <?= highlight(ucfirst($v->discount_type), $q) ?>
                        </span>
                    </td>
                    <td>
                        <?= $v->discount_type == 'fixed' ? highlight('RM ' . number_format($v->discount_value, 2), $q) : highlight(number_format($v->discount_value, 0) . '%', $q) ?>
                    </td>
                    <td><?= highlight('RM ' . number_format($v->min_spend, 2), $q) ?></td>
                    <td><?= date('d M Y', strtotime($v->expiry_date)) ?></td>
                    <td>
                        <?php if ($isExpired): ?>
                            <span class="badge badge-expired">Expired</span>
                        <?php else: ?>
                            <span class="badge badge-active">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-btns">
                        <a href="voucher_details.php?code=<?= $v->voucher_code ?>" class="btn-action btn-edit">Edit</a>
                        
                        <form method="post" onsubmit="return confirm('Delete voucher <?= htmlspecialchars($v->voucher_code) ?>?');" style="margin:0;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="voucher_code" value="<?= $v->voucher_code ?>">
                            <button type="submit" class="btn-action btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Pagination -->
    <?php
$params = [
    'q' => $q
];
render_pagination($pagination['page'], $pagination['totalPages'], $params);

?>

</div>

<?php include '../footer.php'; ?>