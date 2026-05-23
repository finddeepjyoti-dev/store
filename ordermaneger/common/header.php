<?php
ob_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['ordermanager_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Order Manager | UpdatesAll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            background: #0f172a;
        }
        #loader { background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); }
        .dot-loader div { animation: pulse 1.4s infinite ease-in-out both; }
        .dot-loader div:nth-child(2) { animation-delay: 0.16s; }
        .dot-loader div:nth-child(3) { animation-delay: 0.32s; }
        @keyframes pulse {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1.2); opacity: 1; }
        }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex flex-col">

<header class="bg-slate-800/80 backdrop-blur-md border-b border-slate-700/50 px-4 py-3 flex items-center justify-between sticky top-0 z-20 shadow-lg">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center">
            <i class="fas fa-clipboard-list text-gray-900"></i>
        </div>
        <h1 class="text-lg font-bold text-amber-400">Order Manager</h1>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-slate-300 text-sm font-medium hidden sm:block">
            👋 <?= htmlspecialchars($_SESSION['ordermanager_name'] ?? 'Staff') ?>
        </span>
        <a href="logout.php" class="text-slate-400 hover:text-amber-400 transition-colors text-sm font-medium flex items-center gap-1">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<main class="flex-1 p-4 relative">
    <div id="loader" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
        <div class="bg-slate-800/90 backdrop-blur-lg p-8 rounded-2xl border border-slate-700 shadow-2xl flex flex-col items-center">
            <div class="flex space-x-2 dot-loader">
                <div class="w-4 h-4 bg-amber-500 rounded-full"></div>
                <div class="w-4 h-4 bg-amber-400 rounded-full"></div>
                <div class="w-4 h-4 bg-teal-400 rounded-full"></div>
            </div>
            <p class="text-slate-300 text-sm mt-3">Loading...</p>
        </div>
    </div>

    <script>
        function showLoader(){ document.getElementById('loader').style.display='flex'; }
        function hideLoader(){ document.getElementById('loader').style.display='none'; }
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
    </script>