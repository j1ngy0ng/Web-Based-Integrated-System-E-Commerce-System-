<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authorization (member only)
auth('Member');

// --------------------------------------------------------------------
// Pagination
$page  = max(1, (int) req('page', 1));
$limit = 10;

// --------------------------------------------------------------------
// Count total orders
$stmCount = $_db->prepare('
    SELECT COUNT(*)
    FROM `order`
    WHERE userID = ?
');
$stmCount->execute([$_user->id]);
$totalRows = $stmCount->fetchColumn();

// Create pagination variables
$pg = paginate($totalRows, $page, $limit);
extract($pg);   // creates: $page, $offset, $totalPages


// --------------------------------------------------------------------
// Fetch orders belonging to logged-in user
$stm = $_db->prepare('
    SELECT *
    FROM `order`
    WHERE userID = :uid
    ORDER BY orderID DESC
    LIMIT :limit OFFSET :offset
');

$stm->bindValue(':uid', $_user->id, PDO::PARAM_INT);
$stm->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stm->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stm->execute();

$orders = $stm->fetchAll();

// --------------------------------------------------------------------
$_title = "Order | History";
include '../navbar.php';
?>

<style>
    /* --- PAGE LAYOUT --- */
    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        min-height: 70vh;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .page-header h1 {
        color: #2d3748;
        font-size: 2.5em;
        margin-bottom: 10px;
        /* Matches the teal theme */
        background: linear-gradient(135deg, #006989, #009eb3); 
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .page-header p {
        color: #718096;
        font-size: 1.1em;
    }

    /* --- ORDER CARD / TABLE STYLES --- */
    .orders-wrapper {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 105, 137, 0.1);
        overflow: hidden;
        border: 1px solid #e8f4f8;
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .history-table th {
        background: #006989; /* Primary Teal */
        color: white;
        padding: 18px 20px;
        text-align: left;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .history-table td {
        padding: 20px;
        border-bottom: 1px solid #f1f8fc;
        vertical-align: middle;
        color: #4a5568;
    }

    .history-table tr:hover td {
        background-color: #f8fcfd;
    }

    .history-table tr:last-child td {
        border-bottom: none;
    }

    /* --- COLUMN SPECIFICS --- */
    .order-id {
        font-weight: 700;
        color: #006989;
    }

    .order-date {
        font-size: 0.9em;
        color: #718096;
    }

    .order-total {
        font-weight: 700;
        font-size: 1.1em;
        color: #2d3748;
    }

    /* --- STATUS BADGES --- */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-pending { background: #fffaf0; color: #d69e2e; border: 1px solid #fbd38d; }
    .status-paid { background: #ebf8ff; color: #3182ce; border: 1px solid #bee3f8; }
    .status-shipped { background: #e9d8fd; color: #805ad5; border: 1px solid #d6bcfa; }
    .status-completed { background: #f0fff4; color: #38a169; border: 1px solid #c6f6d5; }
    .status-cancelled { background: #fff5f5; color: #e53e3e; border: 1px solid #fed7d7; }

    /* --- THUMBNAIL PREVIEW --- */
    .order-thumbs {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .order-thumbs img {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
    }
    
    .order-thumbs img:hover {
        transform: scale(1.1);
        border-color: #006989;
    }

    .more-items {
        font-size: 0.8em;
        color: #718096;
        background: #f7fafc;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    /* --- ACTION BUTTON --- */
    .btn-view {
        padding: 8px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9em;
        transition: all 0.2s ease;
        border: 2px solid #006989;
        background: transparent;
        color: #006989;
        cursor: pointer;
    }

    .btn-view:hover {
        background: #006989;
        color: white;
        transform: translateY(-2px);
    }

    /* --- EMPTY STATE --- */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .empty-icon { font-size: 4em; margin-bottom: 20px; opacity: 0.5; }
    .btn-shop {
        padding: 12px 30px;
        background: #006989;
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        display: inline-block;
        margin-top: 20px;
    }
    .btn-shop:hover { background: #005a73; }

    @media (max-width: 768px) {
        .history-table { display: block; overflow-x: auto; white-space: nowrap; }
        .page-header h1 { font-size: 2em; }
    }
</style>

<div class="page-container">
    <div class="page-header">
        <h1>📦 Order History</h1>
        <p>Track your past purchases and current status</p>
    </div>

    <?php if (!$orders): ?>
        <div class="empty-state">
            <div class="empty-icon">📜</div>
            <h3 style="color:#2d3748;">No orders yet</h3>
            <p style="color:#718096;">It looks like you haven't placed any orders yet.</p>
            <a href="/product/list.php" class="btn-shop">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="orders-wrapper">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php 
                            // Determine status class
                            $statusClass = 'status-' . strtolower($o->status);
                            
                            // Fetch images for this order
                            $stm = $_db->prepare('
                                SELECT pp.photoURL
                                FROM order_item oi
                                JOIN productPhoto pp ON oi.productID = pp.productID
                                WHERE oi.orderID = ?
                                AND pp.is_main = 1
                                LIMIT 4
                            ');
                            $stm->execute([$o->orderID]);
                            $photos = $stm->fetchAll(PDO::FETCH_COLUMN);
                            
                            // Check for more items
                            $countStm = $_db->prepare('SELECT COUNT(*) FROM order_item WHERE orderID = ?');
                            $countStm->execute([$o->orderID]);
                            $totalItems = $countStm->fetchColumn();
                        ?>
                        <tr>
                            <td>
                                <span class="order-id">#<?= str_pad($o->orderID, 6, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <span class="order-date"><?= date('d M Y', strtotime($o->OrderDate)) ?></span><br>
                                <span style="font-size:0.8em; color:#a0aec0;"><?= date('h:i A', strtotime($o->OrderDate)) ?></span>
                            </td>
                            <td>
                                <div class="order-thumbs">
                                    <?php foreach ($photos as $photo): ?>
                                        <img src="/products/<?= $photo ?>" alt="Product">
                                    <?php endforeach; ?>
                                    
                                    <?php if ($totalItems > 3): ?>
                                        <div class="more-items">+<?= $totalItems - 3 ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="order-total">RM <?= number_format($o->TotalAmount, 2) ?></span>
                            </td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($o->status) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-view" data-get="detail.php?orderID=<?= $o->orderID ?>">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="text-align: center; margin-top: 20px; color: #718096; font-size: 0.9em;">
            Showing <?= count($orders) ?> of <?= $totalRows ?> order(s)
        </p>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <div style="margin-top:30px; text-align:center;">
        <?php
        render_pagination($page, $totalPages);
        ?>
    </div>
<?php endif; ?>


<?php include '../footer.php'; ?>