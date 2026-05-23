<?php
require_once 'common/config.php';
$id = intval($_GET['id']);
$product = $conn->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.cat_id=c.id WHERE p.id=$id")->fetch_assoc();
if(!$product) die('Product not found');

// Related products
if (!empty($product['subcat_id'])) {
    $related = $conn->query("SELECT * FROM products WHERE subcat_id = {$product['subcat_id']} AND id != $id ORDER BY created_at DESC LIMIT 4");
} else {
    $related = $conn->query("SELECT * FROM products WHERE cat_id = {$product['cat_id']} AND id != $id ORDER BY created_at DESC LIMIT 4");
}

// Images slider
$images = [];
if (!empty($product['image'])) $images[] = $product['image'];
$extraImages = $conn->query("SELECT image FROM product_images WHERE product_id = $id ORDER BY sort_order");
while ($ex = $extraImages->fetch_assoc()) $images[] = $ex['image'];
if (empty($images)) $images[] = 'https://via.placeholder.com/400/fce7f3/ec4899?text=No+Image';

// Check if product is in user's wishlist
$isWishlisted = false;
if (isset($_SESSION['user_id'])) {
    $check = $conn->query("SELECT id FROM wishlist WHERE user_id={$_SESSION['user_id']} AND product_id=$id");
    $isWishlisted = $check->num_rows > 0;
}

// AJAX: add to cart
if(isset($_POST['ajax_add'])){
    header('Content-Type: application/json');
    $qty = max(1, intval($_POST['qty']));
    if(isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['qty']+=$qty;
    else $_SESSION['cart'][$id] = ['qty'=>$qty,'price'=>$product['price'],'name'=>$product['name'],'image'=>$product['image']];
    echo json_encode(['success'=>true, 'cartCount'=>array_sum(array_column($_SESSION['cart'],'qty'))]);
    exit;
}

// AJAX: wishlist toggle
if(isset($_POST['ajax_wishlist'])){
    header('Content-Type: application/json');
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success'=>false, 'message'=>'Please login first']);
        exit;
    }
    $uid = $_SESSION['user_id'];
    $check = $conn->query("SELECT id FROM wishlist WHERE user_id=$uid AND product_id=$id");
    if($check->num_rows > 0){
        $conn->query("DELETE FROM wishlist WHERE user_id=$uid AND product_id=$id");
        echo json_encode(['success'=>true, 'action'=>'removed', 'count'=>$conn->query("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id=$uid")->fetch_assoc()['cnt']]);
    } else {
        $conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ($uid, $id)");
        echo json_encode(['success'=>true, 'action'=>'added', 'count'=>$conn->query("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id=$uid")->fetch_assoc()['cnt']]);
    }
    exit;
}

