<?php
// DB connection only – no HTML
require_once __DIR__ . '/common/config.php';

$id = intval($_GET['id']);

// ---------- AJAX Update (status + delivery boy) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update_order') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');

    $status = $conn->real_escape_string($_POST['status']);
    $dboy_id = $_POST['delivery_boy_id'] ? intval($_POST['delivery_boy_id']) : 'NULL';

    $conn->query("UPDATE orders SET status='$status', delivery_boy_id=$dboy_id WHERE id=$id");
    echo json_encode(['success' => true, 'message' => 'Order updated successfully!']);
    exit;
}

// ---------- Normal page load ----------
require_once 'common/header.php';

// Fetch order + user details (including address, district, pin)
$order = $conn->query("SELECT o.*, u.name, u.phone, u.email, u.address AS user_address, u.district, u.pin,
                              db.name AS dboy_name
                       FROM orders o
                       JOIN users u ON o.user_id = u.id
                       LEFT JOIN delivery_boys db ON o.delivery_boy_id = db.id
                       WHERE o.id = $id")->fetch_assoc();

// Build the complete address from user table fields
$userAddress = $order['user_address'] ?? '';
$district = $order['district'] ?? '';
$pin = $order['pin'] ?? '';

// If the order has its own combined address (e.g., from checkout), we prefer it.
// Otherwise, we construct the address from user data.
if (!empty($order['address'])) {
    $fullAddress = $order['address'];
} else {
    $parts = array_filter([$userAddress, $district, $pin]);
    $fullAddress = implode(', ', $parts) ?: 'Not provided';
}

$items = $conn->query("SELECT oi.*, p.name, p.image, p.unit FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=$id");
$deliveryBoys = $conn->query("SELECT id, name FROM delivery_boys WHERE status=1 ORDER BY name");
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Order #<?= $id ?> Detail
    </h2>

    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <span class="text-slate-400 text-sm font-medium">User</span>
                <p class="text-slate-100 font-semibold"><?= htmlspecialchars($order['name']) ?></p>
                <p class="text-slate-400 text-xs"><?= htmlspecialchars($order['email']) ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm font-medium">Phone</span>
                <p class="text-slate-100 font-semibold"><?= htmlspecialchars($order['phone'] ?? 'N/A') ?></p>
            </div>
        </div>

        <!-- Complete Shipping Address -->
        <div>
            <span class="text-slate-400 text-sm font-medium">Shipping Address</span>
            <p class="text-slate-300 mt-1 whitespace-pre-wrap bg-slate-900/50 border border-slate-700 rounded-xl p-3">
                <?= nl2br(htmlspecialchars($fullAddress)) ?>
            </p>
        </div>

        <div>
            <span class="text-slate-400 text-sm font-medium">Payment Method</span>
            <p class="text-slate-100 font-semibold flex items-center gap-2 mt-1">
                <span class="<?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'bg-teal-500/20 text-teal-400 border border-teal-500/30' : 'bg-slate-700 text-slate-300 border border-slate-600' ?> px-3 py-0.5 rounded-full text-xs font-semibold">
                    <?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'QR Code Payment' : 'Cash on Delivery' ?>
                </span>
            </p>
            <?php if (($order['payment_method'] ?? '') == 'QR' && !empty($order['screenshot'])): ?>
                <div class="mt-3">
                    <p class="text-sm text-slate-400 mb-2">Payment Screenshot</p>
                    <img src="../<?= $order['screenshot'] ?>" class="w-32 h-auto rounded-xl border border-slate-600 cursor-pointer hover:scale-105 transition-transform shadow-md" onclick="window.open('../<?= $order['screenshot'] ?>','_blank')">
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-slate-400 text-sm font-medium">Total</span>
                <p class="text-2xl font-bold text-amber-400">₹<?= number_format($order['total_amount'], 2) ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm font-medium">Date</span>
                <p class="text-slate-100 font-semibold"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></p>
            </div>
        </div>

        <?php if (!empty($order['dboy_name'])): ?>
        <div>
            <span class="text-slate-400 text-sm font-medium">Assigned Delivery Boy</span>
            <p class="text-slate-100 font-semibold"><?= htmlspecialchars($order['dboy_name']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Update Form -->
        <div class="mt-6 border-t border-slate-700/50 pt-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Assign Delivery Boy</label>
                <select id="deliveryBoySelect" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">-- Select Delivery Boy --</option>
                    <?php while ($db = $deliveryBoys->fetch_assoc()): ?>
                        <option value="<?= $db['id'] ?>" <?= ($order['delivery_boy_id'] == $db['id']) ? 'selected' : '' ?>><?= htmlspecialchars($db['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Order Status</label>
                <select id="statusSelect" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <?php foreach(['Placed','Dispatched','Delivered','Cancelled'] as $s): ?>
                        <option <?= $order['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button onclick="updateOrder()" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-6 py-2.5 rounded-xl font-bold hover:shadow-lg hover:shadow-amber-500/20 active:scale-95 transition-all">
                <i class="fas fa-save mr-2"></i> Update Order
            </button>
        </div>
    </div>

    <h3 class="text-xl font-bold text-slate-100 mt-8 mb-3 flex items-center gap-2">
        <span class="w-1.5 h-5 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Items
    </h3>
    <div class="space-y-3">
        <?php while($it = $items->fetch_assoc()): ?>
        <div class="flex gap-4 items-center bg-slate-800/70 backdrop-blur border border-slate-700/50 p-4 rounded-2xl shadow-md">
            <img src="../<?= $it['image'] ?>" class="w-14 h-14 rounded-xl object-cover border border-slate-600 shadow-sm">
            <div class="flex-1">
                <p class="text-slate-100 font-semibold"><?= htmlspecialchars($it['name']) ?></p>
                <p class="text-sm text-slate-300">
                    Qty: <?= $it['quantity'] ?><?php if (!empty($it['unit'])): ?> <?= htmlspecialchars($it['unit']) ?><?php endif; ?> × ₹<?= number_format($it['price'], 2) ?>
                </p>
            </div>
            <span class="text-amber-400 font-bold">₹<?= number_format($it['quantity'] * $it['price'], 2) ?></span>
        </div>
        <?php endwhile; ?>
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
        setTimeout(() => {
            location.reload();
        }, 300);
    }, 2500);
}

async function updateOrder() {
    showLoader();
    try {
        const status = document.getElementById('statusSelect').value;
        const deliveryBoyId = document.getElementById('deliveryBoySelect').value;
        const formData = new URLSearchParams();
        formData.append('ajax', 'update_order');
        formData.append('status', status);
        formData.append('delivery_boy_id', deliveryBoyId);
        const res = await fetch('order_detail.php?id=<?= $id ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Update failed.', 'error');
        }
    } catch (e) {
        showToast('Network error.', 'error');
    } finally {
        hideLoader();
    }
}
</script>

<?php include 'common/bottom.php'; ?>