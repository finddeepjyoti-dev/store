<aside id="adminSidebar"
       class="fixed top-0 left-0 h-full w-64 bg-slate-900 border-r border-slate-700/50 transform -translate-x-full lg:translate-x-0 transition-transform z-40 duration-300 shadow-2xl">
    <div class="p-5 border-b border-slate-700/50 flex items-center justify-between">
        <span class="font-extrabold text-xl text-amber-400 tracking-tight">
            Updates<span class="text-teal-400">All</span>
        </span>
        <button onclick="closeSidebar()" class="text-slate-400 hover:text-white text-2xl lg:hidden">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="p-4 space-y-1.5 overflow-y-auto flex-1">
        <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-tachometer-alt"></i></span> Dashboard
        </a>
        <!-- Reports Link -->
        <a href="report.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-chart-bar"></i></span> Reports
        </a>
        <a href="banner.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-image"></i></span> Banners
        </a>
        <a href="category.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-list"></i></span> Categories
        </a>
        <a href="subcategory.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-list"></i></span> Subcategories
        </a>
        <a href="product.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-box"></i></span> Products
        </a>
        <!--a href="order.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-shopping-bag"></i></span> Orders
        </a-->
        <!-- Complete Orders Link -->
        <a href="completeorder.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-clipboard-list"></i></span> All Orders
        </a>
        <a href="user.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-users"></i></span> Users
        </a>
        <a href="deliveryboy_register.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-truck"></i></span> Delivery Boys
        </a>
        <a href="userreport.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-exclamation-circle"></i></span> User Reports
        </a>
        <!-- Staff Register Link -->
        <a href="staffregister.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-user-tie"></i></span> Staff Register
        </a>
        <a href="userwhistlist.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-heart"></i></span> User Wishlists
        </a>
        <a href="customerproductrating.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-star"></i></span> Customer Ratings
        </a>
        <!-- QR Scanner Link -->
        <a href="QRscener.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-qrcode"></i></span> QR Scanner
        </a>
        <a href="setting.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-amber-400 transition-all duration-200 font-medium">
            <span class="w-7 h-7 flex items-center justify-center rounded bg-amber-500/10 text-amber-400"><i class="fas fa-cog"></i></span> Settings
        </a>
        <div class="pt-4 mt-4 border-t border-slate-700/50">
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-200 font-medium">
                <span class="w-7 h-7 flex items-center justify-center rounded bg-red-500/10"><i class="fas fa-sign-out-alt"></i></span> Logout
            </a>
        </div>
    </nav>
</aside>