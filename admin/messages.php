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

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    header('Location: messages.php?marked=1');
    exit;
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    header('Location: messages.php?all_read=1');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    header('Location: messages.php?deleted=1');
    exit;
}

// Fetch all messages
$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$totalMessages = count($messages);
$unreadCount = 0;
$readCount = 0;
foreach ($messages as $m) {
    if ($m['is_read']) $readCount++;
    else $unreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: { 50:'#eff6ff',100:'#dbeafe',600:'#2563eb',700:'#1d4ed8' } },
                    fontFamily: { 'poppins':['Poppins','sans-serif'], 'inter':['Inter','sans-serif'] },
                    animation: { 'fade-in':'fadeIn 0.5s ease-out','slide-up':'slideUp 0.5s ease-out','slide-down':'slideDown 0.4s ease-out','scale-in':'scaleIn 0.3s ease-out' },
                    keyframes: {
                        fadeIn:{'0%':{opacity:'0'},'100%':{opacity:'1'}},
                        slideUp:{'0%':{transform:'translateY(20px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},
                        slideDown:{'0%':{transform:'translateY(-15px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},
                        scaleIn:{'0%':{transform:'scale(0.95)',opacity:'0'},'100%':{transform:'scale(1)',opacity:'1'}},
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
        .sidebar-link { transition: all 0.3s ease; position: relative; }
        .sidebar-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 0; background: #2563eb; border-radius: 0 4px 4px 0; transition: height 0.3s ease; }
        .sidebar-link:hover::before, .sidebar-link.active::before { height: 60%; }
        .sidebar-link.active { background: rgba(37,99,235,0.15); color: #60a5fa; }
        
        .stat-card {
            background: white; border-radius: 1.25rem; padding: 1.5rem;
            border: 1px solid #f1f5f9; transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #06b6d4);
            background-size: 200% 100%; animation: shimmer 3s linear infinite;
            transform: scaleX(0); transform-origin: left; transition: transform 0.5s ease;
        }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 25px 50px -12px rgba(37,99,235,0.15); }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; transition: all 0.4s ease; }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        
        .message-card {
            background: white; border-radius: 1rem; border: 1px solid #f1f5f9;
            transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
        }
        .message-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .message-card.unread { border-left: 4px solid #3b82f6; background: #f8faff; }
        .message-card.unread:hover { border-left-color: #1d4ed8; }
        
        .btn-action { transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94); }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        @media (max-width: 1024px) { .sidebar { width: 100%; position: relative; height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body class="font-inter antialiased bg-slate-50">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="sidebar fixed top-0 left-0 bottom-0 bg-slate-900 text-white z-50 flex flex-col overflow-y-auto">
            <div class="p-6 border-b border-slate-700/50">
                <a href="../index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105"><i class="fas fa-cubes"></i></div>
                    <div><span class="font-poppins font-bold text-lg">E-Find Admin</span><p class="text-xs text-slate-400">Management Panel</p></div>
                </a>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span></a>
                <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-shopping-cart w-5"></i><span>Orders</span></a>
                <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-users w-5"></i><span>Users</span></a>
                <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
                <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
                <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-credit-card w-5"></i><span>Payments</span></a>
                <a href="messages.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300 hover:text-white"><i class="fas fa-envelope w-5"></i><span>Messages</span><?php if ($unreadCount > 0): ?><span class="ml-auto bg-blue-500 text-white text-xs rounded-full px-2 py-0.5 animate-pulse"><?php echo $unreadCount; ?></span><?php endif; ?></a>
                <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-chart-bar w-5"></i><span>Reports</span></a>
                <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span></a>
                <hr class="border-slate-700/50 my-4">
                <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-home w-5"></i><span>Back to Site</span></a>
                <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10"><i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>
            </nav>
            <div class="p-4 border-t border-slate-700/50">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold"><?php echo strtoupper(substr(Session::get('user_name'), 0, 1)); ?></div>
                    <div><p class="text-sm font-medium text-white"><?php echo htmlspecialchars(Session::get('user_name')); ?></p><p class="text-xs text-slate-400">Administrator</p></div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content flex-1 p-6 lg:p-8">
            
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4 animate-slide-down">
                <div>
                    <h1 class="text-3xl font-poppins font-extrabold text-slate-900">
                        Customer <span class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Messages</span>
                    </h1>
                    <p class="text-slate-500 mt-1">Total: <strong><?php echo $totalMessages; ?></strong> messages · <span class="text-blue-600"><?php echo $unreadCount; ?> unread</span> · <span class="text-slate-400"><?php echo $readCount; ?> read</span></p>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($unreadCount > 0): ?>
                    <a href="?mark_all_read=1" class="btn-action px-4 py-2.5 bg-blue-50 text-blue-700 font-semibold rounded-xl hover:bg-blue-100 transition-all text-sm">
                        <i class="fas fa-check-double mr-1"></i> Mark All Read
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_GET['marked'])): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down"><i class="fas fa-check-circle mr-2 text-lg"></i> Message marked as read.</div>
            <?php endif; ?>
            <?php if (isset($_GET['all_read'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down"><i class="fas fa-check-double mr-2 text-lg"></i> All messages marked as read.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down"><i class="fas fa-trash-alt mr-2 text-lg"></i> Message deleted successfully.</div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="stat-card animate-scale-in"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total</p><p class="text-3xl font-bold text-slate-900 mt-2"><?php echo $totalMessages; ?></p></div><div class="stat-icon bg-slate-100 text-slate-600"><i class="fas fa-envelope"></i></div></div></div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.1s;"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Unread</p><p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $unreadCount; ?></p></div><div class="stat-icon bg-blue-100 text-blue-600"><i class="fas fa-envelope-open"></i></div></div></div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.2s;"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Read</p><p class="text-3xl font-bold text-green-600 mt-2"><?php echo $readCount; ?></p></div><div class="stat-icon bg-green-100 text-green-600"><i class="fas fa-check-circle"></i></div></div></div>
            </div>

            <!-- Messages List -->
            <div class="space-y-4 animate-scale-in" style="animation-delay:0.3s;">
                <?php if (empty($messages)): ?>
                <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-inbox text-4xl text-slate-300"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-slate-700 mb-2">No Messages Yet</h4>
                    <p class="text-slate-400">Messages from the contact form will appear here.</p>
                </div>
                <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                <div class="message-card <?php echo !$msg['is_read'] ? 'unread' : ''; ?> p-6">
                    <div class="flex flex-col lg:flex-row justify-between gap-4">
                        <div class="flex-1">
                            <!-- Sender Info -->
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm <?php echo $msg['is_read'] ? 'bg-slate-400' : 'bg-gradient-to-br from-blue-500 to-blue-700'; ?>">
                                    <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900"><?php echo htmlspecialchars($msg['name']); ?></h4>
                                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($msg['email']); ?></p>
                                </div>
                                <?php if (!$msg['is_read']): ?>
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">New</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Subject -->
                            <?php if ($msg['subject']): ?>
                            <div class="mb-2">
                                <span class="inline-flex items-center px-3 py-1 bg-purple-50 text-purple-700 rounded-lg text-xs font-semibold">
                                    <i class="fas fa-tag mr-1.5"></i><?php echo htmlspecialchars($msg['subject']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Message -->
                            <div class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                            
                            <!-- Timestamp -->
                            <p class="text-xs text-slate-400 mt-3 flex items-center">
                                <i class="far fa-clock mr-1.5"></i>
                                <?php echo date('F d, Y \a\t h:i A', strtotime($msg['created_at'])); ?>
                            </p>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex lg:flex-col gap-2 flex-shrink-0">
                            <?php if (!$msg['is_read']): ?>
                            <a href="?mark_read=1&id=<?php echo $msg['id']; ?>" class="btn-action px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-xs font-semibold hover:bg-blue-100 text-center">
                                <i class="fas fa-check mr-1"></i> Mark Read
                            </a>
                            <?php endif; ?>
                            <a href="?delete=1&id=<?php echo $msg['id']; ?>" class="btn-action px-4 py-2 bg-red-50 text-red-600 rounded-xl text-xs font-semibold hover:bg-red-100 text-center" onclick="return confirm('Delete this message permanently?')">
                                <i class="fas fa-trash mr-1"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>