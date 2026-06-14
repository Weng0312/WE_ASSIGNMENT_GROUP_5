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
   Read the date range filter from the URL (?start_date=...&end_date=...)
================================ */
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date']   ?? '';

/* ===============================
   FILTER SQL
   Build extra conditions for the JOIN below based on the date range
================================ */
$joinConditions = [];
$params = [];

if (!empty($startDate)) {
    $joinConditions[] = "e.eventDate >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $joinConditions[] = "e.eventDate <= ?";
    $params[] = $endDate;
}

// SAFETY CHECK: If user enters dates backwards (start > end), swap them
// so the displayed "Filtered: ... -> ..." badge always reads correctly
if (!empty($startDate) && !empty($endDate) && $startDate > $endDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

// NOTE: These conditions are appended to the LEFT JOIN (not a WHERE clause).
// This keeps clubs with ZERO events in the date range still visible
// (LEFT JOIN preserves them, WHERE would filter them out)
$joinSQL = !empty($joinConditions) ? ' AND ' . implode(' AND ', $joinConditions) : '';

/* ===============================
   NUMBER OF EVENTS PER CLUB
   For every club, count how many events fall in the selected date range.
   LEFT JOIN ensures clubs with 0 events still appear in the results
   (with totalEvents = 0), sorted highest -> lowest
================================ */
$clubEventStmt = $pdo->prepare("
    SELECT
        c.clubName,
        COUNT(e.Event_ID) AS totalEvents
    FROM club c
    LEFT JOIN event e
        ON c.Club_ID = e.Club_ID
        $joinSQL
    GROUP BY c.Club_ID, c.clubName
    ORDER BY totalEvents DESC, c.clubName ASC
");

$clubEventStmt->execute($params);
$clubEventRows = $clubEventStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js (horizontal bar chart)
$chartLabels = array_column($clubEventRows, 'clubName');
$chartData   = array_map('intval', array_column($clubEventRows, 'totalEvents'));
$totalEvents = array_sum($chartData);

// Used to scale the small inline progress bars in the table (see nec-event-count-bar)
$maxCount    = !empty($chartData) ? max($chartData) : 1;

// Flag used to show/hide the "Clear filter" button and the active-filter badge
$isFiltered = !empty($startDate) || !empty($endDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events Organized by Each Club</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css?v=18">
    <link rel="stylesheet" href="../STYLE/CSS/Module_3/event_management_dashboard_CSS.css?v=1">
    <!-- Page-specific styles (navy/slate theme, filter card, bar chart, table) -->
    <link rel="stylesheet" href="../STYLE/CSS/Module_3/number_events_club_CSS.css?v=9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">
    <?php include '../sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid dashboard-page event-mgmt-page">

            <!-- HEADER -->
            <div class="nec-header">
                <h1 class="nec-title">
                    <span>Event Management</span>
                    Events Organized by Each Club
                </h1>
                <a href="event_management_dashboard.php" class="nec-back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- ===================================================
                 FILTER CARD
                 Date range picker (Start Date / End Date).
                 Submitting reloads page with ?start_date=...&end_date=...
                 "Clear" link (only shown when filtered) resets to "?"
            ==================================================== -->
            <form method="GET" id="filterForm" class="nec-filter-card">
                <div class="row g-3 align-items-end">

                    <div class="col-auto">
                        <label class="filter-label">Start Date</label>
                        <input type="date"
                               name="start_date"
                               class="form-control"
                               value="<?= e($startDate) ?>">
                    </div>

                    <div class="col-auto d-flex align-items-end nec-filter-divider pb-1">
                        <i class="fa-solid fa-arrow-right-long"></i>
                    </div>

                    <div class="col-auto">
                        <label class="filter-label">End Date</label>
                        <input type="date"
                               name="end_date"
                               class="form-control"
                               value="<?= e($endDate) ?>">
                    </div>

                    <div class="col-auto d-flex align-items-end gap-2">
                        <button type="submit" class="nec-search-btn">
                            <i class="fa-solid fa-magnifying-glass"></i> Apply Filter
                        </button>
                        <?php if ($isFiltered): ?>
                            <a href="?" class="nec-clear-btn">
                                <i class="fa-solid fa-xmark"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Active filter badge: shows the currently applied date range -->
                <?php if ($isFiltered): ?>
                    <div>
                        <span class="nec-active-filter">
                            <i class="fa-solid fa-circle-check"></i>
                            Filtered:
                            <?= !empty($startDate) ? e(date('d M Y', strtotime($startDate))) : '—' ?>
                            &nbsp;→&nbsp;
                            <?= !empty($endDate)   ? e(date('d M Y', strtotime($endDate)))   : '—' ?>
                        </span>
                    </div>
                <?php endif; ?>
            </form>

            <?php if ($totalEvents > 0): ?>

                <!-- ===================================================
                     HORIZONTAL BAR CHART
                     One bar per club, sorted by event count (Chart.js)
                ==================================================== -->
                <div class="nec-chart-card">
                    <h5><i class="fa-solid fa-chart-bar me-2"></i>Total Events per Club</h5>
                    <div class="nec-chart-wrap">
                        <canvas id="clubEventChart"></canvas>
                    </div>
                </div>

                <!-- ===================================================
                     CLUB BREAKDOWN TABLE
                     Same data as the chart, plus a small visual
                     "progress bar" next to each event count
                     (.nec-event-count-bar width is scaled by $maxCount)
                ==================================================== -->
                <div class="nec-table-card">
                    <h5><i class="fa-solid fa-table me-2"></i>Club Breakdown</h5>
                    <div class="table-responsive">
                        <table class="nec-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Club Name</th>
                                    <th>Total Events</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($clubEventRows as $row): ?>
                                <tr>
                                    <td><span class="nec-rank-badge"><?= e($no++) ?></span></td>
                                    <td><?= e($row['clubName']) ?></td>
                                    <td>
                                        <span class="nec-event-count">
                                            <!-- Inline bar width: scaled relative to the highest count (max 80px) -->
                                            <span class="nec-event-count-bar"
                                                  style="width:<?= max(4, round(($row['totalEvents'] / $maxCount) * 80)) ?>px"></span>
                                            <?= e($row['totalEvents']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>

                <!-- EMPTY STATE: different message depending on whether a filter is active -->
                <div class="nec-empty">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-3"></i>
                    <h5>No data found</h5>
                    <p><?= $isFiltered ? 'No events match the selected date range. Try adjusting or clearing the filter.' : 'No clubs or events have been recorded yet.' ?></p>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<?php if ($totalEvents > 0): ?>
<!-- ===================================================
     CHART.JS SETUP — Horizontal Bar Chart
     clubLabels / clubData come straight from PHP
==================================================== -->
<script>
const clubLabels = <?= json_encode($chartLabels) ?>;
const clubData   = <?= json_encode($chartData) ?>;

// Classic admin navy palette — bars get darker/more opaque the higher the count
const maxVal = Math.max(...clubData, 1);
const barColors = clubData.map(v => {
    const intensity = 0.35 + 0.65 * (v / maxVal);
    return `rgba(74, 127, 181, ${intensity.toFixed(2)})`;
});

new Chart(document.getElementById('clubEventChart'), {
    type: 'bar',
    data: {
        labels: clubLabels,
        datasets: [{
            label: 'Total Events',
            data: clubData,
            backgroundColor: barColors,
            borderRadius: 5,
            barPercentage: 0.55,
            categoryPercentage: 0.75
        }]
    },
    options: {
        indexAxis: 'y',           // makes this a HORIZONTAL bar chart
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                // Custom navy tooltip with singular/plural "event(s)" wording
                backgroundColor: '#1e3a5f',
                titleColor: '#a8c4e0',
                bodyColor: '#ffffff',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => '  ' + ctx.raw + ' event' + (ctx.raw !== 1 ? 's' : '')
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                // Adds 1 extra unit of headroom so the highest bar doesn't touch the edge
                suggestedMax: Math.max(...clubData, 1) + 1,
                ticks: { precision: 0, stepSize: 1, color: '#64748b', font: { size: 12 } },
                grid: { color: '#eef1f6' },
                border: { color: '#dde4ed' }
            },
            y: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: '#334155', font: { size: 13 }, padding: 8 }
            }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>