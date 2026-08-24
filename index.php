<?php
require_once 'config/app.php';
require_once 'includes/Session.php';
require_once 'includes/Service.php';

Session::init();
$serviceManager = new ServiceManager();
$featuredServices = $serviceManager->getFeaturedServices();

$serviceIcons = [
    'engraving' => 'fa-trophy',
    'embroidery' => 'fa-tshirt',
    'tracking' => 'fa-map-marker-alt',
    'calligraphy' => 'fa-paint-brush',
    'branding' => 'fa-palette',
    'printing' => 'fa-print',
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Smart Custom Services & Tracking</title>
    
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
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* ===== HERO SECTION ===== */
        .hero-section {
            background: linear-gradient(160deg, #0f172a 0%, #1e293b 30%, #1e3a5f 60%, #1e293b 100%);
            background-size: 400% 400%;
            animation: gradientFlow 15s ease infinite;
            position: relative;
            overflow: hidden;
            min-height: 520px;
        }
        @keyframes gradientFlow {
            0%, 100% { background-position: 0% 50%; }
            25% { background-position: 100% 0%; }
            50% { background-position: 100% 100%; }
            75% { background-position: 0% 100%; }
        }
        
        .hero-icons-container {
            position: absolute;
            right: 0; top: 0; bottom: 0;
            width: 52%;
            pointer-events: none;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        
        .hero-floating-icon {
            position: absolute;
            opacity: 0.14;
            color: #60a5fa;
            animation: heroIconFloat 8s ease-in-out infinite;
        }
        .hero-floating-icon:nth-child(odd) { color: #a78bfa; animation-duration: 9s; }
        .hero-floating-icon:nth-child(3n) { color: #fb923c; animation-duration: 7s; }
        .hero-floating-icon:nth-child(4n) { color: #34d399; animation-duration: 10s; }
        .hero-floating-icon:nth-child(5n) { color: #f472b6; animation-duration: 8.5s; }
        
        @keyframes heroIconFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
            20% { transform: translate(-35px, -30px) rotate(6deg) scale(1.06); }
            40% { transform: translate(25px, -55px) rotate(-5deg) scale(0.94); }
            60% { transform: translate(-20px, -35px) rotate(4deg) scale(1.04); }
            80% { transform: translate(30px, -50px) rotate(-4deg) scale(0.96); }
        }
        .hero-floating-icon.horizontal { animation-name: heroIconFloatHorizontal; }
        @keyframes heroIconFloatHorizontal {
            0%, 100% { transform: translate(0, -35px) rotate(0deg) scale(1); }
            30% { transform: translate(50px, -25px) rotate(7deg) scale(1.08); }
            60% { transform: translate(-40px, -45px) rotate(-5deg) scale(0.92); }
        }
        .hero-floating-icon.diagonal { animation-name: heroIconFloatDiagonal; }
        @keyframes heroIconFloatDiagonal {
            0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
            25% { transform: translate(40px, -40px) rotate(8deg) scale(1.1); }
            50% { transform: translate(-30px, -20px) rotate(-6deg) scale(0.9); }
            75% { transform: translate(20px, -50px) rotate(4deg) scale(1.05); }
        }
        
        .hero-particle {
            position: absolute; border-radius: 50%;
            background: rgba(96, 165, 250, 0.5);
            pointer-events: none;
            animation: particleFloat 6s ease-in-out infinite;
        }
        @keyframes particleFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            50% { transform: translate(30px, -50px) scale(1.8); opacity: 0.7; }
        }
        
        .hero-glow {
            position: absolute; border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            animation: glowPulse 8s ease-in-out infinite;
        }
        @keyframes glowPulse {
            0%, 100% { transform: scale(1); opacity: 0.25; }
            50% { transform: scale(1.4); opacity: 0.5; }
        }
        
        /* ===== SERVICES SECTION - Original Animated Background ===== */
        .services-section {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f0f4ff 25%, #fef7ed 50%, #f0f9ff 75%, #ffffff 100%);
            background-size: 100% 300%;
            animation: servicesGradient 12s ease infinite;
        }
        @keyframes servicesGradient {
            0%, 100% { background-position: 0% 0%; }
            25% { background-position: 0% 50%; }
            50% { background-position: 0% 100%; }
            75% { background-position: 0% 50%; }
        }
        
        .services-floating-icon {
            position: absolute;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
            animation: servicesIconFloat 8s ease-in-out infinite;
        }
        .services-floating-icon:nth-child(odd) { animation-duration: 9s; }
        .services-floating-icon:nth-child(3n) { animation-duration: 7s; }
        .services-floating-icon:nth-child(4n) { animation-duration: 10s; }
        .services-floating-icon:nth-child(5n) { animation-duration: 6.5s; }
        
        @keyframes servicesIconFloat {
            0% { transform: translate(0, 0) rotate(0deg) scale(1); }
            20% { transform: translate(40px, -30px) rotate(8deg) scale(1.1); }
            40% { transform: translate(-20px, -55px) rotate(-5deg) scale(0.9); }
            60% { transform: translate(-35px, -20px) rotate(3deg) scale(1.05); }
            80% { transform: translate(25px, -45px) rotate(-7deg) scale(0.95); }
            100% { transform: translate(0, 0) rotate(0deg) scale(1); }
        }
        .services-floating-icon.alt-animation { animation-name: servicesIconFloatAlt; }
        @keyframes servicesIconFloatAlt {
            0% { transform: translate(0, 0) rotate(0deg) scale(1); }
            25% { transform: translate(-35px, -25px) rotate(-6deg) scale(1.08); }
            50% { transform: translate(30px, -50px) rotate(4deg) scale(0.92); }
            75% { transform: translate(-25px, -15px) rotate(-3deg) scale(1.04); }
            100% { transform: translate(0, 0) rotate(0deg) scale(1); }
        }
        .services-floating-icon.horizontal-animation { animation-name: servicesIconFloatHorizontal; }
        @keyframes servicesIconFloatHorizontal {
            0% { transform: translate(0, -30px) rotate(0deg) scale(1); }
            30% { transform: translate(50px, -20px) rotate(5deg) scale(1.1); }
            60% { transform: translate(-40px, -40px) rotate(-4deg) scale(0.9); }
            100% { transform: translate(0, -30px) rotate(0deg) scale(1); }
        }
        
        .services-accent-dot {
            position: absolute; border-radius: 50%;
            pointer-events: none; z-index: 0;
            animation: accentDotPulse 5s ease-in-out infinite;
        }
        @keyframes accentDotPulse {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.8); }
        }
        
        .services-accent-shape {
            position: absolute; border-radius: 50%;
            filter: blur(80px);
            pointer-events: none; z-index: 0;
            animation: accentShapeMove 15s ease-in-out infinite;
        }
        @keyframes accentShapeMove {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -30px) scale(1.2); }
            66% { transform: translate(-30px, 20px) scale(0.85); }
        }
        
        /* ===== SERVICE CARDS ===== */
        .service-card {
            background: white;
            border-radius: 20px; padding: 32px;
            border: 1px solid #f1f5f9;
            transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative; overflow: hidden;
            height: 100%; display: flex; flex-direction: column;
            z-index: 1;
        }
        .service-card::before {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #f97316, #34d399, #2563eb);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.5s ease;
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .service-card:hover::before { transform: scaleX(1); }
        .service-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px -15px rgba(37,99,235,0.2); border-color: #cbd5e1; }
        
        .card-icon-wrapper {
            width: 58px; height: 58px;
            background: linear-gradient(135deg, #eff6ff, #f5f3ff);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #2563eb;
            transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin-bottom: 20px; position: relative; z-index: 1;
        }
        .service-card:hover .card-icon-wrapper {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white; transform: scale(1.1) rotate(-8deg);
            box-shadow: 0 15px 35px rgba(37,99,235,0.35);
        }
        
        .feature-item { display: flex; align-items: center; gap: 10px; padding: 7px 0; font-size: 14px; color: #64748b; transition: all 0.3s ease; }
        .feature-item i { color: #10b981; font-size: 13px; transition: all 0.3s ease; }
        .service-card:hover .feature-item { color: #475569; }
        .service-card:hover .feature-item i { transform: scale(1.3) rotate(10deg); }
        
        .btn-order {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px; background: #eff6ff; color: #2563eb;
            font-weight: 600; font-size: 14px; border-radius: 12px;
            transition: all 0.4s ease; text-decoration: none;
        }
        .btn-order:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; box-shadow: 0 10px 30px rgba(37,99,235,0.35); transform: translateY(-3px); }
        .btn-order i { transition: transform 0.3s ease; }
        .btn-order:hover i { transform: translateX(5px); }
        
        /* ===== STEP CARDS ===== */
        .step-card {
            background: white; border-radius: 20px; padding: 36px 28px;
            border: 1px solid #f1f5f9; text-align: center;
            transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative; overflow: hidden;
        }
        .step-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #2563eb, #7c3aed); transform: scaleX(0); transition: transform 0.4s ease; }
        .step-card:hover::after { transform: scaleX(1); }
        .step-card:hover { transform: translateY(-8px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); border-color: #bfdbfe; }
        .step-number-badge { width: 46px; height: 46px; background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; margin: 0 auto 20px; transition: all 0.4s ease; }
        .step-card:hover .step-number-badge { transform: scale(1.2) rotate(8deg); box-shadow: 0 12px 30px rgba(37,99,235,0.4); }
        
        /* ===== CTA ===== */
        .cta-section { background: linear-gradient(135deg, #1e40af 0%, #5b21b6 50%, #c2410c 100%); background-size: 300% 300%; animation: ctaGradient 10s ease infinite; position: relative; overflow: hidden; }
        @keyframes ctaGradient { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
        
        .navbar-glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .text-gradient { background: linear-gradient(135deg, #2563eb, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        
        @media (max-width: 1023px) { .hero-icons-container { width: 100%; opacity: 0.6; } .hero-floating-icon { opacity: 0.08; } }
    </style>
</head>
<body class="font-inter antialiased text-gray-900 bg-white">

    <!-- NAVIGATION -->
    <nav class="fixed top-0 left-0 right-0 z-50 navbar-glass transition-shadow duration-300" id="mainNav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-18">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg shadow-md group-hover:shadow-lg transition-all duration-300 group-hover:scale-105"><i class="fas fa-cubes"></i></div>
                    <span class="text-xl lg:text-2xl font-poppins font-bold"><span class="text-blue-600">E-Find</span><span class="text-orange-500"> & Soft Solutions</span></span>
                </a>
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="index.php" class="px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-50 rounded-xl">Home</a>
                    <div class="relative group">
                        <button class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-xl transition-all flex items-center">Services <i class="fas fa-chevron-down ml-1 text-xs group-hover:rotate-180 transition-transform"></i></button>
                        <div class="absolute top-full left-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2">
                            <a href="services.php?category=engraving" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-trophy w-5 mr-3 text-amber-500"></i>Engraving</a>
                            <a href="services.php?category=embroidery" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-tshirt w-5 mr-3 text-purple-500"></i>Embroidery</a>
                            <a href="services.php?category=tracking" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-map-marker-alt w-5 mr-3 text-red-500"></i>Tracking</a>
                            <a href="services.php?category=branding" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-palette w-5 mr-3 text-green-500"></i>Branding</a>
                            <a href="services.php?category=printing" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-print w-5 mr-3 text-cyan-500"></i>Printing</a>
                        </div>
                    </div>
                    <a href="track.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-xl transition-all">Track Order</a>
                    <a href="contact.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-xl transition-all">Contact</a>
                </div>
                <div class="hidden lg:flex items-center space-x-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-xl transition-all border border-gray-200">
                                <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                                <span class="max-w-[120px] truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <i class="fas fa-chevron-down text-xs group-hover:rotate-180 transition-transform"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2">
                                <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg"><i class="fas fa-user w-5 mr-3 text-gray-400"></i>Profile</a>
                                <a href="my-orders.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg"><i class="fas fa-shopping-bag w-5 mr-3 text-gray-400"></i>My Orders</a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?><hr class="my-1"><a href="admin/dashboard.php" class="flex items-center px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 mx-2 rounded-lg font-medium"><i class="fas fa-tachometer-alt w-5 mr-3"></i>Admin Panel</a><?php endif; ?>
                                <?php if ($_SESSION['user_role'] === 'delivery'): ?><hr class="my-1"><a href="rider/dashboard.php" class="flex items-center px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 mx-2 rounded-lg font-medium"><i class="fas fa-motorcycle w-5 mr-3"></i>My Deliveries</a><?php endif; ?>
                                <hr class="my-1"><a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 mx-2 rounded-lg"><i class="fas fa-sign-out-alt w-5 mr-3"></i>Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-5 py-2.5 text-sm font-semibold text-gray-700 border-2 border-gray-300 rounded-xl hover:border-blue-600 transition-all">Sign In</a>
                        <a href="register.php" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl hover:shadow-lg transition-all">Get Started</a>
                    <?php endif; ?>
                </div>
                <button class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-xl" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')"><i class="fas fa-bars text-xl"></i></button>
            </div>
            <div id="mobileMenu" class="lg:hidden hidden pb-4 space-y-1">
                <a href="index.php" class="block px-4 py-3 text-blue-600 bg-blue-50 rounded-xl font-medium">Home</a>
                <a href="services.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl">Services</a>
                <a href="track.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl">Track Order</a>
                <a href="contact.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl">Contact</a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-section pt-20 lg:pt-24 pb-16 lg:pb-20">
        <div class="hero-icons-container">
            <div class="hero-floating-icon" style="top:5%;right:8%;font-size:90px;"><i class="fas fa-trophy"></i></div>
            <div class="hero-floating-icon horizontal" style="top:22%;right:25%;font-size:80px;"><i class="fas fa-tshirt"></i></div>
            <div class="hero-floating-icon diagonal" style="top:40%;right:5%;font-size:85px;"><i class="fas fa-map-marker-alt"></i></div>
            <div class="hero-floating-icon" style="top:55%;right:30%;font-size:75px;"><i class="fas fa-palette"></i></div>
            <div class="hero-floating-icon horizontal" style="top:70%;right:10%;font-size:70px;"><i class="fas fa-paint-brush"></i></div>
            <div class="hero-floating-icon diagonal" style="top:85%;right:28%;font-size:65px;"><i class="fas fa-print"></i></div>
            <div class="hero-floating-icon" style="top:15%;right:40%;font-size:60px;"><i class="fas fa-cog"></i></div>
            <div class="hero-floating-icon horizontal" style="top:48%;right:20%;font-size:72px;"><i class="fas fa-star"></i></div>
            <div class="hero-floating-icon diagonal" style="top:65%;right:38%;font-size:55px;"><i class="fas fa-gem"></i></div>
            <div class="hero-floating-icon" style="top:32%;right:42%;font-size:68px;"><i class="fas fa-medal"></i></div>
            <div class="hero-floating-icon horizontal" style="top:78%;right:20%;font-size:58px;"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="hero-floating-icon diagonal" style="top:10%;right:20%;font-size:78px;"><i class="fas fa-layer-group"></i></div>
            <div class="hero-particle" style="top:18%;right:15%;width:10px;height:10px;animation-delay:0s;"></div>
            <div class="hero-particle" style="top:38%;right:35%;width:8px;height:8px;animation-delay:1.5s;"></div>
            <div class="hero-particle" style="top:58%;right:12%;width:12px;height:12px;animation-delay:3s;"></div>
            <div class="hero-particle" style="top:75%;right:32%;width:7px;height:7px;animation-delay:2s;"></div>
            <div class="hero-particle" style="top:25%;right:45%;width:9px;height:9px;animation-delay:4s;"></div>
            <div class="hero-particle" style="top:50%;right:40%;width:6px;height:6px;animation-delay:5s;"></div>
            <div class="hero-glow" style="top:15%;right:10%;width:350px;height:350px;background:rgba(37,99,235,0.15);"></div>
            <div class="hero-glow" style="bottom:10%;right:25%;width:280px;height:280px;background:rgba(124,58,237,0.12);animation-delay:4s;"></div>
            <div class="hero-glow" style="top:45%;right:35%;width:220px;height:220px;background:rgba(249,115,22,0.08);animation-delay:2s;"></div>
        </div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-poppins font-extrabold text-white mb-5 leading-tight" data-aos="fade-up">
                    Transform Ideas Into
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-purple-400 to-orange-400">Reality</span>
                </h1>
                <p class="text-lg text-white/60 mb-8 leading-relaxed" data-aos="fade-up" data-aos-delay="100">
                    Professional engraving, embroidery, tracking, branding & printing services — crafted with precision, delivered with care.
                </p>
                <div class="flex flex-col sm:flex-row gap-3" data-aos="fade-up" data-aos-delay="200">
                    <a href="services.php" class="px-7 py-3.5 bg-white text-gray-900 font-semibold rounded-xl hover:bg-gray-100 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 inline-flex items-center justify-center">
                        <i class="fas fa-compass mr-2"></i> Explore Services
                    </a>
                    <a href="track.php" class="px-7 py-3.5 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-xl border border-white/30 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-0.5 inline-flex items-center justify-center">
                        <i class="fas fa-map-marker-alt mr-2"></i> Track Your Order
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES SECTION - With Original Animated Background -->
    <section class="services-section py-20 lg:py-24">
        <!-- Animated background icons -->
        <div class="services-floating-icon" style="top:8%;left:3%;font-size:55px;color:#2563eb;"><i class="fas fa-trophy"></i></div>
        <div class="services-floating-icon alt-animation" style="top:15%;right:5%;font-size:48px;color:#7c3aed;"><i class="fas fa-tshirt"></i></div>
        <div class="services-floating-icon horizontal-animation" style="top:40%;left:2%;font-size:42px;color:#f97316;"><i class="fas fa-map-marker-alt"></i></div>
        <div class="services-floating-icon" style="top:55%;right:3%;font-size:52px;color:#10b981;"><i class="fas fa-palette"></i></div>
        <div class="services-floating-icon alt-animation" style="top:70%;left:6%;font-size:38px;color:#8b5cf6;"><i class="fas fa-paint-brush"></i></div>
        <div class="services-floating-icon horizontal-animation" style="top:35%;right:2%;font-size:50px;color:#ec4899;"><i class="fas fa-print"></i></div>
        <div class="services-floating-icon" style="top:25%;left:50%;font-size:36px;color:#06b6d4;"><i class="fas fa-cog"></i></div>
        <div class="services-floating-icon alt-animation" style="top:80%;right:15%;font-size:44px;color:#6366f1;"><i class="fas fa-star"></i></div>
        <div class="services-floating-icon horizontal-animation" style="top:60%;left:45%;font-size:40px;color:#14b8a6;"><i class="fas fa-gem"></i></div>
        <div class="services-floating-icon" style="top:10%;left:35%;font-size:34px;color:#f59e0b;"><i class="fas fa-wand-magic-sparkles"></i></div>
        <div class="services-floating-icon alt-animation" style="top:50%;right:10%;font-size:46px;color:#3b82f6;"><i class="fas fa-layer-group"></i></div>
        <div class="services-floating-icon horizontal-animation" style="top:85%;left:30%;font-size:38px;color:#ef4444;"><i class="fas fa-medal"></i></div>
        
        <div class="services-accent-dot" style="top:20%;left:10%;width:6px;height:6px;background:#2563eb;animation-delay:0s;"></div>
        <div class="services-accent-dot" style="top:30%;right:12%;width:8px;height:8px;background:#7c3aed;animation-delay:1.5s;"></div>
        <div class="services-accent-dot" style="top:65%;left:20%;width:5px;height:5px;background:#f97316;animation-delay:3s;"></div>
        <div class="services-accent-dot" style="top:75%;right:8%;width:7px;height:7px;background:#10b981;animation-delay:2s;"></div>
        <div class="services-accent-dot" style="top:45%;left:55%;width:9px;height:9px;background:#ec4899;animation-delay:4s;"></div>
        
        <div class="services-accent-shape" style="top:-5%;right:-5%;width:350px;height:350px;background:rgba(37,99,235,0.06);"></div>
        <div class="services-accent-shape" style="bottom:-8%;left:-3%;width:300px;height:300px;background:rgba(249,115,22,0.05);animation-delay:5s;"></div>
        <div class="services-accent-shape" style="top:50%;left:50%;width:250px;height:250px;background:rgba(124,58,237,0.04);animation-delay:8s;"></div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14" data-aos="fade-up">
                <span class="inline-block px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-4">What We Offer</span>
                <h2 class="text-3xl lg:text-5xl font-poppins font-extrabold text-gray-900 mb-4">Our <span class="text-gradient">Services</span></h2>
                <p class="text-gray-500 max-w-xl mx-auto">Comprehensive professional services to elevate your brand and bring your vision to life.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <?php $index = 0; foreach ($featuredServices as $service): $iconClass = $serviceIcons[$service['category']] ?? 'fa-cog'; ?>
                <div data-aos="fade-up" data-aos-delay="<?php echo $index * 80; ?>">
                    <div class="service-card group">
                        <div class="card-icon-wrapper"><i class="fas <?php echo $iconClass; ?>"></i></div>
                        <h3 class="text-lg font-poppins font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p class="text-gray-500 text-sm mb-5 flex-grow leading-relaxed"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php $features = json_decode($service['features'], true); ?>
                        <?php if ($features): ?><ul class="space-y-2 mb-6"><?php foreach (array_slice($features, 0, 3) as $f): ?><li class="feature-item"><i class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($f); ?></span></li><?php endforeach; ?></ul><?php endif; ?>
                        <div class="flex items-center justify-between pt-5 border-t border-gray-100">
                            <span class="font-poppins font-bold text-blue-600 text-sm">From UGX <?php echo number_format($service['base_price']); ?></span>
                            <a href="order.php?service=<?php echo $service['slug']; ?>" class="btn-order">Order <i class="fas fa-arrow-right text-xs"></i></a>
                        </div>
                    </div>
                </div>
                <?php $index++; endforeach; ?>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="py-20 lg:py-24 bg-gray-50/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14" data-aos="fade-up">
                <span class="inline-block px-4 py-1.5 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-4">Simple Process</span>
                <h2 class="text-3xl lg:text-5xl font-poppins font-extrabold text-gray-900 mb-4">How It <span class="text-gradient">Works</span></h2>
                <p class="text-gray-500 max-w-xl mx-auto">Four simple steps from order to delivery.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php $steps = [['num'=>'01','icon'=>'fa-magnifying-glass','title'=>'Choose Service','desc'=>'Browse and select the perfect service for your needs.'],['num'=>'02','icon'=>'fa-sliders','title'=>'Customize','desc'=>'Add specifications and upload your design files.'],['num'=>'03','icon'=>'fa-credit-card','title'=>'Pay Securely','desc'=>'Pay via Mobile Money or Cash on Delivery.'],['num'=>'04','icon'=>'fa-truck-fast','title'=>'Track & Receive','desc'=>'Track in real-time and receive at your door.']]; ?>
                <?php foreach ($steps as $i => $step): ?>
                <div data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                    <div class="step-card h-full">
                        <div class="step-number-badge"><?php echo $step['num']; ?></div>
                        <div class="text-3xl mb-4 text-blue-600"><i class="fas <?php echo $step['icon']; ?>"></i></div>
                        <h3 class="text-lg font-poppins font-bold text-gray-900 mb-2"><?php echo $step['title']; ?></h3>
                        <p class="text-gray-500 text-sm leading-relaxed"><?php echo $step['desc']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section py-16 lg:py-20">
        <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-4xl font-poppins font-extrabold text-white mb-4" data-aos="fade-up">Ready to Get Started?</h2>
            <p class="text-white/70 text-lg mb-8" data-aos="fade-up" data-aos-delay="100">Join our satisfied customers and experience premium custom services today.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center" data-aos="fade-up" data-aos-delay="200">
                <a href="register.php" class="px-7 py-3.5 bg-white text-blue-700 font-semibold rounded-xl hover:bg-gray-100 shadow-lg transition-all"><i class="fas fa-user-plus mr-2"></i> Create Free Account</a>
                <a href="services.php" class="px-7 py-3.5 border-2 border-white/40 text-white font-semibold rounded-xl hover:bg-white/10 transition-all"><i class="fas fa-cogs mr-2"></i> View All Services</a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-gray-900 text-gray-400 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                <div class="col-span-2 lg:col-span-1"><div class="flex items-center space-x-3 mb-4"><div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-orange-500 rounded-lg flex items-center justify-center text-white"><i class="fas fa-cubes"></i></div><span class="text-xl font-poppins font-bold text-white">E-Find & Soft Solutions</span></div><p class="text-gray-500 text-sm">Professional custom services & tracking platform.</p></div>
                <div><h4 class="text-white font-semibold text-sm mb-4">Quick Links</h4><ul class="space-y-2 text-sm"><li><a href="index.php" class="hover:text-white">Home</a></li><li><a href="services.php" class="hover:text-white">Services</a></li><li><a href="track.php" class="hover:text-white">Track Order</a></li><li><a href="contact.php" class="hover:text-white">Contact</a></li></ul></div>
                <div><h4 class="text-white font-semibold text-sm mb-4">Services</h4><ul class="space-y-2 text-sm"><li><a href="services.php?category=engraving" class="hover:text-white">Engraving</a></li><li><a href="services.php?category=embroidery" class="hover:text-white">Embroidery</a></li><li><a href="services.php?category=tracking" class="hover:text-white">Tracking</a></li><li><a href="services.php?category=branding" class="hover:text-white">Branding</a></li></ul></div>
                <div><h4 class="text-white font-semibold text-sm mb-4">Contact</h4><ul class="space-y-2 text-sm"><li>123 Business Park, Kampala</li><li>+256 700 000000</li><li>info@efind.com</li></ul></div>
            </div>
            <div class="border-t border-gray-800 pt-6 text-center"><p class="text-gray-600 text-xs">&copy; <?php echo date('Y'); ?> E-Find and Soft Solutions. All rights reserved.</p></div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true, easing: 'ease-out-cubic' });
        window.addEventListener('scroll', function() { document.getElementById('mainNav').classList.toggle('shadow-lg', window.scrollY > 20); });
    </script>
</body>
</html>