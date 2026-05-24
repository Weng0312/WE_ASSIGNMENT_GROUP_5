<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module 1/index.php");
    exit();
}

/* ===============================
   HELPER FUNCTIONS
================================ */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function selected($value1, $value2)
{
    return ((string)$value1 === (string)$value2) ? 'selected' : '';
}

function formatPercent($value)
{
    return number_format((float)$value, 2) . '%';
}

function getRecognitionLevel($totalPoints)
{
    if ($totalPoints < 20) {
        return 'Warning / Reminder';
    }

    if ($totalPoints <= 49) {
        return 'Eligible for Certificate';
    }

    if ($totalPoints <= 79) {
        return 'Active Student Award';
    }

    return 'Outstanding Participant';
}

/* ===============================
   FILTER VALUES
================================ */
$selectedReport = $_GET['report_type'] ?? 'participants_per_event';
$selectedClubID = $_GET['club_id'] ?? 'all';
$selectedEventID = $_GET['event_id'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$reportTitles = [
    'participants_per_event' => 'Number of Participants Per Event',
    'attendance_rate' => 'Attendance Rate For Each Event',
    'student_event_points' => 'Points Accumulated By Each Student Per Event',
    'overall_student_points' => 'Overall Semester / Date Range Points',
    'most_active_clubs' => 'Most Active Clubs Based On Event Organization'
];

if (!array_key_exists($selectedReport, $reportTitles)) {
    $selectedReport = 'participants_per_event';
}

/* ===============================
   FETCH CLUB OPTIONS
================================ */
$clubStmt = $pdo->query("
    SELECT
        Club_ID,
        clubName
    FROM club
    ORDER BY clubName ASC
");

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
    ORDER BY eventDate DESC
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

if (!empty($startDate)) {
    $where[] = "e.eventDate >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "e.eventDate <= ?";
    $params[] = $endDate;
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* ===============================
   SUMMARY CARDS
================================ */
$totalEventsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.Event_ID)
    FROM event e
    $whereSQL
");
$totalEventsStmt->execute($params);
$totalEvents = $totalEventsStmt->fetchColumn();

$totalParticipantsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT er.EventRegistration_ID)
    FROM event e
    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID
    $whereSQL
");
$totalParticipantsStmt->execute($params);
$totalParticipants = $totalParticipantsStmt->fetchColumn();

$totalAttendanceStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ea.Attendance_ID)
    FROM event e
    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID
    LEFT JOIN event_attendance ea
        ON er.EventRegistration_ID = ea.EventRegistrationID
       AND ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
    $whereSQL
");
$totalAttendanceStmt->execute($params);
$totalAttendance = $totalAttendanceStmt->fetchColumn();

$overallAttendanceRate = 0;

if ($totalParticipants > 0) {
    $overallAttendanceRate = ($totalAttendance / $totalParticipants) * 100;
}

