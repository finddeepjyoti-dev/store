<?php
require_once 'common/config.php';

// AJAX: return subcategories for a given category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] == 'get_subcategories') {
        $cat_id = intval($_POST['cat_id']);
        $subcats = $conn->query("SELECT id, name, image FROM subcategories WHERE cat_id = $cat_id ORDER BY name");
        $list = [];
        while ($sc = $subcats->fetch_assoc()) {
            $list[] = $sc;
        }
        echo json_encode(['success' => true, 'subcategories' => $list]);
        exit;
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Heading -->
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            Categories→
        </h1>

        <!-- Category Strip (horizontal scroll) -->
        <div class="mb-10">
            <div class="flex overflow-x-auto gap-4 lg:gap-6 pb-3 hide-scrollbar" id="categoryStrip">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <button onclick="selectCategory(<?= $cat['id'] ?>, this)"
                        class="flex-shrink-0 flex flex-col items-center px-5 py-4 rounded-2xl transition-all duration-300 bg-white/90 text-gray-600 hover:text-rose-600 hover:bg-rose-50 border border-rose-100 active:scale-95 category-btn"
                        data-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>"
                        data-id="<?= $cat['id'] ?>">
                    <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-full bg-gradient-to-br from-rose-50 to-pink-50 flex items-center justify-center mb-3 border border-rose-200 shadow-sm">
                        <?php if (!empty($cat['image'])): ?>
                            <img src="<?= htmlspecialchars($cat['image']) ?>" class="w-12 h-12 lg:w-14 lg:h-14 object-cover rounded-full" onerror="this.style.display='none'">
                        <?php else: ?>
                            <i class="fas fa-layer-group text-rose-400 text-2xl lg:text-3xl"></i>
                        <?php endif; ?>
                    </div>
                    <span class="text-sm font-semibold text-center whitespace-nowrap lg:text-base"><?= htmlspecialchars($cat['name']) ?></span>
                </button>
                <?php endwhile; ?>
            </div>
            <div id="noCategoryFound" class="text-center text-gray-400 text-sm py-4 hidden">No categories found</div>
        </div>

        <!-- Subcategory Section -->
        <div>
            <h3 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mb-6 flex items-center gap-2" id="subcatHeading">
                <i class="fas fa-folder-tree text-rose-400"></i> Explore Subcategories
            </h3>
            <div id="subcategoryGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                <div class="col-span-full text-center py-16" id="placeholderMsg">
                    <div class="inline-block mb-4 p-5 rounded-full bg-white/70 border border-rose-200">
                        <i class="fas fa-hand-pointer text-4xl text-rose-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium text-lg">Select a category to view subcategories</p>
                </div>
            </div>
        </div>
    </div>
    <br>
    <br>
    <br>
    <br>
</main>

<?php include 'common/bottom.php'; ?>

<script>
const searchInput = document.getElementById('searchInput');
const categoryBtns = document.querySelectorAll('.category-btn');
const noCatFound = document.getElementById('noCategoryFound');
const subcatGrid = document.getElementById('subcategoryGrid');
const placeholderMsg = document.getElementById('placeholderMsg');
const subcatHeading = document.getElementById('subcatHeading');

let activeCategoryBtn = null;
let currentSubcats = [];

// Live search: filter categories and subcategories
searchInput?.addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    let found = false;

    categoryBtns.forEach(btn => {
        const name = btn.getAttribute('data-name');
        if (name.includes(query)) {
            btn.style.display = '';
            found = true;
        } else {
            btn.style.display = 'none';
        }
    });
    noCatFound.classList.toggle('hidden', found);

    if (activeCategoryBtn && currentSubcats.length > 0) {
        let html = '';
        let anySubVisible = false;
        currentSubcats.forEach(sc => {
            const scName = sc.name.toLowerCase();
            if (scName.includes(query) || query === '') {
                html += `
                <a href="product.php?subcat=${sc.id}"
                   class="subcat-item bg-white border border-rose-200/60 rounded-2xl p-4 lg:p-5 flex flex-col items-center text-center hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30 transition-all duration-300 active:scale-95 group"
                   data-name="${scName}">
                    <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-xl bg-gradient-to-br from-rose-50 to-pink-50 flex items-center justify-center mb-3 border border-rose-200 shadow-inner group-hover:border-rose-300 transition-all">
                        ${sc.image 
                            ? `<img src="${sc.image}" class="w-10 h-10 lg:w-12 lg:h-12 object-cover rounded-lg" onerror="this.style.display='none'">` 
                            : '<i class="fas fa-folder text-rose-400 text-2xl lg:text-3xl"></i>'}
                    </div>
                    <span class="text-sm lg:text-base font-semibold text-gray-700 group-hover:text-rose-600 leading-tight">${sc.name}</span>
                </a>`;
                anySubVisible = true;
            }
        });
        subcatGrid.innerHTML = html;
        if (!anySubVisible && query !== '') {
            subcatGrid.innerHTML = '<div class="col-span-full text-center py-16"><div class="inline-block mb-4 p-5 rounded-full bg-white/70 border border-rose-200"><i class="fas fa-search-minus text-4xl text-rose-400"></i></div><p class="text-gray-500 font-medium text-lg">No matching subcategories</p></div>';
        }
    }
});

