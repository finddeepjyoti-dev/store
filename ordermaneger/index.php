<?php
// admin/completeorder.php
require_once 'common/header.php';

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$fromDate     = $_GET['from_date'] ?? '';
$toDate       = $_GET['to_date'] ?? '';
$search       = $_GET['search'] ?? '';

$whereParts = [];

// Status filter
if ($statusFilter !== 'all' && in_array($statusFilter, ['Placed', 'Dispatched', 'Delivered', 'Cancelled'])) {
    $whereParts[] = "o.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

// Date range filter
if (!empty($fromDate) && !empty($toDate)) {
    $fromDateEsc = $conn->real_escape_string($fromDate);
    $toDateEsc   = $conn->real_escape_string($toDate);
    $whereParts[] = "DATE(o.created_at) BETWEEN '$fromDateEsc' AND '$toDateEsc'";
} elseif (!empty($fromDate)) {
    $fromDateEsc = $conn->real_escape_string($fromDate);
    $whereParts[] = "DATE(o.created_at) >= '$fromDateEsc'";
} elseif (!empty($toDate)) {
    $toDateEsc   = $conn->real_escape_string($toDate);
    $whereParts[] = "DATE(o.created_at) <= '$toDateEsc'";
}

// Build where clause
$whereSQL = '';
if (!empty($whereParts)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereParts);
}

$orders = $conn->query("
    SELECT o.*, u.name AS user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $whereSQL
    ORDER BY o.created_at ASC
");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        All Orders
    </h2>

    <!-- Filters & Search -->
    <form method="get" class="flex flex-col sm:flex-row gap-4 mb-6 flex-wrap items-end">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Status</label>
            <select name="status" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-amber-400 w-full" onchange="this.form.submit()">
                <option value="all" <?= $statusFilter == 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="Placed" <?= $statusFilter == 'Placed' ? 'selected' : '' ?>>Placed</option>
                <option value="Dispatched" <?= $statusFilter == 'Dispatched' ? 'selected' : '' ?>>Dispatched</option>
                <option value="Delivered" <?= $statusFilter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="Cancelled" <?= $statusFilter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">From Date</label>
            <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-amber-400 w-full">
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">To Date</label>
            <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-amber-400 w-full">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-slate-400 mb-1">Search</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" id="orderSearch" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Order ID, Product or User..."
                       class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
        </div>
        <button type="submit" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg transition-all active:scale-95">
            Apply Filters
        </button>
    </form>

    <!-- Orders Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Order ID</th>
                    <th class="p-4 font-semibold">User</th>
                    <th class="p-4 font-semibold">Amount</th>
                    <th class="p-4 font-semibold">Payment</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold">Date</th>
                    <th class="p-4 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="ordersTableBody">
                <?php if ($orders->num_rows == 0): ?>
                <tr>
                    <td colspan="7" class="p-6 text-center text-slate-500">
                        <i class="fas fa-box-open text-3xl mb-2 block opacity-50"></i>
                        No orders found.
                    </td>
                </tr>
                <?php endif; ?>
                <?php while ($o = $orders->fetch_assoc()): 
                    // Pre-fetch product names for this order
                    $prodRes = $conn->query("SELECT p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = {$o['id']}");
                    $prodNames = [];
                    while ($p = $prodRes->fetch_assoc()) $prodNames[] = strtolower($p['name']);
                    $prodString = htmlspecialchars(implode(' ', $prodNames));
                ?>
                <tr class="hover:bg-slate-700/40 transition-colors order-row"
                    data-order-id="<?= $o['id'] ?>"
                    data-user-name="<?= htmlspecialchars(strtolower($o['user_name'])) ?>"
                    data-products="<?= $prodString ?>">
                    <td class="p-4 text-slate-200 font-mono text-xs">#<?= $o['id'] ?></td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($o['user_name']) ?></td>
                    <td class="p-4 text-slate-100 font-semibold">₹<?= number_format($o['total_amount'], 2) ?></td>
                    <td class="p-4">
                        <?php if ($o['payment_method'] == 'QR'): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-teal-500/20 text-teal-400 border border-teal-500/30">QR</span>
                                <?php if (!empty($o['screenshot'])): ?>
                                    <img src="../<?= $o['screenshot'] ?>" class="w-8 h-8 rounded-lg object-cover border border-slate-600 cursor-pointer hover:scale-105 transition-transform" onclick="window.open('../<?= $o['screenshot'] ?>','_blank')" title="View payment screenshot">
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-xs font-medium text-slate-400 bg-slate-700/50 px-2 py-0.5 rounded-full border border-slate-600">COD</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <?php
                        $statusClass = match($o['status']) {
                            'Placed'     => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                            'Dispatched' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                            'Delivered'  => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                            'Cancelled'  => 'bg-red-500/20 text-red-400 border-red-500/30',
                            default      => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
                        };
                        ?>
                        <span class="inline-block text-xs font-semibold px-3 py-0.5 rounded-full border <?= $statusClass ?>"><?= $o['status'] ?></span>
                    </td>
                    <td class="p-4 text-slate-300 text-xs"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                    <td class="p-4 text-right">
                        <a href="order_detail.php?id=<?= $o['id'] ?>" class="inline-flex items-center text-amber-400 hover:text-amber-300 transition-colors">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Live search filter (instant filtering)
document.getElementById('orderSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.order-row');
    rows.forEach(row => {
        const orderId = '#' + row.getAttribute('data-order-id');
        const userName = row.getAttribute('data-user-name');
        const products = row.getAttribute('data-products');
        if (query === '' || orderId.includes(query) || userName.includes(query) || products.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});

// Trigger the live search on page load to apply the search parameter from URL
(function() {
    const searchInput = document.getElementById('orderSearch');
    if (searchInput.value.trim() !== '') {
        searchInput.dispatchEvent(new Event('input'));
    }
})();
</script>

<?php include 'common/bottom.php'; ?>