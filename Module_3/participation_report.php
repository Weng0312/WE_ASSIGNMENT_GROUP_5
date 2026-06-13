<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$club_id = $_GET['club_id'] ?? 'all';

$sql = "SELECT e.eventTitle, COUNT(er.EventRegistration_ID) as regCount 
        FROM event e 
        LEFT JOIN event_registration er ON e.Event_ID = er.Event_ID";
$params = [];

if ($club_id !== 'all') {
    $sql .= " WHERE e.Club_ID = :club_id";
    $params['club_id'] = $club_id;
}
$sql .= " GROUP BY e.Event_ID";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module_4/participation_attendance_dashboard_CSS.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../sidebar.php'; ?>
    <div id="content" class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Student Participation Report</h3>
            <a href="event_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>

        <form method="GET" class="filter-form mb-4">
            <select name="club_id" class="form-select w-25" onchange="this.form.submit()">
                <option value="all">All Clubs</option>
                <?php 
                $clubs = $pdo->query("SELECT Club_ID, clubName FROM club")->fetchAll();
                foreach ($clubs as $club) {
                    $selected = ($club_id == $club['Club_ID']) ? 'selected' : '';
                    echo "<option value='{$club['Club_ID']}' $selected>{$club['clubName']}</option>";
                }
                ?>
            </select>
        </form>

        <div class="chart-container">
            <canvas id="reportChart"></canvas>
        </div>
    </div>

    <script>
        const data = <?= json_encode($data) ?>;
        new Chart(document.getElementById('reportChart'), {
            type: 'bar',
            data: { 
                labels: data.map(item => item.eventTitle), 
                datasets: [{ 
                    label: 'Number of Registrations', 
                    data: data.map(item => item.regCount), 
                    backgroundColor: '#0d6efd' 
                }] 
            },
            options: { maintainAspectRatio: false, responsive: true }
        });
    </script>
</body>
</html>