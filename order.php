<?php
ob_start(); // prevent header errors, capture unexpected output
error_reporting(E_ALL); // temporarily show all errors to debug the blank page
ini_set('display_errors', 1);

require_once 'common/config.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$userId = $_SESSION['user_id'];

// AJAX cancel order
if(isset($_POST['ajax_cancel'])) {
    header('Content-Type: application/json');
    $orderId = intval($_POST['order_id']);
    $order = $conn->query("SELECT status FROM orders WHERE id=$orderId AND user_id=$userId")->fetch_assoc();
    if($order && $order['status'] == 'Placed') {
        $conn->query("UPDATE orders SET status='Cancelled' WHERE id=$orderId");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled.']);
    }
    exit;
}

// AJAX submit rating
if(isset($_POST['ajax_rating'])) {
    header('Content-Type: application/json');
    $productId = intval($_POST['product_id']);
    $star = intval($_POST['star']);
    $message = trim($_POST['message'] ?? '');
    
    if ($star < 1 || $star > 5) {
        echo json_encode(['success' => false, 'message' => 'Please select a star rating.']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO productratingreport (user_id, product_id, star, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $userId, $productId, $star, $message);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thank you for your review!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit rating.']);
    }
    $stmt->close();
    exit;
}

// Fetch active orders
$activeOrders = $conn->query("
    SELECT o.*, db.name AS dboy_name, db.phone AS dboy_phone
    FROM orders o
    LEFT JOIN delivery_boys db ON o.delivery_boy_id = db.id
    WHERE o.user_id = $userId AND o.status NOT IN ('Delivered','Cancelled')
    ORDER BY o.created_at DESC
");

// Fetch history orders
$historyOrders = $conn->query("
    SELECT o.*, db.name AS dboy_name, db.phone AS dboy_phone
    FROM orders o
    LEFT JOIN delivery_boys db ON o.delivery_boy_id = db.id
    WHERE o.user_id = $userId AND o.status IN ('Delivered','Cancelled')
    ORDER BY o.created_at DESC
");
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            My Orders
        </h1>

        <!-- Tabs -->
        <div class="flex bg-white border border-rose-200 rounded-2xl p-1 mb-8 shadow-sm">
            <button id="activeTab"
                    class="flex-1 py-3 text-sm lg:text-base font-bold rounded-xl transition-all duration-300 bg-gradient-to-r from-rose-400 to-pink-400 text-white shadow-md"
                    onclick="switchTab('active')">Active Orders</button>
            <button id="historyTab"
                    class="flex-1 py-3 text-sm lg:text-base font-bold rounded-xl text-gray-500 transition-all duration-300"
                    onclick="switchTab('history')">Order History</button>
        </div>

        <!-- Active Orders -->
        <div id="activeOrders">
            <?php if($activeOrders->num_rows==0): ?>
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="w-28 h-28 lg:w-36 lg:h-36 rounded-full bg-white border border-rose-200 flex items-center justify-center mb-6 shadow-sm">
                        <i class="fas fa-box-open text-5xl lg:text-6xl text-rose-300"></i>
                    </div>
                    <p class="text-gray-500 text-lg font-medium">No active orders</p>
                </div>
            <?php endif; ?>

            <?php while($order = $activeOrders->fetch_assoc()): 
                $orderId = $order['id'];
                $orderItems = $conn->query("SELECT oi.*, p.name, p.image, p.unit FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=$orderId");
                $statusStep = ['Placed'=>0, 'Dispatched'=>1, 'Delivered'=>2]; $current = $statusStep[$order['status']] ?? 0;
            ?>
            <div class="bg-white border border-rose-200/60 rounded-3xl p-5 lg:p-6 mb-4 transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-800 text-lg lg:text-xl font-serif">Order #<?= $orderId ?></span>
                    <span class="text-sm text-gray-500 bg-gray-50 border border-gray-200 px-3 py-1 rounded-full"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                </div>
                <p class="text-rose-600 font-extrabold text-2xl lg:text-3xl mt-2">₹<?= number_format($order['total_amount'],2) ?></p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-medium text-gray-500">Payment:</span>
                    <span class="text-xs font-semibold <?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'text-rose-600' : 'text-gray-600' ?>"><?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'QR Code' : 'Cash on Delivery' ?></span>
                    <?php if (($order['payment_method'] ?? '') == 'QR' && !empty($order['screenshot'])): ?>
                        <img src="<?= $order['screenshot'] ?>" class="w-8 h-8 rounded object-cover border border-rose-200 cursor-pointer" onclick="window.open('<?= $order['screenshot'] ?>','_blank')">
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['address'])): ?>
                <div class="mt-2 text-sm text-gray-600"><span class="font-medium text-rose-500">📍 Address:</span> <?= htmlspecialchars($order['address']) ?></div>
                <?php endif; ?>
                <?php if (in_array($order['status'], ['Dispatched','Delivered']) && !empty($order['dboy_name'])): ?>
                <div class="mt-2 text-sm text-gray-600">
                    <span class="font-medium text-rose-500">🛵 Delivery Boy:</span> <?= htmlspecialchars($order['dboy_name']) ?> (<?= htmlspecialchars($order['dboy_phone'] ?? 'N/A') ?>)
                </div>
                <?php endif; ?>
                <?php if (!empty($order['verification_code']) && !in_array($order['status'], ['Delivered','Cancelled'])): ?>
                <div class="mt-3 flex items-center gap-2 bg-rose-50 border border-rose-200 rounded-xl p-3">
                    <span class="text-sm font-medium text-rose-600">🔒 Verification Code:</span>
                    <span class="text-2xl font-extrabold text-rose-500 tracking-widest"><?= htmlspecialchars($order['verification_code']) ?></span>
                </div>
                <?php endif; ?>
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full mt-2 <?= $order['status']=='Placed'?'bg-blue-50 text-blue-600 border border-blue-200':'bg-rose-50 text-rose-600 border border-rose-200' ?>"><?= $order['status'] ?></span>
                <div class="flex items-center justify-between mt-6">
                    <?php foreach(['Placed','Dispatched','Delivered'] as $i => $stage): ?>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full flex items-center justify-center shadow-sm transition-all <?= $i<=$current ? 'bg-gradient-to-br from-rose-400 to-pink-400 text-white' : 'bg-gray-100 border border-gray-200 text-gray-400' ?>">
                            <i class="fas <?= $stage=='Placed'?'fa-clipboard-list':($stage=='Dispatched'?'fa-truck':'fa-check-circle') ?> text-sm lg:text-base"></i>
                        </div>
                        <span class="text-xs mt-1.5 font-medium <?= $i<=$current?'text-rose-500':'text-gray-400' ?>"><?= $stage ?></span>
                    </div>
                    <?php if($i<2): ?>
                    <div class="flex-1 h-1 rounded-full mx-1 <?= $i<$current ? 'bg-gradient-to-r from-rose-400 to-pink-400' : 'bg-gray-200' ?>"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Items (always visible) -->
                <div class="mt-4 border-t border-rose-100 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Items</h4>
                    <div class="space-y-2">
                        <?php while($it = $orderItems->fetch_assoc()): 
                            $unitText = !empty($it['unit']) ? ' ' . htmlspecialchars($it['unit']) : '';
                            $itemTotal = $it['quantity'] * $it['price'];
                        ?>
                        <div class="flex gap-3 items-center bg-gray-50 border border-rose-100 p-3 rounded-xl">
                            <img src="<?= $it['image'] ?>" class="w-12 h-12 rounded-lg object-cover shadow-sm">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($it['name']) ?></p>
                                <p class="text-xs text-gray-500">Qty: <?= $it['quantity'] . $unitText ?> × ₹<?= number_format($it['price'], 2) ?></p>
                            </div>
                            <span class="text-sm font-bold text-rose-600">₹<?= number_format($itemTotal, 2) ?></span>

                            <!-- Rate Product button (only for delivered orders) -->
                            <?php if ($order['status'] == 'Delivered'): ?>
                                <button onclick="openRatingModal('<?= addslashes(htmlspecialchars($it['name'] ?? 'Product')) ?>', <?= $it['product_id'] ?? 0 ?>)" class="inline-flex items-center gap-1 bg-rose-50 border border-rose-200 text-rose-600 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-rose-100 transition">
                                    <i class="fas fa-star"></i> Rate
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <?php if($order['status'] == 'Placed'): ?>
                <button onclick="cancelOrder(<?= $order['id'] ?>)" class="mt-4 text-sm text-red-400 font-medium hover:text-red-500 flex items-center gap-1 transition-colors">
                    <i class="fas fa-times-circle"></i> Cancel Order
                </button>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Order History -->
        <div id="historyOrders" class="hidden">
            <?php if($historyOrders->num_rows==0): ?>
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="w-28 h-28 lg:w-36 lg:h-36 rounded-full bg-white border border-rose-200 flex items-center justify-center mb-6 shadow-sm">
                        <i class="fas fa-history text-5xl lg:text-6xl text-rose-300"></i>
                    </div>
                    <p class="text-gray-500 text-lg font-medium">No past orders</p>
                </div>
            <?php endif; ?>

            <?php while($order = $historyOrders->fetch_assoc()): 
                $orderId = $order['id'];
                $orderItems = $conn->query("SELECT oi.*, p.name, p.image, p.unit FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=$orderId");
            ?>
            <div class="bg-white border border-rose-200/60 rounded-3xl p-5 lg:p-6 mb-4 transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30">
                <div class="flex justify-between">
                    <span class="font-bold text-gray-800 font-serif text-lg lg:text-xl">Order #<?= $orderId ?></span>
                    <span class="text-sm text-gray-500 bg-gray-50 border border-gray-200 px-3 py-1 rounded-full"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                </div>
                <p class="text-rose-600 font-extrabold text-xl lg:text-2xl mt-2">₹<?= number_format($order['total_amount'],2) ?></p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-medium text-gray-500">Payment:</span>
                    <span class="text-xs font-semibold <?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'text-rose-600' : 'text-gray-600' ?>"><?= ($order['payment_method'] ?? 'COD') == 'QR' ? 'QR Code' : 'Cash on Delivery' ?></span>
                    <?php if (($order['payment_method'] ?? '') == 'QR' && !empty($order['screenshot'])): ?>
                        <img src="<?= $order['screenshot'] ?>" class="w-8 h-8 rounded object-cover border border-rose-200 cursor-pointer" onclick="window.open('<?= $order['screenshot'] ?>','_blank')">
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['address'])): ?>
                    <div class="mt-2 text-sm text-gray-600"><span class="font-medium text-rose-500">📍 Address:</span> <?= htmlspecialchars($order['address']) ?></div>
                <?php endif; ?>
                <?php if ($order['status'] == 'Delivered' && !empty($order['dboy_name'])): ?>
                <div class="mt-2 text-sm text-gray-600">
                    <span class="font-medium text-rose-500">🛵 Delivery Boy:</span> <?= htmlspecialchars($order['dboy_name']) ?> (<?= htmlspecialchars($order['dboy_phone'] ?? 'N/A') ?>)
                </div>
                <?php endif; ?>
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full mt-2 <?= $order['status']=='Delivered'?'bg-emerald-50 text-emerald-600 border border-emerald-200':'bg-red-50 text-red-500 border border-red-200' ?>"><?= $order['status'] ?></span>

                <!-- Items -->
                <div class="mt-4 border-t border-rose-100 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Items</h4>
                    <div class="space-y-2">
                        <?php while($it = $orderItems->fetch_assoc()): 
                            $unitText = !empty($it['unit']) ? ' ' . htmlspecialchars($it['unit']) : '';
                            $itemTotal = $it['quantity'] * $it['price'];
                        ?>
                        <div class="flex gap-3 items-center bg-gray-50 border border-rose-100 p-3 rounded-xl">
                            <img src="<?= $it['image'] ?>" class="w-12 h-12 rounded-lg object-cover shadow-sm">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($it['name']) ?></p>
                                <p class="text-xs text-gray-500">Qty: <?= $it['quantity'] . $unitText ?> × ₹<?= number_format($it['price'], 2) ?></p>
                            </div>
                            <span class="text-sm font-bold text-rose-600">₹<?= number_format($itemTotal, 2) ?></span>

                            <!-- Rate Product button (only for delivered orders) -->
                            <?php if ($order['status'] == 'Delivered'): ?>
                                <button onclick="openRatingModal('<?= addslashes(htmlspecialchars($it['name'] ?? 'Product')) ?>', <?= $it['product_id'] ?? 0 ?>)" class="inline-flex items-center gap-1 bg-rose-50 border border-rose-200 text-rose-600 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-rose-100 transition">
                                    <i class="fas fa-star"></i> Rate
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<!-- Rating Modal -->
<div id="ratingModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white border border-rose-200 rounded-3xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-serif font-bold text-rose-600">Rate Product</h3>
            <button onclick="closeRatingModal()" class="text-gray-400 hover:text-gray-600 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <form id="ratingForm">
            <input type="hidden" name="product_id" id="ratingProductId">
            <div class="mb-5 text-center">
                <label class="block text-sm font-semibold text-gray-500 mb-2">Your Rating</label>
                <div id="starRating" class="flex justify-center gap-1 text-3xl">
                    <i class="far fa-star star text-gray-300 cursor-pointer hover:text-rose-400" data-star="1"></i>
                    <i class="far fa-star star text-gray-300 cursor-pointer hover:text-rose-400" data-star="2"></i>
                    <i class="far fa-star star text-gray-300 cursor-pointer hover:text-rose-400" data-star="3"></i>
                    <i class="far fa-star star text-gray-300 cursor-pointer hover:text-rose-400" data-star="4"></i>
                    <i class="far fa-star star text-gray-300 cursor-pointer hover:text-rose-400" data-star="5"></i>
                </div>
                <input type="hidden" name="star" id="selectedStar" value="0">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-500 mb-1">Your Review (max 15 words)</label>
                <textarea name="message" id="reviewMessage" rows="3" class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400" placeholder="Share your experience..."></textarea>
                <div class="text-xs text-gray-400 mt-1"><span id="wordCount">0</span>/15 words</div>
            </div>
            <div id="ratingMsg" class="text-sm font-medium hidden mb-4"></div>
            <button type="submit" class="w-full bg-gradient-to-r from-rose-400 to-pink-400 text-white py-3 rounded-xl font-bold shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition">
                Submit Review
            </button>
        </form>
    </div>