// Fetch product reviews
$reviews = $conn->query("SELECT r.star, r.message, r.created_at, u.name 
                         FROM productratingreport r 
                         JOIN users u ON r.user_id = u.id 
                         WHERE r.product_id = $id 
                         ORDER BY r.created_at DESC 
                         LIMIT 10");
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            <!-- Left: Image Slider -->
            <div class="lg:w-1/2 w-full flex-shrink-0">
                <div class="relative w-full h-72 sm:h-80 lg:h-[28rem] bg-white rounded-3xl overflow-hidden border border-rose-200 shadow-md cursor-pointer" id="slider" onclick="openImageModal()">
                    <?php foreach($images as $i => $img): ?>
                    <img src="<?= $img ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 pointer-events-none <?= $i===0?'opacity-100':'opacity-0' ?>" data-slide="<?= $i ?>">
                    <?php endforeach; ?>
                    <?php if (count($images) > 1): ?>
                        <button class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 backdrop-blur rounded-full p-2.5 border border-rose-200 shadow-sm hover:bg-rose-50 transition active:scale-90 z-10" onclick="event.stopPropagation(); changeSlide(-1)">
                            <i class="fas fa-chevron-left text-rose-500"></i>
                        </button>
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 backdrop-blur rounded-full p-2.5 border border-rose-200 shadow-sm hover:bg-rose-50 transition active:scale-90 z-10" onclick="event.stopPropagation(); changeSlide(1)">
                            <i class="fas fa-chevron-right text-rose-500"></i>
                        </button>
                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                            <?php foreach($images as $i => $img): ?>
                            <span class="dot w-2.5 h-2.5 rounded-full transition-all duration-300 cursor-pointer <?= $i===0?'bg-rose-400 scale-125':'bg-gray-300' ?>" data-index="<?= $i ?>" onclick="event.stopPropagation(); goToSlide(<?= $i ?>)"></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="flex-1 flex flex-col">
                <div class="flex justify-between items-start">
                    <h1 class="text-2xl lg:text-3xl font-serif font-black text-rose-600 mb-2">
                        <?= htmlspecialchars($product['name']) ?>
                    </h1>
                    <div class="flex items-center gap-2">
                        <button onclick="shareProduct()" class="text-2xl lg:text-3xl p-2 transition-transform active:scale-90 text-gray-400 hover:text-rose-500" title="Share">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button id="wishlistBtn" onclick="toggleWishlist()" class="text-2xl lg:text-3xl p-2 transition-transform active:scale-90">
                            <i class="fas fa-heart <?= $isWishlisted ? 'text-red-500' : 'text-gray-400' ?>"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-baseline gap-3 mb-4">
                    <p class="text-rose-600 text-3xl lg:text-4xl font-extrabold">₹<?= number_format($product['price'],2) ?></p>
                    <?php if (!empty($product['mrp']) && $product['mrp'] > $product['price']): ?>
                        <p class="text-gray-400 text-lg line-through">₹<?= number_format($product['mrp'],2) ?></p>
                        <?php if (!empty($product['offer_percent']) && $product['offer_percent'] > 0): ?>
                            <span class="bg-rose-100 text-rose-600 text-sm font-bold px-2 py-0.5 rounded-full border border-rose-200"><?= number_format($product['offer_percent'],0) ?>% off</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Description with See More -->
                <?php
                $fullDesc = $product['description'] ?? '';
                $shortDesc = mb_substr($fullDesc, 0, 150);
                $hasMore = mb_strlen($fullDesc) > 150;
                ?>
                <div class="text-sm lg:text-base text-gray-600 leading-relaxed space-y-2 mt-1">
                    <?php if ($hasMore): ?>
                    <p id="descShort"><?= nl2br(htmlspecialchars($shortDesc)) ?>...</p>
                    <p id="descFull" class="hidden"><?= nl2br(htmlspecialchars($fullDesc)) ?></p>
                    <button id="descToggleBtn" onclick="toggleDescription()" class="text-rose-500 hover:text-rose-400 text-sm font-medium focus:outline-none inline-flex items-center">
                        See More <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </button>
                    <?php else: ?>
                    <p><?= nl2br(htmlspecialchars($fullDesc)) ?></p>
                    <?php endif; ?>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-500">Stock:</span>
                    <?php if($product['stock']>0): ?>
                        <span class="inline-flex items-center gap-1 text-sm font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-0.5 rounded-full">
                            <i class="fas fa-check-circle text-xs"></i> In Stock
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-sm font-bold text-red-500 bg-red-50 border border-red-200 px-3 py-0.5 rounded-full">
                            <i class="fas fa-times-circle text-xs"></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-5 mt-6">
                    <span class="text-sm font-bold text-gray-700">Quantity:</span>
                    <div class="flex items-center rounded-xl overflow-hidden shadow-sm border border-rose-200">
                        <button onclick="changeQty(-1)" class="w-10 h-10 lg:w-12 lg:h-12 bg-white hover:bg-rose-50 text-rose-600 font-bold text-lg flex items-center justify-center transition active:scale-90">−</button>
                        <span id="qtyDisplay" class="w-14 lg:w-16 text-center font-bold text-lg text-gray-800">1</span>
                        <button onclick="changeQty(1)" class="w-10 h-10 lg:w-12 lg:h-12 bg-white hover:bg-rose-50 text-rose-600 font-bold text-lg flex items-center justify-center transition active:scale-90">+</button>
                    </div>
                    <?php if (!empty($product['unit'])): ?>
                        <span class="text-sm font-medium text-gray-500"><?= htmlspecialchars($product['unit']) ?></span>
                    <?php endif; ?>
                    <span class="text-xs text-gray-400">Max: <?= $product['stock'] ?></span>
                </div>

                <div class="flex gap-4 mt-8">
                    <button onclick="addToCart()" class="flex-1 bg-gradient-to-r from-rose-400 to-pink-400 text-white py-3.5 rounded-2xl font-bold text-lg shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button onclick="buyNow()" class="flex-1 bg-white border border-rose-200 text-rose-600 py-3.5 rounded-2xl font-bold text-lg shadow-md hover:bg-rose-50 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-bolt"></i> Buy Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Reviews -->
        <section class="mt-12">
            <h2 class="text-xl lg:text-2xl font-serif font-black text-rose-600 flex items-center gap-2 mb-6">
                <i class="fas fa-star text-rose-400"></i> Customer Reviews
                <?php if ($reviews->num_rows > 0): ?>
                    <span class="text-sm text-gray-400 font-normal">(<?= $reviews->num_rows ?>)</span>
                <?php endif; ?>
            </h2>
            
            <?php if ($reviews->num_rows > 0): ?>
            <div class="relative overflow-hidden" id="reviewsCarousel">
                <div class="flex transition-transform duration-500 ease-in-out" id="reviewsTrack">
                    <?php while($rev = $reviews->fetch_assoc()): 
                        $starIcons = '';
                        for ($s = 1; $s <= 5; $s++) {
                            $starIcons .= ($s <= $rev['star']) ? '<i class="fas fa-star text-rose-400 text-xs"></i>' : '<i class="far fa-star text-gray-300 text-xs"></i>';
                        }
                    ?>
                    <div class="w-full flex-shrink-0 px-2">
                        <div class="bg-white border border-rose-100 rounded-2xl p-5 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-rose-100 to-pink-100 border border-rose-200 flex items-center justify-center">
                                    <i class="fas fa-user text-rose-500 text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($rev['name']) ?></p>
                                    <div class="flex gap-0.5 mt-0.5"><?= $starIcons ?></div>
                                </div>
                                <span class="ml-auto text-xs text-gray-400"><?= date('d M', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed italic">“<?= htmlspecialchars($rev['message']) ?>”</p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <button onclick="moveReview(-1)" class="absolute left-0 top-1/2 -translate-y-1/2 bg-white/90 rounded-full p-1.5 border border-rose-200 hover:bg-rose-50 transition">
                    <i class="fas fa-chevron-left text-rose-500 text-sm"></i>
                </button>
                <button onclick="moveReview(1)" class="absolute right-0 top-1/2 -translate-y-1/2 bg-white/90 rounded-full p-1.5 border border-rose-200 hover:bg-rose-50 transition">
                    <i class="fas fa-chevron-right text-rose-500 text-sm"></i>
                </button>
            </div>
            <?php else: ?>
                <div class="text-center py-10 text-gray-400">
                    <i class="far fa-comment-dots text-4xl mb-3 block opacity-50"></i>
                    <p class="text-lg">No reviews yet. Be the first to review!</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Related Products -->
        <section class="mt-12">
            <h2 class="text-xl lg:text-2xl font-serif font-black mb-6 text-rose-600 flex items-center gap-2">
                <span class="w-1.5 h-5 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
                You May Also Like
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                <?php while($r = $related->fetch_assoc()): ?>
                <a href="product_detail.php?id=<?= $r['id'] ?>"
                   class="bg-white border border-rose-200/60 rounded-3xl p-3 lg:p-4 flex flex-col group transition-all duration-300 hover:border-rose-300 hover:shadow-lg hover:shadow-rose-200/30">
                    <div class="w-full h-36 lg:h-48 rounded-2xl overflow-hidden mb-3 border border-rose-100">
                        <img src="<?= $r['image'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" onerror="this.src='https://via.placeholder.com/300/fce7f3/ec4899?text=No+Image'">
                    </div>
                    <h3 class="text-sm lg:text-base font-bold text-gray-800 truncate"><?= htmlspecialchars($r['name']) ?></h3>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="text-rose-600 font-extrabold text-lg lg:text-xl">₹<?= number_format($r['price'],2) ?></span>
                    </div>
                    <?php if (!empty($r['mrp']) && $r['mrp'] > $r['price']): ?>
                        <span class="text-gray-400 text-xs line-through">₹<?= number_format($r['mrp'],2) ?></span>
                    <?php endif; ?>
                </a>
                <?php endwhile; ?>
            </div>
            <?php if ($related->num_rows == 0): ?>
                <p class="text-center text-gray-400 py-10">No related products yet.</p>
            <?php endif; ?>
        </section>
    </div>
</main>
<br>
<br>
<br>
<br>

<!-- ====== Image Modal (floral‑light version) ====== -->
<div id="imageModal" class="fixed inset-0 bg-white/95 backdrop-blur-sm z-50 hidden flex" onclick="closeImageModal(event)">
    <div class="relative w-full h-full flex flex-col items-center justify-center overflow-hidden" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-gray-600 text-3xl hover:text-rose-500 transition z-30">
            <i class="fas fa-times"></i>
        </button>

        <div id="imagePanContainer" class="w-full h-full flex items-center justify-center" style="cursor: grab;">
            <img id="modalImage" src="" class="select-none transition-transform duration-100" style="transform: scale(1) translate(0px, 0px);" draggable="false" alt="Product Image">
        </div>

        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-3 bg-white/90 backdrop-blur rounded-full px-5 py-2.5 border border-rose-200 shadow-sm z-30">
            <button onclick="zoomImage(-0.3)" class="text-gray-600 hover:text-rose-500 text-xl p-1 transition"><i class="fas fa-search-minus"></i></button>
            <span id="zoomLevel" class="text-gray-700 text-sm font-medium w-12 text-center">100%</span>
            <button onclick="zoomImage(0.3)" class="text-gray-600 hover:text-rose-500 text-xl p-1 transition"><i class="fas fa-search-plus"></i></button>
            <button onclick="resetView()" class="text-gray-600 hover:text-rose-500 text-sm px-2 py-1 transition font-medium">Reset</button>
        </div>
    </div>
</div>

<?php include 'common/bottom.php'; ?>

<script>
const productImages = <?= json_encode($images) ?>;
let slideIndex = 0;
const slides = document.querySelectorAll('[data-slide]');
const dots = document.querySelectorAll('.dot');

function goToSlide(index) {
    slides.forEach((s, i) => s.classList.toggle('opacity-100', i === index));
    dots.forEach((d, i) => {
        d.classList.toggle('bg-rose-400', i === index);
        d.classList.toggle('scale-125', i === index);
        d.classList.toggle('bg-gray-300', i !== index);
    });
    slideIndex = index;
}

function changeSlide(dir) {
    if (slides.length === 0) return;
    let newIndex = (slideIndex + dir + slides.length) % slides.length;
    goToSlide(newIndex);
}

dots.forEach(dot => dot.addEventListener('click', () => goToSlide(parseInt(dot.dataset.index))));

// Quantity
let currentQty = 1;
const maxStock = <?= $product['stock'] ?>;
const qtyDisplay = document.getElementById('qtyDisplay');
function changeQty(delta) {
    let newQty = currentQty + delta;
    if (newQty < 1) newQty = 1;
    if (newQty > maxStock) newQty = maxStock;
    currentQty = newQty;
    qtyDisplay.textContent = currentQty;
}

// Add to Cart
async function addToCart() {
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax_add', '1');
        formData.append('qty', currentQty);
        const res = await fetch('product_detail.php?id=<?= $id ?>', { method:'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            document.getElementById('cartCount').textContent = data.cartCount;
            alert('Added to cart!');
        }
    } catch(e) { console.error(e); } finally { hideLoader(); }
}

// Buy Now
async function buyNow() {
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax_add', '1');
        formData.append('qty', currentQty);
        const res = await fetch('product_detail.php?id=<?= $id ?>', { method:'POST', body: formData });
        const data = await res.json();
        if (data.success) { window.location.href = 'checkout.php'; }
        else { alert('Could not process. Please try again.'); }
    } catch(e) { console.error(e); } finally { hideLoader(); }
}

// Wishlist Toggle
async function toggleWishlist() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Please login to add to wishlist.');
        return;
    <?php endif; ?>
    const btn = document.getElementById('wishlistBtn');
    const icon = btn.querySelector('i');
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax_wishlist', '1');
        const res = await fetch('product_detail.php?id=<?= $id ?>', { method:'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            icon.className = data.action === 'added' ? 'fas fa-heart text-red-500' : 'fas fa-heart text-gray-400';
        } else { alert(data.message || 'Error'); }
    } catch(e) { console.error(e); } finally { hideLoader(); }
}

