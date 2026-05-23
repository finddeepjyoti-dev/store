<?php
// install.php – Create database/tables, default admin, sample data
if (file_exists('installed.lock')) {
    die('Already installed. <a href="admin/login.php">Go to Admin Login</a>');
}
$step = isset($_POST['install']) ? 2 : 1;
$error = '';
$success = '';

if ($step == 2) {
    $host = 'localhost';
    $user = 'awdsite_result';
    $pass = '!5Lpt86sHa9R]IKc';               // XAMPP default
    $dbname = 'awdsite_result';

    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        $error = 'DB connection failed: ' . $conn->connect_error;
    } else {
        $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $conn->select_db($dbname);

        $sql = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS `admin` (
          `id` int(11) AUTO_INCREMENT PRIMARY KEY,
          `username` varchar(50) NOT NULL UNIQUE,
          `password` varchar(255) NOT NULL
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS `categories` (
          `id` int(11) AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(100) NOT NULL,
          `image` varchar(255) DEFAULT NULL
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS `products` (
          `id` int(11) AUTO_INCREMENT PRIMARY KEY,
          `cat_id` int(11) NOT NULL,
          `name` varchar(200) NOT NULL,
          `description` text,
          `price` decimal(10,2) NOT NULL,
          `stock` int(11) DEFAULT 0,
          `image` varchar(255) DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`cat_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `status` ENUM('Placed','Dispatched','Delivered','Cancelled') DEFAULT 'Placed',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS `order_items` (
          `id` int(11) AUTO_INCREMENT PRIMARY KEY,
          `order_id` int(11) NOT NULL,
          `product_id` int(11) NOT NULL,
          `quantity` int(11) NOT NULL,
          `price` decimal(10,2) NOT NULL,
          FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS `banners` (
          `id` int(11) AUTO_INCREMENT PRIMARY KEY,
          `title` varchar(255) NOT NULL,
          `image` varchar(255) NOT NULL,
          `link` varchar(500) DEFAULT NULL,
          `cat_id` int(11) DEFAULT NULL,
          `sort_order` int(11) DEFAULT 0,
          FOREIGN KEY (`cat_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Responded') DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        if ($conn->multi_query($sql)) {
            while ($conn->next_result()) {;}
            // Default admin: admin / admin
            $admin_pass = password_hash('admin', PASSWORD_DEFAULT);
            $conn->query("INSERT IGNORE INTO `admin` (`username`,`password`) VALUES ('admin','$admin_pass')");

            // Sample category & product
            $conn->query("INSERT IGNORE INTO `categories` (`id`,`name`,`image`) VALUES (1,'Electronics','https://via.placeholder.com/100/FF6600/FFF?text=Electronics')");
            $conn->query("INSERT IGNORE INTO `products` (`id`,`cat_id`,`name`,`description`,`price`,`stock`,`image`) VALUES (1,1,'Wireless Earbuds','Premium sound, long battery life',999.00,10,'https://via.placeholder.com/300/FF6600/FFF?text=Earbuds')");

            if (!is_dir('images')) mkdir('images', 0755);
            if (!is_dir('images/categories')) mkdir('images/categories', 0755, true);

            file_put_contents('installed.lock', 'installed');
            $success = 'Installation complete! Redirecting to admin login...';
        } else {
            $error = 'Table creation failed: ' . $conn->error;
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Quick Kart - Install</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body{user-select:none;-webkit-user-select:none;}</style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md mx-4">
    <h1 class="text-2xl font-bold text-center mb-6 text-indigo-600">Quick Kart Installation</h1>
    <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $success ?></div>
        <script>setTimeout(()=>{window.location='admin/login.php';},2000);</script>
    <?php else: ?>
        <form method="post">
            <button type="submit" name="install" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700">
                <i class="fas fa-download mr-2"></i> Install Now
            </button>
        </form>
        <p class="text-xs text-gray-500 mt-4 text-center">Make sure MySQL is running (XAMPP) – user root, no password.</p>
    <?php endif; ?>
</div>
<script>
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('selectstart', e => e.preventDefault());
</script>
</body>
</html>