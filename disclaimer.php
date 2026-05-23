<?php
require_once 'common/config.php';
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            Disclaimer
        </h1>

        <div class="bg-white border border-rose-200/60 rounded-3xl p-6 lg:p-10 space-y-5 text-gray-600 leading-relaxed shadow-sm">
            <p class="text-sm text-gray-400">Last updated: <?= date('F d, Y') ?></p>

            <p>
                The information provided on <strong class="text-rose-600">BloomBouquet</strong> (the "Website") is for general informational purposes only. All content, product descriptions, prices, and images are provided in good faith; however, we make no representation or warranty of any kind, express or implied, regarding the accuracy, adequacy, validity, reliability, availability, or completeness of any information on the Website.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">Product Information</h2>
            <p>
                While we strive to keep product details up‑to‑date, flower varieties, colors, and arrangements may vary slightly from the images shown due to seasonal availability and natural variations. Always refer to the actual product upon delivery for the most accurate representation.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">External Links</h2>
            <p>
                The Website may contain links to external sites that are not provided or maintained by us. We do not guarantee the accuracy, relevance, timeliness, or completeness of any information on these external websites. The inclusion of any links does not necessarily imply a recommendation or endorse the views expressed within them.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">Limitation of Liability</h2>
            <p>
                Under no circumstance shall BloomBouquet be liable for any loss or damage of any kind arising out of or in connection with the use of the Website or reliance on any information provided. Your use of the Website and your reliance on any information is solely at your own risk.
            </p>

            <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mt-8">Consent</h2>
            <p>
                By using our website, you hereby consent to our disclaimer and agree to its terms. Should we update, amend or make any changes to this document, those changes will be prominently posted here.
            </p>

            <p class="mt-8 text-sm text-gray-500">
                If you require any more information or have any questions about our site's disclaimer, please feel free to contact us at <a href="mailto:support@bloombouquet.com" class="text-rose-500 hover:underline">support@bloombouquet.com</a>.
            </p>
        </div>
    </div><br>
<br>
<br>
<br>

</main>

<?php include 'common/bottom.php'; ?>
</body>
</html>