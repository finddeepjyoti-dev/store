<?php
require_once 'common/header.php';

// --------------------------------------------------
// 1. Fetch filter parameters (GET)
// --------------------------------------------------
$period  = isset($_GET['period']) ? $_GET['period'] : 'daily';
$from    = $_GET['from'] ?? '';
$to      = $_GET['to'] ?? '';

// Default date range: last 7 days if nothing set
if (empty($from) || empty($to)) {
    $to   = date('Y-m-d');
    $from = date('Y-m-d', strtotime('-7 days'));
}

// For monthly/yearly, we ignore exact dates and use whole month/year
$whereClause = "";
if ($period == 'daily') {
    $whereClause = "DATE(o.created_at) BETWEEN '$from' AND '$to'";
} elseif ($period == 'monthly') {
    // Assume we pass month in $from as 'YYYY-MM'
    $whereClause = "DATE_FORMAT(o.created_at, '%Y-%m') = '$from'";
    // Adjust $from to first day of month for display
    if (preg_match('/^\d{4}-\d{2}$/', $from)) {
        $from = $from . '-01';
        $to   = date('Y-m-t', strtotime($from));
    }
} elseif ($period == 'yearly') {
    // $from is year, e.g., '2025'
    $whereClause = "YEAR(o.created_at) = '$from'";
    $from = $from . '-01-01';
    $to   = $from = $from . '-12-31';
}

// Common filter for delivered orders (revenue / successful sales)
$deliveredWhere = $whereClause . " AND o.status = 'Delivered'";

// --------------------------------------------------
// 2. Orders & Revenue data (by day/month/year)
// --------------------------------------------------
if ($period == 'daily') {
    $groupBy = "DATE(o.created_at)";
    $dateFormat = "DATE_FORMAT(o.created_at, '%d %b')";
} elseif ($period == 'monthly') {
    $groupBy = "DATE_FORMAT(o.created_at, '%Y-%m')";
    $dateFormat = "DATE_FORMAT(o.created_at, '%b %Y')";
} else { // yearly
    $groupBy = "YEAR(o.created_at)";
    $dateFormat = "YEAR(o.created_at)";
}