// Share
function shareProduct() {
    const url = window.location.href;
    const title = <?= json_encode($product['name']) ?>;
    const text = "Check out this product: " + title;
    if (navigator.share) {
        navigator.share({ title, text, url }).catch(err => console.log('Share failed', err));
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copied to clipboard!');
        }).catch(() => { prompt('Copy this link to share:', url); });
    }
}

// ====== Image Modal Logic (unchanged core) ======
let currentZoom = 1, panX = 0, panY = 0;
const modalImage = document.getElementById('modalImage');
const imageModal = document.getElementById('imageModal');
const panContainer = document.getElementById('imagePanContainer');
const zoomLevelEl = document.getElementById('zoomLevel');

function openImageModal() {
    if (productImages.length === 0) return;
    modalImage.src = productImages[slideIndex];
    resetView();
    imageModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal(event) {
    if (event && event.target !== imageModal) return;
    imageModal.style.display = 'none';
    document.body.style.overflow = '';
}

function applyTransform() {
    modalImage.style.transform = `scale(${currentZoom}) translate(${panX}px, ${panY}px)`;
    zoomLevelEl.textContent = Math.round(currentZoom * 100) + '%';
}

function resetView() { currentZoom = 1; panX = 0; panY = 0; applyTransform(); }

function zoomImage(delta) {
    currentZoom = Math.min(3, Math.max(0.5, currentZoom + delta));
    applyTransform();
}

// Mouse wheel zoom
panContainer.addEventListener('wheel', (e) => {
    e.preventDefault();
    zoomImage(e.deltaY < 0 ? 0.3 : -0.3);
});

// Mouse drag
let isDragging = false, startMouseX, startMouseY, startPanX, startPanY;
panContainer.addEventListener('mousedown', (e) => {
    isDragging = true;
    panContainer.style.cursor = 'grabbing';
    startMouseX = e.clientX; startMouseY = e.clientY;
    startPanX = panX; startPanY = panY;
    e.preventDefault();
});
window.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    const dx = (e.clientX - startMouseX) / currentZoom;
    const dy = (e.clientY - startMouseY) / currentZoom;
    panX = startPanX + dx; panY = startPanY + dy;
    applyTransform();
});
window.addEventListener('mouseup', () => {
    if (isDragging) { isDragging = false; panContainer.style.cursor = 'grab'; }
});

