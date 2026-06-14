<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// ACCESS CONTROL: Restrict this page to Administrators only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module_1/index.php");
    exit();
}

// SECURITY HELPER: Escape output to prevent XSS
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// UI HELPER: Returns 'selected' attribute if values match (used for dropdowns)
function selected($value1, $value2)
{
    return ((string)$value1 === (string)$value2) ? 'selected' : '';
}

/* ===============================
   FILTER VALUES
   Read filters from the URL: ?club_id=...&limit=...
   "limit" controls how many top events are shown
================================ */
$selectedClubID = $_GET['club_id'] ?? 'all';
$limit          = $_GET['limit'] ?? '10';

// VALIDATION: only allow whitelisted limit values (prevents arbitrary input,
// e.g. LIMIT 99999 or SQL injection via this parameter)
$allowedLimits = ['5', '10', '15', '20'];
if (!in_array($limit, $allowedLimits)) {
    $limit = '10';
}

/* ===============================
   FETCH CLUB OPTIONS
   Populates the "Club" dropdown filter, A–Z
================================ */
$clubStmt = $pdo->prepare("
    SELECT Club_ID, clubName
    FROM club
    ORDER BY clubName ASC
");
$clubStmt->execute();
$clubs = $clubStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   FILTER SQL
   Optional WHERE clause to restrict results to one club
================================ */
$where  = [];
$params = [];

if ($selectedClubID !== 'all' && $selectedClubID !== '') {
    $where[]  = "e.Club_ID = ?";
    $params[] = $selectedClubID;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/* ===============================
   POPULAR EVENTS DATA
   For each event, count how many registrations it has.
   LEFT JOINs ensure events with 0 registrations still appear
   (totalRegistrations = 0), sorted highest -> lowest.
   LIMIT is bound separately below as an INTEGER (PDO requires
   explicit type binding for LIMIT to work correctly).
================================ */
$popularStmt = $pdo->prepare("
    SELECT
        e.eventTitle,
        c.clubName,
        COUNT(er.EventRegistration_ID) AS totalRegistrations
    FROM event e
    LEFT JOIN event_registration er ON e.Event_ID = er.Event_ID
    LEFT JOIN club c                ON e.Club_ID  = c.Club_ID
    $whereSQL
    GROUP BY e.Event_ID, e.eventTitle, c.clubName
    ORDER BY totalRegistrations DESC, e.eventTitle ASC
    LIMIT ?
");

$params[] = (int)$limit;

// Bind each parameter individually so the LIMIT value is sent as
// PDO::PARAM_INT (the last param) while all filter values are strings
foreach ($params as $i => $value) {
    $type = ($i === count($params) - 1) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $popularStmt->bindValue($i + 1, $value, $type);
}

$popularStmt->execute();
$popularRows = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the Chart.js doughnut chart
$chartLabels        = array_column($popularRows, 'eventTitle');
$chartData          = array_map('intval', array_column($popularRows, 'totalRegistrations'));
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
    <!-- Page-specific styles (amber/gold theme, doughnut chart, ranking table) -->
    <link rel="stylesheet" href="../STYLE/CSS/Module_3/popular_events_chart_CSS.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid dashboard-page event-mgmt-page">

            <!-- PAGE HEADER -->
            <div class="d-flex justify-content-between align-items-center participation-report-header">
                <div>
                    <h1 class="participation-report-title">Popular Events</h1>
                </div>
                <a href="event_management_dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back
                </a>
            </div>

            <!-- ===================================================
                 FILTERS
                 - Club dropdown: limit results to one club
                 - "Show Top" dropdown: choose 5/10/15/20 events
                 Both auto-submit on change
            ==================================================== -->
            <form method="GET" id="filterForm" class="participation-filter-card">
                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label>Club</label>
                        <select name="club_id" class="form-select"
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
                        <select name="limit" class="form-select"
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

                <!-- ===================================================
                     DOUGHNUT CHART CARD
                     Shows registration share per event, with a
                     center stat overlay showing the grand total,
                     plus a custom legend built in JS below
                ==================================================== -->
                <div class="participation-chart-card popular-chart-card">

                    <h5>Most Popular Events by Registration</h5>
                    <p class="popular-chart-subtitle">
                        Showing top <?= e($limit) ?> events by total registrations
                    </p>

                    <!-- Doughnut + center stat -->
                    <div class="popular-chart-wrapper">
                        <canvas id="popularEventsChart"></canvas>
                        <div class="popular-chart-center-stat">
                            <span class="stat-number"><?= $totalRegistrations ?></span>
                            <span class="stat-label">Total<br>Registrations</span>
                        </div>
                    </div>

                    <!-- Custom legend (populated dynamically by JS, not Chart.js default) -->
                    <div class="popular-chart-legend" id="popularLegend"></div>

                </div>

                <!-- ===================================================
                     RANKING TABLE CARD
                     Same data as the chart, with medal-style rank
                     badges for the top 3 (gold/silver/bronze)
                ==================================================== -->
                <div class="dashboard-table-card popular-table-card">

                    <h5>Ranking</h5>
                    <p class="popular-table-subtitle">Sorted by registration count</p>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Event</th>
                                    <th>Club</th>
                                    <th class="text-end">Registrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach ($popularRows as $row): ?>
                                <?php
                                    // Assign special badge styles for ranks 1-3 (gold/silver/bronze),
                                    // everything else gets a plain "rank-other" badge
                                    $badgeClass = match($rank) {
                                        1 => 'rank-badge rank-1',
                                        2 => 'rank-badge rank-2',
                                        3 => 'rank-badge rank-3',
                                        default => 'rank-badge rank-other'
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <span class="<?= $badgeClass ?>">#<?= e($rank++) ?></span>
                                    </td>
                                    <td><?= e($row['eventTitle']) ?></td>
                                    <td>
                                        <span class="club-pill"><?= e($row['clubName']) ?></span>
                                    </td>
                                    <td><?= e($row['totalRegistrations']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

            <?php else: ?>

                <!-- EMPTY STATE: no registrations found for the current filter -->
                <div class="dashboard-table-card mb-4 text-center py-5">
                    <i class="fa-solid fa-circle-info fa-2x mb-3 text-muted"></i>
                    <h5>No registration data found</h5>
                    <p class="text-muted mb-0">No events have registrations yet for the selected filter.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalRegistrations > 0): ?>
<!-- ===================================================
     CHART.JS SETUP — Doughnut Chart + Custom Legend
     popularLabels / popularData come straight from PHP
==================================================== -->
<script>
const popularLabels = <?= json_encode($chartLabels) ?>;
const popularData   = <?= json_encode($chartData) ?>;

// Amber/gold palette — warm, cohesive, professional
// Each event slice gets a different shade (up to 20 predefined colors)
const sliceColors = [
    '#f59e0b', '#fbbf24', '#d97706', '#fb923c',
    '#fcd34d', '#ea580c', '#facc15', '#f97316',
    '#fde68a', '#eab308', '#fdba74', '#ca8a04',
    '#ffedd5', '#c2410c', '#fed7aa', '#92400e',
    '#fef3c7', '#a16207', '#fef9c3', '#78350f'
];

// Only use as many colors as there are slices/events
const colors = sliceColors.slice(0, popularLabels.length);

// Build chart
new Chart(document.getElementById('popularEventsChart'), {
    type: 'doughnut',
    data: {
        labels: popularLabels,
        datasets: [{
            label: 'Registrations',
            data: popularData,
            backgroundColor: colors,
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',   // controls the thickness of the doughnut ring (and size of center hole for the stat overlay)
        plugins: {
            legend: { display: false }, // default legend hidden — custom legend built below instead
            tooltip: {
                callbacks: {
                    // Tooltip shows both raw count AND percentage of total registrations
                    label: function(context) {
                        const pct = ((context.raw / <?= $totalRegistrations ?>) * 100).toFixed(1);
                        return ` ${context.raw} registrations (${pct}%)`;
                    }
                }
            }
        }
    }
});

// Build custom legend
// Loops through each event and creates a colored dot + label,
// matching the colors used in the doughnut chart above
const legendEl = document.getElementById('popularLegend');
popularLabels.forEach((label, i) => {
    const item = document.createElement('div');
    item.className = 'popular-chart-legend-item';
    item.innerHTML = `
        <span class="popular-chart-legend-dot" style="background:${colors[i]}"></span>
        <span>${label}</span>
    `;
    legendEl.appendChild(item);
});
</script>
<?php endif; ?>

</body>
</html>