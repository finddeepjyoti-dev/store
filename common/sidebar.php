<aside id="sidebar" class="sidebar fixed top-0 left-0 h-full w-80 bg-white/95 backdrop-blur-2xl z-50 transform -translate-x-full lg:hidden shadow-2xl border-r border-rose-200/60">
    <div class="p-6 bg-gradient-to-br from-rose-50 via-pink-50 to-white flex justify-between items-center border-b border-rose-200/60">
        <span class="font-script font-bold text-xl text-rose-500 tracking-wide">✦ Menu</span>
        <button onclick="closeSidebar()" class="text-rose-500 text-2xl active:scale-90 transition-transform"><i class="fas fa-times"></i></button>
    </div>
    <nav class="p-5 space-y-1">
        <a href="index.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-500"><i class="fas fa-home"></i></span> Home
        </a>
        <a href="product.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-pink-500"><i class="fas fa-th-large"></i></span> Products
        </a>
        <a href="cart.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-400"><i class="fas fa-shopping-cart"></i></span> Cart
        </a>
        <a href="order.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-600"><i class="fas fa-box"></i></span> Orders
        </a>
        <a href="profile.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-500"><i class="fas fa-user"></i></span> Profile
        </a>
        <hr class="my-5 border-rose-200">
        <a href="about.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-500"><i class="fas fa-info-circle"></i></span> About
        </a>
        <a href="terms.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-500"><i class="fas fa-file-contract"></i></span> Terms &amp; Conditions
        </a>
        <a href="disclaimer.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-500"><i class="fas fa-shield-alt"></i></span> Disclaimer
        </a>
        <a href="contact.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all duration-300 active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100 text-rose-500"><i class="fas fa-envelope"></i></span> Contact Us
        </a>
        <hr class="my-5 border-rose-200">
        <?php if(isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-red-400 hover:bg-red-50 active:scale-[0.98] transition-all font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-100"><i class="fas fa-sign-out-alt"></i></span> Logout
        </a>
        <?php else: ?>
        <a href="login.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl text-gray-600 hover:text-rose-500 hover:bg-rose-50 transition-all active:scale-[0.98] font-medium">
            <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-100"><i class="fas fa-sign-in-alt"></i></span> Login
        </a>
        <?php endif; ?>
    </nav>
</aside>