$orderStats = $conn->query("
    SELECT $groupBy AS period, 
           COUNT(DISTINCT o.id) AS order_count, 
           SUM(o.total_amount) AS revenue
    FROM orders o
    WHERE $deliveredWhere
    GROUP BY period
    ORDER BY period ASC
");

$chartLabels = [];
$orderCounts = [];
$revenues = [];
while ($row = $orderStats->fetch_assoc()) {
    // Format the label a bit nicer for daily
    if ($period == 'daily') {
        $label = date('d M', strtotime($row['period']));
    } else {
        $label = $row['period'];
    }
    $chartLabels[] = $label;
    $orderCounts[] = (int)$row['order_count'];
    $revenues[]    = (float)$row['revenue'];
}

// --------------------------------------------------
// 3. Top Selling Products
// --------------------------------------------------
$topProducts = $conn->query("
    SELECT p.id, p.name, SUM(oi.quantity) AS total_qty, 
           SUM(oi.quantity * oi.price) AS total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status = 'Delivered' AND $whereClause  -- same time filter for delivered
    GROUP BY p.id
    ORDER BY total_qty DESC
    LIMIT 10
");
$prodNames = [];
$prodQtys  = [];
while ($p = $topProducts->fetch_assoc()) {
    $prodNames[] = $p['name'];
    $prodQtys[]  = (int)$p['total_qty'];
}

// --------------------------------------------------
// 4. Top Categories
// --------------------------------------------------
$topCategories = $conn->query("
    SELECT c.id, c.name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.cat_id = c.id
    WHERE o.status = 'Delivered' AND $whereClause
    GROUP BY c.id
    ORDER BY total_qty DESC
    LIMIT 6
");
$catNames = [];
$catQtys  = [];
while ($c = $topCategories->fetch_assoc()) {
    $catNames[] = $c['name'];
    $catQtys[]  = (int)$c['total_qty'];
}

// --------------------------------------------------
// 5. Top Subcategories
// --------------------------------------------------
$topSubcats = $conn->query("
    SELECT s.id, s.name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN subcategories s ON p.subcat_id = s.id
    WHERE o.status = 'Delivered' AND $whereClause
    GROUP BY s.id
    ORDER BY total_qty DESC
    LIMIT 6
");
$subcatNames = [];
$subcatQtys  = [];
while ($s = $topSubcats->fetch_assoc()) {
    $subcatNames[] = $s['name'];
    $subcatQtys[]  = (int)$s['total_qty'];
}
?>

<div class="space-y-8">
    <h2 class="text-2xl font-bold text-slate-100 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Reports & Analytics
    </h2>

    <!-- Filters -->
    <form method="get" class="flex flex-wrap gap-4 items-end bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-xl">
        <div>
            <label class="block text-sm text-slate-300 mb-1">Period</label>
            <select name="period" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2 text-white focus:ring-amber-400">
                <option value="daily" <?= $period=='daily'?'selected':'' ?>>Daily</option>
                <option value="monthly" <?= $period=='monthly'?'selected':'' ?>>Monthly</option>
                <option value="yearly" <?= $period=='yearly'?'selected':'' ?>>Yearly</option>
            </select>
        </div>

        <?php if ($period == 'daily'): ?>
        <div>
            <label class="block text-sm text-slate-300 mb-1">From</label>
            <input type="date" name="from" value="<?= $from ?>" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2 text-white focus:ring-amber-400">
        </div>
        <div>
            <label class="block text-sm text-slate-300 mb-1">To</label>
            <input type="date" name="to" value="<?= $to ?>" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2 text-white focus:ring-amber-400">
        </div>
        <?php elseif ($period == 'monthly'): ?>
        <div>
            <label class="block text-sm text-slate-300 mb-1">Month</label>
            <input type="month" name="from" value="<?= $from ?>" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2 text-white focus:ring-amber-400">
        </div>
        <?php else: ?>
        <div>
            <label class="block text-sm text-slate-300 mb-1">Year</label>
            <select name="from" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2 text-white focus:ring-amber-400">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                <option value="<?= $y ?>" <?= $from==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2 rounded-xl font-bold hover:shadow-lg active:scale-95 transition-all">
            Apply
        </button>
    </form>

    <!-- Chart: Orders & Revenue -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6">
        <h3 class="text-xl font-semibold text-slate-200 mb-4">Orders & Revenue</h3>
        <canvas id="ordersRevenueChart" height="80"></canvas>
    </div>

    <!-- Chart: Top Products -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6">
        <h3 class="text-xl font-semibold text-slate-200 mb-4">Top Selling Products</h3>
        <canvas id="topProductsChart" height="80"></canvas>
    </div>

    <!-- Chart: Top Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6">
            <h3 class="text-xl font-semibold text-slate-200 mb-4">Top Categories</h3>
            <canvas id="topCategoriesChart" height="80"></canvas>
        </div>
        <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6">
            <h3 class="text-xl font-semibold text-slate-200 mb-4">Top Subcategories</h3>
            <canvas id="topSubcategoriesChart" height="80"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ---------- Orders & Revenue ----------
const ctx1 = document.getElementById('ordersRevenueChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Orders',
                data: <?= json_encode($orderCounts) ?>,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderRadius: 6,
                yAxisID: 'yLeft'
            },
            {
                label: 'Revenue (₹)',
                data: <?= json_encode($revenues) ?>,
                type: 'line',
                borderColor: '#2dd4bf',
                backgroundColor: 'rgba(45, 212, 191, 0.1)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#2dd4bf',
                yAxisID: 'yRight'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: '#cbd5e1' } }
        },
        scales: {
            x: { ticks: { color: '#94a3b8' } },
            yLeft: {
                type: 'linear',
                position: 'left',
                title: { display: true, text: 'Orders', color: '#f59e0b' },
                ticks: { color: '#f59e0b' }
            },
            yRight: {
                type: 'linear',
                position: 'right',
                title: { display: true, text: 'Revenue (₹)', color: '#2dd4bf' },
                ticks: { color: '#2dd4bf' },
                grid: { drawOnChartArea: false }
            }
        }
    }
});

// ---------- Top Products ----------
const ctx2 = document.getElementById('topProductsChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?= json_encode($prodNames) ?>,
        datasets: [{
            label: 'Quantity Sold',
            data: <?= json_encode($prodQtys) ?>,
            backgroundColor: 'rgba(245, 158, 11, 0.7)',
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { labels: { color: '#cbd5e1' } } },
        scales: {
            x: { ticks: { color: '#94a3b8' }, title: { display: true, text: 'Quantity', color: '#cbd5e1' } },
            y: { ticks: { color: '#94a3b8' } }
        }
    }
});

// ---------- Top Categories (Pie) ----------
const ctx3 = document.getElementById('topCategoriesChart').getContext('2d');
new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($catNames) ?>,
        datasets: [{
            label: 'Sales Qty',
            data: <?= json_encode($catQtys) ?>,
            backgroundColor: ['#f59e0b','#14b8a6','#8b5cf6','#ef4444','#3b82f6','#22c55e'],
            borderColor: '#1e293b'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#cbd5e1' } } }
    }
});

// ---------- Top Subcategories (Pie) ----------
const ctx4 = document.getElementById('topSubcategoriesChart').getContext('2d');
new Chart(ctx4, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($subcatNames) ?>,
        datasets: [{
            label: 'Sales Qty',
            data: <?= json_encode($subcatQtys) ?>,
            backgroundColor: ['#f59e0b','#14b8a6','#8b5cf6','#ef4444','#3b82f6','#22c55e'],
            borderColor: '#1e293b'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#cbd5e1' } } }
    }
});
</script>

<?php include 'common/bottom.php'; ?>