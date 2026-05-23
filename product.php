<?php
require_once 'common/config.php';
$cat_id = $_GET['cat'] ?? null;
$subcat_id = $_GET['subcat'] ?? null;
$sort = $_GET['sort'] ?? '';

$categoryName = 'All Products';
$where = '';

// Subcategory filter takes priority
if ($subcat_id) {
    $where = "WHERE p.subcat_id = " . intval($subcat_id);
    $subcatRes = $conn->query("SELECT name FROM subcategories WHERE id=" . intval($subcat_id));
    if ($subcatRow = $subcatRes->fetch_assoc()) {
        $categoryName = htmlspecialchars($subcatRow['name']);
    }
} elseif ($cat_id) {
    $where = "WHERE p.cat_id = " . intval($cat_id);
    $catRes = $conn->query("SELECT name FROM categories WHERE id=" . intval($cat_id));
    if ($catRow = $catRes->fetch_assoc()) {
        $categoryName = htmlspecialchars($catRow['name']);
    }
}

$order = "ORDER BY p.created_at DESC";
if ($sort == 'price_asc') $order = "ORDER BY p.price ASC";
elseif ($sort == 'price_desc') $order = "ORDER BY p.price DESC";

$products = $conn->query("SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON p.cat_id = c.id $where $order");
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            <?= $categoryName ?>
        </h1>

        <!-- Sort Dropdown -->
        <div class="flex gap-2 mb-8">
            <select id="sortSelect" class="bg-white border border-rose-200 rounded-2xl px-4 py-2.5 text-sm lg:text-base text-gray-700 focus:ring-2 focus:ring-rose-400 focus:border-transparent shadow-sm" onchange="applyFilter()">
                <option value="">✨ Sort By</option>
                <option value="newest" <?= $sort==''?'selected':'' ?>>Newest</option>
                <option value="price_asc" <?= $sort=='price_asc'?'selected':'' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>>Price: High to Low</option>
            </select>
        </div>

        <?php if ($products->num_rows == 0): ?>
            <div class="text-center py-16">
                <div class="inline-block mb-4 p-6 rounded-full bg-white/80 border border-rose-200">
                    <i class="fas fa-box-open text-5xl text-rose-400"></i>
                </div>
                <p class="text-gray-500 text-lg">No products found in this category</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6" id="productGrid">
                <?php while($prod = $products->fetch_assoc()): ?>
                <a href="product_detail.php?id=<?= $prod['id'] ?>"
                   class="bg-white border border-rose-200/60 rounded-3xl p-3 lg:p-4 flex flex-col group transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30">
                    <div class="w-full h-40 lg:h-52 rounded-2xl overflow-hidden mb-3 border border-rose-100">
                        <img src="<?= $prod['image'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" onerror="this.src='https://via.placeholder.com/300/fce7f3/ec4899?text=No+Image'">
                    </div>
                    <h3 class="text-sm lg:text-base font-bold text-gray-800 truncate">
                        <?= htmlspecialchars($prod['name']) ?>
                    </h3>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-rose-600 font-extrabold text-lg lg:text-xl">₹<?= number_format($prod['price'],2) ?></span>
                    </div>
                    <?php if (!empty($prod['mrp']) && $prod['mrp'] > $prod['price']): ?>
                        <span class="text-gray-400 text-xs line-through">₹<?= number_format($prod['mrp'],2) ?></span>
                    <?php endif; ?>
                </a>
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
function applyFilter(){
    const sort = document.getElementById('sortSelect').value;
    let url = new URL(window.location.href);
    if(sort) url.searchParams.set('sort', sort);
    else url.searchParams.delete('sort');
    window.location = url.toString();
}
</script>
</body>
</html>