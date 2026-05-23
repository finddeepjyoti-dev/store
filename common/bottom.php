<!-- Desktop Footer -->
<footer class="hidden lg:block bg-white/80 backdrop-blur-xl border-t border-rose-200/60 mt-auto">
    <div class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Brand + Description -->
        <div>
            <h3 class="font-script text-2xl font-bold text-rose-500 mb-3">BloomBouquet</h3>
            <p class="text-gray-500 text-sm leading-relaxed">
                Fresh handcrafted bouquets for every occasion. Same‑day delivery, premium blooms, and a touch of elegance.
            </p>
        </div>

        <!-- Quick Links -->
        <div>
            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Quick Links</h4>
            <ul class="space-y-2 text-sm text-gray-500">
                <li><a href="index.php" class="hover:text-rose-500 transition">Home</a></li>
                <li><a href="about.php" class="hover:text-rose-500 transition">About</a></li>
                <li><a href="disclaimer.php" class="hover:text-rose-500 transition">Disclaimer</a></li>
                <li><a href="terms.php" class="hover:text-rose-500 transition">Terms & Conditions</a></li>
                <li><a href="contact.php" class="hover:text-rose-500 transition">Contact Us</a></li>
            </ul>
        </div>

        <!-- Contact Info -->
        <div>
            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Get In Touch</h4>
            <ul class="space-y-2 text-sm text-gray-500">
                <li class="flex items-start gap-2">
                    <i class="fas fa-map-marker-alt text-rose-400 mt-1"></i>
                    <span>123 Petal Lane,<br>Assam, India</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-envelope text-rose-400"></i>
                    <a href="mailto:support@bloombouquet.com" class="hover:text-rose-500 transition">support@bloombouquet.com</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-phone-alt text-rose-400"></i>
                    <a href="tel:+91-9101620936" class="hover:text-rose-500 transition">+91-9101620936</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Copyright -->
    <div class="border-t border-rose-200/40">
        <div class="max-w-7xl mx-auto px-6 py-4 text-center text-sm text-gray-400">
            <p>&copy; <?= date('Y') ?> BloomBouquet. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Mobile Bottom Navigation -->
<footer class="fixed bottom-3 left-3 right-3 z-30 lg:hidden">
    <div class="bg-white/90 backdrop-blur-2xl rounded-3xl shadow-2xl shadow-rose-200/50 border border-rose-200/50 px-1 py-2">
        <div class="flex justify-around items-center">
            <a href="index.php" class="flex flex-col items-center py-2 px-3 rounded-2xl text-gray-400 hover:text-rose-500 transition-all active:scale-90">
                <i class="fas fa-home text-xl"></i><span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="category.php" class="flex flex-col items-center py-2 px-3 rounded-2xl text-gray-400 hover:text-rose-500 transition-all active:scale-90">
                <i class="fas fa-th-large text-xl"></i><span class="text-[10px] font-bold mt-1">Categories</span>
            </a>
            <a href="cart.php" class="relative flex flex-col items-center py-2 px-3 rounded-2xl text-gray-400 hover:text-rose-500 transition-all active:scale-90">
                <div class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span id="cartCount" class="absolute -top-2 -right-3 bg-gradient-to-br from-rose-400 to-pink-500 text-white text-[10px] font-black rounded-full h-5 w-5 flex items-center justify-center shadow-lg shadow-rose-200/40">
                        <?= array_sum(array_column($_SESSION['cart']??[],'qty')) ?>
                    </span>
                </div>
                <span class="text-[10px] font-bold mt-1">Cart</span>
            </a>
            <!-- Profile -->
            <a href="profile.php" class="flex flex-col items-center py-2 px-3 rounded-2xl text-gray-400 hover:text-rose-500 transition-all active:scale-90">
                <?php if (!empty($headerProfilePic)): ?>
                    <img src="<?= htmlspecialchars($headerProfilePic) ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <i class="fas fa-user text-xl" style="display:none;"></i>
                <?php else: ?>
                    <i class="fas fa-user text-xl"></i>
                <?php endif; ?>
                <span class="text-[10px] font-bold mt-1">Profile</span>
            </a>
        </div>
    </div>
</footer>