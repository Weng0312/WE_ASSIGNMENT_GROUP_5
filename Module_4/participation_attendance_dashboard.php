<?php
session_start();

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module_1/index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Participation & Attendance Dashboard</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=18">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page">

            <h2 class="dashboard-title fw-bold">
                Participation & Attendance Dashboard
            </h2>

            <br>

            <!-- REPORT MENU -->
            <div class="dashboard-table-card">

                <div class="report-menu-grid">

                    <a href="student_participation_events.php" class="report-menu-card">
                        <div class="report-menu-icon green">
                            <i class="fa-solid fa-chart-column"></i>
                        </div>

                        <h4>Student Participation in Events</h4>
                        <p>View total student participation by event, club, and semester.</p>
                    </a>

                    <a href="attendance_overview_chart.php" class="report-menu-card">
                        <div class="report-menu-icon blue">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>

                        <h4>Attendance Overview</h4>
                        <p>View attendance status percentage including present, late, absent, and volunteer.</p>
                    </a>

                    <a href="most_active_students.php" class="report-menu-card">
                        <div class="report-menu-icon purple">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>

                        <h4>Most Active Students</h4>
                        <p>View students ranked by participation, attendance, and total points.</p>
                    </a>

                    <a href="most_active_clubs.php" class="report-menu-card">
                        <div class="report-menu-icon orange">
                            <i class="fa-solid fa-people-group"></i>
                        </div>

                        <h4>Most Active Clubs</h4>
                        <p>View clubs ranked by attendance rate, participation, and events conducted.</p>
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>