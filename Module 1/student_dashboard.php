<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

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

$user_id = $_SESSION['user_id'];

// Query 1: Calculate Student's Total Points
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(
            CASE
                WHEN UPPER(TRIM(a.attendanceStatus)) = 'PRESENT' THEN 10
                WHEN UPPER(TRIM(a.attendanceStatus)) = 'LATE' THEN 5
                WHEN UPPER(TRIM(a.attendanceStatus)) = 'VOLUNTEER' THEN 5
                WHEN UPPER(TRIM(a.attendanceStatus)) = 'ABSENT' THEN -10
                ELSE 0
            END
        ), 0) AS total_points
    FROM event_attendance a
    INNER JOIN event_registration er ON a.EventRegistrationID = er.EventRegistration_ID
    WHERE er.User_ID = ?
");
$stmt->execute([$user_id]);
$totalPoints = (int) $stmt->fetchColumn();

// Query 2: Count Active Club Memberships
$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_membership WHERE User_ID = ? AND membershipStatus = 'Active'");
$stmt->execute([$user_id]);
$clubCount = (int) $stmt->fetchColumn();

// Query 3: Count Registered Events
$stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registration WHERE User_ID = ?");
$stmt->execute([$user_id]);
$eventCount = (int) $stmt->fetchColumn();
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
                            <?php echo htmlspecialchars($_SESSION['name']); ?>!
                        </h2>
                        <p class="text-muted mx-auto" style="max-width: 600px;">This is your student home page. You can
                            view your points, register for events, and join clubs here using the sidebar navigation.</p>
                        <hr class="my-4">

                        <!-- Simple Quick Stats Row -->
                        <div class="row justify-content-center g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <span class="text-muted d-block small fw-bold text-uppercase">Points</span>
                                    <span class="fs-3 fw-bold text-success"><?= $totalPoints ?></span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <span class="text-muted d-block small fw-bold text-uppercase">Clubs</span>
                                    <span class="fs-3 fw-bold text-primary"><?= $clubCount ?></span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <span class="text-muted d-block small fw-bold text-uppercase">Events</span>
                                    <span class="fs-3 fw-bold text-info"><?= $eventCount ?></span>
                                </div>
                            </div>
                        </div>

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