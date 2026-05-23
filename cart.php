<?php
require_once 'common/config.php';
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// AJAX operations
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['ajax'])){
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $pid = intval($_POST['product_id']);
    if($action == 'update'){
        $qty = max(1, intval($_POST['qty']));
        if(isset($_SESSION['cart'][$pid])){
            $_SESSION['cart'][$pid]['qty'] = $qty;
        }
    } elseif($action == 'delete'){
        unset($_SESSION['cart'][$pid]);
    }
    $total = 0;
    foreach($_SESSION['cart'] as $item) $total += $item['qty'] * $item['price'];
    echo json_encode(['success'=>true, 'total'=>number_format($total,2), 'cartCount'=>array_sum(array_column($_SESSION['cart'],'qty'))]);
    exit;
}
$total = 0;
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            My Cart
        </h1>

        <?php if(empty($_SESSION['cart'])): ?>
            <!-- Empty Cart -->
            <div class="flex flex-col items-center justify-center py-20">
                <div class="w-28 h-28 lg:w-36 lg:h-36 rounded-full bg-white/80 flex items-center justify-center mb-6 border border-rose-200 shadow-sm">
                    <i class="fas fa-shopping-cart text-5xl lg:text-6xl text-rose-300"></i>
                </div>
                <p class="text-gray-500 text-lg font-medium">Your cart is empty</p>
                <a href="index.php" class="mt-6 bg-gradient-to-r from-rose-400 to-pink-400 text-white px-8 py-3 lg:px-10 lg:py-4 rounded-full font-bold shadow-lg shadow-rose-200/50 active:scale-95 transition-all duration-300 hover:shadow-rose-300/60 text-lg lg:text-xl">
                    <i class="fas fa-shopping-bag mr-2"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-10">
                <!-- Items List -->
                <div id="cartItems" class="flex-1 space-y-4">
                    <?php foreach($_SESSION['cart'] as $pid => $item): $total += $item['qty'] * $item['price']; ?>
                    <div class="bg-white border border-rose-200/60 rounded-3xl p-4 lg:p-5 flex gap-4 items-center transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30 group" data-pid="<?= $pid ?>">
                        <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl overflow-hidden border border-rose-100 shadow-inner flex-shrink-0">
                            <img src="<?= $item['image'] ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-800 text-sm lg:text-base truncate"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="text-rose-600 font-extrabold text-lg lg:text-xl mt-1">₹<?= number_format($item['price'],2) ?></p>
                            <div class="flex items-center gap-3 mt-2">
                                <button onclick="changeQty(<?= $pid ?>, -1)" class="w-8 h-8 lg:w-10 lg:h-10 bg-white border border-rose-200 text-rose-600 rounded-full flex items-center justify-center font-bold hover:bg-rose-50 transition active:scale-90">
                                    −
                                </button>
                                <span class="qty-val text-sm lg:text-base font-bold text-rose-600 w-6 text-center"><?= $item['qty'] ?></span>
                                <button onclick="changeQty(<?= $pid ?>, 1)" class="w-8 h-8 lg:w-10 lg:h-10 bg-white border border-rose-200 text-rose-600 rounded-full flex items-center justify-center font-bold hover:bg-rose-50 transition active:scale-90">
                                    +
                                </button>
                            </div>
                        </div>
                        <button onclick="deleteItem(<?= $pid ?>)" class="text-gray-400 hover:text-red-400 transition-colors p-2 active:scale-90">
                            <i class="fas fa-trash-alt text-lg lg:text-xl"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total & Checkout (sticky on desktop) -->
                <div class="lg:w-80 xl:w-96">
                    <div class="bg-white border border-rose-200/60 rounded-3xl p-5 lg:p-6 shadow-lg shadow-rose-100/50 lg:sticky lg:top-28">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Subtotal (<?= array_sum(array_column($_SESSION['cart'],'qty')) ?> items)</span>
                            <span id="totalAmount" class="text-2xl lg:text-3xl font-extrabold text-rose-600">₹<?= number_format($total,2) ?></span>
                        </div>
                        <a href="checkout.php" class="mt-5 block w-full bg-gradient-to-r from-rose-400 to-pink-400 text-white text-center py-3.5 rounded-2xl font-bold text-lg lg:text-xl shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all duration-300">
                            <i class="fas fa-lock mr-2"></i> Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <br>
    <br>
    <br>
    <br>
</main>

<?php include 'common/bottom.php'; ?>

<script>
async function updateCart(pid, action, qty=1){
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax','1');
        formData.append('action', action);
        formData.append('product_id', pid);
        if(action=='update') formData.append('qty', qty);
        const res = await fetch('cart.php', { method:'POST', body: formData });
        const data = await res.json();
        if(data.success){
            document.getElementById('totalAmount').textContent = '₹'+data.total;
            document.getElementById('cartCount').textContent = data.cartCount;
            if(action=='delete'){
                const elem = document.querySelector(`[data-pid="${pid}"]`);
                if(elem) {
                    elem.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => elem.remove(), 300);
                }
                // reload if cart empty
                if(document.querySelectorAll('#cartItems > div').length === 0) location.reload();
            }
        }
    } catch(e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}
function changeQty(pid, delta){
    const el = document.querySelector(`[data-pid="${pid}"] .qty-val`);
    let qty = parseInt(el.textContent) + delta;
    if(qty<1) qty=1;
    el.textContent = qty;
    updateCart(pid, 'update', qty);
}
function deleteItem(pid){ updateCart(pid, 'delete'); }
</script>
</body>
</html>