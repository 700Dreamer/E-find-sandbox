<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';

Session::init();

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        $success = 'Thank you for your message! We will get back to you shortly.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* Custom Animations & Effects */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0.4); }
            50% { box-shadow: 0 0 0 15px rgba(59,130,246,0); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes slide-in-right {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fade-scale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .float-animation {
            animation: float 4s ease-in-out infinite;
        }
        
        .card-hover {
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            backdrop-filter: blur(0px);
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            backdrop-filter: blur(0px);
        }
        
        .card-hover-3d {
            transition: all 0.3s ease;
        }
        
        .card-hover-3d:hover {
            transform: rotateX(2deg) rotateY(2deg) translateY(-5px);
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.15);
        }
        
        .icon-shine {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover .icon-shine {
            transform: rotateY(180deg);
        }
        
        .btn-ripple {
            position: relative;
            overflow: hidden;
        }
        
        .btn-ripple:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .btn-ripple:active:after {
            width: 200%;
            height: 200%;
        }
        
        .gradient-border {
            position: relative;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #3b82f6, #f97316, #8b5cf6) border-box;
            border: 2px solid transparent;
        }
        
        .modal-backdrop {
            backdrop-filter: blur(8px);
            transition: opacity 0.3s ease;
        }
        
        .table-row-hover {
            transition: all 0.2s ease;
        }
        
        .table-row-hover:hover {
            background: linear-gradient(90deg, rgba(59,130,246,0.05), rgba(249,115,22,0.05));
            transform: scale(1.01);
        }
        
        .input-focus-effect {
            transition: all 0.2s ease;
        }
        
        .input-focus-effect:focus {
            transform: translateY(-2px);
        }
        
        .sidebar-card {
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        
        .sidebar-card:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, #ffffff, #f8fafc);
        }
        
        .floating-chat-btn {
            animation: pulse-glow 2s infinite;
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #f97316);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2563eb, #ea580c);
        }
        
        /* Glass morphism effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Loading spinner */
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                        accent: { 50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea580c',700:'#c2410c',800:'#9a3412',900:'#7c2d12' }
                    },
                    fontFamily: { 'poppins':['Poppins','sans-serif'], 'inter':['Inter','sans-serif'] },
                    animation: {
                        'fade-in': 'fade-scale 0.5s ease-out',
                        'slide-in': 'slide-in-right 0.4s ease-out',
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter antialiased bg-gradient-to-br from-gray-50 via-gray-50 to-blue-50/30">

    <!-- Animated Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute top-0 -left-40 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 -right-40 w-80 h-80 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse animation-delay-4000"></div>
    </div>

    <!-- NAVIGATION with enhancements -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm transition-all duration-300" id="mainNav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <!-- Logo with hover animation -->
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:rotate-3">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <span class="text-xl lg:text-2xl font-poppins font-bold">
                        <span class="text-blue-600">E-Find</span>
                        <span class="text-orange-500"> & Soft Solutions</span>
                    </span>
                </a>
                
                <!-- Desktop Nav with underline effect -->
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all relative group">Home
                        <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    
                    <div class="relative group">
                        <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all flex items-center">
                            Services <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 transform origin-top scale-95 group-hover:scale-100">
                            <div class="p-2 grid gap-1">
                                <a href="services.php?category=engraving" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition-all hover:translate-x-1"><i class="fas fa-trophy w-6"></i><span class="ml-3 text-sm">Engraving</span></a>
                                <a href="services.php?category=embroidery" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition-all hover:translate-x-1"><i class="fas fa-tshirt w-6"></i><span class="ml-3 text-sm">Embroidery</span></a>
                                <a href="services.php?category=tracking" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition-all hover:translate-x-1"><i class="fas fa-map-marker-alt w-6"></i><span class="ml-3 text-sm">Tracking</span></a>
                                <a href="services.php?category=calligraphy" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition-all hover:translate-x-1"><i class="fas fa-paint-brush w-6"></i><span class="ml-3 text-sm">Calligraphy</span></a>
                                <a href="services.php?category=branding" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition-all hover:translate-x-1"><i class="fas fa-palette w-6"></i><span class="ml-3 text-sm">Branding</span></a>
                                <a href="services.php?category=printing" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition-all hover:translate-x-1"><i class="fas fa-print w-6"></i><span class="ml-3 text-sm">Printing</span></a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="track.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all relative group">Track Order
                        <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    <a href="contact.php" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg transition-all shadow-md hover:shadow-xl hover:-translate-y-0.5">Contact</a>
                </div>
                
                <!-- User Actions -->
                <div class="hidden lg:flex items-center space-x-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition-all">
                                <i class="fas fa-user-circle text-lg"></i>
                                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2 transform origin-top-right scale-95 group-hover:scale-100">
                                <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg transition-all hover:translate-x-1"><i class="fas fa-user w-5 mr-3"></i> Profile</a>
                                <a href="my-orders.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg transition-all hover:translate-x-1"><i class="fas fa-shopping-bag w-5 mr-3"></i> My Orders</a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <hr class="my-1"><a href="admin/dashboard.php" class="flex items-center px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 mx-2 rounded-lg transition-all hover:translate-x-1"><i class="fas fa-tachometer-alt w-5 mr-3"></i> Admin Panel</a>
                                <?php endif; ?>
                                <hr class="my-1"><a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 mx-2 rounded-lg transition-all hover:translate-x-1"><i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-5 py-2.5 text-sm font-semibold text-gray-700 border-2 border-gray-300 rounded-xl hover:border-blue-600 hover:text-blue-600 transition-all hover:-translate-y-0.5">Sign In</a>
                        <a href="register.php" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:shadow-lg transition-all transform hover:-translate-y-0.5">Get Started</a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Button -->
                <button class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-all" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobileMenu" class="lg:hidden hidden pb-4 space-y-1 animate-fade-in">
                <a href="index.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-all">Home</a>
                <a href="services.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-all">Services</a>
                <a href="track.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-all">Track Order</a>
                <a href="contact.php" class="block px-4 py-3 text-blue-600 bg-blue-50 rounded-lg font-medium">Contact</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="flex gap-2 mt-3 px-4">
                    <a href="login.php" class="flex-1 text-center py-2.5 border-2 border-gray-300 rounded-xl font-semibold text-sm transition-all hover:border-blue-600">Sign In</a>
                    <a href="register.php" class="flex-1 text-center py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm transition-all hover:bg-blue-700">Get Started</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="pt-24 lg:pt-28 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Page Header with enhanced animation -->
            <div class="text-center mb-16" data-aos="fade-up" data-aos-duration="1000">
                <div class="inline-flex items-center gap-2 bg-blue-100/50 backdrop-blur-sm px-4 py-2 rounded-full mb-4">
                    <i class="fas fa-comment-dots text-blue-600 text-sm"></i>
                    <span class="text-sm font-semibold text-blue-600">Get in Touch</span>
                </div>
                <h1 class="text-4xl lg:text-5xl font-poppins font-extrabold text-gray-900 mb-4">
                    <span class="bg-gradient-to-r from-blue-600 via-blue-700 to-orange-600 bg-clip-text text-transparent animate-gradient">Let's Connect</span>
                </h1>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">Have a question or need help? We're here for you! Reach out and we'll get back to you as soon as possible.</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                
                <!-- Polished Sidebar - Contact Info Cards with 3D effects -->
                <div class="space-y-6" data-aos="fade-right" data-aos-duration="800">
                    <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100 card-hover-3d cursor-pointer group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16 transition-all duration-500 group-hover:scale-150"></div>
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg transition-all duration-500 group-hover:rotate-12 group-hover:scale-110">
                            <i class="fas fa-phone text-2xl text-white"></i>
                        </div>
                        <h4 class="font-poppins font-bold text-xl text-gray-900 mb-2">Phone Support</h4>
                        <p class="text-gray-500 mb-3">Available 24/7 for urgent inquiries</p>
                        <p class="text-gray-900 font-semibold text-lg">+256 700 000000</p>
                        <p class="text-gray-600">+256 701 000000</p>
                        <div class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <span class="text-sm text-blue-600 font-semibold">Call now →</span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100 card-hover-3d cursor-pointer group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 rounded-full -mr-16 -mt-16 transition-all duration-500 group-hover:scale-150"></div>
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg transition-all duration-500 group-hover:rotate-12 group-hover:scale-110">
                            <i class="fas fa-envelope text-2xl text-white"></i>
                        </div>
                        <h4 class="font-poppins font-bold text-xl text-gray-900 mb-2">Email Us</h4>
                        <p className="text-gray-500 mb-3">Response within 24 hours</p>
                        <p class="text-gray-700">info@efind.com</p>
                        <p class="text-gray-700">support@efind.com</p>
                        <div class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <span class="text-sm text-green-600 font-semibold">Send email →</span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100 card-hover-3d cursor-pointer group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full -mr-16 -mt-16 transition-all duration-500 group-hover:scale-150"></div>
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg transition-all duration-500 group-hover:rotate-12 group-hover:scale-110">
                            <i class="fas fa-map-marker-alt text-2xl text-white"></i>
                        </div>
                        <h4 class="font-poppins font-bold text-xl text-gray-900 mb-2">Visit Our Office</h4>
                        <p class="text-gray-500 mb-3">Come say hello!</p>
                        <p class="text-gray-700">123 Business Park</p>
                        <p class="text-gray-700">Kampala, Uganda</p>
                        <div class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <span class="text-sm text-orange-600 font-semibold">Get directions →</span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100 card-hover-3d cursor-pointer group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full -mr-16 -mt-16 transition-all duration-500 group-hover:scale-150"></div>
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg transition-all duration-500 group-hover:rotate-12 group-hover:scale-110">
                            <i class="fas fa-clock text-2xl text-white"></i>
                        </div>
                        <h4 class="font-poppins font-bold text-xl text-gray-900 mb-2">Working Hours</h4>
                        <p class="text-gray-500 mb-3">We're here to serve you</p>
                        <p class="text-gray-700 font-semibold">Monday - Saturday</p>
                        <p class="text-gray-700">8:00 AM - 6:00 PM</p>
                        <div class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <span class="text-sm text-purple-600 font-semibold">Schedule visit →</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Form with enhanced styling -->
                <div class="lg:col-span-2" data-aos="fade-left" data-aos-duration="800">
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-500/5 to-orange-500/5 rounded-full -mr-32 -mt-32"></div>
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-to-tr from-purple-500/5 to-pink-500/5 rounded-full -ml-32 -mb-32"></div>
                        
                        <h3 class="text-2xl font-poppins font-bold text-gray-900 mb-2">Send us a Message</h3>
                        <p class="text-gray-500 mb-8">Fill out the form below and we'll get back to you within 24 hours.</p>
                        
                        <?php if ($success): ?>
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-fade-in">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-check-circle text-xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold">Message Sent Successfully!</p>
                                <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-shake">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-circle text-xl text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold">Error</p>
                                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-6" id="contactForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="transform transition-all duration-300 focus-within:scale-105">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Name <span class="text-red-500">*</span></label>
                                    <div class="relative group">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"><i class="fas fa-user"></i></span>
                                        <input type="text" name="name" class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none input-focus-effect" placeholder="John Doe" required>
                                    </div>
                                </div>
                                <div class="transform transition-all duration-300 focus-within:scale-105">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Email <span class="text-red-500">*</span></label>
                                    <div class="relative group">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none input-focus-effect" placeholder="john@example.com" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="transform transition-all duration-300 focus-within:scale-105">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                                <div class="relative group">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"><i class="fas fa-tag"></i></span>
                                    <input type="text" name="subject" class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none input-focus-effect" placeholder="What is this about?">
                                </div>
                            </div>
                            
                            <div class="transform transition-all duration-300 focus-within:scale-105">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Message <span class="text-red-500">*</span></label>
                                <textarea name="message" rows="5" class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none resize-none input-focus-effect" placeholder="Tell us how we can help you..." required></textarea>
                            </div>
                            
                            <button type="submit" id="submitBtn" class="w-full py-4 bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-blue-600/30 transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 btn-ripple">
                                <i class="fas fa-paper-plane"></i>
                                <span>Send Message</span>
                                <div class="hidden loading-spinner ml-2"></div>
                            </button>
                        </form>
                        
                        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-lock text-green-600 mr-1"></i> 
                                Your information is safe with us. We never share your data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Responsive Pricing Table Section -->
            <div class="mt-20" data-aos="fade-up" data-aos-duration="800">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-100 to-purple-100 backdrop-blur-sm px-4 py-2 rounded-full mb-4">
                        <i class="fas fa-chart-line text-blue-600 text-sm"></i>
                        <span class="text-sm font-semibold text-blue-600">Service Pricing</span>
                    </div>
                    <h2 class="text-3xl lg:text-4xl font-poppins font-bold text-gray-900 mb-4">Our Premium Services</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto">Choose the perfect service package for your needs</p>
                </div>
                
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                    <th class="px-6 py-4 text-left font-semibold">Service</th>
                                    <th class="px-6 py-4 text-left font-semibold">Duration</th>
                                    <th class="px-6 py-4 text-left font-semibold">Price (UGX)</th>
                                    <th class="px-6 py-4 text-left font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr class="table-row-hover transition-all cursor-pointer">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-trophy text-blue-500"></i>
                                            <span>Premium Engraving</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">2-3 Days</td>
                                    <td class="px-6 py-4 font-semibold text-blue-600">UGX 150,000</td>
                                    <td class="px-6 py-4">
                                        <button class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg text-sm hover:shadow-lg transition-all transform hover:-translate-y-0.5 hover:scale-105">
                                            Order Now
                                        </button>
                                    </td>
                                </tr>
                                <tr class="table-row-hover transition-all cursor-pointer">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-tshirt text-green-500"></i>
                                            <span>Custom Embroidery</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">5-7 Days</td>
                                    <td class="px-6 py-4 font-semibold text-blue-600">UGX 250,000</td>
                                    <td class="px-6 py-4">
                                        <button class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg text-sm hover:shadow-lg transition-all transform hover:-translate-y-0.5 hover:scale-105">
                                            Order Now
                                        </button>
                                    </td>
                                </tr>
                                <tr class="table-row-hover transition-all cursor-pointer">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-map-marker-alt text-orange-500"></i>
                                            <span>Real-time Tracking Setup</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">3-4 Days</td>
                                    <td class="px-6 py-4 font-semibold text-blue-600">UGX 350,000</td>
                                    <td class="px-6 py-4">
                                        <button class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg text-sm hover:shadow-lg transition-all transform hover:-translate-y-0.5 hover:scale-105">
                                            Order Now
                                        </button>
                                    </td>
                                </tr>
                                <tr class="table-row-hover transition-all cursor-pointer">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-palette text-purple-500"></i>
                                            <span>Complete Branding Package</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">7-10 Days</td>
                                    <td class="px-6 py-4 font-semibold text-blue-600">UGX 500,000</td>
                                    <td class="px-6 py-4">
                                        <button class="px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg text-sm hover:shadow-lg transition-all transform hover:-translate-y-0.5 hover:scale-105">
                                            Order Now
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="text-center mt-6">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-tag text-blue-500 mr-1"></i>
                        Bulk orders get 15% discount. Contact us for custom quotes!
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Chat Button & Modal -->
    <button id="chatModalBtn" class="fixed bottom-8 right-8 z-50 w-14 h-14 bg-gradient-to-r from-blue-600 to-orange-500 rounded-full shadow-2xl flex items-center justify-center text-white text-xl hover:scale-110 transition-all duration-300 floating-chat-btn group">
        <i class="fas fa-comment-dots"></i>
        <span class="absolute right-full mr-3 px-3 py-1 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">Need Help?</span>
    </button>

    <!-- Elegant Modal -->
    <div id="chatModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-t-3xl p-6 text-white">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-headset text-2xl"></i>
                        <h3 class="text-xl font-bold">Quick Support</h3>
                    </div>
                    <button id="closeModalBtn" class="text-white hover:bg-white/20 rounded-full w-8 h-8 flex items-center justify-center transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-blue-100 text-sm mt-2">We're here to help! Leave a message and we'll get back to you ASAP.</p>
            </div>
            <div class="p-6">
                <form id="quickSupportForm">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Your Name</label>
                        <input type="text" id="quickName" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none" placeholder="Enter your name" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="quickEmail" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none" placeholder="your@email.com" required>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                        <textarea id="quickMessage" rows="3" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none resize-none" placeholder="How can we help you?" required></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                        Send Message
                    </button>
                </form>
                <p class="text-xs text-gray-500 text-center mt-4">
                    <i class="fas fa-clock"></i> Response within 2 hours
                </p>
            </div>
        </div>
    </div>

    <!-- FOOTER with enhanced design -->
    <footer class="bg-gray-900 text-gray-400 pt-16 pb-8 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-orange-500"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg transform transition-all hover:rotate-12"><i class="fas fa-cubes"></i></div>
                        <span class="text-2xl font-poppins font-bold text-white">E-Find & Soft Solutions</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed mb-6">Your one-stop platform for custom services, tracking solutions, and professional branding.</p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 transition-all hover:scale-110"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-pink-600 transition-all hover:scale-110"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-green-600 transition-all hover:scale-110"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-400 transition-all hover:scale-110"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-poppins font-semibold text-lg mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="hover:text-white transition-all hover:translate-x-1 inline-block">Home</a></li>
                        <li><a href="services.php" class="hover:text-white transition-all hover:translate-x-1 inline-block">Services</a></li>
                        <li><a href="track.php" class="hover:text-white transition-all hover:translate-x-1 inline-block">Track Order</a></li>
                        <li><a href="contact.php" class="hover:text-white transition-all hover:translate-x-1 inline-block">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-poppins font-semibold text-lg mb-6">Our Services</h4>
                    <ul class="space-y-3">
                        <li><a href="services.php?category=engraving" class="hover:text-white transition-all hover:translate-x-1 inline-block"><i class="fas fa-trophy mr-2 text-blue-400"></i>Engraving</a></li>
                        <li><a href="services.php?category=embroidery" class="hover:text-white transition-all hover:translate-x-1 inline-block"><i class="fas fa-tshirt mr-2 text-blue-400"></i>Embroidery</a></li>
                        <li><a href="services.php?category=tracking" class="hover:text-white transition-all hover:translate-x-1 inline-block"><i class="fas fa-map-marker-alt mr-2 text-blue-400"></i>Tracking</a></li>
                        <li><a href="services.php?category=branding" class="hover:text-white transition-all hover:translate-x-1 inline-block"><i class="fas fa-palette mr-2 text-blue-400"></i>Branding</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-poppins font-semibold text-lg mb-6">Newsletter</h4>
                    <p class="text-gray-400 mb-4">Subscribe for updates and offers</p>
                    <div class="flex">
                        <input type="email" placeholder="Your email" class="flex-1 px-4 py-2 rounded-l-xl bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500">
                        <button class="px-4 py-2 bg-blue-600 rounded-r-xl hover:bg-blue-700 transition-all">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 text-center">
                <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> E-Find and Soft Solutions. All rights reserved. | Designed with <i class="fas fa-heart text-red-500"></i> for excellence</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({ 
            duration: 800, 
            once: true,
            easing: 'ease-out-quad'
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('mainNav');
            if (window.scrollY > 50) { 
                nav.classList.add('shadow-lg', 'bg-white/95');
                nav.classList.remove('bg-white/95');
            } else { 
                nav.classList.remove('shadow-lg');
            }
        });
        
        // Form submission loading state
        const form = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const btn = submitBtn;
                const spinner = btn.querySelector('.loading-spinner');
                const btnText = btn.querySelector('span');
                const btnIcon = btn.querySelector('i');
                
                if (btnText && spinner) {
                    btnIcon.classList.add('hidden');
                    btnText.textContent = 'Sending...';
                    spinner.classList.remove('hidden');
                    btn.disabled = true;
                }
            });
        }
        
        // Modal functionality
        const modalBtn = document.getElementById('chatModalBtn');
        const modal = document.getElementById('chatModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const modalContent = document.getElementById('modalContent');
        
        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                if (modalContent) {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }
            }, 10);
        }
        
        function closeModal() {
            if (modalContent) {
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');
            }
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }
        
        if (modalBtn) modalBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        
        // Click outside to close
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        }
        
        // Quick support form submission
        const quickForm = document.getElementById('quickSupportForm');
        if (quickForm) {
            quickForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const name = document.getElementById('quickName').value;
                const email = document.getElementById('quickEmail').value;
                const message = document.getElementById('quickMessage').value;
                
                if (name && email && message) {
                    alert(`Thank you ${name}! Our support team will contact you at ${email} within 2 hours.`);
                    closeModal();
                    quickForm.reset();
                } else {
                    alert('Please fill all fields');
                }
            });
        }
        
        // Add animation delay class
        const style = document.createElement('style');
        style.textContent = `
            .animation-delay-2000 { animation-delay: 2s; }
            .animation-delay-4000 { animation-delay: 4s; }
            @keyframes animate-gradient {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            .animate-gradient {
                background-size: 200% auto;
                animation: animate-gradient 3s linear infinite;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .animate-shake {
                animation: shake 0.3s ease-in-out;
            }
        `;
        document.head.appendChild(style);
        
        // Parallax hover effect for cards (optional subtle)
        document.querySelectorAll('.card-hover-3d').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                this.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'rotateX(0deg) rotateY(0deg) translateY(0px)';
            });
        });
    </script>
</body>
</html>