<?php
ob_start();
require_once 'common/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = $conn->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
$msg = $error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $district = trim($_POST['district'] ?? '');
    $pin      = trim($_POST['pin'] ?? '');

    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, district=?, pin=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $phone, $address, $district, $pin, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $msg = "Profile updated successfully.";
        $user = $conn->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
    } else {
        $error = "Update failed.";
    }
    $stmt->close();
}

// Handle password change (only if user has a password set)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (empty($user['password'])) {
        $error = "Cannot change password for Google-authenticated accounts.";
    } else {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        if (password_verify($old, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $msg = "Password changed successfully.";
            } else {
                $error = "Password change failed.";
            }
            $stmt->close();
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Handle report submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    header('Content-Type: application/json');
    $reportMsg = trim($_POST['report_message']);
    if (empty($reportMsg)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO reports (user_id, name, phone, email, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $_SESSION['user_id'], $user['name'], $user['phone'], $user['email'], $reportMsg);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Report submitted. We will get back to you.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Submission failed.']);
    }
    $stmt->close();
    exit;
}
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="flex-1 w-full px-4 py-6 lg:py-10 bg-rose-50/30 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl lg:text-3xl font-serif font-black mb-8 text-rose-600 flex items-center gap-2">
            <span class="w-1.5 h-6 lg:h-7 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
            My Profile
        </h1>

        <!-- Messages -->
        <?php if ($msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-2xl mb-4 flex items-center gap-3 font-semibold">
                <i class="fas fa-check-circle text-lg"></i> <?= $msg ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-500 p-4 rounded-2xl mb-4 flex items-center gap-3 font-semibold">
                <i class="fas fa-exclamation-circle text-lg"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6 lg:gap-10">
            <!-- Profile Card -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white border border-rose-200/60 rounded-3xl p-6 shadow-sm">
                    <div class="flex flex-col items-center">
                        <!-- Profile picture with fallback -->
                        <div class="w-28 h-28 lg:w-32 lg:h-32 rounded-full bg-rose-50 flex items-center justify-center mb-4 border-2 border-rose-300 shadow-sm overflow-hidden">
                            <?php if (!empty($user['profile_pic'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile" class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="fas fa-user text-5xl lg:text-6xl text-rose-400" style="display:none;"></i>
                            <?php else: ?>
                                <i class="fas fa-user text-5xl lg:text-6xl text-rose-400"></i>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 font-serif"><?= htmlspecialchars($user['name']) ?></h2>
                        <p class="text-gray-500 text-sm lg:text-base mt-1"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-gray-600 mt-3 flex items-center gap-2">
                            <i class="fas fa-phone text-rose-500"></i> <?= htmlspecialchars($user['phone'] ?: 'Not set') ?>
                        </p>
                        <?php if (!empty($user['address']) || !empty($user['district']) || !empty($user['pin'])): ?>
                            <div class="text-gray-600 mt-2 text-center leading-relaxed max-w-xs">
                                <i class="fas fa-map-marker-alt text-rose-500 mr-1"></i>
                                <?= !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : '' ?>
                                <?= !empty($user['district']) ? ', ' . htmlspecialchars($user['district']) : '' ?>
                                <?= !empty($user['pin']) ? ' - ' . htmlspecialchars($user['pin']) : '' ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-3 mt-8">
                        <a href="order.php" class="flex items-center justify-center gap-2 bg-rose-50 border border-rose-200 text-rose-600 py-3 rounded-2xl font-bold hover:bg-rose-100 transition-all active:scale-95">
                            <i class="fas fa-box"></i> My Orders
                        </a>
                        <a href="whistlist.php" class="flex items-center justify-center gap-2 bg-pink-50 border border-pink-200 text-pink-600 py-3 rounded-2xl font-bold hover:bg-pink-100 transition-all active:scale-95">
                            <i class="fas fa-heart"></i> My Wishlist
                        </a>
                    </div>
                    <button onclick="openReportModal()" class="w-full mt-3 flex items-center justify-center gap-2 bg-red-50 border border-red-200 text-red-500 py-3 rounded-2xl font-bold hover:bg-red-100 transition-all active:scale-95">
                        <i class="fas fa-exclamation-circle"></i> Report a Problem
                    </button>
                    <div class="mt-3">
                        <a href="logout.php" class="w-full flex items-center justify-center gap-2 bg-gray-100 border border-gray-200 text-gray-600 py-3 rounded-2xl font-bold hover:bg-gray-200 transition-all active:scale-95">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Edit Profile & Password Section -->
            <div class="flex-1">
                <div id="editSection" class="bg-white border border-rose-200/60 rounded-3xl p-6 shadow-sm">
                    <h2 class="text-xl lg:text-2xl font-serif font-bold mb-5 text-rose-600 flex items-center gap-2">
                        <span class="w-1.5 h-5 lg:h-6 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
                        Edit Profile
                    </h2>
                    <form method="post" class="space-y-5">
                        <div>
                            <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">Phone</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">District</label>
                                <input type="text" name="district" value="<?= htmlspecialchars($user['district'] ?? '') ?>" class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                            </div>
                            <div>
                                <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">PIN Number</label>
                                <input type="text" name="pin" value="<?= htmlspecialchars($user['pin'] ?? '') ?>" class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="w-full bg-gradient-to-r from-rose-400 to-pink-400 text-white py-3.5 rounded-2xl font-bold text-lg lg:text-xl shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all">
                            Update Profile
                        </button>
                    </form>

                    <!-- Password change – only if the user has a password -->
                    <?php if (!empty($user['password'])): ?>
                    <hr class="my-8 border-rose-100">
                    <h2 class="text-xl lg:text-2xl font-serif font-bold mb-5 text-rose-600 flex items-center gap-2">
                        <span class="w-1.5 h-5 lg:h-6 bg-gradient-to-b from-rose-400 to-pink-500 rounded-full"></span>
                        Change Password
                    </h2>
                    <form method="post" class="space-y-5">
                        <div>
                            <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">Current Password</label>
                            <input type="password" name="old_password" required class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm lg:text-base font-semibold text-gray-500 mb-1">New Password</label>
                            <input type="password" name="new_password" required minlength="6" class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 lg:py-4 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow">
                        </div>
                        <button type="submit" name="change_password" class="w-full bg-gradient-to-r from-rose-400 to-pink-400 text-white py-3.5 rounded-2xl font-bold text-lg lg:text-xl shadow-lg shadow-rose-200/50 hover:shadow-rose-300/60 active:scale-95 transition-all">
                            Change Password
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<br><br><br><br><br>

<!-- Report Modal -->
<div id="reportModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white border border-rose-200 rounded-3xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-serif font-bold text-rose-600 flex items-center gap-2">
                <span class="w-1.5 h-5 bg-gradient-to-b from-red-400 to-pink-500 rounded-full"></span>
                Report a Problem
            </h3>
            <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <form id="reportForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Name</label>
                <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly class="w-full bg-gray-50 border border-rose-200 rounded-2xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Phone</label>
                <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly class="w-full bg-gray-50 border border-rose-200 rounded-2xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly class="w-full bg-gray-50 border border-rose-200 rounded-2xl px-4 py-3 text-gray-700 font-medium focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Message *</label>
                <textarea name="report_message" rows="4" required class="w-full bg-white border border-rose-200 rounded-2xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-400 transition-shadow" placeholder="Describe your issue..."></textarea>
            </div>
            <div id="reportMsg" class="text-sm font-medium hidden"></div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeReportModal()" class="px-5 py-2.5 bg-gray-100 rounded-2xl font-semibold text-gray-600 hover:bg-gray-200 transition active:scale-95">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-red-400 to-pink-400 text-white rounded-2xl font-semibold hover:shadow-lg active:scale-95 transition-all">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<?php include 'common/bottom.php'; ?>

<script>
function toggleEdit() {
    const section = document.getElementById('editSection');
    section.classList.toggle('hidden');
    if (!section.classList.contains('hidden')) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

const reportModal = document.getElementById('reportModal');
function openReportModal() { reportModal.style.display = 'flex'; }
function closeReportModal() { reportModal.style.display = 'none'; }
reportModal.addEventListener('click', function(e) { if(e.target === reportModal) closeReportModal(); });

document.getElementById('reportForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('reportMsg');
    showLoader();
    try {
        const fd = new FormData(this);
        fd.append('submit_report', '1');
        const res = await fetch('profile.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            msgDiv.className = 'text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-xl';
            msgDiv.textContent = data.message;
            this.reset();
            setTimeout(closeReportModal, 1500);
        } else {
            msgDiv.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
            msgDiv.textContent = data.message;
        }
    } catch (err) {
        msgDiv.className = 'text-sm font-medium text-red-500 bg-red-50 border border-red-200 p-3 rounded-xl';
        msgDiv.textContent = 'Network error.';
    } finally {
        hideLoader();
        msgDiv.classList.remove('hidden');
    }
});
</script>

<style>
@media (min-width: 1024px) {
    #editSection {
        display: block !important;
    }
}
</style>
</body>
</html>