</div>

<?php include 'common/bottom.php'; ?>

<script>
function switchTab(tab) {
    const activeTab = document.getElementById('activeTab');
    const historyTab = document.getElementById('historyTab');
    const activeOrders = document.getElementById('activeOrders');
    const historyOrders = document.getElementById('historyOrders');

    if (tab === 'active') {
        activeOrders.classList.remove('hidden');
        historyOrders.classList.add('hidden');
        activeTab.className = 'flex-1 py-3 text-sm lg:text-base font-bold rounded-xl transition-all duration-300 bg-gradient-to-r from-rose-400 to-pink-400 text-white shadow-md';
        historyTab.className = 'flex-1 py-3 text-sm lg:text-base font-bold rounded-xl text-gray-500 transition-all duration-300';
    } else {
        activeOrders.classList.add('hidden');
        historyOrders.classList.remove('hidden');
        historyTab.className = 'flex-1 py-3 text-sm lg:text-base font-bold rounded-xl transition-all duration-300 bg-gradient-to-r from-rose-400 to-pink-400 text-white shadow-md';
        activeTab.className = 'flex-1 py-3 text-sm lg:text-base font-bold rounded-xl text-gray-500 transition-all duration-300';
    }
}

async function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) return;
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax_cancel', '1');
        formData.append('order_id', orderId);
        const res = await fetch('order.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Cancellation failed.');
        }
    } catch (e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}

