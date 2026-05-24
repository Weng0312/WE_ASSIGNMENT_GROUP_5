<?php
session_start();

$_SESSION['current_module'] = 'committee';

// Security Check: If not logged in or not a Committee member, redirect to login
if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Dashboard - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../topbar.php'; ?>
    
    <div id="wrapper">
        <?php
            $_SESSION['current_module'] = 'committee';
            include '../sidebar.php';
        ?>

        <div id="content">

            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Committee Panel</h2>
                    <span class="text-muted"><?php echo date('l, jS F Y'); ?></span>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-person-workspace text-info display-1 mb-4"></i>
                        <h2 class="fw-bold text-primary">Welcome, Committee Member!</h2>
                        <p class="text-muted mx-auto" style="max-width: 600px;">Use this portal to manage your club
                            activities and memberships. You can view your club details and assign roles through the
                            sidebar navigation.</p>
                        <hr class="my-4">
                        <div class="alert alert-primary d-inline-block px-5">
                            <strong>Your ID:</strong> <?php echo htmlspecialchars($_SESSION['studentID']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>