$totalPointsStmt = $pdo->prepare("
    SELECT COALESCE(SUM(p.pointsValue), 0)
    FROM event e
    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID
    LEFT JOIN event_attendance ea
        ON er.EventRegistration_ID = ea.EventRegistrationID
    LEFT JOIN points p
        ON ea.Attendance_ID = p.Attendance_ID
    $whereSQL
");
$totalPointsStmt->execute($params);
$totalPoints = $totalPointsStmt->fetchColumn();

/* ===============================
   LOAD SELECTED REPORT DATA
================================ */
$reportData = [];

if ($selectedReport === 'participants_per_event') {

    $stmt = $pdo->prepare("
        SELECT
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName,
            COUNT(DISTINCT er.EventRegistration_ID) AS totalParticipants
        FROM event e
        LEFT JOIN club c
            ON e.Club_ID = c.Club_ID
        LEFT JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        $whereSQL
        GROUP BY
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName
        ORDER BY
            e.eventDate DESC,
            e.eventTitle ASC
    ");

    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'attendance_rate') {

    $stmt = $pdo->prepare("
        SELECT
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName,

            COUNT(DISTINCT er.EventRegistration_ID) AS totalRegistered,

            SUM(
                CASE
                    WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
                    THEN 1
                    ELSE 0
                END
            ) AS totalAttended,

            SUM(
                CASE
                    WHEN ea.attendanceStatus = 'Absent'
                    THEN 1
                    ELSE 0
                END
            ) AS totalAbsent,

            ROUND(
                (
                    SUM(
                        CASE
                            WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
                            THEN 1
                            ELSE 0
                        END
                    ) / NULLIF(COUNT(DISTINCT er.EventRegistration_ID), 0)
                ) * 100,
                2
            ) AS attendanceRate

        FROM event e
        LEFT JOIN club c
            ON e.Club_ID = c.Club_ID
        LEFT JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        $whereSQL
        GROUP BY
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName
        ORDER BY
            e.eventDate DESC,
            e.eventTitle ASC
    ");

    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'student_event_points') {

    $stmt = $pdo->prepare("
        SELECT
            s.studentID,
            u.userName,
            e.eventTitle,
            e.eventDate,
            c.clubName,
            ea.attendanceStatus,
            COALESCE(p.pointsValue, 0) AS pointsValue
        FROM event e
        INNER JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        INNER JOIN student s
            ON er.User_ID = s.User_ID
        INNER JOIN user u
            ON er.User_ID = u.User_ID
        LEFT JOIN club c
            ON e.Club_ID = c.Club_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        LEFT JOIN points p
            ON ea.Attendance_ID = p.Attendance_ID
        $whereSQL
        ORDER BY
            e.eventDate DESC,
            u.userName ASC
    ");

    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'overall_student_points') {

    $stmt = $pdo->prepare("
        SELECT
            s.studentID,
            u.userName,
            COUNT(DISTINCT e.Event_ID) AS totalEventsJoined,
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
        LEFT JOIN points p
            ON ea.Attendance_ID = p.Attendance_ID
        $whereSQL
        GROUP BY
            s.User_ID,
            s.studentID,
            u.userName
        ORDER BY
            totalPoints DESC,
            u.userName ASC
    ");

    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'most_active_clubs') {

    $stmt = $pdo->prepare("
        SELECT
            c.clubName,
            COUNT(DISTINCT e.Event_ID) AS eventsHeld,
            COUNT(DISTINCT er.EventRegistration_ID) AS totalParticipants,
            COALESCE(SUM(p.pointsValue), 0) AS totalPointsAwarded
        FROM club c
        LEFT JOIN event e
            ON c.Club_ID = e.Club_ID
        LEFT JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        LEFT JOIN points p
            ON ea.Attendance_ID = p.Attendance_ID
        $whereSQL
        GROUP BY
            c.Club_ID,
            c.clubName
        ORDER BY
            eventsHeld DESC,
            totalParticipants DESC,
            totalPointsAwarded DESC
    ");

    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pdfExportURL = "export_report.php?"
    . "report_type=" . urlencode($selectedReport)
    . "&club_id=" . urlencode($selectedClubID)
    . "&event_id=" . urlencode($selectedEventID)
    . "&start_date=" . urlencode($startDate)
    . "&end_date=" . urlencode($endDate)
    . "&export_format=pdf";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reports Page</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module 4/report_CSS.css?v=9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid report-page">

            <h2 class="report-title fw-bold" >Reports Page</h2><br>

            <!-- FILTER FORM -->
            <form method="GET">

                <div class="report-filter-card mb-4">

                    <div class="row g-3">

                        <div class="col-md-4 filter-field">
                            <label>Report Type</label>

                            <select name="report_type" class="form-select">
                                <?php foreach ($reportTitles as $key => $title): ?>
                                    <option value="<?= e($key) ?>" <?= selected($selectedReport, $key) ?>>
                                        <?= e($title) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 filter-field">
                            <label>Club</label>

                            <select name="club_id" class="form-select" onchange="this.form.submit()">
                                <option value="all">All Clubs</option>

                                <?php foreach ($clubs as $club): ?>
                                    <option value="<?= e($club['Club_ID']) ?>"
                                        <?= selected($selectedClubID, $club['Club_ID']) ?>>
                                        <?= e($club['clubName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 filter-field">
                            <label>Event</label>

                            <select name="event_id" class="form-select">
                                <option value="all">All Events</option>

                                <?php foreach ($events as $event): ?>
                                    <option value="<?= e($event['Event_ID']) ?>"
                                        <?= selected($selectedEventID, $event['Event_ID']) ?>>
                                        <?= e($event['eventTitle']) ?> - <?= e($event['eventDate']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 filter-field">
                            <label>Start Date</label>

                            <input type="date"
                                name="start_date"
                                class="form-control"
                                value="<?= e($startDate) ?>">
                        </div>

                        <div class="col-md-4 filter-field">
                            <label>End Date</label>

                            <input type="date"
                                name="end_date"
                                class="form-control"
                                value="<?= e($endDate) ?>">
                        </div>

                        <div class="col-md-4">
                            <div class="filter-button-row">

                                <button type="button"
                                        class="btn btn-secondary reset-btn"
                                        onclick="window.location.href='report.php'">
                                    <i class="fa-solid fa-rotate-left me-2"></i>
                                    Reset
                                </button>

                                <button type="submit" class="btn btn-primary search-btn">
                                    <i class="fa-solid fa-magnifying-glass me-2"></i>
                                    Search
                                </button>

                            </div>
                        </div>

                    </div>

                </div>

            </form>

            <!-- SUMMARY CARDS -->
            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="report-card d-flex align-items-center">
                        <div class="report-icon blue">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>

                        <div>
                            <h6>Total Events</h6>
                            <h2 class="text-primary"><?= e($totalEvents) ?></h2>
                            <p>Events in selected filter</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="report-card d-flex align-items-center">
                        <div class="report-icon green">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div>
                            <h6>Total Participants</h6>
                            <h2 class="text-success"><?= e($totalParticipants) ?></h2>
                            <p>Total event registrations</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="report-card d-flex align-items-center">
                        <div class="report-icon purple">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>

                        <div>
                            <h6>Attendance Rate</h6>
                            <h2 class="text-purple"><?= e(formatPercent($overallAttendanceRate)) ?></h2>
                            <p>Overall attendance rate</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="report-card d-flex align-items-center">
                        <div class="report-icon orange">
                            <i class="fa-solid fa-star"></i>
                        </div>

                        <div>
                            <h6>Total Points</h6>
                            <h2 class="text-warning"><?= e($totalPoints) ?></h2>
                            <p>Points awarded</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SELECTED REPORT TABLE -->
            <div class="report-table-card mb-4">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>
                        <h5><?= e($reportTitles[$selectedReport]) ?></h5>

                        <p class="text-muted mb-0">
                            Generated on <?= date("F d, Y h:i A") ?>
                        </p>
                    </div>

                </div>

                <div class="table-responsive">

                    <?php if ($selectedReport === 'participants_per_event'): ?>

                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Event</th>
                                    <th>Club</th>
                                    <th>Event Date</th>
                                    <th>Total Participants</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php $no = 1; ?>

                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= e($row['eventTitle']) ?></td>
                                            <td><?= e($row['clubName']) ?></td>
                                            <td><?= e($row['eventDate']) ?></td>
                                            <td><?= e($row['totalParticipants']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No data found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php elseif ($selectedReport === 'attendance_rate'): ?>

                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Event</th>
                                    <th>Club</th>
                                    <th>Registered</th>
                                    <th>Attended</th>
                                    <th>Absent</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php $no = 1; ?>

                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= e($row['eventTitle']) ?></td>
                                            <td><?= e($row['clubName']) ?></td>
                                            <td><?= e($row['totalRegistered']) ?></td>
                                            <td><?= e($row['totalAttended'] ?? 0) ?></td>
                                            <td><?= e($row['totalAbsent'] ?? 0) ?></td>
                                            <td><?= e(formatPercent($row['attendanceRate'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No data found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php elseif ($selectedReport === 'student_event_points'): ?>

                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Club</th>
                                    <th>Status</th>
                                    <th>Points</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php $no = 1; ?>

                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= e($row['studentID']) ?></td>
                                            <td><?= e($row['userName']) ?></td>
                                            <td><?= e($row['eventTitle']) ?></td>
                                            <td><?= e($row['clubName']) ?></td>
                                            <td>
                                                <?php if ($row['attendanceStatus'] === 'Present'): ?>
                                                    <span class="badge bg-success">Present</span>

                                                <?php elseif ($row['attendanceStatus'] === 'Late'): ?>
                                                    <span class="badge bg-warning text-dark">Late</span>

                                                <?php elseif ($row['attendanceStatus'] === 'Volunteer'): ?>
                                                    <span class="badge bg-primary">Volunteer</span>

                                                <?php elseif ($row['attendanceStatus'] === 'Absent'): ?>
                                                    <span class="badge bg-danger">Absent</span>

                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Registered</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($row['pointsValue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No data found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php elseif ($selectedReport === 'overall_student_points'): ?>

                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Events Joined</th>
                                    <th>Total Points</th>
                                    <th>Recognition</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php $rank = 1; ?>

                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= $rank++ ?></td>
                                            <td><?= e($row['studentID']) ?></td>
                                            <td><?= e($row['userName']) ?></td>
                                            <td><?= e($row['totalEventsJoined']) ?></td>
                                            <td><?= e($row['totalPoints']) ?></td>
                                            <td><?= e(getRecognitionLevel((int)$row['totalPoints'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No data found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php elseif ($selectedReport === 'most_active_clubs'): ?>

                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Club Name</th>
                                    <th>Events Held</th>
                                    <th>Total Participants</th>
                                    <th>Total Points Awarded</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($reportData)): ?>
                                    <?php $rank = 1; ?>

                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?= $rank++ ?></td>
                                            <td><?= e($row['clubName']) ?></td>
                                            <td><?= e($row['eventsHeld']) ?></td>
                                            <td><?= e($row['totalParticipants']) ?></td>
                                            <td><?= e($row['totalPointsAwarded']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No data found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php endif; ?>

                </div>

                <!-- EXPORT BUTTON AT RIGHT BOTTOM -->
                <div class="export-button-bottom">
                    <iframe name="pdfDownloadFrame" style="display:none;"></iframe>

                    <a href="<?= e($pdfExportURL) ?>"
                       target="pdfDownloadFrame"
                       class="btn btn-danger">
                        <i class="fa-solid fa-file-pdf me-2"></i>
                        Export This Report as PDF
                    </a>
                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>