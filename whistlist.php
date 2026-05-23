<?php
require_once 'common/config.php';
if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];

// Handle remove action via AJAX
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_remove'])){
    header('Content-Type: application/json');
    $product_id = intval($_POST['product_id']);
    $conn->query("DELETE FROM wishlist WHERE user_id=$uid AND product_id=$product_id");
    echo json_encode(['success'=>true]);
    exit;
}

$wishlist = $conn->query("SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = $uid ORDER BY w.created_at DESC");
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            My Wishlist
        </h1>

        <?php if($wishlist->num_rows == 0): ?>
            <div class="flex flex-col items-center justify-center py-20">
                <div class="w-28 h-28 lg:w-36 lg:h-36 rounded-full bg-white border border-rose-200 flex items-center justify-center mb-6 shadow-sm">
                    <i class="fas fa-heart-broken text-5xl lg:text-6xl text-rose-300"></i>
                </div>
                <p class="text-gray-500 text-lg font-medium">Your wishlist is empty</p>
                <a href="index.php" class="mt-6 bg-gradient-to-r from-rose-400 to-pink-400 text-white px-8 py-3 lg:px-10 lg:py-4 rounded-full font-bold shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all">
                    <i class="fas fa-shopping-bag mr-2"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                <?php while($prod = $wishlist->fetch_assoc()): ?>
                <div class="bg-white border border-rose-200/60 rounded-3xl p-3 lg:p-4 flex flex-col group transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30 relative">
                    <a href="product_detail.php?id=<?= $prod['id'] ?>" class="flex flex-col flex-1">
                        <div class="w-full h-40 lg:h-52 rounded-2xl overflow-hidden mb-3 bg-rose-50 border border-rose-100">
                            <img src="<?= $prod['image'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" onerror="this.src='https://via.placeholder.com/300/fce7f3/ec4899?text=No+Image'">
                        </div>
                        <h3 class="text-sm lg:text-base font-bold text-gray-800 truncate"><?= htmlspecialchars($prod['name']) ?></h3>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="text-rose-600 font-extrabold text-lg lg:text-xl">₹<?= number_format($prod['price'],2) ?></span>
                        </div>
                        <?php if (!empty($prod['mrp']) && $prod['mrp'] > $prod['price']): ?>
                            <span class="text-gray-400 text-xs line-through">₹<?= number_format($prod['mrp'],2) ?></span>
                        <?php endif; ?>
                    </a>
                    <button onclick="removeFromWishlist(<?= $prod['id'] ?>)" class="absolute top-3 right-3 bg-red-50 hover:bg-red-100 text-red-400 rounded-full p-2 transition-colors z-10" title="Remove from wishlist">
                        <i class="fas fa-trash-alt text-sm lg:text-base"></i>
                    </button>
                </div>
                <?php endwhile; ?>
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
async function removeFromWishlist(productId) {
    if(!confirm('Remove this item from wishlist?')) return;
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax_remove', '1');
        formData.append('product_id', productId);
        const res = await fetch('whistlist.php', { method:'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            location.reload();
        } else {
            alert('Failed to remove.');
        }
    } catch(e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}
</script>
</body>
</html>