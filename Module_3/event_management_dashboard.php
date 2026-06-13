<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module_1/index.php");
    exit();
}

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Event Management Dashboard</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <!-- Reuse same base dashboard layout styles as Module 4 -->
    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=18">

    <!-- Module 3's own theme on top -->
    <link rel="stylesheet" href="../STYLE/CSS/Module_3/event_management_dashboard_CSS.css?v=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page event-mgmt-page">

            <h2 class="dashboard-title fw-bold">
                Event Management Dashboard
            </h2>

            <br>

            <!-- REPORT MENU -->
            <div class="dashboard-table-card">

                <div class="report-menu-grid">

                    <a href="monthly_events_trend_chart.php" class="report-menu-card">
                        <div class="report-menu-icon teal">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>

                        <h4>Monthly Event Activity</h4>
                        <p>View number of events organized each month across the semester.</p>
                    </a>

                    <a href="number_events_club.php" class="report-menu-card">
                        <div class="report-menu-icon coral">
                            <i class="fa-solid fa-chart-simple"></i>
                        </div>

                        <h4>Events per Club</h4>
                        <p>View number of events organized by each club.</p>
                    </a>

                    <a href="popular_events_chart.php" class="report-menu-card">
                        <div class="report-menu-icon gold">
                            <i class="fa-solid fa-fire"></i>
                        </div>

                        <h4>Popular Events</h4>
                        <p>View most popular events based on registration count.</p>
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>