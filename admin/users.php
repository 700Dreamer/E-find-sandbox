<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::isAdmin()) {
    header('Location: ../login.php');
    exit;  
}

$database = new Database();
$db = $database->getConnection();

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);
    $success = "User role updated successfully!";
}

// Handle user activation/deactivation
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$userId]);
    header('Location: users.php?toggled=1');
    exit;
}

// Handle user deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    // Don't allow deleting yourself
    if ($userId != Session::get('user_id')) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        header('Location: users.php?deleted=1');
        exit;
    }
}

// Fetch all users with stats
$users = $db->query("
    SELECT u.*, 
           COUNT(o.id) as total_orders,
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$totalUsers = count($users);
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$riderCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'delivery'")->fetchColumn();
$customerCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' }
                    },
                    fontFamily: { 'poppins':['Poppins','sans-serif'], 'inter':['Inter','sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-down': 'slideDown 0.4s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        slideDown: { '0%': { transform: 'translateY(-15px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        scaleIn: { '0%': { transform: 'scale(0.95)', opacity: '0' }, '100%': { transform: 'scale(1)', opacity: '1' } },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; }
        .sidebar { width: var(--sidebar-width); }
        .main-content { margin-left: var(--sidebar-width); }
        
        .sidebar-link {
            transition: all 0.3s ease;
            position: relative;
        }
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 0;
            background: #2563eb;
            border-radius: 0 4px 4px 0;
            transition: height 0.3s ease;
        }
        .sidebar-link:hover::before,
        .sidebar-link.active::before { height: 60%; }
        .sidebar-link.active { background: rgba(37,99,235,0.15); color: #60a5fa; }
        
        .user-row { transition: all 0.3s ease; }
        .user-row:hover { background: linear-gradient(90deg, #eff6ff 0%, transparent 100%); transform: translateX(3px); }
        
        .stat-card {
            background: white;
            border-radius: 1.25rem;
            padding: 1.5rem;
            border: 1px solid #f1f5f9;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #f97316);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 25px 50px -12px rgba(37,99,235,0.15); }
        
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            transition: all 0.4s ease;
        }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        
        .btn-action { transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94); }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .modal-overlay { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { animation: scaleIn 0.3s ease; }
        @keyframes scaleIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
        
        @media (max-width: 1024px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body class="font-inter antialiased bg-slate-50">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="sidebar fixed top-0 left-0 bottom-0 bg-slate-900 text-white z-50 flex flex-col overflow-y-auto">
            <div class="p-6 border-b border-slate-700/50">
                <a href="../index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div>
                        <span class="font-poppins font-bold text-lg">E-Find Admin</span>
                        <p class="text-xs text-slate-400">Management Panel</p>
                    </div>
                </a>
            </div>
            
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
                </a>
                <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-shopping-cart w-5"></i><span>Orders</span>
                </a>
                <a href="users.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300 hover:text-white">
                    <i class="fas fa-users w-5"></i><span>Users</span>
                </a>
                <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-credit-card w-5"></i><span>Payments</span>
                </a>
                <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-envelope w-5"></i><span>Messages</span>
                </a>
                <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-chart-bar w-5"></i><span>Reports</span>
                </a>
                <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span>
                </a>
                
                <hr class="border-slate-700/50 my-4">
                
                <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-home w-5"></i><span>Back to Site</span>
                </a>
                <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10">
                    <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                </a>
            </nav>
            
            <div class="p-4 border-t border-slate-700/50">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        <?php echo strtoupper(substr(Session::get('user_name'), 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars(Session::get('user_name')); ?></p>
                        <p class="text-xs text-slate-400">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content flex-1 p-6 lg:p-8">
            
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 animate-slide-down">
                <div>
                    <h1 class="text-3xl font-poppins font-extrabold text-slate-900">
                        Manage <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Users</span>
                    </h1>
                    <p class="text-slate-500 mt-1">Total: <strong><?php echo $totalUsers; ?></strong> users · <span class="text-green-600"><?php echo $activeUsers; ?> active</span></p>
                </div>
                <button onclick="openAddUserModal()" class="btn-action mt-4 sm:mt-0 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-blue-600/25 transition-all duration-300 text-sm">
                    <i class="fas fa-plus mr-1"></i> Add User
                </button>
            </div>

            <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down">
                <i class="fas fa-check-circle mr-2 text-lg"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['toggled'])): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down">
                <i class="fas fa-info-circle mr-2 text-lg"></i> User status toggled successfully.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down">
                <i class="fas fa-trash-alt mr-2 text-lg"></i> User deleted successfully.
            </div>
            <?php endif; ?>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="stat-card animate-scale-in" style="animation-delay:0s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Users</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2"><?php echo $totalUsers; ?></p>
                        </div>
                        <div class="stat-icon bg-blue-100 text-blue-600"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.1s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Admins</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2"><?php echo $adminCount; ?></p>
                        </div>
                        <div class="stat-icon bg-purple-100 text-purple-600"><i class="fas fa-shield-alt"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.2s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Riders</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2"><?php echo $riderCount; ?></p>
                        </div>
                        <div class="stat-icon bg-amber-100 text-amber-600"><i class="fas fa-motorcycle"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.3s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Customers</p>
                            <p class="text-3xl font-bold text-slate-900 mt-2"><?php echo $customerCount; ?></p>
                        </div>
                        <div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fas fa-user"></i></div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden animate-scale-in">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-5 py-4">User</th>
                                <th class="px-5 py-4">Email</th>
                                <th class="px-5 py-4">Phone</th>
                                <th class="px-5 py-4">Role</th>
                                <th class="px-5 py-4">Orders</th>
                                <th class="px-5 py-4">Total Spent</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($users as $user): 
                                $isSelf = ($user['id'] == Session::get('user_id'));
                            ?>
                            <tr class="user-row <?php echo !$user['is_active'] ? 'opacity-60' : ''; ?>">
                                <!-- User -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md <?php echo $user['role'] === 'admin' ? 'bg-gradient-to-br from-purple-500 to-purple-700' : ($user['role'] === 'delivery' ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'); ?>">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                            <?php if ($isSelf): ?>
                                            <span class="text-xs text-blue-500 font-medium">(You)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Email -->
                                <td class="px-5 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                
                                <!-- Phone -->
                                <td class="px-5 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></td>
                                
                                <!-- Role -->
                                <td class="px-5 py-4">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition-all" onchange="this.form.submit()" <?php echo $isSelf ? 'disabled' : ''; ?>>
                                            <option value="customer" <?php echo $user['role']==='customer'?'selected':''; ?>>Customer</option>
                                            <option value="admin" <?php echo $user['role']==='admin'?'selected':''; ?>>Admin</option>
                                            <option value="delivery" <?php echo $user['role']==='delivery'?'selected':''; ?>>Delivery</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                </td>
                                
                                <!-- Orders -->
                                <td class="px-5 py-4 text-sm">
                                    <span class="font-semibold text-slate-800"><?php echo $user['total_orders']; ?></span>
                                </td>
                                
                                <!-- Total Spent -->
                                <td class="px-5 py-4 text-sm">
                                    <span class="font-semibold text-slate-800">UGX <?php echo number_format($user['total_spent']); ?></span>
                                </td>
                                
                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $user['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo $user['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php if (!$isSelf): ?>
                                        <a href="?toggle=1&id=<?php echo $user['id']; ?>" class="btn-action px-3 py-1.5 rounded-lg text-xs font-semibold transition-all <?php echo $user['is_active'] ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100'; ?>" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas <?php echo $user['is_active'] ? 'fa-ban' : 'fa-check'; ?> mr-1"></i>
                                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="?delete=1&id=<?php echo $user['id']; ?>" class="btn-action px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100" onclick="return confirm('Delete user <?php echo addslashes($user['name']); ?>? This action cannot be undone.')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-3 bg-slate-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs text-slate-500">Total: <strong><?php echo $totalUsers; ?></strong> users</span>
                    <span class="text-xs text-slate-400">Role changes are saved automatically</span>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL -->
    <div id="addUserModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4 modal-overlay">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="px-6 py-4 border-b flex justify-between items-center rounded-t-2xl">
                <h3 class="text-lg font-poppins font-bold text-slate-900"><i class="fas fa-user-plus mr-2 text-blue-600"></i>Add New User</h3>
                <button onclick="closeAddUserModal()" class="text-slate-400 hover:text-slate-600 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="create-user.php" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
                    <input type="text" name="name" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all" placeholder="Enter full name" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all" placeholder="Enter email" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all" placeholder="Create password" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all" placeholder="Enter phone number">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Role</label>
                    <select name="role" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                        <option value="delivery">Delivery</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeAddUserModal()" class="px-5 py-2.5 border border-gray-300 rounded-xl text-slate-700 font-medium hover:bg-gray-50 transition-colors text-sm">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-blue-600/25 transition-all duration-300 text-sm">
                        <i class="fas fa-plus mr-1"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddUserModal() { document.getElementById('addUserModal').classList.remove('hidden'); }
        function closeAddUserModal() { document.getElementById('addUserModal').classList.add('hidden'); }
        document.getElementById('addUserModal').addEventListener('click', function(e) { if (e.target === this) closeAddUserModal(); });
    </script>
</body>
</html>