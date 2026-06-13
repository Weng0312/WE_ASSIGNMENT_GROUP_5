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
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

/* ===============================
   FILTER SQL
================================ */
$where = [];
$params = [];

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
   NUMBER OF EVENTS PER CLUB
================================ */
$clubEventStmt = $pdo->prepare("
    SELECT
        c.clubName,
        COUNT(e.Event_ID) AS totalEvents
    FROM club c
    LEFT JOIN event e
        ON c.Club_ID = e.Club_ID
        " . (!empty($whereSQL) ? str_replace('WHERE', 'AND', $whereSQL) : '') . "
    GROUP BY c.Club_ID, c.clubName
    ORDER BY totalEvents DESC, c.clubName ASC
");

$clubEventStmt->execute($params);
$clubEventRows = $clubEventStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = array_column($clubEventRows, 'clubName');
$chartData   = array_map('intval', array_column($clubEventRows, 'totalEvents'));

$totalEvents = array_sum($chartData);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Events per Club</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=18">

    <link rel="stylesheet" href="../STYLE/CSS/Module_3/event_management_dashboard_CSS.css?v=1">

    <link rel="stylesheet" href="../STYLE/CSS/Module_3/number_events_club_CSS.css?v=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page event-mgmt-page">

            <div class="d-flex justify-content-between align-items-center participation-report-header">

                <div>
                    <h1 class="participation-report-title">
                        Events per Club
                    </h1>
                </div>

                <a href="event_management_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Back
                </a>

            </div>

            <!-- FILTER SECTION -->
            <form method="GET" id="filterForm" class="participation-filter-card">

                <div class="row g-3 align-items-end">

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

            <?php if ($totalEvents > 0): ?>

                <div class="participation-chart-card mb-4 club-bar-chart-card">

                    <h5>Number of Events Organized by Each Club</h5>

                    <canvas id="clubEventChart"></canvas>

                </div>

                <div class="club-table-card">

                    <h5>Club Breakdown</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Club</th>
                                    <th>Total Events</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php $no = 1; ?>

                                <?php foreach ($clubEventRows as $row): ?>
                                    <tr>
                                        <td><?= e($no++) ?></td>
                                        <td><?= e($row['clubName']) ?></td>
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

                    <h5>No club data found</h5>

                    <p class="text-muted mb-0">
                        No clubs or events match the selected filter.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalEvents > 0): ?>

<script>
const clubLabels = <?= json_encode($chartLabels) ?>;
const clubData   = <?= json_encode($chartData) ?>;

// Generate a coral-themed gradient palette so each bar differs slightly
const baseColors = [
    '#fb7185', '#f97316', '#fb923c', '#f43f5e',
    '#fda4af', '#ea580c', '#e11d48', '#f59e0b'
];

const barColors = clubLabels.map((_, i) => baseColors[i % baseColors.length]);

new Chart(document.getElementById('clubEventChart'), {
    type: 'bar',
    data: {
        labels: clubLabels,
        datasets: [{
            label: 'Total Events',
            data: clubData,
            backgroundColor: barColors,
            borderRadius: 8
        }]
    },
    options: {
        indexAxis: 'y', // Horizontal bars for easier club name reading
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Total Events: ' + context.raw;
                    }
                }
            }
        },
        scales: {
            x: {
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