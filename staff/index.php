<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $validUsername = 'admin';
        $validPassword = 'elephant2025';
        
        if ($_POST['username'] === $validUsername && $_POST['password'] === $validPassword) {
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_username'] = $_POST['username'];
            header('Location: index.php');
            exit;
        } else {
            $loginError = "Invalid username or password";
        }
    }
    
    include 'login.php';
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'orders';
$allowedPages = ['orders', 'order-details', 'settings', 'logout'];

if (!in_array($page, $allowedPages)) {
    $page = 'orders';
}

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regal Elephant - Staff Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/staff-style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="staff-header mb-4 px-3">
                        <h2>Staff Portal</h2>
                        <p>The Regal Elephant</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'orders' ? 'active' : ''; ?>" href="?page=orders">
                                <i class="bi bi-cart me-2"></i>
                                Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>" href="?page=settings">
                                <i class="bi bi-gear me-2"></i>
                                Settings
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link" href="?page=logout">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include "pages/{$page}.php"; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="assets/js/staff.js"></script>
</body>
</html>