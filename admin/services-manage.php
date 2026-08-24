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

$success = $error = null;

// Handle Add/Edit Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $serviceId = $_POST['service_id'] ?? null;
    $name = trim($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    $description = trim($_POST['description']);
    $longDescription = trim($_POST['long_description']);
    $category = trim($_POST['category']);
    $basePrice = (float)$_POST['base_price'];
    $icon = trim($_POST['icon']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $features = json_encode(array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))));
    
    // Handle image upload
    $imagePath = $_POST['existing_image'] ?? null;
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/services/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION);
        $filename = $slug . '-' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['service_image']['tmp_name'], $uploadDir . $filename)) {
            $imagePath = 'uploads/services/' . $filename;
        }
    }
    
    try {
        if ($serviceId) {
            // Update
            $stmt = $db->prepare("UPDATE services SET name=?, slug=?, description=?, long_description=?, icon=?, image_path=?, category=?, base_price=?, features=?, is_active=?, is_featured=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $longDescription, $icon, $imagePath, $category, $basePrice, $features, $isActive, $isFeatured, $serviceId]);
            $success = "Service updated successfully!";
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO services (name, slug, description, long_description, icon, image_path, category, base_price, features, is_active, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $slug, $description, $longDescription, $icon, $imagePath, $category, $basePrice, $features, $isActive, $isFeatured]);
            $success = "Service added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    header('Location: services-manage.php?deleted=1');
    exit;
}

// Handle Toggle Status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $stmt = $db->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    header('Location: services-manage.php');
    exit;
}

