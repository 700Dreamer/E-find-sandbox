<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';
require_once 'includes/Service.php';

Session::init();
$serviceManager = new ServiceManager();

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$services = $serviceManager->getAllServices($category, $search);
$categories = $serviceManager->getCategories();

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
    <title>Our Services - <?php echo APP_NAME; ?></title>
    
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
</head>
<body class="font-inter antialiased bg-gray-50">

    <!-- NAVIGATION -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm transition-all duration-300" id="mainNav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg shadow-lg">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <span class="text-xl lg:text-2xl font-poppins font-bold">
                        <span class="text-blue-600">E-Find</span>
                        <span class="text-orange-500"> & Soft Solutions</span>
                    </span>
                </a>
                
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all">Home</a>
                    
                    <div class="relative group">
                        <button class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg transition-all flex items-center">
                            <i class="fas fa-cogs mr-1"></i> Services <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="p-2 grid gap-1">
                                <a href="services.php?category=engraving" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600"><i class="fas fa-trophy w-6"></i><span class="ml-3 text-sm font-medium">Engraving</span></a>
                                <a href="services.php?category=embroidery" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600"><i class="fas fa-tshirt w-6"></i><span class="ml-3 text-sm font-medium">Embroidery</span></a>
                                <a href="services.php?category=tracking" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600"><i class="fas fa-map-marker-alt w-6"></i><span class="ml-3 text-sm font-medium">Tracking</span></a>
                                <a href="services.php?category=calligraphy" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600"><i class="fas fa-paint-brush w-6"></i><span class="ml-3 text-sm font-medium">Calligraphy</span></a>
                                <a href="services.php?category=branding" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600"><i class="fas fa-palette w-6"></i><span class="ml-3 text-sm font-medium">Branding</span></a>
                                <a href="services.php?category=printing" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600"><i class="fas fa-print w-6"></i><span class="ml-3 text-sm font-medium">Printing</span></a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="track.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all">Track Order</a>
                    <a href="contact.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all">Contact</a>
                </div>
                
                <div class="hidden lg:flex items-center space-x-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition-all">
                                <i class="fas fa-user-circle text-lg"></i>
                                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2">
                                <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg"><i class="fas fa-user w-5 mr-3"></i> Profile</a>
                                <a href="my-orders.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg"><i class="fas fa-shopping-bag w-5 mr-3"></i> My Orders</a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <hr class="my-1"><a href="admin/dashboard.php" class="flex items-center px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-tachometer-alt w-5 mr-3"></i> Admin Panel</a>
                                <?php endif; ?>
                                <hr class="my-1"><a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 mx-2 rounded-lg"><i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-5 py-2.5 text-sm font-semibold text-gray-700 border-2 border-gray-300 rounded-xl hover:border-blue-600 hover:text-blue-600 transition-all">Sign In</a>
                        <a href="register.php" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:shadow-lg transition-all">Get Started</a>
                    <?php endif; ?>
                </div>
                
                <button class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            <div id="mobileMenu" class="lg:hidden hidden pb-4 space-y-1">
                <a href="index.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Home</a>
                <a href="services.php" class="block px-4 py-3 text-blue-600 bg-blue-50 rounded-lg font-medium">Services</a>
                <a href="track.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Track Order</a>
                <a href="contact.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Contact</a>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <section class="pt-24 lg:pt-28 pb-12 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center" data-aos="fade-up">
                <h1 class="text-4xl lg:text-5xl font-poppins font-extrabold mb-4">Our Services</h1>
                <p class="text-white/80 text-lg mb-8">Discover our comprehensive range of professional services designed to meet your needs</p>
                <form action="services.php" method="GET" class="max-w-md mx-auto">
                    <div class="relative">
                        <input type="text" name="search" class="w-full px-5 py-3.5 rounded-xl text-gray-900 shadow-lg focus:ring-4 focus:ring-white/30 outline-none" placeholder="Search services..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- CATEGORY FILTER -->
    <section class="py-6 bg-white border-b sticky top-16 lg:top-20 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-2">
                <a href="services.php" class="px-5 py-2.5 rounded-full text-sm font-semibold transition-all <?php echo !$category ? 'bg-blue-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All Services</a>
                <?php foreach ($categories as $cat): ?>
                <a href="services.php?category=<?php echo urlencode($cat); ?>" class="px-5 py-2.5 rounded-full text-sm font-semibold transition-all <?php echo $category === $cat ? 'bg-blue-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                    <i class="fas <?php echo $serviceIcons[$cat] ?? 'fa-cog'; ?> mr-1"></i> <?php echo ucfirst($cat); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SERVICES GRID -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (empty($services)): ?>
            <div class="text-center py-16" data-aos="fade-up">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-poppins font-bold text-gray-900 mb-2">No services found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your search or filter criteria</p>
                <a href="services.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    <i class="fas fa-list mr-2"></i> View All Services
                </a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php $index = 0; foreach ($services as $service): 
                    $iconClass = $serviceIcons[$service['category']] ?? 'fa-cog';
                ?>
                <div data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 h-full flex flex-col border border-gray-100 group">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center text-white text-2xl mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas <?php echo $iconClass; ?>"></i>
                        </div>
                        <h3 class="text-xl font-poppins font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p class="text-gray-600 mb-6 flex-grow"><?php echo htmlspecialchars($service['description']); ?></p>
                        
                        <?php $features = json_decode($service['features'], true); ?>
                        <?php if ($features): ?>
                        <ul class="space-y-3 mb-6">
                            <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                            <li class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <?php echo htmlspecialchars($feature); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <span class="text-lg font-poppins font-bold text-blue-600">From UGX <?php echo number_format($service['base_price']); ?></span>
                            <a href="order.php?service=<?php echo $service['slug']; ?>" class="inline-flex items-center px-5 py-2.5 bg-blue-50 text-blue-600 font-semibold rounded-lg hover:bg-blue-600 hover:text-white transition-all duration-300 text-sm">
                                Order Now <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php $index++; endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-orange-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-poppins font-bold text-white mb-4" data-aos="fade-up">Need a Custom Service?</h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">Can't find what you're looking for? Contact us for custom solutions tailored to your needs.</p>
            <a href="contact.php" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-gray-100 shadow-xl transition-all duration-300" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-envelope mr-2"></i> Contact Us
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-gray-900 text-gray-400 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg"><i class="fas fa-cubes"></i></div>
                        <span class="text-2xl font-poppins font-bold text-white">E-Find & Soft Solutions</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed mb-6">Your one-stop platform for custom services, tracking solutions, and professional branding.</p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-poppins font-semibold text-lg mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="services.php" class="hover:text-white transition-colors">Services</a></li>
                        <li><a href="track.php" class="hover:text-white transition-colors">Track Order</a></li>
                        <li><a href="contact.php" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-poppins font-semibold text-lg mb-6">Our Services</h4>
                    <ul class="space-y-3">
                        <li><a href="services.php?category=engraving" class="hover:text-white transition-colors"><i class="fas fa-trophy mr-2 text-blue-400"></i>Engraving</a></li>
                        <li><a href="services.php?category=embroidery" class="hover:text-white transition-colors"><i class="fas fa-tshirt mr-2 text-blue-400"></i>Embroidery</a></li>
                        <li><a href="services.php?category=tracking" class="hover:text-white transition-colors"><i class="fas fa-map-marker-alt mr-2 text-blue-400"></i>Tracking</a></li>
                        <li><a href="services.php?category=branding" class="hover:text-white transition-colors"><i class="fas fa-palette mr-2 text-blue-400"></i>Branding</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-poppins font-semibold text-lg mb-6">Contact Info</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start space-x-3"><i class="fas fa-map-marker-alt text-blue-400 mt-1"></i><span>123 Business Park, Kampala, Uganda</span></li>
                        <li class="flex items-center space-x-3"><i class="fas fa-phone text-blue-400"></i><span>+256 700 000000</span></li>
                        <li class="flex items-center space-x-3"><i class="fas fa-envelope text-blue-400"></i><span>info@efind.com</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 text-center">
                <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> E-Find and Soft Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('mainNav');
            if (window.scrollY > 50) { nav.classList.add('shadow-lg'); }
            else { nav.classList.remove('shadow-lg'); }
        });
    </script>
</body>
</html>