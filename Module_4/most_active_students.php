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
   MOST ACTIVE STUDENTS DATA
================================ */
$activeStudentsStmt = $pdo->prepare("
    SELECT
        s.studentID,
        u.userName,

        COUNT(DISTINCT er.EventRegistration_ID) AS totalEventsJoined,

        COUNT(DISTINCT CASE
            WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
            THEN ea.Attendance_ID
        END) AS totalAttendance,

        COALESCE(SUM(p.pointsValue), 0) AS totalPoints

    FROM event e

    INNER JOIN event_registration er
        ON e.Event_ID = er.Event_ID

    INNER JOIN student s
        ON er.User_ID = s.User_ID

    INNER JOIN user u
        ON er.User_ID = u.User_ID

    LEFT JOIN event_attendance ea
        ON er.EventRegistration_ID = ea.EventRegistrationID
       AND ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')

    LEFT JOIN points p
        ON ea.Attendance_ID = p.Attendance_ID

    $whereSQL

    GROUP BY
        s.User_ID,
        s.studentID,
        u.userName

    ORDER BY
        totalPoints DESC,
        totalAttendance DESC,
        totalEventsJoined DESC,
        u.userName ASC
");

$activeStudentsStmt->execute($params);
$activeStudents = $activeStudentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Most Active Students</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/most_active_students_CSS.css?v=5">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page active-students-page">

            <div class="d-flex justify-content-between align-items-center active-students-header">

                <div>
                    <h1 class="active-students-title">
                        Most Active Students
                    </h1>
                </div>

                <a href="participation_attendance_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Back
                </a>

            </div>

            <form method="GET" id="filterForm" class="active-students-filter-card">

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

            <div class="dashboard-table-card active-students-list-card">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        Most Active Students List
                    </h5>
                </div>

                <div class="table-responsive">

                    <table class="table align-middle active-students-table">

                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Events Joined</th>
                                <th>Total Attendance</th>
                                <th>Total Points</th>
                                <th>Recognition Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($activeStudents)): ?>

                                <?php $rank = 1; ?>

                                <?php foreach ($activeStudents as $student): ?>

                                    <?php
                                    $recognition = getRecognitionLevel($student['totalPoints']);
                                    $recognitionClass = getRecognitionClass($student['totalPoints']);
                                    ?>

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
                                            <?= e($student['studentID']) ?>
                                        </td>

                                        <td>
                                            <strong><?= e($student['userName']) ?></strong>
                                        </td>

                                        <td>
                                            <?= e($student['totalEventsJoined']) ?>
                                        </td>

                                        <td>
                                            <?= e($student['totalAttendance']) ?>
                                        </td>

                                        <td>
                                            <strong><?= e($student['totalPoints']) ?></strong>
                                        </td>

                                        <td>
                                            <span class="recognition-badge <?= e($recognitionClass) ?>">
                                                <?= e($recognition) ?>
                                            </span>
                                        </td>

                                    </tr>

                                    <?php $rank++; ?>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No active student data found.
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