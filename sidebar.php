<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$currentMode = $_SESSION['current_module'] ?? 'student';

$isAdmin = ($role === 'Administrator');

/*
    Safer committee check:
    User must really be committee AND current mode must be committee.
*/
$isCommitteeUser = ($role === 'Committee' || strpos($role, 'Committee') !== false);
$isCommitteeMode = ($currentMode === 'committee' && $isCommitteeUser);

$BASE = '..';

$participationPages = [
    'participation_attendance_dashboard.php',
    'attendance_overview_chart.php',
    'student_participation_events.php',
    'most_active_students.php',
    'most_active_clubs.php'
];

$reportPages = [
    'report.php',
    'export_report.php'
];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../STYLE/CSS/sidebar_CSS.css?v=11">

<nav id="sidebar">
    <ul class="list-unstyled components">

        <?php if ($isAdmin): ?>

            <li class="<?= ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_1/admin_dashboard.php">
                    <i class="bi bi-house-door-fill me-2"></i> Dashboard
                </a>
            </li>

            <li class="<?= ($currentPage == 'manage_users.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_1/manage_users.php">
                    <i class="bi bi-people-fill me-2"></i> User Management
                </a>
            </li>

            <li class="<?= ($currentPage == 'register.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_1/register.php">
                    <i class="bi bi-person-plus-fill me-2"></i> User Registration
                </a>
            </li>

            <li class="<?= ($currentPage == 'club_management.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_2/club_management.php">
                    <i class="bi bi-shield-fill me-2"></i> Club Management
                </a>
            </li>

            <li class="<?= in_array($currentPage, $participationPages) ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_4/participation_attendance_dashboard.php">
                    <i class="bi bi-bar-chart-fill me-2"></i> Participation & Attendance
                </a>
            </li>

            <li class="<?= in_array($currentPage, $reportPages) ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_4/report.php">
                    <i class="bi bi-file-earmark-bar-graph-fill me-2"></i> Reports
                </a>
            </li>

        <?php elseif ($isCommitteeMode): ?>

            <li class="<?= ($currentPage == 'committee_dashboard.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_1/committee_dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            
            <li class="<?= ($currentPage == 'club_members.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_2/club_members.php">
                    <i class="bi bi-person-lines-fill me-2"></i> Club Members
                </a>
            </li>

            <li class="<?= ($currentPage == 'event_management.php' || $currentPage == 'create_event.php' || $currentPage == 'edit_event.php' || $currentPage == 'view_event.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_3/event_management.php">
                    <i class="bi bi-calendar-event me-2"></i> Event Management
                </a>
            </li>

            <li class="<?= ($currentPage == 'attendance_management.php' || $currentPage == 'manual_attendance.php' || $currentPage == 'qr_attendance.php' || $currentPage == 'qr_list.php' || $currentPage == 'edit_attendance.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_4/attendance_management.php">
                    <i class="bi bi-check2-square me-2"></i> Attendance Management
                </a>
            </li>

            <li>
                <a href="<?= $BASE ?>/Module_1/student_dashboard.php">
                    <i class="bi bi-arrow-return-left me-2"></i> Back to Student Mode
                </a>
            </li>

        <?php else: ?>

            <li class="<?= ($currentPage == 'student_dashboard.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_1/student_dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <li class="<?= ($currentPage == 'my_membership.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_2/my_membership.php">
                    <i class="bi bi-person-badge me-2"></i> My Membership
                </a>
            </li>

            <li class="<?= ($currentPage == 'club_list.php' || $currentPage == 'club_details.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_2/club_list.php">
                    <i class="bi bi-list-ul me-2"></i> Club List
                </a>
            </li>

            <li class="<?= ($currentPage == 'event_list.php' || $currentPage == 'book_event.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_3/event_list.php">
                    <i class="bi bi-calendar-event me-2"></i> Event List
                </a>
            </li>

            <li class="<?= ($currentPage == 'my_event_registration.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_3/my_event_registration.php">
                    <i class="bi bi-calendar-check me-2"></i> My Event Registration
                </a>
            </li>

            <li class="<?= ($currentPage == 'Participation_and_Points.php') ? 'active' : ''; ?>">
                <a href="<?= $BASE ?>/Module_4/Participation_and_Points.php">
                    <i class="bi bi-award me-2"></i> Participation & Points
                </a>
            </li>

        <?php endif; ?>

    </ul>
</nav>