// Rating modal
let ratingModal = document.getElementById('ratingModal');
let ratingForm = document.getElementById('ratingForm');
let ratingMsg = document.getElementById('ratingMsg');
let stars = document.querySelectorAll('.star');
let selectedStarInput = document.getElementById('selectedStar');

stars.forEach(star => {
    star.addEventListener('click', function() {
        let value = this.getAttribute('data-star');
        selectedStarInput.value = value;
        stars.forEach((s, index) => {
            if (index < value) {
                s.classList.remove('far', 'text-gray-300');
                s.classList.add('fas', 'text-rose-400');
            } else {
                s.classList.add('far', 'text-gray-300');
                s.classList.remove('fas', 'text-rose-400');
            }
        });
    });
    star.addEventListener('mouseenter', function() {
        let value = this.getAttribute('data-star');
        stars.forEach((s, index) => {
            if (index < value) s.classList.add('text-rose-400');
            else s.classList.remove('text-rose-400');
        });
    });
    star.addEventListener('mouseleave', function() {
        let current = selectedStarInput.value;
        stars.forEach((s, index) => {
            if (index < current) s.classList.add('text-rose-400');
            else s.classList.remove('text-rose-400');
        });
    });
});

document.getElementById('reviewMessage').addEventListener('input', function() {
    let words = this.value.trim().split(/\s+/).filter(w => w.length > 0);
    document.getElementById('wordCount').textContent = words.length;
    if (words.length > 15) {
        this.value = words.slice(0, 15).join(' ');
        document.getElementById('wordCount').textContent = 15;
    }
});

