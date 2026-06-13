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
$limit          = $_GET['limit'] ?? '10';

// Whitelist allowed limit values to avoid injection via GET
$allowedLimits = ['5', '10', '15', '20'];

if (!in_array($limit, $allowedLimits)) {
    $limit = '10';
}

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
   FILTER SQL
================================ */
$where = [];
$params = [];

if ($selectedClubID !== 'all' && $selectedClubID !== '') {
    $where[] = "e.Club_ID = ?";
    $params[] = $selectedClubID;
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* ===============================
   POPULAR EVENTS DATA
   Ranked by total registrations (descending)
================================ */
$popularStmt = $pdo->prepare("
    SELECT
        e.eventTitle,
        c.clubName,
        COUNT(er.EventRegistration_ID) AS totalRegistrations
    FROM event e
    LEFT JOIN event_registration er
        ON e.Event_ID = er.Event_ID
    LEFT JOIN club c
        ON e.Club_ID = c.Club_ID
    $whereSQL
    GROUP BY e.Event_ID, e.eventTitle, c.clubName
    ORDER BY totalRegistrations DESC, e.eventTitle ASC
    LIMIT :limit
");

foreach ($params as $i => $value) {
    $popularStmt->bindValue($i + 1, $value);
}
$popularStmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$popularStmt->execute();
$popularRows = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = array_column($popularRows, 'eventTitle');
$chartData   = array_map('intval', array_column($popularRows, 'totalRegistrations'));

$totalRegistrations = array_sum($chartData);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Popular Events</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=18">

    <link rel="stylesheet" href="../STYLE/CSS/Module_3/event_management_dashboard_CSS.css?v=1">

    <link rel="stylesheet" href="../STYLE/CSS/Module_3/popular_events_chart_CSS.css?v=1">

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
                        Popular Events
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
                        <label>Show Top</label>

                        <select name="limit"
                                class="form-select"
                                onchange="document.getElementById('filterForm').submit();">

                            <?php foreach ($allowedLimits as $limitOption): ?>
                                <option value="<?= e($limitOption) ?>"
                                    <?= selected($limit, $limitOption) ?>>
                                    Top <?= e($limitOption) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>

            </form>

            <?php if ($totalRegistrations > 0): ?>

                <div class="popular-events-layout">

                    <div class="participation-chart-card popular-chart-card">

                        <h5>Most Popular Events by Registration Count</h5>

                        <canvas id="popularEventsChart"></canvas>

                    </div>

                    <div class="dashboard-table-card popular-table-card">

                        <h5>Ranking</h5>

                        <div class="table-responsive">
                            <table class="table align-middle">

                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Event</th>
                                        <th>Club</th>
                                        <th>Registrations</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php $rank = 1; ?>

                                    <?php foreach ($popularRows as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="rank-badge">
                                                    #<?= e($rank++) ?>
                                                </span>
                                            </td>
                                            <td><?= e($row['eventTitle']) ?></td>
                                            <td><?= e($row['clubName']) ?></td>
                                            <td><?= e($row['totalRegistrations']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="dashboard-table-card mb-4 text-center">
                    <i class="fa-solid fa-circle-info fa-2x mb-3 text-muted"></i>

                    <h5>No registration data found</h5>

                    <p class="text-muted mb-0">
                        No events have registrations yet for the selected filter.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalRegistrations > 0): ?>

<script>
const popularLabels = <?= json_encode($chartLabels) ?>;
const popularData   = <?= json_encode($chartData) ?>;

// Gold/amber themed palette for the doughnut slices
const sliceColors = [
    '#f59e0b', '#fbbf24', '#fcd34d', '#d97706',
    '#fb923c', '#facc15', '#ea580c', '#fde68a',
    '#f97316', '#eab308', '#fed7aa', '#ca8a04',
    '#fef3c7', '#c2410c', '#fdba74', '#a16207',
    '#ffedd5', '#92400e', '#fef9c3', '#78350f'
];

new Chart(document.getElementById('popularEventsChart'), {
    type: 'doughnut',
    data: {
        labels: popularLabels,
        datasets: [{
            label: 'Registrations',
            data: popularData,
            backgroundColor: sliceColors.slice(0, popularLabels.length),
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '55%',
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 14,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' registrations';
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