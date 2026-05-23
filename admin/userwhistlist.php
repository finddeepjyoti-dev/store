<?php
// DB connection – no HTML output before header
require_once __DIR__ . '/common/config.php';

// ---------- AJAX Delete ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax']) && $_POST['action'] === 'delete') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM wishlist WHERE id = $id");
    echo json_encode(['success' => true, 'message' => 'Wishlist item removed.']);
    exit;
}

// ---------- Normal page load ----------
require_once 'common/header.php';

// Fetch all wishlist entries with user & product details
$wishlist = $conn->query("
    SELECT w.id, w.created_at,
           u.name AS user_name,
           p.name AS product_name, p.price, p.image
    FROM wishlist w
    JOIN users u ON w.user_id = u.id
    JOIN products p ON w.product_id = p.id
    ORDER BY w.created_at DESC
");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        User Wishlists
    </h2>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="wishlistSearch" placeholder="Search by Product, User or Price..."
                   class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-11 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-shadow">
        </div>
    </div>

    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-700/50 text-slate-300">
                <tr>
                    <th class="p-4 font-semibold">Product</th>
                    <th class="p-4 font-semibold">Price</th>
                    <th class="p-4 font-semibold">User</th>
                    <th class="p-4 font-semibold">Date Added</th>
                    <th class="p-4 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30" id="wishlistTableBody">
                <?php while ($item = $wishlist->fetch_assoc()): ?>
                <tr class="hover:bg-slate-700/40 transition-colors wishlist-row"
                    data-id="<?= $item['id'] ?>"
                    data-product="<?= htmlspecialchars(strtolower($item['product_name'])) ?>"
                    data-user="<?= htmlspecialchars(strtolower($item['user_name'])) ?>"
                    data-price="<?= $item['price'] ?>">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <?php if ($item['image']): ?>
                                <img src="../<?= $item['image'] ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-600">
                            <?php endif; ?>
                            <span class="text-slate-200 font-medium"><?= htmlspecialchars($item['product_name']) ?></span>
                        </div>
                    </td>
                    <td class="p-4 text-amber-400 font-bold">₹<?= number_format($item['price'], 2) ?></td>
                    <td class="p-4 text-slate-300"><?= htmlspecialchars($item['user_name']) ?></td>
                    <td class="p-4 text-slate-400 text-xs"><?= date('d M Y', strtotime($item['created_at'])) ?></td>
                    <td class="p-4 text-right">
                        <button onclick="deleteWishlistItem(<?= $item['id'] ?>, this)" class="text-red-400 hover:text-red-300 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($wishlist->num_rows == 0): ?>
                <tr>
                    <td colspan="5" class="p-6 text-center text-slate-500">
                        <i class="fas fa-heart-broken text-3xl mb-2 block opacity-50"></i>
                        No items in any wishlist.
                    </td>
                </tr>
                <?php endif; ?>
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

async function deleteWishlistItem(id, btn) {
    if (!confirm('Remove this item from wishlist?')) return;
    showLoader();
    try {
        const res = await fetch('userwhistlist.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'delete', id: id })
        });
        const data = await res.json();
        if (data.success) {
            // Remove the table row
            const row = btn.closest('tr');
            if (row) row.remove();
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

// Live search filter for wishlist
document.getElementById('wishlistSearch').addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.wishlist-row');
    rows.forEach(row => {
        const product = row.getAttribute('data-product');
        const user = row.getAttribute('data-user');
        const price = row.getAttribute('data-price');
        if (query === '' || product.includes(query) || user.includes(query) || price.includes(query)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>