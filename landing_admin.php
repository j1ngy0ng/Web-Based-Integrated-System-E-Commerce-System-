<?php
require_once '_base.php';
auth('Admin');

// ============================================================================
// 1. STATS DATA
// ============================================================================
$sqlRev = "SELECT SUM(TotalAmount) FROM `order` WHERE status IN ('Paid', 'Shipped', 'Completed')";
$totalRevenue = $_db->query($sqlRev)->fetchColumn() ?: 0;

$totalOrders = $_db->query("SELECT COUNT(*) FROM `order`")->fetchColumn();
$pendingOrders = $_db->query("SELECT COUNT(*) FROM `order` WHERE status = 'Pending'")->fetchColumn();

$lowStockThreshold = 10;
$lowStockCount = $_db->query("SELECT COUNT(*) FROM product WHERE stock <= $lowStockThreshold")->fetchColumn();

// ============================================================================
// 2. CHART DATA
// ============================================================================
$sqlTrend = "SELECT DATE(OrderDate) as date, SUM(TotalAmount) as total 
             FROM `order` 
             WHERE status != 'Cancelled' 
             GROUP BY DATE(OrderDate) 
             ORDER BY date ASC LIMIT 10";
$trendData = $_db->query($sqlTrend)->fetchAll();
$trendLabels = [];
$trendValues = [];
foreach ($trendData as $row) {
    $trendLabels[] = date('d M', strtotime($row->date));
    $trendValues[] = $row->total;
}

$sqlStatus = "SELECT status, COUNT(*) as count FROM `order` GROUP BY status";
$statusData = $_db->query($sqlStatus)->fetchAll();
$statusLabels = [];
$statusCounts = [];
$statusColors = [];
$colorMap = ['Pending' => '#f6c23e', 'Paid' => '#36b9cc', 'Shipped' => '#4e73df', 'Completed' => '#1cc88a', 'Cancelled' => '#e74a3b'];
foreach ($statusData as $row) {
    $statusLabels[] = $row->status;
    $statusCounts[] = $row->count;
    $statusColors[] = $colorMap[$row->status] ?? '#858796';
}

$sqlCat = "SELECT c.categoryName, COUNT(p.productID) as total 
           FROM ProductCategory c 
           LEFT JOIN product p ON c.categoryID = p.categoryID 
           GROUP BY c.categoryID";
$catData = $_db->query($sqlCat)->fetchAll();
$catLabels = [];
$catCounts = [];
foreach ($catData as $row) {
    $catLabels[] = $row->categoryName;
    $catCounts[] = $row->total;
}

$sqlRating = "SELECT name, rating_avg FROM product ORDER BY rating_avg DESC LIMIT 5";
$ratingData = $_db->query($sqlRating)->fetchAll();
$ratingLabels = [];
$ratingValues = [];
foreach ($ratingData as $row) {
    $ratingLabels[] = $row->name;
    $ratingValues[] = $row->rating_avg;
}

$sqlStock = "SELECT name, stock FROM product WHERE stock <= $lowStockThreshold ORDER BY stock ASC LIMIT 5";
$stockData = $_db->query($sqlStock)->fetchAll();
$stockLabels = [];
$stockValues = [];
foreach ($stockData as $row) {
    $stockLabels[] = $row->name;
    $stockValues[] = $row->stock;
}

// ============================================================================
// 3. UNREAD MESSAGES DATA
// ============================================================================
$sqlUnreadRooms = "
    SELECT cr.roomID, u.name,
           (SELECT message FROM chat_message 
            WHERE roomID = cr.roomID 
              AND senderID = cr.userID 
              AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
            ORDER BY msgID DESC LIMIT 1) AS last_msg,
           (SELECT COUNT(*) 
            FROM chat_message 
            WHERE roomID = cr.roomID 
              AND senderID = cr.userID
              AND created_at > IFNULL(cr.last_read_at, '1970-01-01')
           ) AS unread_count
    FROM chat_room cr
    JOIN user u ON cr.userID = u.id
    HAVING unread_count > 0
    ORDER BY unread_count DESC, cr.roomID ASC
";
$unreadRooms = $_db->query($sqlUnreadRooms)->fetchAll(PDO::FETCH_OBJ);
$totalUnreadChats = 0;
foreach ($unreadRooms as $r) $totalUnreadChats += $r->unread_count;

