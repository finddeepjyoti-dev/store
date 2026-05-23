<?php
// DB connection only – NO HTML output
require_once __DIR__ . '/common/config.php';

// ---------- AJAX CRUD ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    @ob_end_clean();
    ob_start();
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action == 'add') {
        $companyName = $conn->real_escape_string($_POST['company_name']);
        $imgPath = '';

        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imgName = time() . '_qr.' . $ext;
            $targetDir = '../images/qr/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imgName)) {
                $imgPath = 'images/qr/' . $imgName;
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Please select an image.']);
            exit;
        }

        // Default status = 1 (active)
        $conn->query("INSERT INTO qr_codes (image, company_name, status) VALUES ('$imgPath', '$companyName', 1)");
        echo json_encode(['success' => true, 'message' => 'QR code added successfully!']);
        exit;

    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT image FROM qr_codes WHERE id=$id")->fetch_assoc();
        if ($res && !empty($res['image'])) {
            @unlink('../' . $res['image']);
        }
        $conn->query("DELETE FROM qr_codes WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'QR code deleted successfully!']);
        exit;

    } elseif ($action == 'toggle') {
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT status FROM qr_codes WHERE id=$id")->fetch_assoc();
        if ($res) {
            $newStatus = $res['status'] ? 0 : 1;
            $conn->query("UPDATE qr_codes SET status=$newStatus WHERE id=$id");
            echo json_encode(['success' => true, 'newStatus' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'message' => 'QR code not found.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ---------- Normal page load ----------
require_once 'common/header.php';

$qrCodes = $conn->query("SELECT * FROM qr_codes ORDER BY id DESC");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        QR Scanner Codes
    </h2>

    <button onclick="openModal()" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add QR Code
    </button>

    <!-- QR Codes Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">ID</th>
                    <th class="p-4 font-semibold">Image</th>
                    <th class="p-4 font-semibold">Company Name</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
                <?php if ($qrCodes->num_rows == 0): ?>
                <tr>
                    <td colspan="5" class="p-6 text-center text-slate-500">
                        <i class="fas fa-qrcode text-3xl mb-2 block opacity-50"></i>
                        No QR codes found.
                    </td>
                </tr>
                <?php endif; ?>
                <?php while ($qr = $qrCodes->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors">
                    <td class="p-4 text-slate-200 font-mono text-xs">#<?= $qr['id'] ?></td>
                    <td class="p-4">
                        <?php if (!empty($qr['image'])): ?>
                            <img src="../<?= $qr['image'] ?>" class="w-16 h-16 object-cover rounded-lg border border-slate-600">
                        <?php else: ?>
                            <span class="text-slate-500 text-xs">No image</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($qr['company_name']) ?></td>
                    <td class="p-4">
                        <button onclick="toggleStatus(<?= $qr['id'] ?>)" class="text-xs font-semibold px-3 py-0.5 rounded-full border transition-all <?= $qr['status'] ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30 hover:bg-red-500/30' ?>">
                            <?= $qr['status'] ? 'Active' : 'Inactive' ?>
                        </button>
                    </td>
                    <td class="p-4 text-right">
                        <button onclick="confirmDelete(<?= $qr['id'] ?>)" class="text-red-400 hover:text-red-300 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="qrModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-xl font-bold text-slate-100 mb-4">Add QR Code</h3>
        <form id="qrForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" value="add">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Company Name *</label>
                <input type="text" name="company_name" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">QR Image *</label>
                <input type="file" name="image" required accept="image/*" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all">
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
        <h3 class="text-xl font-bold text-slate-100 mb-2">Delete QR Code?</h3>
        <p class="text-sm text-slate-400 mb-6">This action cannot be undone.</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeConfirm()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
            <button id="confirmDeleteBtn" class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl font-bold hover:shadow-lg transition-all active:scale-95" onclick="processDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div id="toast" class="fixed top-5 right-5 z-[80] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md text-white">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
const modal = document.getElementById('qrModal');
const form = document.getElementById('qrForm');
const toast = document.getElementById('toast');
const toastIcon = document.getElementById('toastIcon');
const toastMsg = document.getElementById('toastMessage');
const confirmModal = document.getElementById('confirmModal');
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

function openModal() {
    form.reset();
    modal.style.display = 'flex';
}

function closeModal() { modal.style.display = 'none'; }

function confirmDelete(id) {
    pendingDeleteId = id;
    confirmModal.style.display = 'flex';
}

function closeConfirm() {
    confirmModal.style.display = 'none';
    pendingDeleteId = null;
}

async function toggleStatus(id) {
    showLoader();
    try {
        const params = new URLSearchParams();
        params.append('ajax', '1');
        params.append('action', 'toggle');
        params.append('id', id);
        const res = await fetch('QRscener.php', { method: 'POST', body: params });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || 'Toggle failed.', 'error');
        }
    } catch (e) {
        showToast('Network error.', 'error');
    } finally {
        hideLoader();
    }
}

async function processDelete() {
    if (!pendingDeleteId) return;
    closeConfirm();
    showLoader();
    try {
        const params = new URLSearchParams();
        params.append('ajax', '1');
        params.append('action', 'delete');
        params.append('id', pendingDeleteId);
        const res = await fetch('QRscener.php', {
            method: 'POST',
            body: params
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
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoader();
    try {
        const fd = new FormData(form);
        const res = await fetch('QRscener.php', { method: 'POST', body: fd });
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
</script>

<?php include 'common/bottom.php'; ?>