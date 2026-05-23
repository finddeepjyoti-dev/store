<?php require_once 'common/header.php';
$totalUsers = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];
$totalOrders = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];
$revenue = $conn->query("SELECT SUM(total_amount) AS rev FROM orders WHERE status='Delivered'")->fetch_assoc()['rev'] ?? 0;
$totalProducts = $conn->query("SELECT COUNT(*) AS cnt FROM products")->fetch_assoc()['cnt'];
$activeProducts = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock>0")->fetch_assoc()['cnt'];
$cancelled = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status='Cancelled'")->fetch_assoc()['cnt'];
$shipments = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status='Dispatched'")->fetch_assoc()['cnt'];
$pendingReports = $conn->query("SELECT COUNT(*) AS cnt FROM reports WHERE status='Pending'")->fetch_assoc()['cnt'];

// Fetch monthly data for the last 12 months
$monthlyData = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS period,
           COUNT(*) AS order_count,
           SUM(CASE WHEN status = 'Delivered' THEN total_amount ELSE 0 END) AS revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY period
    ORDER BY period ASC
");

$months = [];
$orderCounts = [];
$revenues = [];
while ($row = $monthlyData->fetch_assoc()) {
    $months[] = date('M y', strtotime($row['period'] . '-01'));
    $orderCounts[] = (int)$row['order_count'];
    $revenues[] = (float)$row['revenue'];
}
?>
<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Dashboard Overview
    </h2>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <!-- Users -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center">
                <i class="fas fa-users text-2xl text-amber-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Total Users</p>
                <p class="text-2xl font-bold text-slate-100"><?= $totalUsers ?></p>
            </div>
        </div>
        <!-- Orders -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-teal-500/20 flex items-center justify-center">
                <i class="fas fa-shopping-cart text-2xl text-teal-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Total Orders</p>
                <p class="text-2xl font-bold text-slate-100"><?= $totalOrders ?></p>
            </div>
        </div>
        <!-- Revenue -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                <i class="fas fa-rupee-sign text-2xl text-emerald-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Revenue</p>
                <p class="text-2xl font-bold text-slate-100">₹<?= number_format($revenue, 2) ?></p>
            </div>
        </div>
        <!-- Total Products -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-sky-500/20 flex items-center justify-center">
                <i class="fas fa-cubes text-2xl text-sky-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Total Products</p>
                <p class="text-2xl font-bold text-slate-100"><?= $totalProducts ?></p>
            </div>
        </div>
        <!-- Active Products -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center">
                <i class="fas fa-boxes text-2xl text-amber-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Active Products</p>
                <p class="text-2xl font-bold text-slate-100"><?= $activeProducts ?></p>
            </div>
        </div>
        <!-- Shipments -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                <i class="fas fa-truck text-2xl text-yellow-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Shipments</p>
                <p class="text-2xl font-bold text-slate-100"><?= $shipments ?></p>
            </div>
        </div>
        <!-- Cancelled -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                <i class="fas fa-ban text-2xl text-red-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Cancelled</p>
                <p class="text-2xl font-bold text-slate-100"><?= $cancelled ?></p>
            </div>
        </div>
        <!-- Pending Reports -->
        <div class="stat-card bg-slate-800/70 backdrop-blur p-4 rounded-2xl border border-slate-700/50 shadow-lg flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-2xl text-orange-400"></i>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Pending Reports</p>
                <p class="text-2xl font-bold text-slate-100"><?= $pendingReports ?></p>
            </div>
        </div>
    </div>

    <!-- Growth Chart (12 months) -->
    <div class="mt-8 bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6">
        <h3 class="text-lg font-bold text-slate-200 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-line text-amber-400"></i> Monthly Growth
        </h3>
        <div class="w-full h-64">
            <canvas id="growthChart"></canvas>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="product.php" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-4 py-2 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95">
            <i class="fas fa-plus mr-1"></i> Add Product
        </a>
        <a href="category.php" class="bg-gradient-to-r from-teal-500 to-cyan-500 text-white px-4 py-2 rounded-xl font-bold hover:shadow-lg hover:shadow-teal-500/20 transition-all active:scale-95">
            <i class="fas fa-plus mr-1"></i> Add Category
        </a>
        <a href="banner.php" class="bg-gradient-to-r from-sky-500 to-indigo-500 text-white px-4 py-2 rounded-xl font-bold hover:shadow-lg hover:shadow-sky-500/20 transition-all active:scale-95">
            <i class="fas fa-plus mr-1"></i> Add Banner
        </a>
        <a href="userreport.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-4 py-2 rounded-xl font-bold hover:shadow-lg hover:shadow-orange-500/20 transition-all active:scale-95">
            <i class="fas fa-flag mr-1"></i> View Reports
        </a>
        <a href="order.php" class="bg-gradient-to-r from-emerald-500 to-green-500 text-white px-4 py-2 rounded-xl font-bold hover:shadow-lg hover:shadow-emerald-500/20 transition-all active:scale-95">
            <i class="fas fa-list mr-1"></i> Manage Orders
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('growthChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Orders',
                data: <?= json_encode($orderCounts) ?>,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderRadius: 6,
                yAxisID: 'yOrders'
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
                tension: 0.3,
                yAxisID: 'yRevenue'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#cbd5e1' }
            }
        },
        scales: {
            yOrders: {
                type: 'linear',
                position: 'left',
                title: { display: true, text: 'Orders', color: '#f59e0b' },
                ticks: { color: '#f59e0b' },
                beginAtZero: true
            },
            yRevenue: {
                type: 'linear',
                position: 'right',
                title: { display: true, text: 'Revenue (₹)', color: '#2dd4bf' },
                ticks: { color: '#2dd4bf' },
                grid: { drawOnChartArea: false },
                beginAtZero: true
            },
            x: {
                ticks: { color: '#94a3b8' }
            }
        }
    }
});
</script>

<?php include 'common/bottom.php'; ?>