// ============================================================================
// DASHBOARD PAGE
// ============================================================================
$_title = 'Dashboard';
include 'navbar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        text-align: center;
        border-bottom: 4px solid #ddd;
        position: relative;
        cursor: pointer;
    }

    .stat-card h3 {
        margin: 0;
        font-size: 2rem;
        color: #333;
    }

    .stat-card p {
        margin: 5px 0 0;
        color: #777;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .border-blue {
        border-bottom-color: #4e73df;
    }

    .border-green {
        border-bottom-color: #1cc88a;
    }

    .border-yellow {
        border-bottom-color: #f6c23e;
    }

    .border-red {
        border-bottom-color: #e74a3b;
    }

    .border-purple {
        border-bottom-color: #6f42c1;
    }

    .charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }

    .chart-box {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        min-height: 350px;
    }

    .chart-box h4 {
        margin-top: 0;
        margin-bottom: 15px;
        text-align: center;
        color: #555;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    @media (max-width: 900px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        max-height: 300px;
        overflow-y: auto;
        z-index: 100;
    }

    .dropdown-content a {
        display: block;
        padding: 10px 15px;
        border-bottom: 1px solid #f0f0f0;
        text-decoration: none;
        color: #333;
    }

    .dropdown-content a div {
        font-size: 12px;
        color: #555;
        margin-top: 3px;
    }

    #aiPanel {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        padding: 25px;
        margin-top: 30px;
        margin-bottom: 30px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    #aiPanel h4 {
        text-align: center;
        color: #333;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 25px;
        font-size: 1.5rem;
    }

    .ai-insight {
        margin-bottom: 20px;
        padding: 15px 20px;
        border-left: 5px solid #4e73df;
        background: #f9f9f9;
        border-radius: 8px;
        transition: background 0.3s, border-left-color 0.3s;
    }

    .ai-insight h5 {
        margin: 0 0 8px 0;
        color: #4e73df;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .ai-insight p {
        margin: 0;
        line-height: 1.6;
        color: #555;
    }

    .ai-insight:hover {
        background: #f0f4ff;
        border-left-color: #1cc88a;
    }

    #aiButton {
        display: block;
        margin: 15px auto 0;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        background: #4e73df;
        color: #fff;
        cursor: pointer;
        font-size: 15px;
        transition: background 0.3s;
    }

    #aiButton:hover {
        background: #2e59d9;
    }
</style>

<div class="dashboard-container">
    <h2 style="margin-bottom: 20px;">Store Dashboard</h2>

    <!-- STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card border-green">
            <h3>RM <?= number_format($totalRevenue, 2) ?></h3>
            <p>Total Revenue</p>
        </div>
        <div class="stat-card border-blue">
            <h3><?= number_format($totalOrders) ?></h3>
            <p>Total Orders</p>
        </div>
        <div class="stat-card border-yellow">
            <h3><?= number_format($pendingOrders) ?></h3>
            <p>Pending Orders</p>
        </div>
        <div class="stat-card border-red">
            <h3><?= number_format($lowStockCount) ?></h3>
            <p>Low Stock Items</p>
        </div>
        <div class="stat-card border-purple" id="unreadCard">
            <h3 id="unreadCount"><?= $totalUnreadChats ?></h3>
            <p>Unread Messages</p>
            <div class="dropdown-content" id="unreadDropdown">
                <?php foreach ($unreadRooms as $r): ?>
                    <a href="chat/admin/chat.php?roomID=<?= $r->roomID ?>">
                        <strong><?= htmlspecialchars($r->name) ?></strong>
                        <span style="float:right; background:red; color:white; border-radius:8px; padding:2px 6px; font-size:12px;"><?= $r->unread_count ?></span>
                        <div><?= htmlspecialchars(substr($r->last_msg, 0, 50)) ?><?= strlen($r->last_msg) > 50 ? '...' : '' ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- AI INSIGHTS PANEL -->
    <div id="aiPanel">
    <h4>AI Recommendations</h4>
    <div id="aiContent">
        <div class="ai-insight">
            <h5>Sales</h5>
            <p id="salesInsight">Click "Analyze Dashboard" to get AI insights...</p>
        </div>
        <div class="ai-insight">
            <h5>Inventory</h5>
            <p id="inventoryInsight"></p>
        </div>
        <div class="ai-insight">
            <h5>Customer Service</h5>
            <p id="customerInsight"></p>
        </div>
        <div class="ai-insight">
            <h5>Marketing</h5>
            <p id="marketingInsight"></p>
        </div>
    </div>
    <button id="aiButton">Analyze Dashboard</button>
