<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';

Session::init();

if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = (int) Session::get('user_id');

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT o.*, s.name AS service_name, s.icon AS service_icon,
           p.status AS payment_status
    FROM orders o
    JOIN services s ON o.service_id = s.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.user_id = :uid
    ORDER BY o.created_at DESC
    LIMIT 10 OFFSET 0
");
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders – <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body { padding-top: 85px; }
        .status-badge { padding: 0.4em 1em; border-radius: 50px; font-size: 0.85rem; font-weight: 500; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section style="min-height: 80vh; background: #f9fafb; padding: 2rem 0;">
        <div class="container">
            <h1 class="mb-4 fw-bold">My Orders</h1>
            
            <?php if (empty($orders)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                <h3>No orders yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet.</p>
                <a href="services.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i> Browse Services
                </a>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order #</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): 
                                $statusColors = [
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'processing' => 'primary',
                                    'ready' => 'success',
                                    'in_transit' => 'info',
                                    'delivered' => 'success',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $color = $statusColors[$order['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <span class="me-2"><?php echo htmlspecialchars($order['service_icon'] ?? ''); ?></span>
                                    <?php echo htmlspecialchars($order['service_name']); ?>
                                </td>
                                <td>UGX <?php echo number_format($order['total_amount']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($order['payment_status']) && $order['payment_status'] === 'pending'): ?>
                                        <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-credit-card me-1"></i> Pay Now
                                        </a>
                                    <?php elseif (isset($order['payment_status']) && $order['payment_status'] === 'completed'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="track.php?order=<?php echo urlencode($order['order_number']); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-map-marker-alt me-1"></i> Track
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>