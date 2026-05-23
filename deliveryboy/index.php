<?php
// No session_start() – common/config.php already starts the session
require_once __DIR__ . '/config.php';

// Must be logged in as delivery boy
if (!isset($_SESSION['dboy_id'])) {
    header('Location: login.php');
    exit;
}

$dboy_id = $_SESSION['dboy_id'];
$dboy_name = $_SESSION['dboy_name'];

// ---------- AJAX operations ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $orderId = intval($_POST['order_id']);

    // Verify the order belongs to this delivery boy
    $check = $conn->query("SELECT id, status FROM orders WHERE id=$orderId AND delivery_boy_id=$dboy_id")->fetch_assoc();
    if (!$check) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you.']);
        exit;
    }

    if ($action === 'generate_code') {
        // Generate a random 6-digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $conn->query("UPDATE orders SET verification_code='$code' WHERE id=$orderId");
        echo json_encode(['success' => true, 'code' => $code]);
        exit;

    } elseif ($action === 'verify_and_update') {
        $enteredCode = trim($_POST['code']);
        $newStatus = $_POST['status'];  // 'Delivered' or 'Cancelled'

        // Fetch the stored code
        $order = $conn->query("SELECT verification_code FROM orders WHERE id=$orderId")->fetch_assoc();
        if ($order && $order['verification_code'] && $order['verification_code'] === $enteredCode) {
            // Code matches – update status and clear the code
            $conn->query("UPDATE orders SET status='$newStatus', verification_code=NULL WHERE id=$orderId");
            echo json_encode(['success' => true, 'message' => 'Order updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ---------- Fetch active and history orders separately ----------
$activeOrders = $conn->query("
    SELECT o.*, u.name AS user_name, u.phone AS user_phone, u.email AS user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.delivery_boy_id = $dboy_id
      AND o.status NOT IN ('Delivered', 'Cancelled')
    ORDER BY o.created_at DESC
");

$historyOrders = $conn->query("
    SELECT o.*, u.name AS user_name, u.phone AS user_phone, u.email AS user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.delivery_boy_id = $dboy_id
      AND o.status IN ('Delivered', 'Cancelled')
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Delivery Dashboard | QuickKart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            background: #0f172a;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .loader { display: none; }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3), 0 0 20px rgba(245,158,11,0.1);
        }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex flex-col">

<!-- Top Bar -->
<header class="bg-slate-800/80 backdrop-blur-md border-b border-slate-700/50 px-4 py-3 flex items-center justify-between sticky top-0 z-20 shadow-lg">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center">
            <i class="fas fa-motorcycle text-gray-900"></i>
        </div>
        <h1 class="text-lg font-bold text-amber-400">Hello, <?= htmlspecialchars($dboy_name) ?></h1>
    </div>
    <a href="login.php?logout=1" class="text-slate-400 hover:text-amber-400 transition-colors text-sm font-medium flex items-center gap-1">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</header>

<!-- Orders Section -->
<main class="flex-1 p-4">
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        My Orders
    </h2>

    <!-- Tabs -->
    <div class="flex bg-slate-800/70 border border-slate-700/50 rounded-2xl p-1 mb-6 shadow-lg">
        <button id="activeTab"
                class="flex-1 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 shadow-md"
                onclick="switchTab('active')">My Delivery Orders</button>
        <button id="historyTab"
                class="flex-1 py-2.5 text-sm font-bold rounded-xl text-slate-400 transition-all duration-300"
                onclick="switchTab('history')">My History</button>
    </div>

    <!-- Active Orders (with code generation and verification) -->
    <div id="activeOrders">
        <?php if($activeOrders->num_rows == 0): ?>
            <div class="flex flex-col items-center justify-center py-16">
                <div class="w-28 h-28 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center mb-6 shadow-inner">
                    <i class="fas fa-box-open text-5xl text-amber-500/60"></i>
                </div>
                <p class="text-slate-400 text-lg font-medium">No active orders</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php while ($order = $activeOrders->fetch_assoc()): 
                    $itemsRes = $conn->query("SELECT SUM(quantity) AS total_qty FROM order_items WHERE order_id = {$order['id']}");
                    $totalQty = $itemsRes->fetch_assoc()['total_qty'] ?? 0;
                    $hasCode = !empty($order['verification_code']);
                ?>
                <div class="bg-slate-800/70 backdrop-blur border border-slate-700/50 rounded-3xl p-5 card-hover" data-order-id="<?= $order['id'] ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-slate-100 font-serif">Order #<?= $order['id'] ?></h3>
                            <p class="text-sm text-slate-400 mt-1"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                            <?= $order['status'] ?>
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-user text-slate-500 w-4"></i><span class="text-slate-300"><?= htmlspecialchars($order['user_name']) ?></span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-phone-alt text-slate-500 w-4"></i><a href="tel:<?= htmlspecialchars($order['user_phone']) ?>" class="text-amber-400 font-medium"><?= htmlspecialchars($order['user_phone'] ?? 'N/A') ?></a></div>
                        <div class="flex items-start gap-2 sm:col-span-2"><i class="fas fa-map-marker-alt text-slate-500 w-4 mt-1"></i><span class="text-slate-300"><?= nl2br(htmlspecialchars($order['address'] ?? 'No address')) ?></span></div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <span class="text-xs font-medium text-slate-500">Payment:</span>
                        <span class="text-xs font-semibold <?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'text-teal-400' : 'text-slate-300' ?>"><?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'QR Code' : 'Cash on Delivery' ?></span>
                        <?php if (($order['payment_method'] ?? '') == 'QR' && !empty($order['screenshot'])): ?>
                            <img src="../<?= $order['screenshot'] ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-600 cursor-pointer hover:scale-105 transition-transform" onclick="window.open('../<?= $order['screenshot'] ?>','_blank')">
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 flex justify-between items-center border-t border-slate-700/50 pt-3">
                        <div class="text-sm text-slate-400"><span class="font-medium"><?= $totalQty ?> item(s)</span></div>
                        <div class="text-lg font-extrabold text-amber-400">₹<?= number_format($order['total_amount'], 2) ?></div>
                    </div>

                    <!-- Verification section (for active orders) -->
                    <div class="mt-4 border-t border-slate-700/50 pt-4 space-y-3">
                        <?php if (!$hasCode): ?>
                            <button onclick="generateCode(<?= $order['id'] ?>, this)" class="bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-amber-500/20 hover:shadow-amber-500/40 active:scale-95 transition-all">
                                <i class="fas fa-key mr-1"></i> Generate Code
                            </button>
                        <?php else: ?>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                <div class="flex-1 w-full">
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Enter Code from Customer</label>
                                    <input type="text" id="codeInput_<?= $order['id'] ?>" class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400" placeholder="6-digit code" maxlength="6" inputmode="numeric">
                                </div>
                                <select id="statusSelect_<?= $order['id'] ?>" class="bg-slate-700 border border-slate-600 rounded-xl px-3 py-2.5 text-sm text-white">
                                    <option value="Delivered">Delivered</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <button onclick="verifyAndUpdate(<?= $order['id'] ?>)" class="bg-gradient-to-r from-emerald-500 to-green-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 active:scale-95 transition-all">
                                    Update
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- History Orders (read-only) -->
    <div id="historyOrders" class="hidden">
        <?php if($historyOrders->num_rows == 0): ?>
            <div class="flex flex-col items-center justify-center py-16">
                <div class="w-28 h-28 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center mb-6 shadow-inner">
                    <i class="fas fa-history text-5xl text-amber-500/60"></i>
                </div>
                <p class="text-slate-400 text-lg font-medium">No past orders</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php while ($order = $historyOrders->fetch_assoc()): 
                    $itemsRes = $conn->query("SELECT SUM(quantity) AS total_qty FROM order_items WHERE order_id = {$order['id']}");
                    $totalQty = $itemsRes->fetch_assoc()['total_qty'] ?? 0;
                ?>
                <div class="bg-slate-800/70 backdrop-blur border border-slate-700/50 rounded-3xl p-5 card-hover">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-slate-100 font-serif">Order #<?= $order['id'] ?></h3>
                            <p class="text-sm text-slate-400 mt-1"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $order['status'] == 'Delivered' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' ?>">
                            <?= $order['status'] ?>
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-user text-slate-500 w-4"></i><span class="text-slate-300"><?= htmlspecialchars($order['user_name']) ?></span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-phone-alt text-slate-500 w-4"></i><a href="tel:<?= htmlspecialchars($order['user_phone']) ?>" class="text-amber-400 font-medium"><?= htmlspecialchars($order['user_phone'] ?? 'N/A') ?></a></div>
                        <div class="flex items-start gap-2 sm:col-span-2"><i class="fas fa-map-marker-alt text-slate-500 w-4 mt-1"></i><span class="text-slate-300"><?= nl2br(htmlspecialchars($order['address'] ?? 'No address')) ?></span></div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <span class="text-xs font-medium text-slate-500">Payment:</span>
                        <span class="text-xs font-semibold <?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'text-teal-400' : 'text-slate-300' ?>"><?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'QR Code' : 'Cash on Delivery' ?></span>
                        <?php if (($order['payment_method'] ?? '') == 'QR' && !empty($order['screenshot'])): ?>
                            <img src="../<?= $order['screenshot'] ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-600 cursor-pointer hover:scale-105 transition-transform" onclick="window.open('../<?= $order['screenshot'] ?>','_blank')">
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 flex justify-between items-center border-t border-slate-700/50 pt-3">
                        <div class="text-sm text-slate-400"><span class="font-medium"><?= $totalQty ?> item(s)</span></div>
                        <div class="text-lg font-extrabold text-amber-400">₹<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Loader & Toast -->
<div id="loader" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px);">
    <div class="bg-slate-800/90 backdrop-blur-lg p-8 rounded-2xl border border-slate-700 shadow-2xl flex flex-col items-center">
        <div class="flex space-x-2">
            <div class="w-4 h-4 bg-amber-500 rounded-full animate-bounce"></div>
            <div class="w-4 h-4 bg-amber-400 rounded-full animate-bounce" style="animation-delay:0.16s"></div>
            <div class="w-4 h-4 bg-teal-400 rounded-full animate-bounce" style="animation-delay:0.32s"></div>
        </div>
        <p class="text-slate-300 text-sm mt-3">Loading...</p>
    </div>
</div>
<div id="toast" class="fixed top-5 right-5 z-[80] flex items-center gap-2 px-6 py-3 rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 backdrop-blur-md text-white">
    <span id="toastIcon" class="text-lg"></span>
    <span id="toastMessage" class="font-medium"></span>
</div>

<script>
function showLoader() { document.getElementById('loader').style.display = 'flex'; }
function hideLoader() { document.getElementById('loader').style.display = 'none'; }

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

function switchTab(tab) {
    const activeTab = document.getElementById('activeTab');
    const historyTab = document.getElementById('historyTab');
    const activeOrders = document.getElementById('activeOrders');
    const historyOrders = document.getElementById('historyOrders');

    if (tab === 'active') {
        activeOrders.classList.remove('hidden');
        historyOrders.classList.add('hidden');
        activeTab.className = 'flex-1 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 shadow-md';
        historyTab.className = 'flex-1 py-2.5 text-sm font-bold rounded-xl text-slate-400 transition-all duration-300';
    } else {
        activeOrders.classList.add('hidden');
        historyOrders.classList.remove('hidden');
        historyTab.className = 'flex-1 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 shadow-md';
        activeTab.className = 'flex-1 py-2.5 text-sm font-bold rounded-xl text-slate-400 transition-all duration-300';
    }
}

async function generateCode(orderId, btn) {
    showLoader();
    try {
        const res = await fetch('index.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'generate_code', order_id: orderId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Code generated! Waiting for customer verification.', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Error generating code.', 'error');
        }
    } catch (e) { showToast('Network error.', 'error'); }
    finally { hideLoader(); }
}

async function verifyAndUpdate(orderId) {
    const code = document.getElementById('codeInput_' + orderId).value.trim();
    const status = document.getElementById('statusSelect_' + orderId).value;
    if (code.length !== 6) {
        showToast('Please enter a valid 6-digit code.', 'error');
        return;
    }
    showLoader();
    try {
        const res = await fetch('index.php', {
            method: 'POST',
            body: new URLSearchParams({ ajax: 1, action: 'verify_and_update', order_id: orderId, code: code, status: status })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Verification failed.', 'error');
        }
    } catch (e) { showToast('Network error.', 'error'); }
    finally { hideLoader(); }
}
</script>
</body>
</html>