// Touch drag & pinch zoom
let touchDist0 = 0, touchZoom0 = 1, touchDragging = false;
panContainer.addEventListener('touchstart', (e) => {
    if (e.touches.length === 1) {
        touchDragging = true;
        startMouseX = e.touches[0].clientX; startMouseY = e.touches[0].clientY;
        startPanX = panX; startPanY = panY;
    } else if (e.touches.length === 2) {
        touchDragging = false;
        touchDist0 = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
        touchZoom0 = currentZoom;
    }
}, {passive: false});

panContainer.addEventListener('touchmove', (e) => {
    e.preventDefault();
    if (e.touches.length === 1 && touchDragging) {
        const dx = (e.touches[0].clientX - startMouseX) / currentZoom;
        const dy = (e.touches[0].clientY - startMouseY) / currentZoom;
        panX = startPanX + dx; panY = startPanY + dy;
        applyTransform();
    } else if (e.touches.length === 2 && touchDist0 > 0) {
        const dist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
        currentZoom = Math.min(3, Math.max(0.5, touchZoom0 * (dist / touchDist0)));
        applyTransform();
    }
}, {passive: false});

panContainer.addEventListener('touchend', () => { touchDragging = false; touchDist0 = 0; });

// Close with Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && imageModal.style.display === 'flex') closeImageModal();
});

