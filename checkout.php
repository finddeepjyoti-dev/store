<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'common/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }

// Ensure district and pin columns exist in users table
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS district VARCHAR(100) NULL AFTER address");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS pin VARCHAR(10) NULL AFTER district");

$user = $conn->query("SELECT * FROM users WHERE id=" . $_SESSION['user_id'])->fetch_assoc();

$activeQR = $conn->query("SELECT * FROM qr_codes WHERE status = 1 LIMIT 1")->fetch_assoc();

$error = '';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $district = trim($_POST['district']);
    $pin      = trim($_POST['pin']);

    $payment_method = $_POST['payment_method'] ?? 'COD';
    $screenshotPath = '';

    // Validate QR payment
    if ($payment_method === 'QR') {
        if (!empty($_FILES['screenshot']['name'])) {
            $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
            $imgName = time() . '_payment.' . $ext;
            $targetDir = 'images/payments/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $targetDir . $imgName)) {
                $screenshotPath = 'images/payments/' . $imgName;
            } else {
                $error = "Failed to upload screenshot.";
            }
        } else {
            $error = "Please upload a payment screenshot for QR payment.";
        }
    }

    if (empty($phone)) $error = "Phone number is required.";
    if (empty($address)) $error = "Shipping address is required.";
    if (empty($district)) $error = "District is required.";
    if (empty($pin)) $error = "PIN Number is required.";

    if (!isset($error) || $error === '') {
        // ---- Save to users table ----
        $stmt = $conn->prepare("UPDATE users SET phone=?, address=?, district=?, pin=? WHERE id=?");
        $stmt->bind_param("ssssi", $phone, $address, $district, $pin, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        // ---- Build full shipping address for order ----
        $fullAddress = $address . ", " . $district . " - " . $pin;

        $total = 0;
        foreach ($_SESSION['cart'] as $item) $total += $item['qty'] * $item['price'];

        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, screenshot, address, status) VALUES (?, ?, ?, ?, ?, 'Placed')");
        $stmt->bind_param("idsss", $_SESSION['user_id'], $total, $payment_method, $screenshotPath, $fullAddress);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        foreach ($_SESSION['cart'] as $pid => $item) {
            $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, $pid, {$item['qty']}, {$item['price']})");
        }

        $_SESSION['cart'] = [];
        header("Location: order.php?id=$order_id");
        exit;
    }
}
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            Checkout
        </h1>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-500 p-4 rounded-2xl mb-6 flex items-center gap-3 font-semibold">
                <i class="fas fa-exclamation-circle text-lg"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6 lg:gap-10">
            <!-- Order Summary -->
            <div class="flex-1">
                <div class="bg-white border border-rose-200/60 rounded-3xl p-5 lg:p-6 shadow-sm">
                    <h2 class="font-bold text-lg lg:text-xl text-rose-600 flex items-center gap-2 mb-4">
                        <i class="fas fa-receipt text-rose-400"></i> Order Summary
                    </h2>
                    <?php
                    $totalQty = 0;
                    $totalPrice = 0;
                    foreach ($_SESSION['cart'] as $pid => $item):
                        $subtotal = $item['qty'] * $item['price'];
                        $totalQty += $item['qty'];
                        $totalPrice += $subtotal;
                    ?>
                    <div class="flex items-center gap-4 mb-4 pb-4 border-b border-rose-100 last:border-0 last:pb-0 last:mb-0">
                        <img src="<?= $item['image'] ?>" class="w-14 h-14 lg:w-16 lg:h-16 rounded-xl object-cover border border-rose-200 shadow-sm">
                        <div class="flex-1">
                            <h3 class="font-semibold text-sm lg:text-base text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="text-xs lg:text-sm text-gray-500 mt-0.5">₹<?= number_format($item['price'], 2) ?> × <?= $item['qty'] ?></p>
                        </div>
                        <span class="font-bold text-sm lg:text-base text-rose-600">₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-rose-100">
                        <span class="text-gray-500 font-medium">Total Items: <strong class="text-gray-700"><?= $totalQty ?></strong></span>
                        <span class="text-2xl lg:text-3xl font-extrabold text-rose-600">₹<?= number_format($totalPrice, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Shipping & Payment Form -->
            <div class="flex-1">
                <form method="post" enctype="multipart/form-data" class="bg-white border border-rose-200/60 rounded-3xl p-5 lg:p-6 space-y-5 shadow-sm">
                    <h2 class="font-bold text-lg lg:text-xl text-rose-600 flex items-center gap-2 mb-2">
                        <i class="fas fa-truck text-rose-400"></i> Shipping Details
                    </h2>

                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Full Name</label>
                        <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly class="w-full bg-gray-50 border border-rose-200 rounded-xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
                        <input type="hidden" name="name" value="<?= htmlspecialchars($user['name']) ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Phone Number *</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required
                               placeholder="Enter your phone number"
                               class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-2">Address *</label>
                        <textarea name="address" rows="2" required class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-500 mb-1">District *</label>
                            <input type="text" name="district" value="<?= htmlspecialchars($user['district'] ?? '') ?>" required
                                   placeholder="Your district"
                                   class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-500 mb-1">PIN Number *</label>
                            <input type="text" name="pin" value="<?= htmlspecialchars($user['pin'] ?? '') ?>" required
                                   placeholder="6-digit PIN"
                                   class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-3">Payment Method</label>
                        <div class="space-y-3">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="radio" name="payment_method" value="COD" checked class="mt-1 accent-rose-400 w-5 h-5">
                                <div class="flex-1 bg-gray-50 rounded-2xl p-3 border border-rose-100 group-hover:border-rose-300 transition-colors">
                                    <span class="text-sm font-semibold text-gray-800">Cash on Delivery</span>
                                    <p class="text-xs text-gray-500 mt-1">Pay when you receive the order</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="radio" name="payment_method" value="QR" class="mt-1 accent-rose-400 w-5 h-5">
                                <div class="flex-1 bg-gray-50 rounded-2xl p-3 border border-rose-100 group-hover:border-rose-300 transition-colors">
                                    <span class="text-sm font-semibold text-gray-800">QR Code Payment</span>
                                    <p class="text-xs text-gray-500 mt-1">Scan the QR code and upload screenshot</p>
                                </div>
                            </label>
                        </div>

                        <div id="qrSection" class="mt-4 hidden">
                            <?php if ($activeQR): ?>
                                <p class="text-sm font-medium text-gray-600 mb-2">
                                    Scan this QR code to pay <?= !empty($activeQR['company_name']) ? 'to <strong class="text-rose-600">' . htmlspecialchars($activeQR['company_name']) . '</strong>' : '' ?>:
                                </p>
                                <img src="<?= htmlspecialchars($activeQR['image']) ?>" alt="QR Code" class="w-48 h-48 mx-auto rounded-2xl shadow-md border border-rose-200 mb-4">
                            <?php else: ?>
                                <p class="text-sm text-red-400 mb-2">No active QR code available. Please contact support.</p>
                            <?php endif; ?>
                            <label class="block text-sm font-semibold text-gray-500 mb-1">Upload Payment Screenshot *</label>
                            <input type="file" name="screenshot" id="screenshotInput" accept="image/*" class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-sm text-gray-800 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-rose-400 file:text-white hover:file:bg-rose-500 transition-all">
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="w-full bg-gradient-to-r from-rose-400 to-pink-400 text-white py-4 rounded-2xl font-bold text-lg lg:text-xl shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> Place Order
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>
<br>
<br>
<br>
<br>
<?php include 'common/bottom.php'; ?>

<script>
const qrSection = document.getElementById('qrSection');
const screenshotInput = document.getElementById('screenshotInput');
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'QR') {
            qrSection.classList.remove('hidden');
            screenshotInput.required = true;
        } else {
            qrSection.classList.add('hidden');
            screenshotInput.required = false;
        }
    });
});
</script>
</body>
</html>