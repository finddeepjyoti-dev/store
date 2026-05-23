<?php
// DB connection only – NO HTML output
require_once __DIR__ . '/common/config.php';

// AJAX CRUD – must run BEFORE any HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action == 'add' || $action == 'edit') {
        $title   = $conn->real_escape_string($_POST['title']);
        $link    = $conn->real_escape_string(trim($_POST['link'] ?? ''));
        $cat_id  = (isset($_POST['cat_id']) && $_POST['cat_id'] !== '') ? intval($_POST['cat_id']) : 'NULL';
        $imgPath = '';

        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imgName = time() . '_banner.' . $ext;
            $targetDir = '../images/banners/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imgName)) {
                $imgPath = 'images/banners/' . $imgName;
            }
        }

        if ($action == 'add') {
            $conn->query("INSERT INTO banners (title, image, link, cat_id) 
                          VALUES ('$title', '$imgPath', '$link', $cat_id)");
            $msg = 'Banner added successfully!';
        } else {
            $id = intval($_POST['id']);
            if ($imgPath) {
                $conn->query("UPDATE banners SET title='$title', image='$imgPath', link='$link', cat_id=$cat_id WHERE id=$id");
            } else {
                $conn->query("UPDATE banners SET title='$title', link='$link', cat_id=$cat_id WHERE id=$id");
            }
            $msg = 'Banner updated successfully!';
        }
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;

    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM banners WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Banner deleted successfully!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Normal page load – now include the admin header (starts HTML)
require_once 'common/header.php';

// Fetch categories and banners for display
$categories = $conn->query("SELECT id, name FROM categories");
$banners = $conn->query("SELECT b.*, c.name AS cat_name FROM banners b LEFT JOIN categories c ON b.cat_id = c.id ORDER BY b.sort_order ASC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Banners
    </h2>
    <button onclick="openModal('add')" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add Banner
    </button>

    <!-- Banners Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Image</th>
                    <th class="p-4 font-semibold">Title</th>
                    <th class="p-4 font-semibold">Link</th>
                    <th class="p-4 font-semibold">Category</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
                <?php while ($b = $banners->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors">
                    <td class="p-4">
                        <?php if(!empty($b['image'])): ?>
                            <img src="../<?= $b['image'] ?>" class="w-16 h-12 object-cover rounded-lg border border-slate-600" onerror="this.style.display='none'">
                        <?php else: ?>
                            <span class="text-slate-500 text-xs">No image</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($b['title']) ?></td>
                    <td class="p-4 text-slate-400 truncate max-w-[150px]"><?= $b['link'] ?: '—' ?></td>
                    <td class="p-4 text-slate-400"><?= $b['cat_name'] ?: 'All' ?></td>
                    <td class="p-4 text-right">
                        <button onclick='editBanner(<?= $b['id'] ?>, <?= json_encode(htmlspecialchars($b['title'])) ?>, <?= json_encode($b['link']) ?>, <?= $b['cat_id'] ?? 'null' ?>)' class="text-amber-400 hover:text-amber-300 mr-3 transition-colors">
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
<div id="bannerModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <h3 class="text-xl font-bold text-slate-100 mb-4" id="modalTitle">Add Banner</h3>
        <form id="bannerForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" id="bannerAction" value="add">
            <input type="hidden" name="id" id="bannerId">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Title *</label>
                <input type="text" name="title" id="bannerTitle" required 
                       class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Click Link (URL)</label>
                <input type="url" name="link" id="bannerLink" placeholder="https://..." 
                       class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Category (optional)</label>
                <select name="cat_id" id="bannerCat" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">All Categories</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Banner Image *</label>
                <input type="file" name="image" id="bannerImage" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep current image (when editing).</p>
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
        <h3 class="text-xl font-bold text-slate-100 mb-2">Delete Banner?</h3>
        <p class="text-sm text-slate-400 mb-6">This action cannot be undone.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeConfirm()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
            <button id="confirmDeleteBtn" class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl font-bold hover:shadow-lg transition-all active:scale-95">Delete</button>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div id="toast" class="fixed top-5 right-5 z-[80] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
const modal = document.getElementById('bannerModal');
const form = document.getElementById('bannerForm');
const toast = document.getElementById('toast');
const toastIcon = document.getElementById('toastIcon');
const toastMsg = document.getElementById('toastMessage');
const confirmModal = document.getElementById('confirmModal');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
let pendingDeleteId = null;

function showToast(message, type = 'success') {
    const isSuccess = type === 'success';
    toast.classList.remove('translate-x-full', 'opacity-0', 'bg-emerald-500/90', 'bg-red-500/90', 'text-white');
    toast.classList.add(isSuccess ? 'bg-emerald-500/90' : 'bg-red-500/90', 'text-white');
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
    document.getElementById('bannerAction').value = mode;
    document.getElementById('bannerId').value = '';
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Add Banner' : 'Edit Banner';
    document.getElementById('bannerImage').required = (mode === 'add');
    modal.style.display = 'flex';
}

function closeModal() { modal.style.display = 'none'; }

function editBanner(id, title, link, catId) {
    openModal('edit');
    document.getElementById('bannerId').value = id;
    document.getElementById('bannerTitle').value = title;
    document.getElementById('bannerLink').value = link || '';
    document.getElementById('bannerCat').value = catId || '';
    document.getElementById('bannerImage').required = false;
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
        const res = await fetch('banner.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'delete', id: pendingDeleteId })
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch (e) { showToast('Server error – please reload.', 'error'); return; }
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
        const res = await fetch('banner.php', { method: 'POST', body: fd });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch (e) { showToast('Server error – please reload.', 'error'); return; }
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
</script>

<?php include 'common/bottom.php'; ?>