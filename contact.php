<?php
ob_start();
error_reporting(E_ALL);            // temporarily show errors – remove after debugging
ini_set('display_errors', 1);

require_once 'common/config.php';

// Must be logged in to submit a report
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user data (handle possible failure)
$user = $conn->query("SELECT * FROM users WHERE id = " . (int)$_SESSION['user_id'])->fetch_assoc();
if (!$user) {
    die('User not found. Please log in again.');
}

// Handle report submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    header('Content-Type: application/json');
    $reportMsg = trim($_POST['report_message'] ?? '');
    if (empty($reportMsg)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO reports (user_id, name, phone, email, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $_SESSION['user_id'], $user['name'], $user['phone'], $user['email'], $reportMsg);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Your message has been sent. We will get back to you soon!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Submission failed. Please try again.']);
    }
    $stmt->close();
    exit;
}
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            Contact Us
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left: Contact Info -->
            <div class="bg-white border border-rose-200/60 rounded-3xl p-6 lg:p-10 space-y-6 shadow-sm">
                <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600">Get In Touch</h2>
                <p class="text-gray-600 leading-relaxed">
                    Have a question, suggestion, or need help? We'd love to hear from you.
                    Fill out the form and our team will get back to you shortly.
                </p>

                <div class="space-y-4 text-gray-600">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-rose-500"></i>
                        </div>
                        <div>
                            <p class="font-medium text-rose-600">Address</p>
                            <p class="text-sm text-gray-500">123 Petal Lane,<br>Assam, India</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-envelope text-rose-500"></i>
                        </div>
                        <div>
                            <p class="font-medium text-rose-600">Email</p>
                            <a href="mailto:support@bloombouquet.com" class="text-sm text-gray-500 hover:text-rose-500 transition">support@bloombouquet.com</a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-phone-alt text-rose-500"></i>
                        </div>
                        <div>
                            <p class="font-medium text-rose-600">Phone</p>
                            <a href="tel:+91-9101620936" class="text-sm text-gray-500 hover:text-rose-500 transition">+91-9101620936</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Contact Form -->
            <div class="bg-white border border-rose-200/60 rounded-3xl p-6 lg:p-10 shadow-sm">
                <h2 class="text-xl lg:text-2xl font-serif font-bold text-rose-600 mb-6">Send a Message</h2>
                <form id="contactForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1.5">Name</label>
                        <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly
                               class="w-full bg-gray-50 border border-rose-200 rounded-xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1.5">Phone</label>
                        <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly
                               class="w-full bg-gray-50 border border-rose-200 rounded-xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1.5">Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly
                               class="w-full bg-gray-50 border border-rose-200 rounded-xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1.5">Message *</label>
                        <textarea name="report_message" id="reportMessage" rows="4" required
                                  class="w-full bg-white border border-rose-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow"
                                  placeholder="Write your message..."></textarea>
                    </div>
                    <div id="contactMsg" class="text-sm font-medium hidden"></div>
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-rose-400 to-pink-400 text-white py-3.5 rounded-xl font-bold text-lg shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>
<br><br><br><br>
<?php include 'common/bottom.php'; ?>

<script>
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('contactMsg');
    msgDiv.classList.add('hidden');
    showLoader();
    try {
        const fd = new FormData(this);
        fd.append('submit_report', '1');
        const res = await fetch('contact.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            msgDiv.className = 'text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-xl';
            msgDiv.textContent = data.message;
            this.reset();
        } else {
            msgDiv.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
            msgDiv.textContent = data.message;
        }
    } catch (err) {
        msgDiv.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
        msgDiv.textContent = 'Network error. Please try again.';
    } finally {
        hideLoader();
        msgDiv.classList.remove('hidden');
    }
});
</script>
</body>
</html>