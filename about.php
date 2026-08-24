<?php
require_once 'config/app.php';
require_once 'includes/Session.php';

Session::init();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section style="padding-top: 120px; min-height: 80vh;">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h1 class="display-4 fw-bold">About E-Find</h1>
                <p class="lead text-muted">Your trusted partner for custom services and tracking solutions</p>
            </div>
            
            <div class="row g-4 align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2>Who We Are</h2>
                    <p class="lead">E-Find is a modern digital platform designed to provide multiple custom services through one centralized online system.</p>
                    <p>We bridge the gap between customers and service providers by simplifying ordering, payments, tracking, and delivery processes.</p>
                    <p>Our mission is to make professional custom services accessible, convenient, and reliable for everyone.</p>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h4 class="mb-3">Why Choose Us?</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Professional Quality</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Fast Turnaround</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Secure Payments</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Real-time Tracking</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Door-to-Door Delivery</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>