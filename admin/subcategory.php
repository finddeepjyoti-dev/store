<?php
// DB connection only – no HTML
require_once __DIR__ . '/common/config.php';

// AJAX CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action == 'add' || $action == 'edit') {
        $cat_id = intval($_POST['cat_id']);
        $name   = $conn->real_escape_string($_POST['name']);
        $imgPath = '';

        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imgName = time() . '_subcat.' . $ext;
            $targetDir = '../images/subcategories/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imgName)) {
                $imgPath = 'images/subcategories/' . $imgName;
            }
        }

        if ($action == 'add') {
            $conn->query("INSERT INTO subcategories (cat_id, name, image) VALUES ($cat_id, '$name', '$imgPath')");
            $msg = 'Subcategory added successfully!';
        } else {
            $id = intval($_POST['id']);
            if ($imgPath) {
                $conn->query("UPDATE subcategories SET cat_id=$cat_id, name='$name', image='$imgPath' WHERE id=$id");
            } else {
                $conn->query("UPDATE subcategories SET cat_id=$cat_id, name='$name' WHERE id=$id");
            }
            $msg = 'Subcategory updated successfully!';
        }
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;

    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM subcategories WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Subcategory deleted successfully!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Normal page load
require_once 'common/header.php';

$categories = $conn->query("SELECT id, name FROM categories");
$subcategories = $conn->query("SELECT s.*, c.name AS cat_name FROM subcategories s JOIN categories c ON s.cat_id = c.id ORDER BY s.id DESC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Subcategories
    </h2>
    <button onclick="openModal('add')" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add Subcategory
    </button>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="subcatSearch" placeholder="Search by ID, Name or Category..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Image</th>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold">Category</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="subcatTableBody">
                <?php while ($s = $subcategories->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors subcat-row"
                    data-id="<?= $s['id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($s['name'])) ?>"
                    data-catname="<?= htmlspecialchars(strtolower($s['cat_name'])) ?>">
                    <td class="p-4">
                        <?php if (!empty($s['image'])): ?>
                            <img src="../<?= $s['image'] ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-600" onerror="this.style.display='none'">
                        <?php else: ?>
                            <span class="text-slate-500 text-xs">No image</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($s['name']) ?></td>
                    <td class="p-4 text-slate-400"><?= htmlspecialchars($s['cat_name']) ?></td>
                    <td class="p-4 text-right">
                        <button onclick='editSub(<?= $s['id'] ?>, <?= $s['cat_id'] ?>, <?= json_encode(htmlspecialchars($s['name'])) ?>)' class="text-amber-400 hover:text-amber-300 mr-3 transition-colors">
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
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-xl font-bold text-slate-100 mb-4" id="modalTitle">Add Subcategory</h3>
        <form id="form" enctype="multipart/form-data">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id" id="subId">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Category *</label>
                <select name="cat_id" id="catId" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Select Category</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Subcategory Name *</label>
                <input type="text" name="name" id="name" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Image</label>
                <input type="file" name="image" id="imageInput" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep current image when editing.</p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 rounded-xl font-bold hover:shadow-lg transition-all active:scale-95">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[70] p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-6 w-full max-w-sm text-center">
        <i class="fas fa-exclamation-triangle text-4xl text-amber-400 mb-4"></i>
        <h3 class="text-xl font-bold text-slate-100 mb-2">Delete Subcategory?</h3>
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
        setTimeout(() => {
            location.reload();
        }, 300);
    }, 2500);
}

function openModal(mode) {
    form.reset();
    document.getElementById('action').value = mode;
    document.getElementById('subId').value = '';
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Add Subcategory' : 'Edit Subcategory';
    modal.style.display = 'flex';
}

function closeModal() { modal.style.display = 'none'; }

function editSub(id, cat_id, name) {
    openModal('edit');
    document.getElementById('subId').value = id;
    document.getElementById('catId').value = cat_id;
    document.getElementById('name').value = name;
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
        const res = await fetch('subcategory.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'delete', id: pendingDeleteId })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Delete failed.', 'error');
        }
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
        const res = await fetch('subcategory.php', { method: 'POST', body: fd });
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

// Live search filter for subcategories
document.getElementById('subcatSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.subcat-row');
    rows.forEach(row => {
        const id = row.getAttribute('data-id');
        const name = row.getAttribute('data-name');
        const catName = row.getAttribute('data-catname');
        if (query === '' || id.includes(query) || name.includes(query) || catName.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>