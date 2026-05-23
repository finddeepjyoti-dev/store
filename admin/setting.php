<?php
require_once 'common/header.php';

// Path to the admin login file (where credentials are stored)
$loginFilePath = __DIR__ . '/login.php';

// Read current hardcoded credentials from login.php
$currentUser = 'sss';
$currentPass = 's11';

if (file_exists($loginFilePath)) {
    $content = file_get_contents($loginFilePath);
    preg_match('/\$valid_username\s*=\s*[\'"](.*?)[\'"]\s*;/', $content, $userMatch);
    preg_match('/\$valid_password\s*=\s*[\'"](.*?)[\'"]\s*;/', $content, $passMatch);
    if (!empty($userMatch[1])) $currentUser = $userMatch[1];
    if (!empty($passMatch[1])) $currentPass = $passMatch[1];
}

$msg = '';
$error = '';

// When form is submitted, update the login.php file
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $newUser = trim($_POST['username'] ?? '');
    $newPass = trim($_POST['password'] ?? '');

    if (empty($newUser) || empty($newPass)) {
        $error = 'Username and password cannot be empty.';
    } else {
        $pattern = [
            '/\$valid_username\s*=\s*\'[^\']*\'\s*;/',
            '/\$valid_password\s*=\s*\'[^\']*\'\s*;/'
        ];
        $replacement = [
            "\$valid_username = '" . addslashes($newUser) . "';",
            "\$valid_password = '" . addslashes($newPass) . "';"
        ];
        $newContent = preg_replace($pattern, $replacement, $content);
        if (file_put_contents($loginFilePath, $newContent)) {
            $msg = 'Credentials updated successfully. Please use the new credentials next time you log in.';
            $currentUser = $newUser;
            $currentPass = $newPass;
        } else {
            $error = 'Could not write to login file. Check permissions.';
        }
    }
}
?>

<div>
    <h2 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gradient-to-b from-amber-400 to-teal-400 rounded-full"></span>
        Admin Settings
    </h2>

    <?php if ($msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-2xl mb-4 flex items-center gap-3 font-semibold">
            <i class="fas fa-check-circle text-lg"></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-2xl mb-4 flex items-center gap-3 font-semibold">
            <i class="fas fa-exclamation-circle text-lg"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="bg-slate-800/70 backdrop-blur rounded-2xl border border-slate-700/50 shadow-xl p-6 max-w-md">
        <h3 class="text-xl font-bold text-amber-400 mb-4">Login Credentials</h3>
        <form method="post" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($currentUser) ?>"
                       required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Password</label>
                <input type="password" name="password" value="<?= htmlspecialchars($currentPass) ?>"
                       required class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <button type="submit" name="update" class="w-full bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 py-3 rounded-xl font-bold text-lg shadow-lg shadow-amber-500/20 hover:shadow-amber-500/40 active:scale-95 transition-all">
                <i class="fas fa-save mr-2"></i> Update Credentials
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-4 flex items-center gap-1">
            <i class="fas fa-info-circle"></i> These credentials are used for the admin login page.
        </p>
    </div>
</div>

<?php include 'common/bottom.php'; ?>