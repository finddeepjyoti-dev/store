<?php
// DB connection without any HTML
require_once __DIR__ . '/common/config.php';

// Fetch categories (used by both AJAX and normal page)
$categories = $conn->query("SELECT id, name FROM categories");

// Fetch all product images grouped by product_id
$allImages = [];
$imgRes = $conn->query("SELECT * FROM product_images ORDER BY product_id, sort_order");
while ($img = $imgRes->fetch_assoc()) {
    $allImages[$img['product_id']][] = $img;
}

// ---------- AJAX CRUD (no HTML output) ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();

    header('Content-Type: application/json');
    $action = $_POST['action'];

    // Return subcategories for a given category
    if ($action == 'get_subcategories') {
        $cat_id = intval($_POST['cat_id']);
        $subcats = $conn->query("SELECT id, name FROM subcategories WHERE cat_id = $cat_id ORDER BY name");
        $list = [];
        while ($sc = $subcats->fetch_assoc()) {
            $list[] = $sc;
        }
        echo json_encode(['success' => true, 'subcategories' => $list]);
        exit;
    }

    if ($action == 'add' || $action == 'edit') {
        $cat_id       = intval($_POST['cat_id']);
        $subcat_id    = (isset($_POST['subcat_id']) && $_POST['subcat_id'] !== '') ? intval($_POST['subcat_id']) : 'NULL';
        $name         = $conn->real_escape_string($_POST['name']);
        $desc         = $conn->real_escape_string($_POST['description']);
        $price        = floatval($_POST['price']);
        $mrp          = floatval($_POST['mrp'] ?? 0);
        $unit         = $conn->real_escape_string($_POST['unit'] ?? '');
        $offer_percent = floatval($_POST['offer_percent'] ?? 0);
        $stock        = intval($_POST['stock']);
        $imgPath      = '';

        // Primary Image
        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imgName = time() . '.' . $ext;
            $targetDir = '../images/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imgName);
            $imgPath = 'images/' . $imgName;
        }

        if ($action == 'add') {
            $conn->query("INSERT INTO products (cat_id, subcat_id, name, description, price, mrp, unit, offer_percent, stock, image)
                          VALUES ($cat_id, $subcat_id, '$name', '$desc', $price, $mrp, '$unit', $offer_percent, $stock, '$imgPath')");
            $product_id = $conn->insert_id;
            $msg = 'Product added successfully!';
        } else {
            $product_id = intval($_POST['id']);
            if ($imgPath) {
                $conn->query("UPDATE products SET cat_id=$cat_id, subcat_id=$subcat_id, name='$name', description='$desc',
                              price=$price, mrp=$mrp, unit='$unit', offer_percent=$offer_percent, stock=$stock,
                              image='$imgPath' WHERE id=$product_id");
            } else {
                $conn->query("UPDATE products SET cat_id=$cat_id, subcat_id=$subcat_id, name='$name', description='$desc',
                              price=$price, mrp=$mrp, unit='$unit', offer_percent=$offer_percent, stock=$stock
                              WHERE id=$product_id");
            }
            $msg = 'Product updated successfully!';
        }

        // Additional Images (image2, image3)
        for ($i = 2; $i <= 3; $i++) {
            $field = 'image' . $i;
            $deleteFlag = $_POST['delete_' . $i] ?? 0;

            $existing = $conn->query("SELECT id, image FROM product_images WHERE product_id=$product_id AND sort_order=$i")->fetch_assoc();
            if ($deleteFlag && $existing) {
                @unlink('../' . $existing['image']);
                $conn->query("DELETE FROM product_images WHERE id={$existing['id']}");
                $existing = null;
            }

            if (!empty($_FILES[$field]['name'])) {
                if ($existing) {
                    @unlink('../' . $existing['image']);
                    $conn->query("DELETE FROM product_images WHERE id={$existing['id']}");
                }
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $imgName = time() . "_{$i}." . $ext;
                move_uploaded_file($_FILES[$field]['tmp_name'], $targetDir . $imgName);
                $newPath = 'images/' . $imgName;
                $conn->query("INSERT INTO product_images (product_id, image, sort_order) VALUES ($product_id, '$newPath', $i)");
            }
        }

        echo json_encode(['success' => true, 'message' => $msg]);
        exit;

    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM products WHERE id=$id");
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully!']);
        exit;

    } elseif ($action == 'delete_extra_image') {
        $imgId = intval($_POST['img_id']);
        $img = $conn->query("SELECT image FROM product_images WHERE id=$imgId")->fetch_assoc();
        if ($img) {
            @unlink('../' . $img['image']);
            $conn->query("DELETE FROM product_images WHERE id=$imgId");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ---------- Normal page load ----------
require_once 'common/header.php';

$products = $conn->query("
    SELECT p.*, c.name AS cat_name, sc.name AS subcat_name
    FROM products p
    JOIN categories c ON p.cat_id = c.id
    LEFT JOIN subcategories sc ON p.subcat_id = sc.id
    ORDER BY p.id DESC
");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-4 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Products
    </h2>
    <button onclick="openModal('add')" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95 mb-4">
        <i class="fas fa-plus mr-2"></i> Add Product
    </button>

    <!-- Search Bar -->
    <div class="mb-5">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="productSearch" placeholder="Search by Product ID or Name..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Image</th>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold">Price</th>
                    <th class="p-4 font-semibold">Stock</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="productTableBody">
                <?php while ($p = $products->fetch_assoc()): 
                    $extraImages = $allImages[$p['id']] ?? [];
                    $extraJSON = json_encode(array_map(function($img){ return ['id'=>$img['id'], 'path'=>$img['image'], 'sort_order'=>$img['sort_order']]; }, $extraImages));
                ?>
                <tr class="hover:bg-slate-700/40 transition-colors product-row"
                    data-product-id="<?= $p['id'] ?>"
                    data-product-name="<?= htmlspecialchars(strtolower($p['name'])) ?>">
                    <td class="p-4">
                        <?php if ($p['image']): ?>
                            <img src="../<?= $p['image'] ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-600">
                        <?php else: ?>
                            <span class="text-slate-500 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-slate-200 font-medium">
                        <?= htmlspecialchars($p['name']) ?>
                        <?php if (!empty($p['unit'])): ?>
                            <span class="text-xs text-slate-400 ml-1">/ <?= htmlspecialchars($p['unit']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-1">
                            <span class="font-bold text-slate-100">₹<?= number_format($p['price'], 2) ?></span>
                            <?php if ($p['mrp'] && $p['mrp'] > $p['price']): ?>
                                <span class="text-xs text-slate-500 line-through">₹<?= number_format($p['mrp'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($p['offer_percent'] > 0): ?>
                            <span class="text-xs text-emerald-400 font-medium bg-emerald-500/10 px-2 py-0.5 rounded-full mt-1 inline-block"><?= number_format($p['offer_percent'], 0) ?>% off</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-slate-300"><?= $p['stock'] ?></td>
                    <td class="p-4 text-right">
                        <button onclick='editProduct(<?= $p['id'] ?>, <?= json_encode(htmlspecialchars($p['name'])) ?>, <?= $p['cat_id'] ?>, <?= $p['subcat_id'] ?? 'null' ?>, <?= json_encode(htmlspecialchars($p['description'])) ?>, <?= $p['price'] ?>, <?= $p['mrp'] ?? 0 ?>, <?= json_encode(htmlspecialchars($p['unit'] ?? '')) ?>, <?= $p['offer_percent'] ?? 0 ?>, <?= $p['stock'] ?>, <?= $extraJSON ?>)' class="text-amber-400 hover:text-amber-300 mr-3 transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteProduct(<?= $p['id'] ?>)" class="text-red-400 hover:text-red-300 transition-colors">
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
<div id="productModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <h3 class="text-xl font-bold text-slate-100 mb-4" id="modalTitle">Add Product</h3>
        <form id="productForm" enctype="multipart/form-data">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" id="prodAction" value="add">
            <input type="hidden" name="id" id="prodId">

            <!-- Category -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Category *</label>
                <select name="cat_id" id="prodCat" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400" onchange="loadSubcategories(this.value)">
                    <option value="">Select Category</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Subcategory (dynamic) -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Subcategory</label>
                <select name="subcat_id" id="prodSubcat" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Select Subcategory</option>
                </select>
            </div>

            <!-- Name -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Product Name *</label>
                <input type="text" name="name" id="prodName" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            <!-- Description -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Description</label>
                <textarea name="description" id="prodDesc" rows="3" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea>
            </div>

            <!-- Price, MRP, Offer % row -->
            <div class="grid grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">Selling Price (₹) *</label>
                    <input type="number" name="price" id="prodPrice" step="0.01" required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">MRP (₹)</label>
                    <input type="number" name="mrp" id="prodMrp" step="0.01" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">Offer %</label>
                    <input type="number" name="offer_percent" id="prodOffer" step="0.01" min="0" max="100" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>

            <!-- Unit & Stock row -->
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">Unit (e.g., kg, L, XL)</label>
                    <input type="text" name="unit" id="prodUnit" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400" placeholder="kg, L, XL...">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-1.5">Stock</label>
                    <input type="number" name="stock" id="prodStock" value="0" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>

            <!-- Images -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Primary Image *</label>
                <input type="file" name="image" id="prodImage" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all" accept="image/*">
                <p class="text-xs text-slate-400 mt-1">Main product photo (required for new product).</p>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Image 2 (optional)</label>
                <div class="flex items-center gap-2">
                    <input type="file" name="image2" id="prodImage2" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all" accept="image/*">
                    <input type="hidden" name="delete_2" id="delete_2" value="0">
                    <div id="preview2" class="flex items-center gap-1"></div>
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Image 3 (optional)</label>
                <div class="flex items-center gap-2">
                    <input type="file" name="image3" id="prodImage3" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 transition-all" accept="image/*">
                    <input type="hidden" name="delete_3" id="delete_3" value="0">
                    <div id="preview3" class="flex items-center gap-1"></div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-xl font-medium transition-all active:scale-95">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 transition-all active:scale-95">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-5 right-5 z-[60] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md text-white">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
// ---------- SEARCH ----------
document.getElementById('productSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        const id = row.getAttribute('data-product-id');
        const name = row.getAttribute('data-product-name');
        if (query === '' || id.includes(query) || name.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});

// ---------- EXISTING JS (unchanged) ----------
const modal = document.getElementById('productModal');
const form = document.getElementById('productForm');
const toast = document.getElementById('toast');
const toastIcon = document.getElementById('toastIcon');
const toastMsg = document.getElementById('toastMessage');

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

// Dynamic subcategory loader
async function loadSubcategories(catId) {
    const subcatSelect = document.getElementById('prodSubcat');
    subcatSelect.innerHTML = '<option value="">Loading...</option>';
    if (!catId) {
        subcatSelect.innerHTML = '<option value="">Select Subcategory</option>';
        return;
    }
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_subcategories');
        formData.append('cat_id', catId);
        const res = await fetch('index.php', { method: 'POST', body: formData }); // ensure correct endpoint
        const data = await res.json();
        if (data.success) {
            let options = '<option value="">Select Subcategory</option>';
            data.subcategories.forEach(sc => {
                options += `<option value="${sc.id}">${sc.name}</option>`;
            });
            subcatSelect.innerHTML = options;
        } else {
            subcatSelect.innerHTML = '<option value="">No subcategories</option>';
        }
    } catch(e) {
        subcatSelect.innerHTML = '<option value="">Error loading</option>';
    }
}

function openModal(mode) {
    form.reset();
    document.getElementById('prodAction').value = mode;
    document.getElementById('prodId').value = '';
    document.getElementById('modalTitle').textContent = mode === 'add' ? 'Add Product' : 'Edit Product';
    document.getElementById('preview2').innerHTML = '';
    document.getElementById('preview3').innerHTML = '';
    document.getElementById('delete_2').value = '0';
    document.getElementById('delete_3').value = '0';
    document.getElementById('prodSubcat').innerHTML = '<option value="">Select Subcategory</option>';
    modal.style.display = 'flex';
}

function closeModal() { modal.style.display = 'none'; }

function createPreview(imageId, imagePath, slot) {
    const container = document.getElementById('preview' + slot);
    const div = document.createElement('div');
    div.className = 'relative inline-block';
    div.innerHTML = `
        <img src="../${imagePath}" class="w-12 h-12 object-cover rounded-lg border border-slate-600">
        <button type="button" class="absolute -top-1.5 -right-1.5 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow"
                onclick="deleteExtraImage(${imageId}, ${slot})"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(div);
}

async function deleteExtraImage(imgId, slot) {
    if (!confirm('Delete this image?')) return;
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'delete_extra_image');
        formData.append('img_id', imgId);
        await fetch('index.php', { method: 'POST', body: formData });
        document.getElementById('preview' + slot).innerHTML = '';
        document.getElementById('delete_' + slot).value = '1';
    } catch(e) { 
        showToast('Network error.', 'error');
    } finally { 
        hideLoader(); 
    }
}

function editProduct(id, name, cat_id, subcat_id, desc, price, mrp, unit, offer_percent, stock, extraImages) {
    openModal('edit');
    document.getElementById('prodId').value = id;
    document.getElementById('prodName').value = name;
    document.getElementById('prodCat').value = cat_id;
    loadSubcategories(cat_id).then(() => {
        document.getElementById('prodSubcat').value = subcat_id || '';
    });
    document.getElementById('prodDesc').value = desc;
    document.getElementById('prodPrice').value = price;
    document.getElementById('prodMrp').value = mrp || '';
    document.getElementById('prodUnit').value = unit || '';
    document.getElementById('prodOffer').value = offer_percent || '';
    document.getElementById('prodStock').value = stock;
    document.getElementById('preview2').innerHTML = '';
    document.getElementById('preview3').innerHTML = '';
    document.getElementById('delete_2').value = '0';
    document.getElementById('delete_3').value = '0';

    if (extraImages && extraImages.length) {
        extraImages.forEach(img => {
            if (img.sort_order == 2) {
                createPreview(img.id, img.path, 2);
            } else if (img.sort_order == 3) {
                createPreview(img.id, img.path, 3);
            }
        });
    }
}

async function deleteProduct(id) {
    if (!confirm('Delete this product?')) return;
    showLoader();
    try {
        const res = await fetch('index.php', { method: 'POST', body: new URLSearchParams({ ajax: 1, action: 'delete', id: id }) });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { location.reload(); return; }
        if (data.success) {
            showToast(data.message || 'Product deleted successfully!', 'success');
        } else {
            showToast('Delete failed: ' + (data.message || ''), 'error');
        }
    } catch(e) { showToast('Network error.', 'error'); }
    finally { hideLoader(); }
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoader();
    try {
        const fd = new FormData(form);
        fd.set('delete_2', document.getElementById('delete_2').value);
        fd.set('delete_3', document.getElementById('delete_3').value);
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { location.reload(); return; }
        if (data.success) {
            closeModal();
            showToast(data.message || 'Product saved successfully!', 'success');
        } else {
            showToast('Operation failed: ' + (data.message || ''), 'error');
        }
    } catch(err) { showToast('Network error.', 'error'); }
    finally { hideLoader(); }
});

modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
});
</script>

<?php include 'common/bottom.php'; ?>