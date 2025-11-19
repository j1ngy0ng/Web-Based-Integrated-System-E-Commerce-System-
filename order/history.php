<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authorization (member only)
auth('Member');

// --------------------------------------------------------------------
// Fetch orders belonging to logged-in user
$stm = $_db->prepare('
    SELECT *
    FROM `order`
    WHERE userID = ?
    ORDER BY orderID DESC
');
$stm->execute([$_user->id]);
$orders = $stm->fetchAll();

// --------------------------------------------------------------------
$_title = "Order | History";
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

    tr:hover {
        background: #fafafa;
    }

    .popup {
        display: none;
    }

    tr:hover .popup {
        display: grid !important;
        grid-template-columns: repeat(5, auto);
        gap: 3px;
        margin-top: 5px;
    }

    .popup img {
        width: 50px;
        height: 50px;
        border: 1px solid #333;
        object-fit: cover;
    }
</style>

<h2 style="text-align:center; margin-top:30px;">Order History</h2>

<p style="width:90%; margin: 10px auto;"><?= count($orders) ?> record(s)</p>

<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Total (RM)</th>
            <th></th>
        </tr>
    </thead>

    <tbody>
        <?php if (!$orders): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:20px; color:#888;">
                    No orders found.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= $o->orderID ?></td>
                    <td><?= $o->OrderDate ?></td>
                    <td><?= htmlspecialchars($o->status) ?></td>
                    <td>RM <?= number_format($o->TotalAmount, 2) ?></td>

                    <td>
                        <button data-get="detail.php?orderID=<?= $o->orderID ?>">Detail</button>

                        <div class="popup">
                            <?php
                            $stm = $_db->prepare('
            SELECT pp.photoURL
            FROM order_item oi
            JOIN productPhoto pp ON oi.productID = pp.productID
            WHERE oi.orderID = ?
            AND pp.is_main = 1
        ');
                            $stm->execute([$o->orderID]);
                            $photos = $stm->fetchAll(PDO::FETCH_COLUMN);

                            foreach ($photos as $photo) {
                                echo "<img src='/products/$photo'>";
                            }
                            ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>

<?php include '../footer.php'; ?>