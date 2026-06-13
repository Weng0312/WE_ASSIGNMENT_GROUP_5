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
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

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
   ATTENDANCE STATUS DATA
================================ */
$attendanceStatusStmt = $pdo->prepare("
    SELECT
        COUNT(ea.Attendance_ID) AS totalAttendance,

        COUNT(CASE
            WHEN ea.attendanceStatus = 'Present'
            THEN 1
        END) AS presentCount,

        COUNT(CASE
            WHEN ea.attendanceStatus = 'Late'
            THEN 1
        END) AS lateCount,

        COUNT(CASE
            WHEN ea.attendanceStatus = 'Absent'
            THEN 1
        END) AS absentCount,

        COUNT(CASE
            WHEN ea.attendanceStatus = 'Volunteer'
            THEN 1
        END) AS volunteerCount

    FROM event e
    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID
    LEFT JOIN event_attendance ea
        ON er.EventRegistration_ID = ea.EventRegistrationID
    $whereSQL
");

$attendanceStatusStmt->execute($params);
$attendanceStatusRow = $attendanceStatusStmt->fetch(PDO::FETCH_ASSOC);

$totalAttendance = (int)($attendanceStatusRow['totalAttendance'] ?? 0);

$presentCount = (int)($attendanceStatusRow['presentCount'] ?? 0);
$lateCount = (int)($attendanceStatusRow['lateCount'] ?? 0);
$absentCount = (int)($attendanceStatusRow['absentCount'] ?? 0);
$volunteerCount = (int)($attendanceStatusRow['volunteerCount'] ?? 0);

$attendanceStatusRaw = [
    'Present' => $presentCount,
    'Late' => $lateCount,
    'Absent' => $absentCount,
    'Volunteer' => $volunteerCount
];

$attendanceStatusLabels = array_keys($attendanceStatusRaw);
$attendanceStatusCounts = array_values($attendanceStatusRaw);
$attendanceStatusData = [];

foreach ($attendanceStatusCounts as $count) {
    $percentage = 0;

    if ($totalAttendance > 0) {
        $percentage = round(($count / $totalAttendance) * 100, 2);
    }

    $attendanceStatusData[] = $percentage;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Attendance Status Chart</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=19">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/attendance_overview_chart_CSS.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page overview-page">

            <div class="d-flex justify-content-between align-items-center overview-header">

                <div>
                    <h1 class="overview-title">
                        Attendance Overview
                    </h1>
                </div>

                <a href="participation_attendance_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Back
                </a>

            </div>

            <!-- FILTER SECTION -->
            <form method="GET" id="filterForm" class="overview-filter-card">

                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label>Club</label>

                        <select name="club_id"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <option value="all">All Clubs</option>

                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= e($club['Club_ID']) ?>"
                                    <?= selected($selectedClubID, $club['Club_ID']) ?>>
                                    <?= e($club['clubName']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Event</label>

                        <select name="event_id"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <option value="all">All Events</option>

                            <?php foreach ($events as $event): ?>
                                <option value="<?= e($event['Event_ID']) ?>"
                                    <?= selected($selectedEventID, $event['Event_ID']) ?>>
                                    <?= e($event['eventTitle']) ?> - <?= e($event['eventDate']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Start Date</label>

                        <input type="date"
                               name="start_date"
                               class="form-control"
                               value="<?= e($startDate) ?>"
                               onchange="document.getElementById('filterForm').submit();">
                    </div>

                    <div class="col-md-3">
                        <label>End Date</label>

                        <input type="date"
                               name="end_date"
                               class="form-control"
                               value="<?= e($endDate) ?>"
                               onchange="document.getElementById('filterForm').submit();">
                    </div>

                </div>

            </form>

            <?php if ($totalAttendance > 0): ?>

                <div class="overview-chart-card">

                    <h5>Attendance Status Distribution</h5>

                    <canvas id="attendanceStatusChart"></canvas>

                </div>

            <?php else: ?>

                <div class="dashboard-table-card mb-4 text-center">
                    <i class="fa-solid fa-circle-info fa-2x mb-3 text-muted"></i>

                    <h5>No attendance data found</h5>

                    <p class="text-muted mb-0">
                        No attendance records match the selected filter.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalAttendance > 0): ?>

<script>
const attendanceStatusLabels = <?= json_encode($attendanceStatusLabels) ?>;
const attendanceStatusData = <?= json_encode($attendanceStatusData) ?>;
const attendanceStatusCounts = <?= json_encode($attendanceStatusCounts) ?>;

new Chart(document.getElementById('attendanceStatusChart'), {
    type: 'bar',
    data: {
        labels: attendanceStatusLabels,
        datasets: [{
            label: 'Percentage (%)',
            data: attendanceStatusData,
            backgroundColor: [
                '#22c55e',
                '#f59e0b',
                '#dc2626',
                '#4f46e5'
            ],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const index = context.dataIndex;
                        const label = context.label;
                        const percentage = context.raw;
                        const count = attendanceStatusCounts[index];

                        return label + ': ' + percentage + '% (' + count + ' records)';
                    }
                }
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});
</script>

<?php endif; ?>

</body>
</html>