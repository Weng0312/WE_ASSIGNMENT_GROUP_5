<?php
session_start();

// Reset role to Student when in student mode
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Committee') {
    $_SESSION['role'] = 'Student';
}

$_SESSION['current_module'] = 'student';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Home - FK System</title>
    <link rel="stylesheet" href="../STYLE/CSS/Module1_SD_CSS.css">
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../topbar.php'; ?>
    
    <div id="wrapper">
        <?php
            $dashboardType = 'student';
            include '../sidebar.php';
        ?>

        <div id="content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 fw-bold mb-0">Student Dashboard</h1>
                    <span class="text-muted"><?php echo date('l, jS F Y'); ?></span>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-mortarboard text-success display-1 mb-4"></i>
                        <h2 class="fw-bold text-success">Welcome Back,
                            <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                        <p class="text-muted mx-auto" style="max-width: 600px;">This is your student home page. You can
                            view your points, register for events, and join clubs here using the sidebar navigation.</p>
                        <hr class="my-4">
                        <div class="alert alert-success d-inline-block px-5">
                            <strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['studentID']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>