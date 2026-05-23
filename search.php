<?php
require_once 'common/config.php';

$query = trim($_GET['q'] ?? '');
$searched = false;
$categories = $subcategories = $products = null;

if ($query !== '') {
    $searched = true;
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';

    // Search categories
    $categories = $conn->query("SELECT * FROM categories WHERE name LIKE '$searchTerm' ORDER BY name ASC");

    // Search subcategories
    $subcategories = $conn->query("SELECT s.*, c.name AS cat_name FROM subcategories s JOIN categories c ON s.cat_id = c.id WHERE s.name LIKE '$searchTerm' ORDER BY s.name ASC");

    // Search products
    $products = $conn->query("SELECT p.*, c.name AS cat_name 
                              FROM products p 
                              JOIN categories c ON p.cat_id = c.id 
                              WHERE p.name LIKE '$searchTerm' OR p.description LIKE '$searchTerm' 
                              ORDER BY p.name ASC");
}
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            <?php if ($searched): ?>
                Results for "<?= htmlspecialchars($query) ?>"
            <?php else: ?>
                Search Products
            <?php endif; ?>
        </h1>

        <?php if (!$searched): ?>
            <!-- Empty state -->
            <div class="bg-white border border-rose-200/60 rounded-3xl p-10 lg:p-16 text-center shadow-sm">
                <div class="inline-block mb-4 p-6 lg:p-8 rounded-full bg-white border border-rose-200">
                    <i class="fas fa-search text-5xl lg:text-6xl text-rose-300"></i>
                </div>
                <p class="text-gray-500 text-lg lg:text-xl mb-6">Discover what you desire</p>
                <form action="search.php" method="get" class="flex gap-2 max-w-md lg:max-w-lg mx-auto">
                    <input type="text" name="q" placeholder="Search for luxury items..."
                           class="flex-1 bg-white border border-rose-200 rounded-2xl px-5 py-3.5 lg:py-4 text-sm lg:text-base text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                    <button type="submit" class="bg-gradient-to-r from-rose-400 to-pink-400 text-white px-6 py-3.5 lg:px-8 lg:py-4 rounded-2xl font-bold shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all duration-300">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>

            <!-- Matching Categories -->
            <?php if ($categories && $categories->num_rows > 0): ?>
            <div class="mb-8">
                <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mb-4 flex items-center gap-2">
                    <i class="fas fa-layer-group text-rose-400"></i> Categories
                </h2>
                <div class="flex overflow-x-auto gap-4 lg:gap-6 pb-2 hide-scrollbar">
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <a href="product.php?cat=<?= $cat['id'] ?>"
                       class="flex-shrink-0 flex flex-col items-center px-4 py-3 lg:px-6 lg:py-4 rounded-2xl bg-white border border-rose-200/60 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30 transition-all duration-300 active:scale-95">
                        <div class="w-14 h-14 lg:w-20 lg:h-20 rounded-full bg-rose-50 flex items-center justify-center mb-2 border border-rose-200 shadow-sm">
                            <?php if (!empty($cat['image'])): ?>
                                <img src="<?= htmlspecialchars($cat['image']) ?>" class="w-10 h-10 lg:w-14 lg:h-14 object-cover rounded-full" onerror="this.style.display='none'">
                            <?php else: ?>
                                <i class="fas fa-folder text-rose-400 text-xl lg:text-2xl"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs lg:text-sm font-semibold text-gray-700 text-center"><?= htmlspecialchars($cat['name']) ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Matching Subcategories -->
            <?php if ($subcategories && $subcategories->num_rows > 0): ?>
            <div class="mb-8">
                <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mb-4 flex items-center gap-2">
                    <i class="fas fa-folder-open text-rose-400"></i> Subcategories
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                    <?php while ($sc = $subcategories->fetch_assoc()): ?>
                    <a href="product.php?subcat=<?= $sc['id'] ?>"
                       class="bg-white border border-rose-200/60 rounded-2xl p-4 lg:p-5 flex flex-col items-center text-center hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30 transition-all duration-300 active:scale-95 group">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-xl bg-rose-50 flex items-center justify-center mb-3 border border-rose-200 shadow-inner group-hover:border-rose-300 transition-all">
                            <?php if (!empty($sc['image'])): ?>
                                <img src="<?= htmlspecialchars($sc['image']) ?>" class="w-10 h-10 lg:w-12 lg:h-12 object-cover rounded-lg" onerror="this.style.display='none'">
                            <?php else: ?>
                                <i class="fas fa-folder text-rose-400 text-xl lg:text-2xl"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm lg:text-base font-semibold text-gray-700 group-hover:text-rose-600 leading-tight"><?= htmlspecialchars($sc['name']) ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Matching Products -->
            <?php if ($products && $products->num_rows > 0): ?>
            <div class="mb-8">
                <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mb-4 flex items-center gap-2">
                    <i class="fas fa-box text-rose-400"></i> Products
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                    <?php while ($prod = $products->fetch_assoc()): ?>
                    <a href="product_detail.php?id=<?= $prod['id'] ?>"
                       class="bg-white border border-rose-200/60 rounded-3xl p-3 lg:p-4 flex flex-col group transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30">
                        <div class="w-full h-36 lg:h-48 rounded-2xl overflow-hidden mb-3 border border-rose-100">
                            <img src="<?= $prod['image'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                 onerror="this.src='https://via.placeholder.com/300/fce7f3/ec4899?text=No+Image'">
                        </div>
                        <h3 class="text-sm lg:text-base font-bold text-gray-800 truncate"><?= htmlspecialchars($prod['name']) ?></h3>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="text-rose-600 font-extrabold text-lg lg:text-xl">₹<?= number_format($prod['price'],2) ?></span>
                        </div>
                        <?php if (!empty($prod['mrp']) && $prod['mrp'] > $prod['price']): ?>
                            <span class="text-gray-400 text-xs line-through">₹<?= number_format($prod['mrp'],2) ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- No results at all -->
            <?php if (
                (!$categories || $categories->num_rows == 0) &&
                (!$subcategories || $subcategories->num_rows == 0) &&
                (!$products || $products->num_rows == 0)
            ): ?>
            <div class="bg-white border border-rose-200/60 rounded-3xl p-10 lg:p-16 text-center shadow-sm">
                <div class="inline-block mb-4 p-6 lg:p-8 rounded-full bg-white border border-rose-200">
                    <i class="fas fa-search-minus text-5xl lg:text-6xl text-rose-300"></i>
                </div>
                <p class="text-gray-500 text-lg lg:text-xl">Nothing found for "<?= htmlspecialchars($query) ?>"</p>
                <a href="search.php" class="inline-block mt-4 text-rose-500 font-medium hover:text-rose-400 transition-colors">Clear search</a>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    <br>
    <br>
    <br>
    <br>
</main>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php include 'common/bottom.php'; ?>