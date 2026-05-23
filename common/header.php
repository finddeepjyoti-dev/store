<?php require_once __DIR__.'/config.php'; ?>
<?php
// Fetch user profile picture for header display
$headerProfilePic = '';
if (isset($_SESSION['user_id'])) {
    $res = $conn->query("SELECT profile_pic FROM users WHERE id = " . (int)$_SESSION['user_id']);
    if ($res && $row = $res->fetch_assoc()) {
        $headerProfilePic = $row['profile_pic'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>BloomBouquet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Floral typography: Playfair Display (headings), Inter (body), Dancing Script (logo accent) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body {
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .font-serif { font-family: 'Playfair Display', serif; }
        .font-script { font-family: 'Dancing Script', cursive; }
        #loader {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(244, 114, 182, 0.35);
        }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Sidebar slide-in animation */
        .sidebar {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar.open {
            transform: translateX(0) !important;
        }
    </style>
</head>
<body class="bg-rose-50/30 text-gray-800 min-h-screen flex flex-col antialiased">

<!-- ========== HEADER ========== -->
<header class="bg-white/90 backdrop-blur-xl border-b border-rose-200/60 shadow-sm sticky top-0 z-30">
    <!-- ---------- MOBILE HEADER ---------- -->
    <div class="lg:hidden">
        <div class="px-4 py-3 grid grid-cols-3 items-center">
            <!-- Left: Hamburger -->
            <button id="menuBtnMobile" class="text-rose-500 text-2xl active:scale-90 transition p-1 justify-self-start">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Center: Logo (script font for floral feel) -->
            <a href="index.php" class="text-2xl font-script font-bold tracking-tight text-rose-500 justify-self-center">
                Bloom<span class="text-gray-800">Bouquet</span>
            </a>

            <!-- Right: Search toggle -->
            <button id="searchToggleMobile" class="text-rose-500 text-2xl active:scale-90 transition p-1 justify-self-end">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <!-- Mobile search bar -->
        <div id="searchBarMobile" class="px-4 pb-3 hidden">
            <form action="search.php" method="get" class="flex gap-2">
                <input type="text" name="q" placeholder="Search…" id="searchInputMobile"
                       class="flex-1 bg-rose-50 border border-rose-200 rounded-2xl px-5 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400">
                <button type="submit" class="bg-gradient-to-r from-rose-400 to-pink-400 text-white px-5 py-3 rounded-2xl font-bold active:scale-95 transition">Go</button>
            </form>
        </div>
    </div>

    <!-- ---------- DESKTOP HEADER ---------- -->
    <div class="hidden lg:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-3 lg:py-4">
                <!-- Logo -->
                <a href="index.php" class="flex-shrink-0 text-2xl lg:text-3xl font-script font-bold tracking-tight text-rose-500">
                    Bloom<span class="text-gray-800">Bouquet</span>
                </a>

                <!-- Desktop search -->
                <div class="flex-1 max-w-xl mx-8">
                    <form action="search.php" method="get" class="flex w-full">
                        <input type="text" name="q" placeholder="Search bouquets, categories…"
                               class="flex-1 bg-rose-50 border border-rose-200 rounded-l-2xl px-5 py-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                        <button type="submit" class="bg-gradient-to-r from-rose-400 to-pink-400 text-white px-6 rounded-r-2xl font-bold hover:shadow-lg transition active:scale-95">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Desktop nav links + cart icon -->
                <div class="flex items-center gap-4 lg:gap-6">
                    <nav class="flex items-center gap-1 text-sm font-medium">
                        <a href="index.php" class="px-3 py-2 rounded-xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition">Home</a>
                        <a href="category.php" class="px-3 py-2 rounded-xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition">Categories</a>
                        <a href="product.php" class="px-3 py-2 rounded-xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition">Products</a>
                        <a href="cart.php" class="px-3 py-2 rounded-xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition">Cart</a>
                        <a href="order.php" class="px-3 py-2 rounded-xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition">Orders</a>
                        <!-- Profile link -->
                        <a href="profile.php" class="px-2 py-2 rounded-xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition flex items-center justify-center">
                            <?php if (!empty($headerProfilePic)): ?>
                                <img src="<?= htmlspecialchars($headerProfilePic) ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <i class="fas fa-user" style="display:none;"></i>
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </a>
                    </nav>

                    <!-- Desktop cart icon with badge -->
                    <a href="cart.php" class="relative text-rose-500 text-2xl hover:text-rose-400 transition p-1">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cartCount" class="absolute -top-1 -right-2 bg-gradient-to-br from-rose-400 to-pink-500 text-white text-[10px] font-black rounded-full h-5 w-5 flex items-center justify-center shadow-lg">
                            <?= array_sum(array_column($_SESSION['cart'] ?? [], 'qty')) ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Overlay (mobile) -->
<div id="overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

<!-- Mobile Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Loader -->
<div id="loader" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
    <div class="bg-white/95 p-10 rounded-3xl border border-rose-200 backdrop-blur-2xl">
        <div class="flex space-x-2 justify-center mb-4">
            <div class="w-4 h-4 bg-rose-500 rounded-full animate-bounce"></div>
            <div class="w-4 h-4 bg-pink-400 rounded-full animate-bounce" style="animation-delay:0.15s"></div>
            <div class="w-4 h-4 bg-rose-300 rounded-full animate-bounce" style="animation-delay:0.3s"></div>
        </div>
        <p class="text-gray-600 text-sm text-center">Loading…</p>
    </div>
</div>

<script>
function showLoader() { document.getElementById('loader').style.display = 'flex'; }
function hideLoader() { document.getElementById('loader').style.display = 'none'; }

// ---- Mobile sidebar ----
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('overlay').classList.remove('hidden');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.add('hidden');
}

document.getElementById('menuBtnMobile').addEventListener('click', openSidebar);

// ---- Mobile search toggle ----
document.getElementById('searchToggleMobile').addEventListener('click', function() {
    const sb = document.getElementById('searchBarMobile');
    sb.classList.toggle('hidden');
    if (!sb.classList.contains('hidden')) document.getElementById('searchInputMobile').focus();
});
</script>