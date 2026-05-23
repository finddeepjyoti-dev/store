<?php
require_once 'common/config.php';
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            Terms &amp; Conditions
        </h1>

        <div class="bg-white border border-rose-200/60 rounded-3xl p-6 lg:p-10 space-y-5 text-gray-600 leading-relaxed shadow-sm">
            <p class="text-sm text-gray-400">Last updated: <?= date('F d, Y') ?></p>

            <p>
                Welcome to <strong class="text-rose-600">BloomBouquet</strong>. By accessing or using our website, you agree to comply with and be bound by the following terms and conditions. Please read them carefully before using our services.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">1. Acceptance of Terms</h2>
            <p>
                By creating an account, placing an order, or browsing the Website, you accept these Terms & Conditions in full. If you disagree with any part of these terms, you must not use the Website.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">2. User Account</h2>
            <p>
                You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account. BloomBouquet reserves the right to refuse service, terminate accounts, or cancel orders at its sole discretion.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">3. Product Information & Pricing</h2>
            <p>
                We make every effort to display accurate product information, including prices and availability. However, errors may occur. In the event a product is listed at an incorrect price, we reserve the right to refuse or cancel any orders placed for that product. Prices are subject to change without notice.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">4. Payment & Billing</h2>
            <p>
                By providing a payment method, you represent and warrant that you are authorized to use that payment method. All payments are processed securely through third‑party gateways. We are not responsible for any additional charges or fees imposed by your bank or payment provider.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">5. Shipping & Delivery</h2>
            <p>
                Delivery times are estimates and may vary due to unforeseen circumstances. We are not liable for delays caused beyond our control. The risk of loss and title for items purchased pass to you upon delivery.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">6. Returns & Refunds</h2>
            <p>
                Our return policy allows you to request a return within a specified period (usually 24 hours for flowers) for eligible products. Refunds will be processed to the original payment method after the returned item is received and inspected. Please check the product‑specific return policy before purchase.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">7. Limitation of Liability</h2>
            <p>
                To the fullest extent permitted by law, BloomBouquet shall not be liable for any indirect, incidental, special, or consequential damages arising out of or in connection with the use of our website or products purchased.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">8. Changes to Terms</h2>
            <p>
                We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of the Website after any such changes constitutes your acceptance of the new terms.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">9. Contact Information</h2>
            <p>
                For any questions about these Terms, please contact us:
            </p>
            <ul class="space-y-2 text-rose-600">
                <li><i class="fas fa-envelope mr-2 text-rose-500"></i> support@bloombouquet.com</li>
                <li><i class="fas fa-phone-alt mr-2 text-rose-500"></i> +91-9101620936</li>
                <li><i class="fas fa-map-marker-alt mr-2 text-rose-500"></i> 123 Petal Lane, Assam, India</li>
            </ul>
        </div>
    </div>
    <br>
    <br>
    <br>
    <br>
</main>

<?php include 'common/bottom.php'; ?>
</body>
</html>