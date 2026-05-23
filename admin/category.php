<?php
// Load DB connection FIRST, without any HTML
require_once __DIR__ . '/common/config.php';

// ---------- AJAX CRUD (no HTML output) ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action == 'add' || $action == 'edit') {
        $name = $conn->real_escape_string($_POST['name']);
        $imagePath = '';

        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imgName = time() . '.' . $ext;
            $targetDir = '../images/categories/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imgName)) {
                $imagePath = 'images/categories/' . $imgName;
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
                exit;
            }
        }

        if ($action == 'add') {
            $conn->query("INSERT INTO categories (name, image) VALUES ('$name', '$imagePath')");
            $msg = 'Category added successfully!';
        } else {
            $id = intval($_POST['id']);
            if ($imagePath) {
                $conn->query("UPDATE categories SET name='$name', image='$imagePath' WHERE id=$id");
            } else {
                $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
            }
            $msg = 'Category updated successfully!';
        }
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;

    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM categories WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ---------- Normal page load ----------
require_once 'common/header.php';

$categories = $conn->query("SELECT * FROM categories");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Categories
    </h2>
    <button onclick="openModal()" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add Category
    </button>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="categorySearch" placeholder="Search by ID or Name..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Image</th>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="catTable">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <tr data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>" class="hover:bg-slate-700/40 transition-colors cat-row">
                    <td class="p-4">
                        <?php
                        $src = '';
                        if ($cat['image']) {
                            if (filter_var($cat['image'], FILTER_VALIDATE_URL)) {
                                $src = $cat['image'];
                            } else {
                                $src = '../' . $cat['image'];
                            }
                        }
                        ?>
                        <?php if ($src): ?>
                            <img src="<?= $src ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-600" onerror="this.style.display='none'">
                        <?php else: ?>
                            <span class="text-slate-500 text-xs">No image</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-slate-200 font-medium"><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="p-4 text-right">
                        <button onclick="editCat(<?= $cat['id'] ?>,'<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')" class="text-amber-400 hover:text-amber-300 mr-3 transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteCat(<?= $cat['id'] ?>)" class="text-red-400 hover:text-red-300 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="catModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold text-slate-100 mb-4" id="modalTitle">Add Category</h3>
        <form id="catForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" value="add" id="catAction">
            <input type="hidden" name="id" id="catId">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Name *</label>
                <input type="text" name="name" placeholder="Category Name" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Image</label>
                <input type="file" name="image" id="catImageInput" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep current image when editing.</p>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 rounded-xl font-bold hover:shadow-lg transition-all active:scale-95">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-5 right-5 z-[60] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md bg-emerald-500/90 text-white">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage" class="font-medium">Success</span>
</div>

<script>
const modal = document.getElementById('catModal');
const toast = document.getElementById('toast');
const toastMessage = document.getElementById('toastMessage');

function showToast(message, type = 'success') {
    const isSuccess = type === 'success';
    toast.classList.remove('translate-x-full', 'opacity-0', 'bg-emerald-500/90', 'bg-red-500/90', 'text-white');
    toast.classList.add(isSuccess ? 'bg-emerald-500/90' : 'bg-red-500/90', 'text-white');
    toast.querySelector('i').className = isSuccess ? 'fas fa-check-circle' : 'fas fa-times-circle';
    toastMessage.textContent = message;
    toast.classList.add('translate-x-0', 'opacity-100');

    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            location.reload();
        }, 300);
    }, 2500);
}

function openModal(){
    modal.style.display='flex';
    document.getElementById('catForm').reset();
    document.getElementById('catAction').value='add';
    document.getElementById('modalTitle').textContent='Add Category';
    document.getElementById('catImageInput').required = true;
}
function closeModal(){ modal.style.display='none'; }

function editCat(id, name){
    openModal();
    document.getElementById('catAction').value='edit';
    document.getElementById('catId').value=id;
    document.querySelector('[name="name"]').value=name;
    document.getElementById('modalTitle').textContent='Edit Category';
    document.getElementById('catImageInput').required = false;
}

async function deleteCat(id){
    if(confirm('Delete this category?')){
        showLoader();
        try {
            const res = await fetch('category.php',{
                method:'POST',
                body: new URLSearchParams({ajax:1, action:'delete', id:id})
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch(e) { location.reload(); return; }
            if (data.success) {
                showToast(data.message || 'Category deleted successfully!', 'success');
            } else {
                showToast('Delete failed: ' + (data.message || ''), 'error');
            }
        } catch(e) {
            showToast('Network error.', 'error');
        } finally {
            hideLoader();
        }
    }
}

document.getElementById('catForm').addEventListener('submit', async(e)=>{
    e.preventDefault();
    showLoader();
    const fd = new FormData(e.target);
    try {
        const res = await fetch('category.php',{method:'POST', body:fd});
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { location.reload(); return; }
        if (data.success) {
            closeModal();
            showToast(data.message || 'Category saved successfully!', 'success');
        } else {
            showToast('Operation failed: ' + (data.message || ''), 'error');
        }
    } catch(e) {
        showToast('Network error.', 'error');
    } finally {
        hideLoader();
    }
});

// Search filter
document.getElementById('categorySearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.cat-row');
    rows.forEach(row => {
        const id = row.getAttribute('data-id');
        const name = row.getAttribute('data-name');
        if (query === '' || id.includes(query) || name.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>