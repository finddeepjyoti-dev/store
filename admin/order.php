<?php require_once 'common/header.php';
$orders = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Orders
    </h2>

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
            <tbody class="divide-y divide-slate-700/30">
                <?php while($o = $orders->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors">
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
                    <td class="p-4 text-slate-300"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
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

<?php include 'common/bottom.php'; ?>