// ====== Reviews Carousel ======
let reviewIndex = 0;
const reviewTrack = document.getElementById('reviewsTrack');
const totalReviews = <?= $reviews->num_rows ?>;
let reviewInterval;

function moveReview(dir) {
    if (totalReviews === 0) return;
    reviewIndex = (reviewIndex + dir + totalReviews) % totalReviews;
    reviewTrack.style.transform = `translateX(-${reviewIndex * 100}%)`;
}
function startReviewAutoSlide() { if (totalReviews > 1) reviewInterval = setInterval(() => moveReview(1), 3000); }
function stopReviewAutoSlide() { clearInterval(reviewInterval); }

if (totalReviews > 0) {
    startReviewAutoSlide();
    const carousel = document.getElementById('reviewsCarousel');
    carousel.addEventListener('mouseenter', stopReviewAutoSlide);
    carousel.addEventListener('mouseleave', startReviewAutoSlide);
    let touchStartX = 0;
    carousel.addEventListener('touchstart', e => {
        touchStartX = e.touches[0].clientX;
        stopReviewAutoSlide();
    }, {passive: true});
    carousel.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) moveReview(diff > 0 ? 1 : -1);
        startReviewAutoSlide();
    });
}

// Description toggle
function toggleDescription() {
    const short = document.getElementById('descShort');
    const full = document.getElementById('descFull');
    const btn = document.getElementById('descToggleBtn');
    if (full.classList.contains('hidden')) {
        short.classList.add('hidden');
        full.classList.remove('hidden');
        btn.innerHTML = 'See Less <i class="fas fa-chevron-up ml-1 text-xs"></i>';
    } else {
        short.classList.remove('hidden');
        full.classList.add('hidden');
        btn.innerHTML = 'See More <i class="fas fa-chevron-down ml-1 text-xs"></i>';
    }
}
</script>
</body>
</html>