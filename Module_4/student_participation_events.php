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

function selected($value1, $value2)
{
    return ((string)$value1 === (string)$value2) ? 'selected' : '';
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
   If club selected, only show events from that club
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
   FILTER SQL
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
   PARTICIPATION DATA
================================ */
$participationStmt = $pdo->prepare("
    SELECT
        e.eventTitle AS labelName,
        e.eventDate,
        c.clubName,
        COUNT(DISTINCT er.User_ID) AS totalParticipation
    FROM event e
    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID
    LEFT JOIN club c
        ON e.Club_ID = c.Club_ID
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

$participationStmt->execute($params);
$participationRows = $participationStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = array_column($participationRows, 'labelName');
$chartData = array_map('intval', array_column($participationRows, 'totalParticipation'));

$totalParticipation = array_sum($chartData);

$tableLabel = 'Event';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Student Participation in Events</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=20">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/student_participation_events_CSS.css?v=4">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page participation-report-page">

            <div class="d-flex justify-content-between align-items-center participation-report-header">

                <div>
                    <h1 class="participation-report-title">
                        Student Participation in Events
                    </h1>
                </div>

                <a href="participation_attendance_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Back
                </a>

            </div>

            <!-- FILTER SECTION -->
            <form method="GET" id="filterForm" class="participation-filter-card">

                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
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

                    <div class="col-md-3">
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

            <?php if ($totalParticipation > 0): ?>

                <div class="participation-chart-card mb-4">

                    <h5>Total Student Participation in Events</h5>

                    <canvas id="participationChart"></canvas>

                </div>

                <div class="dashboard-table-card">

                    <h5>Participation Details</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th><?= e($tableLabel) ?></th>
                                    <th>Club</th>
                                    <th>Event Date</th>
                                    <th>Total Participation</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php $no = 1; ?>

                                <?php foreach ($participationRows as $row): ?>
                                    <tr>
                                        <td><?= e($no++) ?></td>
                                        <td><?= e($row['labelName']) ?></td>
                                        <td><?= e($row['clubName']) ?></td>
                                        <td><?= e($row['eventDate']) ?></td>
                                        <td><?= e($row['totalParticipation']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                </div>

            <?php else: ?>

                <div class="dashboard-table-card mb-4 text-center">
                    <i class="fa-solid fa-circle-info fa-2x mb-3 text-muted"></i>

                    <h5>No participation data found</h5>

                    <p class="text-muted mb-0">
                        No event registration matches the selected filter.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalParticipation > 0): ?>

<script>
const participationLabels = <?= json_encode($chartLabels) ?>;
const participationData = <?= json_encode($chartData) ?>;

new Chart(document.getElementById('participationChart'), {
    type: 'bar',
    data: {
        labels: participationLabels,
        datasets: [{
            label: 'Total Participation',
            data: participationData,
            backgroundColor: '#2563eb',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Total Participation: ' + context.raw;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<?php endif; ?>

</body>
</html>