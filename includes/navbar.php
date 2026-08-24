<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm transition-all duration-300" id="mainNav">
    <div class="container mx-auto px-6">
        <div class="flex items-center justify-between h-20">
            <!-- Logo -->
            <a href="index.php" class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl flex items-center justify-center text-white text-lg">
                    <i class="fas fa-cubes"></i>
                </div>
                <span class="text-2xl font-poppins font-bold">
                    <span class="text-primary-600">E-Find</span>
                    <span class="text-accent-500"> & Soft Solutions</span>
                </span>
            </a>
            
            <!-- Desktop Navigation -->
            <div class="hidden lg:flex items-center space-x-1">
                <a href="index.php" class="px-4 py-2 text-sm font-medium rounded-lg transition-all <?php echo $current_page == 'index.php' ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50'; ?>">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
                
                <div class="relative group">
                    <button class="px-4 py-2 text-sm font-medium rounded-lg transition-all text-gray-700 hover:text-primary-600 hover:bg-gray-50 flex items-center">
                        <i class="fas fa-cogs mr-1"></i> Services
                        <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </button>
                    <div class="absolute top-full left-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="p-2 grid gap-1">
                            <a href="services.php?category=engraving" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-gray-700 hover:text-primary-600 transition-colors">
                                <i class="fas fa-trophy w-6"></i><span class="ml-3 text-sm font-medium">Engraving</span>
                            </a>
                            <a href="services.php?category=embroidery" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-gray-700 hover:text-primary-600 transition-colors">
                                <i class="fas fa-tshirt w-6"></i><span class="ml-3 text-sm font-medium">Embroidery</span>
                            </a>
                            <a href="services.php?category=tracking" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-gray-700 hover:text-primary-600 transition-colors">
                                <i class="fas fa-map-marker-alt w-6"></i><span class="ml-3 text-sm font-medium">Security Tracking</span>
                            </a>
                            <a href="services.php?category=calligraphy" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-gray-700 hover:text-primary-600 transition-colors">
                                <i class="fas fa-paint-brush w-6"></i><span class="ml-3 text-sm font-medium">Calligraphy</span>
                            </a>
                            <a href="services.php?category=branding" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-gray-700 hover:text-primary-600 transition-colors">
                                <i class="fas fa-palette w-6"></i><span class="ml-3 text-sm font-medium">Branding & Design</span>
                            </a>
                            <a href="services.php?category=printing" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-gray-700 hover:text-primary-600 transition-colors">
                                <i class="fas fa-print w-6"></i><span class="ml-3 text-sm font-medium">Printing</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <a href="track.php" class="px-4 py-2 text-sm font-medium rounded-lg transition-all <?php echo $current_page == 'track.php' ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50'; ?>">
                    <i class="fas fa-map-marker-alt mr-1"></i> Track Order
                </a>
                <a href="contact.php" class="px-4 py-2 text-sm font-medium rounded-lg transition-all <?php echo $current_page == 'contact.php' ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50'; ?>">
                    <i class="fas fa-envelope mr-1"></i> Contact
                </a>
            </div>
            
            <!-- Actions -->
            <div class="hidden lg:flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="relative p-2 text-gray-700 hover:text-primary-600 transition-colors" id="notificationBell" onclick="toggleNotifications()">
                            <i class="fas fa-bell text-xl"></i>
                            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notifDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 hidden z-50 max-h-96 overflow-y-auto">
                            <div class="p-4 text-center text-gray-500">Loading...</div>
                        </div>
                    </div>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 hover:text-primary-600 rounded-lg hover:bg-gray-50 transition-all">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="p-2">
                                <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 text-sm">
                                    <i class="fas fa-user w-5 mr-3"></i> Profile
                                </a>
                                <a href="my-orders.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 text-sm">
                                    <i class="fas fa-shopping-bag w-5 mr-3"></i> My Orders
                                </a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <hr class="my-2">
                                <a href="admin/dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-primary-600 text-sm">
                                    <i class="fas fa-tachometer-alt w-5 mr-3"></i> Admin Dashboard
                                </a>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_role'] === 'delivery'): ?>
                                <hr class="my-2">
                                <a href="rider/dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-primary-50 text-primary-600 text-sm">
                                    <i class="fas fa-motorcycle w-5 mr-3"></i> My Deliveries
                                </a>
                                <?php endif; ?>
                                <hr class="my-2">
                                <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-red-50 text-red-600 text-sm">
                                    <i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="px-6 py-2.5 text-sm font-semibold text-gray-700 border-2 border-gray-300 rounded-xl hover:border-primary-600 hover:text-primary-600 transition-all duration-300">
                        <i class="fas fa-sign-in-alt mr-1"></i> Sign In
                    </a>
                    <a href="register.php" class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl hover:shadow-lg hover:shadow-primary-600/25 transition-all duration-300 transform hover:-translate-y-0.5">
                        <i class="fas fa-user-plus mr-1"></i> Get Started
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Button -->
            <button class="lg:hidden p-2 text-gray-700 hover:bg-gray-100 rounded-lg" id="mobileMenuBtn">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Notification system
    const bell = document.getElementById('notificationBell');
    if (bell) {
        function updateNotifications() {
            fetch('api/notifications.php?action=count')
                .then(res => res.json())
                .then(data => {
                    const countSpan = document.getElementById('notifCount');
                    if (data.count > 0) {
                        countSpan.textContent = data.count;
                        countSpan.classList.remove('hidden');
                    } else {
                        countSpan.classList.add('hidden');
                    }
                });
        }
        updateNotifications();
        setInterval(updateNotifications, 30000);
    }
});

function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('hidden');
    
    fetch('api/notifications.php?action=list')
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (data.length === 0) {
                html = '<div class="p-4 text-center text-gray-500">No notifications</div>';
            } else {
                data.forEach(n => {
                    html += `<a href="track.php?order=${n.order_number}" class="block px-4 py-3 hover:bg-gray-50 ${!n.is_read ? 'bg-primary-50' : ''}">
                        <p class="text-sm text-gray-700">${n.message}</p>
                        <small class="text-gray-400">${new Date(n.created_at).toLocaleString()}</small>
                    </a>`;
                });
            }
            dropdown.innerHTML = html;
        });
}
</script>