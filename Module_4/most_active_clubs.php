<?php
session_start();

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module_1/index.php");
    exit();
}

/* ===============================
   FILTER VALUES
================================ */
$selectedClubID = $_GET['club_id'] ?? 'all';
$selectedEventID = $_GET['event_id'] ?? 'all';
$selectedSemester = $_GET['semester'] ?? 'all';

/* ===============================
   FETCH CLUB OPTIONS
================================ */
$clubStmt = $pdo->prepare("
    SELECT
        Club_ID,
        clubName
    FROM club
    ORDER BY clubName ASC
");

$clubStmt->execute();
$clubs = $clubStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   FETCH EVENT OPTIONS
================================ */
$eventOptionWhere = [];
$eventOptionParams = [];

if ($selectedClubID !== 'all' && $selectedClubID !== '') {
    $eventOptionWhere[] = "Club_ID = ?";
    $eventOptionParams[] = $selectedClubID;
}

if ($selectedSemester === 'semester1') {
    $eventOptionWhere[] = "MONTH(eventDate) IN (9, 10, 11, 12, 1, 2)";
}

if ($selectedSemester === 'semester2') {
    $eventOptionWhere[] = "MONTH(eventDate) IN (3, 4, 5, 6, 7)";
}

$eventOptionWhereSQL = '';

if (!empty($eventOptionWhere)) {
    $eventOptionWhereSQL = 'WHERE ' . implode(' AND ', $eventOptionWhere);
}

$eventStmt = $pdo->prepare("
    SELECT
        Event_ID,
        eventTitle,
        eventDate
    FROM event
    $eventOptionWhereSQL
    ORDER BY eventDate DESC, eventTitle ASC
");

$eventStmt->execute($eventOptionParams);
$events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

$validEventIDs = array_column($events, 'Event_ID');

if ($selectedEventID !== 'all' && !in_array($selectedEventID, $validEventIDs)) {
    $selectedEventID = 'all';
}

/* ===============================
   MAIN FILTER SQL
================================ */
$where = [];
$params = [];

if ($selectedClubID !== 'all' && $selectedClubID !== '') {
    $where[] = "e.Club_ID = ?";
    $params[] = $selectedClubID;
}

if ($selectedEventID !== 'all' && $selectedEventID !== '') {
    $where[] = "e.Event_ID = ?";
    $params[] = $selectedEventID;
}

if ($selectedSemester === 'semester1') {
    $where[] = "MONTH(e.eventDate) IN (9, 10, 11, 12, 1, 2)";
}

if ($selectedSemester === 'semester2') {
    $where[] = "MONTH(e.eventDate) IN (3, 4, 5, 6, 7)";
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* ===============================
   MOST ACTIVE CLUBS DATA
================================ */
$activeClubsStmt = $pdo->prepare("
    SELECT
        c.Club_ID,
        c.clubName,

        COUNT(DISTINCT e.Event_ID) AS totalEventsConducted,

        COUNT(DISTINCT er.EventRegistration_ID) AS totalParticipation,

        COUNT(DISTINCT CASE
            WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
            THEN ea.Attendance_ID
        END) AS totalAttendance,

        COALESCE(
            ROUND(
                (
                    COUNT(DISTINCT CASE
                        WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
                        THEN ea.Attendance_ID
                    END)
                    /
                    NULLIF(COUNT(DISTINCT er.EventRegistration_ID), 0)
                ) * 100,
                2
            ),
            0
        ) AS attendanceRate

    FROM club c

    LEFT JOIN event e
        ON c.Club_ID = e.Club_ID

    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID

    LEFT JOIN event_attendance ea
        ON er.EventRegistration_ID = ea.EventRegistrationID

    $whereSQL

    GROUP BY
        c.Club_ID,
        c.clubName

    ORDER BY
        attendanceRate DESC,
        totalAttendance DESC,
        totalParticipation DESC,
        totalEventsConducted DESC,
        c.clubName ASC
");

$activeClubsStmt->execute($params);
$activeClubs = $activeClubsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Most Active Clubs</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=24">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/most_active_clubs_CSS.css?v=2">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page active-clubs-page">

            <div class="d-flex justify-content-between align-items-center active-clubs-header">

                <div>
                    <h1 class="active-clubs-title">
                        Most Active Clubs
                    </h1>

                    <p class="active-clubs-subtitle">
                        Clubs are arranged in descending order based on attendance rate, participation, and events conducted.
                    </p>
                </div>

                <a href="participation_attendance_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Back
                </a>

            </div>

            <form method="GET" id="filterForm" class="active-clubs-filter-card">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label>Club</label>

                        <select name="club_id"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <option value="all">
                                All Clubs
                            </option>

                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= e($club['Club_ID']) ?>"
                                    <?= selected($selectedClubID, $club['Club_ID']) ?>>
                                    <?= e($club['clubName']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Event</label>

                        <select name="event_id"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <option value="all">
                                All Events
                            </option>

                            <?php foreach ($events as $event): ?>
                                <option value="<?= e($event['Event_ID']) ?>"
                                    <?= selected($selectedEventID, $event['Event_ID']) ?>>
                                    <?= e($event['eventTitle']) ?> - <?= e($event['eventDate']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Semester</label>

                        <select name="semester"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <option value="all" <?= selected($selectedSemester, 'all') ?>>
                                All Semesters
                            </option>

                            <option value="semester1" <?= selected($selectedSemester, 'semester1') ?>>
                                Semester 1 (September - February)
                            </option>

                            <option value="semester2" <?= selected($selectedSemester, 'semester2') ?>>
                                Semester 2 (March - July)
                            </option>

                        </select>
                    </div>

                </div>

            </form>

            <div class="dashboard-table-card active-clubs-list-card">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        Most Active Clubs List
                    </h5>
                </div>

                <div class="table-responsive">

                    <table class="table align-middle active-clubs-table">

                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Club Name</th>
                                <th>Events Conducted</th>
                                <th>Total Registration</th>
                                <th>Total Attendance</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($activeClubs)): ?>

                                <?php $rank = 1; ?>

                                <?php foreach ($activeClubs as $club): ?>

                                    <tr>

                                        <td>
                                            <?php if ($rank === 1): ?>
                                                <span class="rank-badge first">
                                                    <?= e($rank) ?>
                                                </span>
                                            <?php elseif ($rank === 2): ?>
                                                <span class="rank-badge second">
                                                    <?= e($rank) ?>
                                                </span>
                                            <?php elseif ($rank === 3): ?>
                                                <span class="rank-badge third">
                                                    <?= e($rank) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="rank-badge normal">
                                                    <?= e($rank) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <strong><?= e($club['clubName']) ?></strong>
                                        </td>

                                        <td>
                                            <?= e($club['totalEventsConducted']) ?>
                                        </td>

                                        <td>
                                            <?= e($club['totalParticipation']) ?>
                                        </td>

                                        <td>
                                            <?= e($club['totalAttendance']) ?>
                                        </td>

                                        <td>
                                            <span class="rate-badge">
                                                <?= e(number_format((float)$club['attendanceRate'], 2)) ?>%
                                            </span>
                                        </td>

                                    </tr>

                                    <?php $rank++; ?>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No active club data found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>