<?php
session_start();

$_SESSION['current_module'] = 'admin';

require_once __DIR__ . '/../db_connect.php';

$_SESSION['current_module'] = 'admin';

/** @var PDO $pdo */

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: index.php");
    exit();
}

// --- 1. Fetch Summary Statistics ---
$stmt = $pdo->query("SELECT COUNT(*) FROM user WHERE userRole = 'Student'");
$totalStudents = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM club WHERE clubStatus = 'Active'");
$activeClubs = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM event WHERE eventDate >= CURDATE()");
$upcomingEvents = $stmt->fetchColumn();

// --- 2. Fetch Data for Charts ---
// Count administrators
$stmt = $pdo->query("SELECT COUNT(*) FROM user WHERE userRole = 'Administrator'");
$adminCount = (int)$stmt->fetchColumn();

// Count active committee members (distinct User_ID in active club membership where role is not 'Member')
$stmt = $pdo->query("SELECT COUNT(DISTINCT User_ID) FROM club_membership WHERE membershipStatus = 'Active' AND membershipRole != 'Member'");
$committeeCount = (int)$stmt->fetchColumn();

// Regular students (all students - committee students)
$regularStudentCount = max(0, $totalStudents - $committeeCount);

$roleData = [
    ['userRole' => 'Administrator', 'count' => $adminCount],
    ['userRole' => 'Student', 'count' => $regularStudentCount],
    ['userRole' => 'Committee', 'count' => $committeeCount]
];

$stmt = $pdo->query("
    SELECT u.userName, u.userRole, u.userEmail, cm.membershipRole
    FROM user u
    LEFT JOIN club_membership cm ON u.User_ID = cm.User_ID
    ORDER BY u.User_ID DESC LIMIT 5
");
$recentUsers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FK System</title>
    <link rel="stylesheet" href="../STYLE/CSS/Module1/adminDashboard_CSS.css">
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../topbar.php'; ?>
    
    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content">

            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Administrator Dashboard</h2>
                    <span class="text-muted"><?php echo date('l, jS F Y'); ?></span>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-gradient-primary text-white shadow">
                            <div class="card-body py-4">
                                <h6 class="text-uppercase mb-1">Total Students</h6>
                                <h2 class="display-6 fw-bold"><?php echo $totalStudents; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-gradient-success text-white shadow">
                            <div class="card-body py-4">
                                <h6 class="text-uppercase mb-1">Active Clubs</h6>
                                <h2 class="display-6 fw-bold"><?php echo $activeClubs; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-gradient-info text-white shadow">
                            <div class="card-body py-4">
                                <h6 class="text-uppercase mb-1">Upcoming Events</h6>
                                <h2 class="display-6 fw-bold"><?php echo $upcomingEvents; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 fw-bold text-primary">User Role Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="roleChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 fw-bold text-primary">Recent User Registrations</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Name</th>
                                                <th>Role</th>
                                                <th>Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <?php
                                                $isComm = ($user['userRole'] === 'Student' && !empty($user['membershipRole']) && $user['membershipRole'] !== 'Member');
                                                $displayRole = $isComm ? 'Student (Committee)' : $user['userRole'];
                                                ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold">
                                                        <?php echo htmlspecialchars($user['userName']); ?></td>
                                                    <td><span
                                                            class="badge bg-light text-dark border"><?php echo htmlspecialchars($displayRole); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['userEmail']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 fw-bold text-dark">System Overview</h6>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">You are logged in as a high-level administrator. You have
                                    full access to manage all system modules.</p>
                                <hr>
                                <div class="d-grid gap-2">
                                    <a href="manage_users.php" class="btn btn-sm btn-primary">Manage Accounts</a>
                                    <a href="register.php" class="btn btn-sm btn-outline-primary">Register New</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const roleLabels = <?php echo json_encode(array_column($roleData, 'userRole')); ?>;
        const roleCounts = <?php echo json_encode(array_column($roleData, 'count')); ?>;

        const ctx = document.getElementById('roleChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: roleLabels,
                datasets: [{
                    label: 'Number of Users',
                    data: roleCounts,
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.8)',
                        'rgba(28, 200, 138, 0.8)',
                        'rgba(54, 185, 204, 0.8)',
                        'rgba(246, 194, 62, 0.8)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>