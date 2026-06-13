<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Ensure only Administrators can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module_1/index.php");
    exit();
}

/** * Data Fetching for Insights
 */
// 1. Number of events organized by each club (Bar Chart)
$clubEvents = $pdo->query("SELECT c.clubName, COUNT(e.Event_ID) as eventCount FROM club c LEFT JOIN event e ON c.Club_ID = e.Club_ID GROUP BY c.Club_ID")->fetchAll(PDO::FETCH_ASSOC);

// 2. Popular events based on registration count (Doughnut Chart)
$popularEvents = $pdo->query("SELECT e.eventTitle, COUNT(er.EventRegistration_ID) as regCount FROM event e LEFT JOIN event_registration er ON e.Event_ID = er.Event_ID GROUP BY e.Event_ID ORDER BY regCount DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 3. Monthly event activity trends (Line Chart)
$monthlyTrends = $pdo->query("SELECT DATE_FORMAT(eventDate, '%M') as monthName, COUNT(*) as eventCount FROM event GROUP BY MONTH(eventDate) ORDER BY MONTH(eventDate)")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-wrapper { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    
    <div id="content" class="container-fluid py-4">
        <h2 class="fw-bold mb-4">Event Management Dashboard</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="chart-wrapper">
                    <h5>Events Organized by Club</h5>
                    <canvas id="clubChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="chart-wrapper">
                    <h5>Top 5 Popular Events</h5>
                    <canvas id="popularChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-12">
                <div class="chart-wrapper">
                    <h5>Monthly Event Activity Trends</h5>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data from PHP
        const clubData = <?= json_encode($clubEvents) ?>;
        const popData = <?= json_encode($popularEvents) ?>;
        const trendData = <?= json_encode($monthlyTrends) ?>;

        // 1. Bar Chart: Events by Club
        new Chart(document.getElementById('clubChart'), {
            type: 'bar',
            data: { labels: clubData.map(c => c.clubName), datasets: [{ label: 'Events Organized', data: clubData.map(c => c.eventCount), backgroundColor: '#0d6efd' }] }
        });

        // 2. Doughnut Chart: Popular Events
        new Chart(document.getElementById('popularChart'), {
            type: 'doughnut',
            data: { labels: popData.map(e => e.eventTitle), datasets: [{ data: popData.map(e => e.regCount), backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'] }] }
        });

        // 3. Line Chart: Monthly Trends
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: { labels: trendData.map(t => t.monthName), datasets: [{ label: 'Total Events', data: trendData.map(t => t.eventCount), borderColor: '#28a745', tension: 0.1, fill: true }] }
        });
    </script>
</body>
</html>