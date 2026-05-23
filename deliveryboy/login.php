<?php
// No session_start() here – common/config.php already starts the session
require_once __DIR__ . '/config.php';

// Already logged in?
if (isset($_SESSION['dboy_id'])) {
    header('Location: index.php');
    exit;
}

// Logout (when called from the dashboard)
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, status FROM delivery_boys WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $boy = $res->fetch_assoc();

    if ($boy && $boy['status'] == 1 && password_verify($password, $boy['password'])) {
        $_SESSION['dboy_id'] = $boy['id'];
        $_SESSION['dboy_name'] = $boy['name'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials or account blocked.';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Delivery Boy Login | QuickKart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            background: radial-gradient(circle at 20% 30%, #1e293b, #0f172a);
        }
        .login-card {
            animation: slideUp 0.6s ease-out both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .error-shake {
            animation: shake 0.4s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff;
            -webkit-box-shadow: 0 0 0px 1000px #334155 inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-card bg-slate-800/90 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-2xl p-8 w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 shadow-lg shadow-amber-500/20 mb-4">
                <i class="fas fa-motorcycle text-2xl text-gray-900"></i>
            </div>
            <h1 class="text-2xl font-bold text-amber-400 tracking-tight">Delivery Boy</h1>
            <p class="text-slate-400 text-sm mt-1">Login to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-shake bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded-xl mb-5 flex items-center gap-2 text-sm">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" name="email" required placeholder="your@email.com"
                           class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-10 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-shadow">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 pl-10 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-shadow">
                </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-900 py-3 rounded-xl font-bold text-lg shadow-lg shadow-amber-500/20 hover:shadow-amber-500/40 active:scale-95 transition-all">
                <i class="fas fa-sign-in-alt mr-2"></i> Login
            </button>
        </form>

        <p class="text-xs text-slate-500 text-center mt-6 flex items-center justify-center gap-1">
            <i class="fas fa-shield-alt text-amber-500/50"></i> Secure delivery portal
        </p>
    </div>

    <script>
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
        
        // Remove shake class after animation ends
        const errorDiv = document.querySelector('.error-shake');
        if (errorDiv) {
            errorDiv.addEventListener('animationend', () => errorDiv.classList.remove('error-shake'));
        }
    </script>
</body>
</html>