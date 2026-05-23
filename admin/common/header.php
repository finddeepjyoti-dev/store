<?php
require_once __DIR__.'/config.php';
if(!isset($_SESSION['admin_id'])){ header('Location: ../login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Admin - UpdatesAll</title>
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
        #loader {
            background: rgba(15,23,42,0.7);
            backdrop-filter: blur(8px);
        }
        /* Sidebar transitions */
        #adminSidebar {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #adminSidebar.open {
            transform: translateX(0) !important;
        }
        /* Card hover effects */
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 10px 10px -5px rgba(0,0,0,0.2);
        }
        /* Subtle pulse on loader dots */
        .dot-loader div {
            animation: pulse 1.4s infinite ease-in-out both;
        }
        .dot-loader div:nth-child(2) { animation-delay: 0.16s; }
        .dot-loader div:nth-child(3) { animation-delay: 0.32s; }
        @keyframes pulse {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1.2); opacity: 1; }
        }
        /* Hide scrollbar for mobile */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex flex-col lg:flex-row">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 hidden lg:hidden" onclick="closeSidebar()"></div>

<!-- Main area -->
<div class="flex-1 ml-0 lg:ml-64 flex flex-col min-h-screen">
    <!-- Header bar -->
    <header class="bg-slate-800/80 backdrop-blur-md border-b border-slate-700/50 px-4 py-3 flex items-center justify-between sticky top-0 z-20 shadow-lg">
        <div class="flex items-center gap-3">
            <button id="menuToggle" class="text-slate-300 hover:text-amber-400 text-2xl lg:hidden transition-colors">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-lg font-bold text-amber-400 tracking-tight">Admin Panel</h1>
        </div>
        <a href="setting.php" class="text-slate-400 hover:text-amber-400 transition-colors p-1">
            <i class="fas fa-user-cog text-xl"></i>
        </a>
    </header>

    <!-- Page content wrapper -->
    <main class="flex-1 p-4 relative">
        <!-- Loader (always hidden initially) -->
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

        // Mobile sidebar toggle
        (function() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.getElementById('menuToggle');

            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    overlay.classList.remove('hidden');
                });
            }

            window.closeSidebar = function() {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
            };
        })();

        // Prevent context menu, selection
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
        </script>