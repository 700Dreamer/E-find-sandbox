<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';
require_once 'includes/Auth.php';

Session::init();

if (Session::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = null;
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
    ];
    
    if ($data['password'] !== ($_POST['confirm_password'] ?? '')) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($data);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    
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
<body class="font-inter antialiased bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center space-x-3">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-600 to-accent-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg">
                    <i class="fas fa-cubes"></i>
                </div>
                <span class="text-3xl font-poppins font-bold">
                    <span class="text-primary-600">E-Find</span>
                    <span class="text-accent-500"> & Soft Solutions</span>
                </span>
            </a>
            <p class="text-gray-500 mt-2">Create your account to get started</p>
        </div>
        
        <div class="bg-white rounded-3xl shadow-2xl p-8 animate-fade-in">
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-user"></i></span>
                        <input type="text" name="name" class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <input type="email" name="email" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Enter your email" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Enter your phone" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Create password" required minlength="8">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                        <input type="password" name="confirm_password" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Confirm password" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                        <input type="text" name="city" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Enter your city">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <input type="text" name="address" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 transition-all outline-none" placeholder="Enter your address">
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="terms" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" required>
                    <label for="terms" class="ml-2 text-sm text-gray-600">I agree to the <a href="#" class="text-primary-600 font-semibold">Terms of Service</a></label>
                </div>
                
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/25 transition-all duration-300 transform hover:-translate-y-0.5">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </button>
            </form>
            
            <p class="text-center text-gray-500 mt-6">
                Already have an account?
                <a href="login.php" class="text-primary-600 font-semibold hover:text-primary-700">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>