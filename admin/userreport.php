<?php
// admin/userreport.php – User Reports with Search & Status Toggle

// AJAX Toggle report status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'toggle_report') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');
    $id = intval($_POST['report_id']);
    $current = $conn->query("SELECT status FROM reports WHERE id=$id")->fetch_assoc();
    if ($current) {
        $newStatus = ($current['status'] == 'Pending') ? 'Responded' : 'Pending';
        $conn->query("UPDATE reports SET status='$newStatus' WHERE id=$id");
        echo json_encode(['success' => true, 'newStatus' => $newStatus, 'message' => 'Status updated.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Report not found.']);
    exit;
}

require_once 'common/header.php';
$reports = $conn->query("SELECT r.*, u.name AS user_name FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        User Reports
    </h2>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="reportSearch" placeholder="Search by ID, Name, Email, Phone or Message..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">ID</th>
                    <th class="p-4 font-semibold">User</th>
                    <th class="p-4 font-semibold">Phone</th>
                    <th class="p-4 font-semibold">Email</th>
                    <th class="p-4 font-semibold">Message</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold">Date</th>
                    <th class="p-4 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="reportsTableBody">
                <?php while ($r = $reports->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors report-row"
                    data-id="<?= $r['id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($r['name'] ?? '')) ?>"
                    data-email="<?= htmlspecialchars(strtolower($r['email'] ?? '')) ?>"
                    data-phone="<?= htmlspecialchars(strtolower($r['phone'] ?? '')) ?>"
                    data-message="<?= htmlspecialchars(strtolower($r['message'] ?? '')) ?>">
                    <td class="p-4 text-slate-200 font-mono text-xs">#<?= $r['id'] ?></td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($r['name'] ?? '—') ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($r['phone'] ?? '—') ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($r['email'] ?? '—') ?></td>
                    <td class="p-4 text-slate-300 max-w-[200px] truncate"><?= htmlspecialchars($r['message']) ?></td>
                    <td class="p-4">
                        <span class="inline-block text-xs font-semibold px-3 py-0.5 rounded-full border <?= $r['status'] == 'Pending' ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' ?>">
                            <?= $r['status'] ?>
                        </span>
                    </td>
                    <td class="p-4 text-slate-300 text-xs"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td class="p-4 text-right">
                        <button onclick="toggleStatus(<?= $r['id'] ?>, '<?= $r['status'] ?>')"
                                class="text-xs font-semibold px-3 py-1.5 rounded-xl transition-all active:scale-95 <?= $r['status'] == 'Pending' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30 hover:bg-amber-500/30' ?>">
                            <?= $r['status'] == 'Pending' ? 'Mark Responded' : 'Mark Pending' ?>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-5 right-5 z-[80] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md text-white">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
async function toggleStatus(reportId, currentStatus) {
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', 'toggle_report');
        formData.append('report_id', reportId);
        const res = await fetch('userreport.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || 'Toggle failed.', 'error');
            hideLoader();
        }
    } catch(e) {
        showToast('Network error.', 'error');
        hideLoader();
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMessage');
    const isSuccess = type === 'success';
    toast.classList.remove('translate-x-full', 'opacity-0', 'bg-emerald-500/90', 'bg-red-500/90');
    toast.classList.add(isSuccess ? 'bg-emerald-500/90' : 'bg-red-500/90', 'text-white');
    icon.innerHTML = isSuccess ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
    msg.textContent = message;
    toast.classList.add('translate-x-0', 'opacity-100');
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
    }, 2500);
}

// Live search filter for reports
document.getElementById('reportSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.report-row');
    rows.forEach(row => {
        const id = row.getAttribute('data-id');
        const name = row.getAttribute('data-name');
        const email = row.getAttribute('data-email');
        const phone = row.getAttribute('data-phone');
        const message = row.getAttribute('data-message');
        if (query === '' || id.includes(query) || name.includes(query) || email.includes(query) || phone.includes(query) || message.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>