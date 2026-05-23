<?php
// DB connection only – no HTML
require_once __DIR__ . '/common/config.php';

// AJAX CRUD (must run BEFORE any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $name        = $conn->real_escape_string($_POST['name']);
        $designation = $conn->real_escape_string($_POST['designation']); // 'inserter' or 'ordermanager'
        $address     = $conn->real_escape_string($_POST['address']);
        $phone       = $conn->real_escape_string($_POST['phone']);
        $email       = $conn->real_escape_string($_POST['email']);
        $password    = $_POST['password'] ?? ''; // plain text, never hashed (as requested)

        if ($action === 'add') {
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required.']);
                exit;
            }
            $conn->query("INSERT INTO staff (name, designation, address, phone, email, password) 
                          VALUES ('$name', '$designation', '$address', '$phone', '$email', '$password')");
            $msg = 'Staff added successfully!';
        } else {
            $id = intval($_POST['id']);
            if (!empty($password)) {
                $conn->query("UPDATE staff SET name='$name', designation='$designation', address='$address', 
                              phone='$phone', email='$email', password='$password' WHERE id=$id");
            } else {
                $conn->query("UPDATE staff SET name='$name', designation='$designation', address='$address', 
                              phone='$phone', email='$email' WHERE id=$id");
            }
            $msg = 'Staff updated successfully!';
        }
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;

    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM staff WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Staff deleted successfully!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Normal page load
require_once 'common/header.php';

// Fetch all staff ordered by ID
$staffRes = $conn->query("SELECT * FROM staff ORDER BY id ASC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Staff Management
    </h2>
    <button onclick="openModal('add')" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add Staff
    </button>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="staffSearch" placeholder="Search by ID, Name, Email or Phone..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <!-- Staff Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold">Designation</th>
                    <th class="p-4 font-semibold">Phone</th>
                    <th class="p-4 font-semibold">Email</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="staffTableBody">
                <?php while ($s = $staffRes->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors staff-row"
                    data-id="<?= $s['id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($s['name'])) ?>"
                    data-email="<?= htmlspecialchars(strtolower($s['email'] ?? '')) ?>"
                    data-phone="<?= htmlspecialchars(strtolower($s['phone'] ?? '')) ?>">
                    <td class="p-4 text-slate-200 font-medium">
                        <?= htmlspecialchars($s['name']) ?>
                        <?php if (!empty($s['address'])): ?>
                            <span class="block text-xs text-slate-400"><?= htmlspecialchars($s['address']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <span class="inline-block text-xs font-semibold px-3 py-0.5 rounded-full border 
                            <?= $s['designation'] === 'ordermanager' ? 'bg-teal-500/20 text-teal-400 border-teal-500/30' : 'bg-amber-500/20 text-amber-400 border-amber-500/30' ?>">
                            <?= ucfirst(htmlspecialchars($s['designation'])) ?>
                        </span>
                    </td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($s['email'] ?: '—') ?></td>
                    <td class="p-4 text-right">
                        <button onclick='editStaff(<?= $s['id'] ?>, <?= json_encode(htmlspecialchars($s['name'])) ?>, <?= json_encode($s['designation']) ?>, <?= json_encode(htmlspecialchars($s['address'] ?? '')) ?>, <?= json_encode(htmlspecialchars($s['phone'] ?? '')) ?>, <?= json_encode(htmlspecialchars($s['email'] ?? '')) ?>)' class="text-amber-400 hover:text-amber-300 mr-3 transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $s['id'] ?>)" class="text-red-400 hover:text-red-300 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Modal -->
<div id="modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <h3 class="text-xl font-bold text-slate-100 mb-4" id="modalTitle">Add Staff</h3>
        <form id="staffForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id" id="staffId">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Full Name *</label>
                <input type="text" name="name" id="name" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Designation *</label>
                <select name="designation" id="designation" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="inserter">Inserter (Data Entry)</option>
                    <option value="ordermanager">Order Manager</option>
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Address</label>
                <textarea name="address" id="address" rows="2" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">Phone</label>
                    <input type="text" name="phone" id="phone" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="email" id="email" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Password <span class="text-amber-400" id="passReq">*</span></label>
                <input type="password" name="password" id="password" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep current password when editing.</p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[70] p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-6 w-full max-w-sm text-center">
        <i class="fas fa-exclamation-triangle text-4xl text-amber-400 mb-4"></i>
        <h3 class="text-xl font-bold text-slate-100 mb-2">Delete Staff Member?</h3>
        <p class="text-sm text-slate-400 mb-6">This action cannot be undone.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeConfirm()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
            <button id="confirmDeleteBtn" class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl font-bold hover:shadow-lg transition-all active:scale-95">Delete</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-5 right-5 z-[80] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md text-white">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
const modal = document.getElementById('modal');
const form = document.getElementById('staffForm');
const toast = document.getElementById('toast');
const toastIcon = document.getElementById('toastIcon');
const toastMsg = document.getElementById('toastMessage');
const confirmModal = document.getElementById('confirmModal');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
let pendingDeleteId = null;

function showToast(message, type = 'success') {
    const isSuccess = type === 'success';
    toast.classList.remove('translate-x-full', 'opacity-0', 'bg-emerald-500/90', 'bg-red-500/90');
    toast.classList.add(isSuccess ? 'bg-emerald-500/90' : 'bg-red-500/90', 'text-white');
    toastIcon.innerHTML = isSuccess ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
    toastMsg.textContent = message;
    toast.classList.add('translate-x-0', 'opacity-100');
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => location.reload(), 300);
    }, 2500);
}

function openModal(mode) {
    form.reset();
    document.getElementById('action').value = mode;
    document.getElementById('staffId').value = '';
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Add Staff' : 'Edit Staff';
    const passReq = document.getElementById('passReq');
    const passInput = document.getElementById('password');
    if (mode === 'add') {
        passReq.classList.remove('hidden');
        passInput.required = true;
    } else {
        passReq.classList.add('hidden');
        passInput.required = false;
    }
    modal.style.display = 'flex';
}

function closeModal() { modal.style.display = 'none'; }

function editStaff(id, name, designation, address, phone, email) {
    openModal('edit');
    document.getElementById('staffId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('designation').value = designation;
    document.getElementById('address').value = address || '';
    document.getElementById('phone').value = phone || '';
    document.getElementById('email').value = email || '';
    document.getElementById('password').required = false;
}

function confirmDelete(id) {
    pendingDeleteId = id;
    confirmModal.style.display = 'flex';
}

function closeConfirm() {
    confirmModal.style.display = 'none';
    pendingDeleteId = null;
}

confirmDeleteBtn.addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    closeConfirm();
    showLoader();
    try {
        const res = await fetch('staffregister.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'delete', id: pendingDeleteId })
        });
        const data = await res.json();
        if (data.success) showToast(data.message, 'success');
        else showToast(data.message || 'Delete failed.', 'error');
    } catch (e) {
        showToast('Network error.', 'error');
    } finally {
        hideLoader();
    }
});

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoader();
    try {
        const fd = new FormData(form);
        const res = await fetch('staffregister.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeModal();
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Operation failed.', 'error');
        }
    } catch (err) {
        showToast('Network error.', 'error');
    } finally {
        hideLoader();
    }
});

modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
confirmModal.addEventListener('click', function(e) { if (e.target === confirmModal) closeConfirm(); });

// Live search filter for staff
document.getElementById('staffSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.staff-row');
    rows.forEach(row => {
        const id = row.getAttribute('data-id');
        const name = row.getAttribute('data-name');
        const email = row.getAttribute('data-email');
        const phone = row.getAttribute('data-phone');
        if (query === '' || id.includes(query) || name.includes(query) || email.includes(query) || phone.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>