function openRatingModal(productName, productId) {
    document.getElementById('ratingProductId').value = productId;
    selectedStarInput.value = '0';
    stars.forEach(s => { s.classList.remove('fas','text-rose-400'); s.classList.add('far','text-gray-300'); });
    document.getElementById('reviewMessage').value = '';
    document.getElementById('wordCount').textContent = '0';
    ratingMsg.classList.add('hidden');
    ratingModal.style.display = 'flex';
}

function closeRatingModal() {
    ratingModal.style.display = 'none';
}

ratingModal.addEventListener('click', function(e) {
    if (e.target === ratingModal) closeRatingModal();
});

ratingForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (selectedStarInput.value == '0') {
        ratingMsg.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
        ratingMsg.textContent = 'Please select a star rating.';
        ratingMsg.classList.remove('hidden');
        return;
    }
    showLoader();
    try {
        const fd = new FormData(ratingForm);
        fd.append('ajax_rating', '1');
        const res = await fetch('order.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            ratingMsg.className = 'text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-xl';
            ratingMsg.textContent = data.message;
            ratingMsg.classList.remove('hidden');
            setTimeout(() => closeRatingModal(), 1500);
        } else {
            ratingMsg.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
            ratingMsg.textContent = data.message || 'Submission failed.';
            ratingMsg.classList.remove('hidden');
        }
    } catch (err) {
        ratingMsg.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
        ratingMsg.textContent = 'Network error.';
        ratingMsg.classList.remove('hidden');
    } finally {
        hideLoader();
    }
});
</script>
</body>
</html>