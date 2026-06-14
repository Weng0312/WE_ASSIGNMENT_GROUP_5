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

// UI HELPER: Returns 'selected' attribute string if two values match
// Used to keep the dropdown filter showing the user's current choice
function selected($value1, $value2)
{
    return ((string)$value1 === (string)$value2) ? 'selected' : '';
}

/* ===============================
   FILTER VALUES
   Read filter selections from the URL query string (?club_id=...&year=...)
   Defaults to 'all' if not provided
================================ */
$selectedClubID = $_GET['club_id'] ?? 'all';
$selectedYear   = $_GET['year'] ?? 'all';

/* ===============================
   FETCH CLUB OPTIONS
   Populates the "Club" dropdown filter with every club, A–Z
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
   Gets every distinct year that has at least one event,
   used to populate the "Year" dropdown filter
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

// VALIDATION: If the year in the URL doesn't actually exist in the
// database, ignore it and fall back to "All Years"
if ($selectedYear !== 'all' && !in_array((int)$selectedYear, array_map('intval', $validYears))) {
    $selectedYear = 'all';
}

/* ===============================
   FILTER SQL
   Dynamically build WHERE clause based on which filters
   the user has selected (club, year, or both)
================================ */
$where = [];
$params = [];

if ($selectedClubID !== 'all' && $selectedClubID !== '') {
    $where[] = "e.Club_ID = ?";
    $params[] = $selectedClubID;
}

if ($selectedYear !== 'all' && $selectedYear !== '') {
    $where[] = "YEAR(e.eventDate) = ?";
    $params[] = (int)$selectedYear;
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* ===============================
   MONTHLY EVENT COUNT DATA
   Main query: counts how many events happened in each month,
   grouped and ordered chronologically (oldest to newest)
   - monthKey   -> "2025-01" (used for sorting)
   - monthLabel -> "Jan 2025" (used for display)
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

// Split the results into two arrays for Chart.js:
// $chartLabels = X-axis labels (months), $chartData = Y-axis values (event counts)
$chartLabels = array_column($monthlyRows, 'monthLabel');
$chartData   = array_map('intval', array_column($monthlyRows, 'totalEvents'));

// Used to decide whether to show the chart/table or the "no data" message
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

    <!-- Page-specific styles (filter card, line chart card, breakdown table) -->
    <link rel="stylesheet" href="../STYLE/CSS/Module_3/monthly_events_trend_chart_CSS.css?v=3">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart.js library used to draw the line chart below -->
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

            <!-- ===================================================
                 FILTER SECTION
                 Two dropdowns: Club and Year.
                 Changing either dropdown auto-submits the form
                 (onchange -> form.submit()), reloading the page
                 with the new filter applied via GET parameters.
            ==================================================== -->
            <form method="GET" id="filterForm" class="participation-filter-card">

                <div class="row g-3 align-items-end">

                    <!-- Club filter dropdown -->
                    <div class="col-6 col-md-4">
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

                    <!-- Year filter dropdown -->
                    <div class="col-6 col-md-4">
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

                <!-- ===================================================
                     LINE CHART SECTION
                     Shows events-per-month as a line chart (Chart.js).
                     Data is injected via PHP -> JS below (json_encode).
                ==================================================== -->
                <div class="participation-chart-card mb-4 trend-chart-card">

                    <div class="accent-bar"></div>

                    <h5>Number of Events Organized per Month</h5>
                    <p class="chart-subtitle">Total events recorded across the selected period</p>

                    <div class="chart-wrapper">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>

                </div>

                <!-- ===================================================
                     BREAKDOWN TABLE SECTION
                     Same data as the chart, shown as a simple table
                     with a running row number (No.)
                ==================================================== -->
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

                <!-- EMPTY STATE: shown when no events match the current filters -->
                <div class="dashboard-table-card mb-4 text-center">
                    <i class="fa-solid fa-circle-info fa-2x mb-3"></i>

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

<!-- ===================================================
     CHART.JS SETUP — Line Chart
     monthlyLabels / monthlyData come straight from PHP
     (the same arrays used for the table above)
==================================================== -->
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
            borderColor: '#0d9488',          // teal line color (Module 3 theme)
            backgroundColor: 'rgba(13, 148, 136, 0.08)', // light fill under the line
            borderWidth: 2.5,
            tension: 0.4,                    // makes the line curved/smooth
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#0d9488',
            pointBorderWidth: 2,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,   // allows chart to fill the .chart-wrapper height
        resizeDelay: 200,
        layout: {
            padding: {
                top: 10,
                right: 10,
                left: 0,
                bottom: 0
            }
        },
        plugins: {
            legend: {
                display: false   // hide default legend (only one dataset, title already explains it)
            },
            tooltip: {
                // Custom dark tooltip styling
                backgroundColor: '#0f172a',
                titleColor: '#ffffff',
                bodyColor: '#e2e8f0',
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    // Custom tooltip text: "Events: 5" instead of default "Events Organized: 5"
                    label: function(context) {
                        return 'Events: ' + context.raw;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false   // cleaner look without vertical grid lines
                },
                ticks: {
                    color: '#94a3b8',
                    font: {
                        size: 12
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'  // light horizontal grid lines
                },
                ticks: {
                    precision: 0,     // whole numbers only (can't have half an event)
                    color: '#94a3b8',
                    font: {
                        size: 12
                    },
                    padding: 8
                }
            }
        }
    }
});
</script>

<?php endif; ?>

</body>
</html>