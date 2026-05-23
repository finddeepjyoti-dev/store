<?php
require_once 'common/config.php';
$categories = $conn->query("SELECT * FROM categories");
$featured = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
$banners = $conn->query("SELECT * FROM banners ORDER BY sort_order ASC");
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// AJAX add to cart
if(isset($_POST['ajax_add_cart'])){
    header('Content-Type: application/json');
    $pid = intval($_POST['product_id']);
    $product = $conn->query("SELECT id, name, price, image FROM products WHERE id=$pid")->fetch_assoc();
    if($product){
        if(isset($_SESSION['cart'][$pid])){
            $_SESSION['cart'][$pid]['qty']++;
        } else {
            $_SESSION['cart'][$pid] = ['qty'=>1, 'price'=>$product['price'], 'name'=>$product['name'], 'image'=>$product['image']];
        }
        echo json_encode(['success'=>true, 'cartCount'=>array_sum(array_column($_SESSION['cart'],'qty'))]);
    } else {
        echo json_encode(['success'=>false]);
    }
    exit;
}
?>
<?php include 'common/header.php'; ?>

<!-- Banner Slider -->
<?php if ($banners->num_rows > 0): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
    <div class="relative rounded-3xl overflow-hidden shadow-xl border border-rose-200/60" id="heroSlider">
        <div class="flex transition-transform duration-700 ease-in-out" id="sliderTrack">
            <?php $banners->data_seek(0); while($b = $banners->fetch_assoc()): ?>
            <div class="w-full flex-shrink-0 relative">
                <a href="<?= $b['link'] ?: '#' ?>">
                    <img src="<?= $b['image'] ?>" class="w-full h-44 sm:h-56 lg:h-72 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-white/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-5 left-5">
                        <!--h3 class="font-serif text-2xl lg:text-3xl font-black text-rose-600"><-?= htmlspecialchars($b['title']) ?></h3-->
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Dots -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
            <?php $banners->data_seek(0); $idx=0; while($b = $banners->fetch_assoc()): ?>
            <span class="dot w-2 h-2 lg:w-3 lg:h-3 rounded-full transition-all duration-300 cursor-pointer <?= $idx==0 ? 'bg-rose-400 scale-125' : 'bg-gray-300' ?>" data-index="<?= $idx++ ?>"></span>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Categories -->
<div class="sticky top-[4.0rem] z-20 bg-rose-50/70 backdrop-blur-xl py-3 lg:py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-serif text-lg lg:text-xl font-bold text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-5 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span> Shop by Category
        </h2>
        <div class="flex overflow-x-auto space-x-4 lg:space-x-6 hide-scrollbar">
            <?php while($cat = $categories->fetch_assoc()): ?>
            <a href="product.php?cat=<?= $cat['id'] ?>" class="flex-shrink-0 w-20 lg:w-24 text-center group">
                <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto rounded-full bg-gradient-to-br from-rose-100 to-pink-100 p-0.5 shadow-sm group-active:scale-95 transition">
                    <div class="w-full h-full rounded-full overflow-hidden border-2 border-rose-300/60">
                        <img src="<?= $cat['image'] ?>" class="w-full h-full object-cover">
                    </div>
                </div>
                <p class="text-xs lg:text-sm mt-2 font-semibold text-gray-500 group-hover:text-rose-600 truncate"><?= htmlspecialchars($cat['name']) ?></p>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Featured Products -->
<main class="flex-1 px-4 lg:py-10">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            <?php while($prod = $featured->fetch_assoc()): ?>
            <a href="product_detail.php?id=<?= $prod['id'] ?>"
               class="bg-white border border-rose-200/70 rounded-3xl p-3 lg:p-4 flex flex-col group card-hover transition-all duration-500 relative overflow-hidden">
                <div class="w-full h-40 lg:h-52 rounded-2xl overflow-hidden mb-3 bg-rose-50 relative">
                    <img src="<?= $prod['image'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute top-2 right-2 bg-rose-500/90 text-xs font-bold text-white px-2 py-0.5 rounded-full">New</div>
                </div>
                <h3 class="text-sm lg:text-base font-bold text-gray-800 truncate"><?= htmlspecialchars($prod['name']) ?></h3>
                <div class="flex items-baseline gap-1.5 mt-1.5">
                    <span class="text-rose-600 font-extrabold text-lg lg:text-xl">₹<?= number_format($prod['price'],2) ?></span>
                </div>
                <?php if (!empty($prod['mrp']) && $prod['mrp'] > $prod['price']): ?>
                    <span class="text-gray-400 text-xs line-through">₹<?= number_format($prod['mrp'],2) ?></span>
                <?php endif; ?>
            </a>
            <?php endwhile; ?>
        </div>
    </div><br>
<br>
<br>
<br>
</main>

<?php include 'common/bottom.php'; ?>

<!-- Slider + Scroll Reveal Scripts (unchanged, just adjusted dot colors above) -->
<script>
// ---------- Banner Slider ----------
const track = document.getElementById('sliderTrack');
const dots = document.querySelectorAll('.dot');
let currentSlide = 0;
let autoSlideInterval;
const totalSlides = dots.length;

function goToSlide(index) {
    track.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((dot, i) => {
        dot.classList.toggle('bg-rose-400', i === index);
        dot.classList.toggle('scale-125', i === index);
        dot.classList.toggle('bg-gray-300', i !== index);
    });
    currentSlide = index;
}

function nextSlide() { goToSlide((currentSlide + 1) % totalSlides); }
function prevSlide() { goToSlide((currentSlide - 1 + totalSlides) % totalSlides); }

document.getElementById('nextSlide')?.addEventListener('click', nextSlide);
document.getElementById('prevSlide')?.addEventListener('click', prevSlide);
dots.forEach(d => d.addEventListener('click', () => goToSlide(parseInt(d.dataset.index))));

function startAutoSlide() { clearInterval(autoSlideInterval); autoSlideInterval = setInterval(nextSlide, 4500); }
function stopAutoSlide() { clearInterval(autoSlideInterval); }

// Touch swipe support
const sliderEl = document.getElementById('heroSlider');
let startX = 0;
sliderEl?.addEventListener('touchstart', e => { startX = e.touches[0].clientX; stopAutoSlide(); }, {passive: true});
sliderEl?.addEventListener('touchend', e => {
    const diff = startX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) diff > 0 ? nextSlide() : prevSlide();
    startAutoSlide();
});

if (totalSlides > 1) {
    startAutoSlide();
    sliderEl?.addEventListener('mouseenter', stopAutoSlide);
    sliderEl?.addEventListener('mouseleave', startAutoSlide);
}

// ---------- Scroll Reveal for Product Cards ----------
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('opacity-100', 'translate-y-0');
            entry.target.classList.remove('opacity-0', 'translate-y-6');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.card-hover').forEach(el => {
    el.classList.add('opacity-0', 'translate-y-6', 'transition', 'duration-700');
    observer.observe(el);
});

// ---------- Add to Cart (unchanged) ----------
async function addToCart(pid) {
    showLoader();
    try {
        const formData = new FormData();
        formData.append('ajax_add_cart','1');
        formData.append('product_id', pid);
        const res = await fetch('index.php', { method:'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            const cartCount = document.getElementById('cartCount');
            if(cartCount) cartCount.textContent = data.cartCount;
        }
    } catch(e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}
</script>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</body>
</html>