</div>

    <!-- CHARTS -->
    <div class="charts-row">
        <div class="chart-box">
            <h4>Sales Trend (Daily)</h4><canvas id="trendChart"></canvas>
        </div>
        <div class="chart-box">
            <h4>Order Statuses</h4>
            <div style="max-width:350px;margin:0 auto;"><canvas id="statusChart"></canvas></div>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-box">
            <h4>Products by Category</h4>
            <div style="max-width:350px;margin:0 auto;"><canvas id="catChart"></canvas></div>
        </div>
        <div class="chart-box">
            <h4>Top 5 Rated Products</h4><canvas id="ratingChart"></canvas>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-box" style="grid-column:1/-1;">
            <h4>Inventory Alert: Low Stock Items (Qty ≤ 10)</h4>
            <?php if (count($stockData) > 0): ?>
                <canvas id="stockChart" style="max-height:300px;"></canvas>
            <?php else: ?>
                <p style="text-align:center; margin-top:50px; color:#1cc88a; font-weight:bold;">All stock levels are healthy!</p>
            <?php endif; ?>
        </div>
    </div>


</div>

<script>
    const unreadCard = document.getElementById('unreadCard');
    const unreadDropdown = document.getElementById('unreadDropdown');
    const unreadCount = document.getElementById('unreadCount');

    unreadCard.addEventListener('click', () => {
        unreadDropdown.style.display = (unreadDropdown.style.display === 'block') ? 'none' : 'block';
    });

    document.addEventListener('click', e => {
        if (!unreadCard.contains(e.target)) unreadDropdown.style.display = 'none';
    });

    function fetchUnreadChats() {
        fetch('/chat/admin/fetch_unread_chats.php')
            .then(res => res.json())
            .then(data => {
                unreadCount.innerText = data.total;
                unreadDropdown.innerHTML = '';
                data.rooms.forEach(r => {
                    const a = document.createElement('a');
                    a.href = `chat/admin/chat.php?roomID=${r.roomID}`;
                    a.innerHTML = `
                    <strong>${r.name}</strong>
                    <span style="float:right; background:red; color:white; border-radius:8px; padding:2px 6px; font-size:12px;">${r.unread_count}</span>
                    <div>${r.last_msg? r.last_msg.substring(0,50)+(r.last_msg.length>50?'...':'') : ''}</div>
                `;
                    unreadDropdown.appendChild(a);
                });
            }).catch(err => console.error(err));
    }
    setInterval(fetchUnreadChats, 5000);

    // ----------------- Chart.js -----------------
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Revenue (RM)',
                data: <?= json_encode($trendValues) ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78,115,223,0.05)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusLabels) ?>,
            datasets: [{
                data: <?= json_encode($statusCounts) ?>,
                backgroundColor: <?= json_encode($statusColors) ?>,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(document.getElementById('catChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catCounts) ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(document.getElementById('ratingChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($ratingLabels) ?>,
            datasets: [{
                label: 'Avg Rating',
                data: <?= json_encode($ratingValues) ?>,
                backgroundColor: '#f6c23e',
                borderRadius: 5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    max: 5
                }
            }
        }
    });

    <?php if (count($stockData) > 0): ?>
        new Chart(document.getElementById('stockChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($stockLabels) ?>,
                datasets: [{
                    label: 'Qty Remaining',
                    data: <?= json_encode($stockValues) ?>,
                    backgroundColor: '#e74a3b'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    <?php endif; ?>

    // ----------------- AI BUTTON -----------------
    document.getElementById('aiButton').addEventListener('click', () => {
        const btn = document.getElementById('aiButton');
        const content = document.getElementById('aiContent');
        btn.disabled = true;
        content.innerText = 'Analyzing...';

        fetch('ai/ai_insights.php', {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                // Extract the recommendation strings from each object
                const sales = data.salesInsight?.recommendation || 'Sales: Unable to parse AI response.';
                const inventory = data.inventoryRecommendation?.recommendation || 'Inventory: Check stock levels manually.';
                const customer = data.customerServiceInsight?.recommendation || 'Customer Service: Review unread messages.';
                const marketing = data.marketingSuggestion?.recommendation || 'Marketing: Consider promotions.';

                // Show them inside the AI panel
                content.innerHTML = `
            <p><strong>Sales:</strong> ${sales}</p>
            <p><strong>Inventory:</strong> ${inventory}</p>
            <p><strong>Customer Service:</strong> ${customer}</p>
            <p><strong>Marketing:</strong> ${data.marketingSuggestion?.recommendation || 'Marketing: Consider promotions.'}</p>
        `;
                btn.disabled = false;
            })
            .catch(err => {
                console.error(err);
                content.innerHTML = `
            <p>Failed to fetch AI insights.</p>
            <p>Failed to fetch AI insights.</p>
            <p>Failed to fetch AI insights.</p>
            <p>Failed to fetch AI insights.</p>
        `;
                btn.disabled = false;
            });
    });
</script>

<?php include 'footer.php'; ?>