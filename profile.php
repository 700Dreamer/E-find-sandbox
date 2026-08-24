<?php
require_once 'config/app.php';
require_once 'includes/Session.php';
require_once 'includes/Auth.php';

Session::init();

// Redirect if not logged in
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$auth = new Auth();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                        accent: { 50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea580c',700:'#c2410c',800:'#9a3412',900:'#7c2d12' }
                    },
                    fontFamily: { 'poppins':['Poppins','sans-serif'], 'inter':['Inter','sans-serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="font-inter antialiased bg-gray-50 min-h-screen">
    
    <!-- Navigation Bar (inline to avoid include issues) -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between h-20">
                <a href="index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg"><i class="fas fa-cubes"></i></div>
                    <span class="text-xl font-poppins font-bold"><span class="text-blue-600">E-Find</span><span class="text-orange-500"> & Soft Solutions</span></span>
                </a>
                <div class="hidden lg:flex items-center space-x-4">
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 rounded-lg hover:bg-gray-50 transition-all"><i class="fas fa-home mr-1"></i> Home</a>
                    <a href="services.php" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 rounded-lg hover:bg-gray-50 transition-all"><i class="fas fa-cogs mr-1"></i> Services</a>
                    <a href="track.php" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 rounded-lg hover:bg-gray-50 transition-all"><i class="fas fa-map-marker-alt mr-1"></i> Track</a>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="p-2">
                                <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-50 text-blue-600 text-sm"><i class="fas fa-user w-5 mr-3"></i> Profile</a>
                                <a href="my-orders.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 text-sm"><i class="fas fa-shopping-bag w-5 mr-3"></i> My Orders</a>
                                <hr class="my-2">
                                <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-red-50 text-red-600 text-sm"><i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <section class="pt-32 pb-16">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-600 to-orange-500 rounded-full flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h3 class="text-xl font-poppins font-bold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="text-gray-500 text-sm mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold"><?php echo ucfirst($user['role']); ?></span>
                </div>

                <!-- Profile Details -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-8">
                    <h2 class="text-xl font-poppins font-bold text-gray-900 mb-6">Profile Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Full Name</label>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Email Address</label>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Phone Number</label>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">City</label>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['city'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-500 mb-1">Address</label>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Account Role</label>
                            <p class="font-semibold text-gray-900"><?php echo ucfirst($user['role']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Member Since</label>
                            <p class="font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 mt-8 pt-6 border-t">
                        <a href="my-orders.php" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors inline-flex items-center">
                            <i class="fas fa-shopping-bag mr-2"></i> View My Orders
                        </a>
                        <a href="index.php" class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:border-blue-600 hover:text-blue-600 transition-all inline-flex items-center">
                            <i class="fas fa-home mr-2"></i> Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-10">
        <div class="container mx-auto px-6 text-center">
            <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> E-Find and Soft Solutions. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>