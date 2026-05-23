<?php
// admin/user.php - Complete User Management with Search & Status Toggle
require_once 'common/header.php';

// AJAX toggle user status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'toggle_user') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');
    $id = intval($_POST['id']);
    $current = $conn->query("SELECT status FROM users WHERE id = $id")->fetch_assoc()['status'];
    $new = $current ? 0 : 1;
    $conn->query("UPDATE users SET status = $new WHERE id = $id");
    echo json_encode(['success' => true, 'newStatus' => $new, 'message' => 'User status updated.']);
    exit;
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Users
    </h2>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="userSearch" placeholder="Search by ID, Name, Email, Phone or Address..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">ID</th>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold">Email</th>
                    <th class="p-4 font-semibold">Phone</th>
                    <th class="p-4 font-semibold">Address</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="usersTableBody">
                <?php while($u = $users->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors user-row"
                    data-id="<?= $u['id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($u['name'])) ?>"
                    data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>"
                    data-phone="<?= htmlspecialchars(strtolower($u['phone'] ?? '')) ?>"
                    data-address="<?= htmlspecialchars(strtolower($u['address'] ?? '')) ?>">
                    <td class="p-4 text-slate-200 font-mono text-xs">#<?= $u['id'] ?></td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($u['name']) ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                    <td class="p-4 text-slate-300 max-w-[150px] truncate"><?= htmlspecialchars($u['address'] ?: '—') ?></td>
                    <td class="p-4">
                        <button onclick="toggleStatus(<?= $u['id'] ?>)"
                                class="text-xs font-semibold px-3 py-0.5 rounded-full border transition-all cursor-pointer <?= $u['status'] ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30 hover:bg-red-500/30' ?>">
                            <?= $u['status'] ? 'Active' : 'Blocked' ?>
                        </button>
                    </td>
                    <td class="p-4 text-right">
                        <!-- Extra actions (edit, delete) can be placed here -->
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
async function toggleStatus(userId) {
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', 'toggle_user');
        formData.append('id', userId);
        const res = await fetch('user.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            hideLoader();
            showToast('Toggle failed.', 'error');
        }
    } catch(e) {
        hideLoader();
        showToast('Network error.', 'error');
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

// Live search filter for users (ID, name, email, phone, address)
document.getElementById('userSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.user-row');
    rows.forEach(row => {
        const id = row.getAttribute('data-id');
        const name = row.getAttribute('data-name');
        const email = row.getAttribute('data-email');
        const phone = row.getAttribute('data-phone');
        const address = row.getAttribute('data-address');
        if (query === '' || id.includes(query) || name.includes(query) || email.includes(query) || phone.includes(query) || address.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>