async function selectCategory(catId, btnElement) {
    // Highlight active category
    if (activeCategoryBtn) {
        activeCategoryBtn.classList.remove('bg-rose-50', 'text-rose-600', 'border-rose-300', 'shadow-lg');
        activeCategoryBtn.classList.add('bg-white/90', 'text-gray-600', 'border-rose-100');
    }
    btnElement.classList.add('bg-rose-50', 'text-rose-600', 'border-rose-300', 'shadow-lg');
    btnElement.classList.remove('bg-white/90', 'text-gray-600', 'border-rose-100');
    activeCategoryBtn = btnElement;

    // Loading state
    subcatGrid.innerHTML = '<div class="col-span-full flex justify-center py-16"><div class="flex space-x-2"><div class="w-4 h-4 bg-rose-500 rounded-full animate-bounce"></div><div class="w-4 h-4 bg-pink-400 rounded-full animate-bounce" style="animation-delay:0.15s"></div><div class="w-4 h-4 bg-rose-300 rounded-full animate-bounce" style="animation-delay:0.3s"></div></div></div>';
    placeholderMsg.classList.add('hidden');
    subcatHeading.innerHTML = '<i class="fas fa-folder-tree text-rose-400"></i> Explore Subcategories';

    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_subcategories');
        formData.append('cat_id', catId);
        const res = await fetch('category.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success && data.subcategories.length > 0) {
            currentSubcats = data.subcategories;
            let html = '';
            data.subcategories.forEach(sc => {
                html += `
                <a href="product.php?subcat=${sc.id}"
                   class="subcat-item bg-white border border-rose-200/60 rounded-2xl p-4 lg:p-5 flex flex-col items-center text-center hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30 transition-all duration-300 active:scale-95 group"
                   data-name="${sc.name.toLowerCase()}">
                    <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-xl bg-gradient-to-br from-rose-50 to-pink-50 flex items-center justify-center mb-3 border border-rose-200 shadow-inner group-hover:border-rose-300 transition-all">
                        ${sc.image 
                            ? `<img src="${sc.image}" class="w-10 h-10 lg:w-12 lg:h-12 object-cover rounded-lg" onerror="this.style.display='none'">` 
                            : '<i class="fas fa-folder text-rose-400 text-2xl lg:text-3xl"></i>'}
                    </div>
                    <span class="text-sm lg:text-base font-semibold text-gray-700 group-hover:text-rose-600 leading-tight">${sc.name}</span>
                </a>`;
            });
            subcatGrid.innerHTML = html;
            // re-apply search filter if any
            const query = searchInput?.value.trim().toLowerCase() || '';
            if (query) {
                searchInput.dispatchEvent(new Event('input'));
            }
        } else {
            currentSubcats = [];
            subcatGrid.innerHTML = '<div class="col-span-full text-center py-16"><div class="inline-block mb-4 p-5 rounded-full bg-white/70 border border-rose-200"><i class="fas fa-folder-open text-4xl text-rose-400"></i></div><p class="text-gray-500 font-medium text-lg">No subcategories found</p></div>';
        }
    } catch(e) {
        currentSubcats = [];
        subcatGrid.innerHTML = '<div class="col-span-full text-center py-16 text-red-400"><i class="fas fa-exclamation-triangle text-3xl mb-3"></i><p>Error loading subcategories.</p></div>';
    }
}

// Auto‑select first category on load
if (categoryBtns.length > 0) {
    selectCategory(categoryBtns[0].getAttribute('data-id'), categoryBtns[0]);
}
</script>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</body>
</html>