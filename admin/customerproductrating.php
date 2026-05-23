<?php
require_once 'common/header.php';

// Get filter value (1-5 or 'all')
$filterStar = $_GET['star'] ?? 'all';
$starCondition = '';
if ($filterStar !== 'all' && in_array($filterStar, ['1','2','3','4','5'])) {
    $starCondition = "WHERE r.star = " . intval($filterStar);
}

// Fetch ratings with user and product info
$ratings = $conn->query("
    SELECT r.id, r.star, r.message, r.created_at, 
           u.name AS user_name, p.name AS product_name, p.id AS product_id
    FROM productratingreport r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    $starCondition
    ORDER BY r.created_at DESC
");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Customer Product Ratings
    </h2>

    <!-- Star Filter -->
    <div class="mb-6">
        <label class="block text-sm font-semibold text-slate-300 mb-2">Filter by Star Rating</label>
        <select id="starFilter" class="bg-slate-700 border border-slate-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-amber-400" onchange="applyFilter()">
            <option value="all" <?= $filterStar == 'all' ? 'selected' : '' ?>>All Ratings</option>
            <option value="5" <?= $filterStar == '5' ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ 5 Stars</option>
            <option value="4" <?= $filterStar == '4' ? 'selected' : '' ?>>⭐⭐⭐⭐ 4 Stars</option>
            <option value="3" <?= $filterStar == '3' ? 'selected' : '' ?>>⭐⭐⭐ 3 Stars</option>
            <option value="2" <?= $filterStar == '2' ? 'selected' : '' ?>>⭐⭐ 2 Stars</option>
            <option value="1" <?= $filterStar == '1' ? 'selected' : '' ?>>⭐ 1 Star</option>
        </select>
    </div>

    <!-- Ratings Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">ID</th>
                    <th class="p-4 font-semibold">User</th>
                    <th class="p-4 font-semibold">Product</th>
                    <th class="p-4 font-semibold">Star</th>
                    <th class="p-4 font-semibold">Message</th>
                    <th class="p-4 font-semibold">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
                <?php if ($ratings->num_rows == 0): ?>
                <tr>
                    <td colspan="6" class="p-6 text-center text-slate-500">
                        <i class="fas fa-star text-3xl mb-2 block opacity-50"></i>
                        No ratings found.
                    </td>
                </tr>
                <?php endif; ?>

                <?php while ($r = $ratings->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors">
                    <td class="p-4 text-slate-200 font-mono text-xs">#<?= $r['id'] ?></td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($r['user_name']) ?></td>
                    <td class="p-4 text-slate-300">
                        <a href="product_detail.php?id=<?= $r['product_id'] ?>" class="text-amber-400 hover:text-amber-300">
                            <?= htmlspecialchars($r['product_name']) ?>
                        </a>
                    </td>
                    <td class="p-4">
                        <div class="flex gap-0.5">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fas fa-star <?= $s <= $r['star'] ? 'text-amber-400' : 'text-slate-600' ?> text-xs"></i>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td class="p-4 text-slate-300 max-w-xs truncate"><?= htmlspecialchars($r['message'] ?? '—') ?></td>
                    <td class="p-4 text-slate-400 text-xs"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyFilter() {
    const star = document.getElementById('starFilter').value;
    window.location.href = 'customerproductrating.php?star=' + star;
}
</script>

<?php include 'common/bottom.php'; ?>