// Fetch all services
$services = $db->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Edit mode
$editService = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editService = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--sidebar-width:260px}.sidebar{width:var(--sidebar-width)}.main-content{margin-left:var(--sidebar-width)}
        .sidebar-link{transition:all .3s ease;position:relative}.sidebar-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;background:#2563eb;border-radius:0 4px 4px 0;transition:height .3s ease}
        .sidebar-link:hover::before,.sidebar-link.active::before{height:60%}.sidebar-link.active{background:rgba(37,99,235,.15);color:#60a5fa}
        .service-row{transition:all .2s ease}.service-row:hover{background:#f8fafc}
        @media(max-width:1024px){.sidebar{width:100%;position:relative;height:auto}.main-content{margin-left:0}}
    </style>
</head>
<body class="font-inter antialiased bg-slate-50">
<div class="flex min-h-screen">
    
    <!-- SIDEBAR -->
    <aside class="sidebar fixed top-0 left-0 bottom-0 bg-slate-900 text-white z-50 flex flex-col overflow-y-auto">
        <div class="p-6 border-b border-slate-700/50">
            <a href="../index.php" class="flex items-center space-x-3 group"><div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105"><i class="fas fa-cubes"></i></div><div><span class="font-poppins font-bold text-lg">E-Find Admin</span><p class="text-xs text-slate-400">Management Panel</p></div></a>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span></a>
            <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-shopping-cart w-5"></i><span>Orders</span></a>
            <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-users w-5"></i><span>Users</span></a>
            <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
            <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
            <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-credit-card w-5"></i><span>Payments</span></a>
            <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-envelope w-5"></i><span>Messages</span></a>
            <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-file-alt w-5"></i><span>Reports</span></a>
            <a href="services-manage.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
            <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
            <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span></a>
            <hr class="border-slate-700/50 my-4">
            <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-home w-5"></i><span>Back to Site</span></a>
            <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10"><i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content flex-1 p-6 lg:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div><h1 class="text-3xl font-poppins font-extrabold text-slate-900"><span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Manage Services</span></h1><p class="text-slate-500 mt-1">Add, edit, and manage products & services</p></div>
            <button onclick="toggleForm()" class="mt-4 sm:mt-0 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all text-sm"><i class="fas fa-plus mr-1"></i> <?php echo $editService ? 'Edit Service' : 'Add New Service';?></button>
        </div>

        <?php if($success):?><div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6"><i class="fas fa-check-circle mr-2"></i><?php echo $success;?></div><?php endif;?>
        <?php if($error):?><div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error;?></div><?php endif;?>
        <?php if(isset($_GET['deleted'])):?><div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6"><i class="fas fa-trash-alt mr-2"></i>Service deleted.</div><?php endif;?>

        <!-- Add/Edit Form -->
        <div id="serviceForm" class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm mb-6 <?php echo $editService ? '' : 'hidden';?>">
            <h3 class="font-poppins font-bold text-slate-900 mb-4 text-lg"><?php echo $editService ? 'Edit Service' : 'Add New Service';?></h3>
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <input type="hidden" name="service_id" value="<?php echo $editService['id'] ?? '';?>">
                <input type="hidden" name="existing_image" value="<?php echo $editService['image_path'] ?? '';?>">
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Service Name *</label><input type="text" name="name" value="<?php echo htmlspecialchars($editService['name'] ?? '');?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm" required></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Category *</label><select name="category" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm" required><option value="">Select category</option><option value="engraving" <?php echo ($editService['category']??'')==='engraving'?'selected':'';?>>Engraving</option><option value="embroidery" <?php echo ($editService['category']??'')==='embroidery'?'selected':'';?>>Embroidery</option><option value="tracking" <?php echo ($editService['category']??'')==='tracking'?'selected':'';?>>Security Tracking</option><option value="calligraphy" <?php echo ($editService['category']??'')==='calligraphy'?'selected':'';?>>Calligraphy</option><option value="branding" <?php echo ($editService['category']??'')==='branding'?'selected':'';?>>Branding & Design</option><option value="printing" <?php echo ($editService['category']??'')==='printing'?'selected':'';?>>Printing</option></select></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Base Price (UGX) *</label><input type="number" name="base_price" value="<?php echo $editService['base_price'] ?? '';?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm" required min="0" step="0.01"></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Icon (Font Awesome class)</label><input type="text" name="icon" value="<?php echo htmlspecialchars($editService['icon'] ?? 'fa-cog');?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm" placeholder="e.g., fa-trophy"></div>
                <div class="lg:col-span-2"><label class="block text-sm font-semibold text-slate-700 mb-1">Short Description *</label><textarea name="description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm resize-none" required><?php echo htmlspecialchars($editService['description'] ?? '');?></textarea></div>
                <div class="lg:col-span-2"><label class="block text-sm font-semibold text-slate-700 mb-1">Long Description</label><textarea name="long_description" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm resize-none"><?php echo htmlspecialchars($editService['long_description'] ?? '');?></textarea></div>
                <div class="lg:col-span-2"><label class="block text-sm font-semibold text-slate-700 mb-1">Features (one per line)</label><textarea name="features" rows="4" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm resize-none" placeholder="Laser precision engraving&#10;Multiple metal types supported&#10;Custom designs accepted"><?php if($editService && $editService['features']):?><?php echo implode("\n", json_decode($editService['features'], true) ?: []);?><?php endif;?></textarea></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Service Image</label><input type="file" name="service_image" accept="image/*" class="w-full text-sm"><?php if($editService && $editService['image_path']):?><p class="text-xs text-slate-500 mt-1">Current: <?php echo htmlspecialchars($editService['image_path']);?></p><?php endif;?></div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" <?php echo !isset($editService) || ($editService['is_active']??1) ? 'checked' : '';?>> Active</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" <?php echo ($editService['is_featured']??0) ? 'checked' : '';?>> Featured</label>
                </div>
                <div class="lg:col-span-2 flex gap-3">
                    <button type="submit" name="save_service" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors text-sm"><i class="fas fa-save mr-1"></i><?php echo $editService ? 'Update Service' : 'Add Service';?></button>
                    <?php if($editService):?><a href="services-manage.php" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-colors text-sm">Cancel</a><?php endif;?>
                </div>
            </form>
        </div>

        <!-- Services Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead><tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase"><th class="px-5 py-3">Service</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Price</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-center">Actions</th></tr></thead>
                    <tbody class="divide-y">
                        <?php if(empty($services)):?>
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No services found. Add your first service above.</td></tr>
                        <?php else:?>
                        <?php foreach($services as $svc):?>
                        <tr class="service-row">
                            <td class="px-5 py-4"><div class="flex items-center space-x-3"><div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-lg"><?php if($svc['image_path']):?><img src="../<?php echo $svc['image_path'];?>" class="w-8 h-8 object-cover rounded"><?php else:?><i class="fas <?php echo htmlspecialchars($svc['icon'] ?: 'fa-cog');?> text-blue-600"></i><?php endif;?></div><div><p class="text-sm font-semibold"><?php echo htmlspecialchars($svc['name']);?></p><p class="text-xs text-slate-400"><?php echo htmlspecialchars(substr($svc['description'],0,60));?>...</p></div></div></td>
                            <td class="px-5 py-4 text-sm"><?php echo ucfirst($svc['category']);?></td>
                            <td class="px-5 py-4 text-sm font-semibold">UGX <?php echo number_format($svc['base_price']);?></td>
                            <td class="px-5 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $svc['is_active']?'bg-green-100 text-green-700':'bg-red-100 text-red-700';?>"><?php echo $svc['is_active']?'Active':'Inactive';?></span><?php if($svc['is_featured']):?> <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs ml-1">Featured</span><?php endif;?></td>
                            <td class="px-5 py-4 text-center"><div class="flex items-center justify-center gap-2">
                                <a href="?edit=1&id=<?php echo $svc['id'];?>" class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100"><i class="fas fa-edit"></i></a>
                                <a href="?toggle=1&id=<?php echo $svc['id'];?>" class="px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-xs font-semibold hover:bg-amber-100"><i class="fas <?php echo $svc['is_active']?'fa-eye-slash':'fa-eye';?>"></i></a>
                                <a href="?delete=1&id=<?php echo $svc['id'];?>" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100" onclick="return confirm('Delete this service?')"><i class="fas fa-trash"></i></a>
                            </div></td>
                        </tr>
                        <?php endforeach;?>
                        <?php endif;?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function toggleForm() {
    var form = document.getElementById('serviceForm');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}
<?php if($editService):?>document.getElementById('serviceForm').scrollIntoView({behavior:'smooth'});<?php endif;?>
</script>
</body>
</html>