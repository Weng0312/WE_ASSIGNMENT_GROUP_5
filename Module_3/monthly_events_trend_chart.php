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
$selectedYear   = $_GET['year'] ?? 'all';

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
   FETCH AVAILABLE YEARS
================================ */
$yearStmt = $pdo->prepare("
    SELECT DISTINCT YEAR(eventDate) AS yearValue
    FROM event
    WHERE eventDate IS NOT NULL
    ORDER BY yearValue DESC
");

$yearStmt->execute();
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

$validYears = array_column($years, 'yearValue');

if ($selectedYear !== 'all' && !in_array((int)$selectedYear, array_map('intval', $validYears))) {
    $selectedYear = 'all';
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

if ($selectedYear !== 'all' && $selectedYear !== '') {
    $where[] = "YEAR(e.eventDate) = ?";
    $params[] = $selectedYear;
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* ===============================
   MONTHLY EVENT COUNT DATA
================================ */
$monthlyStmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(e.eventDate, '%Y-%m') AS monthKey,
        DATE_FORMAT(e.eventDate, '%b %Y') AS monthLabel,
        COUNT(e.Event_ID) AS totalEvents
    FROM event e
    $whereSQL
    GROUP BY monthKey, monthLabel
    ORDER BY monthKey ASC
");

$monthlyStmt->execute($params);
$monthlyRows = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = array_column($monthlyRows, 'monthLabel');
$chartData   = array_map('intval', array_column($monthlyRows, 'totalEvents'));

$totalEvents = array_sum($chartData);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Monthly Event Activity</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=18">

    <link rel="stylesheet" href="../STYLE/CSS/Module_3/event_management_dashboard_CSS.css?v=1">

    <link rel="stylesheet" href="../STYLE/CSS/Module_3/monthly_events_trend_chart_CSS.css?v=2">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page event-mgmt-page participation-report-page">

            <!-- PAGE HEADER -->
            <div class="d-flex justify-content-between align-items-center participation-report-header">

                <div>
                    <h2 class="participation-report-title mb-1">Monthly Event Activity</h2>
                    <p class="text-muted mb-0">
                        View the number of events organized by month.
                    </p>
                </div>

                <a href="event_management_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Back
                </a>

            </div>

            <!-- FILTER SECTION -->
            <form method="GET" id="filterForm" class="participation-filter-card">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
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

                    <div class="col-md-4">
                        <label>Year</label>

                        <select name="year"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <option value="all">All Years</option>

                            <?php foreach ($validYears as $yearValue): ?>
                                <option value="<?= e($yearValue) ?>"
                                    <?= selected($selectedYear, $yearValue) ?>>
                                    <?= e($yearValue) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>

            </form>

            <?php if ($totalEvents > 0): ?>

                <!-- CHART SECTION -->
                <div class="participation-chart-card mb-4 trend-chart-card">

                    <h5>Number of Events Organized per Month</h5>

                    <div class="chart-wrapper">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>

                </div>

                <!-- TABLE SECTION -->
                <div class="trend-table-card">

                    <h5>Monthly Breakdown</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Month</th>
                                    <th>Total Events</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php $no = 1; ?>

                                <?php foreach ($monthlyRows as $row): ?>
                                    <tr>
                                        <td><?= e($no++) ?></td>
                                        <td><?= e($row['monthLabel']) ?></td>
                                        <td><?= e($row['totalEvents']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                </div>

            <?php else: ?>

                <div class="dashboard-table-card mb-4 text-center">
                    <i class="fa-solid fa-circle-info fa-2x mb-3 text-muted"></i>

                    <h5>No event data found</h5>

                    <p class="text-muted mb-0">
                        No events match the selected filter.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalEvents > 0): ?>

<script>
const monthlyLabels = <?= json_encode($chartLabels) ?>;
const monthlyData   = <?= json_encode($chartData) ?>;

const monthlyTrendChartCanvas = document.getElementById('monthlyTrendChart');

new Chart(monthlyTrendChartCanvas, {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Events Organized',
            data: monthlyData,
            borderColor: '#0d9488',
            backgroundColor: 'rgba(13, 148, 136, 0.15)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#0d9488',
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        resizeDelay: 200,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Events: ' + context.raw;
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