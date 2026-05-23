<?php
// DB connection only, no HTML
require_once __DIR__ . '/common/config.php';

// AJAX CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action == 'add' || $action == 'edit') {
        $name    = $conn->real_escape_string($_POST['name']);
        $phone   = $conn->real_escape_string($_POST['phone']);
        $address = $conn->real_escape_string($_POST['address']);
        $email   = $conn->real_escape_string($_POST['email']);
        $pass    = $_POST['password'] ?? '';

        if ($action == 'add') {
            if (empty($pass)) {
                echo json_encode(['success' => false, 'message' => 'Password is required']);
                exit;
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO delivery_boys (name, phone, address, email, password) VALUES ('$name','$phone','$address','$email','$hash')");
            $msg = 'Delivery boy added successfully!';
        } else {
            $id = intval($_POST['id']);
            if (!empty($pass)) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $conn->query("UPDATE delivery_boys SET name='$name', phone='$phone', address='$address', email='$email', password='$hash' WHERE id=$id");
            } else {
                $conn->query("UPDATE delivery_boys SET name='$name', phone='$phone', address='$address', email='$email' WHERE id=$id");
            }
            $msg = 'Details updated successfully!';
        }
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;

    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM delivery_boys WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Delivery boy deleted!']);
        exit;

    } elseif ($action == 'toggle') {
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT status FROM delivery_boys WHERE id=$id")->fetch_assoc();
        if ($res) {
            $newStatus = $res['status'] ? 0 : 1;
            $conn->query("UPDATE delivery_boys SET status=$newStatus WHERE id=$id");
            echo json_encode(['success' => true, 'newStatus' => $newStatus]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Normal page
require_once 'common/header.php';
$boys = $conn->query("SELECT * FROM delivery_boys ORDER BY id DESC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Delivery Boys
    </h2>
    <button onclick="openModal('add')" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add Delivery Boy
    </button>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="deliverySearch" placeholder="Search by ID, Name, Email or Phone..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold">Phone</th>
                    <th class="p-4 font-semibold">Email</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="deliveryTableBody">
                <?php while ($b = $boys->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors delivery-row"
                    data-id="<?= $b['id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($b['name'])) ?>"
                    data-email="<?= htmlspecialchars(strtolower($b['email'])) ?>"
                    data-phone="<?= htmlspecialchars(strtolower($b['phone'])) ?>">
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($b['name']) ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($b['phone']) ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($b['email']) ?></td>
                    <td class="p-4">
                        <button onclick="toggleStatus(<?= $b['id'] ?>)" class="text-xs font-semibold px-3 py-0.5 rounded-full border transition-all <?= $b['status'] ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30 hover:bg-red-500/30' ?>">
                            <?= $b['status'] ? 'Active' : 'Blocked' ?>
                        </button>
                    </td>
                    <td class="p-4 text-right">
                        <button onclick='editBoy(<?= $b['id'] ?>, <?= json_encode(htmlspecialchars($b['name'])) ?>, <?= json_encode(htmlspecialchars($b['phone'])) ?>, <?= json_encode(htmlspecialchars($b['address'])) ?>, <?= json_encode(htmlspecialchars($b['email'])) ?>)' class="text-amber-400 hover:text-amber-300 mr-3 transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $b['id'] ?>)" class="text-red-400 hover:text-red-300 transition-colors">
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
        <h3 class="text-xl font-bold text-slate-100 mb-4" id="modalTitle">Add Delivery Boy</h3>
        <form id="form">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id" id="boyId">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Name *</label>
                <input type="text" name="name" id="name" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Phone *</label>
                <input type="text" name="phone" id="phone" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Address *</label>
                <textarea name="address" id="address" rows="2" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Email *</label>
                <input type="email" name="email" id="email" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Password <span class="text-amber-400" id="passReq">*</span></label>
                <input type="password" name="password" id="password" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep current password (when editing).</p>
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
        <h3 class="text-xl font-bold text-slate-100 mb-2">Delete Delivery Boy?</h3>
        <p class="text-sm text-slate-400 mb-6">This action cannot be undone.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeConfirm()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
            <button id="confirmDeleteBtn" class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl font-bold hover:shadow-lg transition-all active:scale-95">Delete</button>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div id="toast" class="fixed top-5 right-5 z-[80] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md text-white">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
const modal = document.getElementById('modal');
const form = document.getElementById('form');
const toast = document.getElementById('toast');
const toastIcon = document.getElementById('toastIcon');
const toastMsg = document.getElementById('toastMessage');
const confirmModal = document.getElementById('confirmModal');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
let pendingDeleteId = null;

function showToast(message, type = 'success') {
    const isSuccess = type === 'success';
    toast.classList.remove('translate-x-full', 'opacity-0', 'bg-emerald-500/90', 'bg-red-500/90');
    toast.classList.add(isSuccess ? 'bg-emerald-500/90' : 'bg-red-500/90');
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
    document.getElementById('boyId').value = '';
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Add Delivery Boy' : 'Edit Delivery Boy';
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

function editBoy(id, name, phone, address, email) {
    openModal('edit');
    document.getElementById('boyId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('phone').value = phone;
    document.getElementById('address').value = address;
    document.getElementById('email').value = email;
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
        const res = await fetch('deliveryboy_register.php', {
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
        const res = await fetch('deliveryboy_register.php', { method: 'POST', body: fd });
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

async function toggleStatus(id) {
    showLoader();
    try {
        const res = await fetch('deliveryboy_register.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'toggle', id: id })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    } catch(e) { 
        hideLoader(); 
    }
}

// Live search filter
document.getElementById('deliverySearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.delivery-row');
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

modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
confirmModal.addEventListener('click', function(e) { if (e.target === confirmModal) closeConfirm(); });
</script>

<?php include 'common/bottom.php'; ?>