<?php
// ==========================================
// [SESSION INITIALIZATION & COMMITTEE VALIDATION]
// ==========================================
session_start();
require_once __DIR__ . '/../db_connect.php';

$_SESSION['current_module'] = 'committee';

// Security Check: If not logged in or not a Committee member, redirect to login
if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$club_id = isset($_SESSION['committee_club_id']) ? (int)$_SESSION['committee_club_id'] : 0;

if ($club_id > 0) {
    // Fetch specifically for this committee_club_id
    $stmt = $pdo->prepare("
        SELECT cm.Club_ID, cm.membershipRole, c.clubName 
        FROM club_membership cm
        JOIN club c ON cm.Club_ID = c.Club_ID
        WHERE cm.User_ID = ? AND cm.Club_ID = ? AND cm.membershipStatus = 'Active'
        LIMIT 1
    ");
    $stmt->execute([$user_id, $club_id]);
    $committeeInfo = $stmt->fetch();
} else {
    // Fallback: get the first active club membership
    $stmt = $pdo->prepare("
        SELECT cm.Club_ID, cm.membershipRole, c.clubName 
        FROM club_membership cm
        JOIN club c ON cm.Club_ID = c.Club_ID
        WHERE cm.User_ID = ? AND cm.membershipStatus = 'Active'
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $committeeInfo = $stmt->fetch();
}

$club_id = $committeeInfo ? (int)$committeeInfo['Club_ID'] : 0;
$club_name = $committeeInfo ? $committeeInfo['clubName'] : 'No Club Assigned';
$committee_role = $committeeInfo ? $committeeInfo['membershipRole'] : 'Committee Member';

// Total Members of this club
$totalMembers = 0;
if ($club_id > 0) {
    $stmtMembers = $pdo->prepare("
        SELECT COUNT(*) 
        FROM club_membership 
        WHERE Club_ID = ? AND membershipStatus = 'Active'
    ");
    $stmtMembers->execute([$club_id]);
    $totalMembers = (int)$stmtMembers->fetchColumn();
}

// Total Events organized by this club
$totalEvents = 0;
if ($club_id > 0) {
    $stmtEvents = $pdo->prepare("
        SELECT COUNT(*) 
        FROM event 
        WHERE Club_ID = ?
    ");
    $stmtEvents->execute([$club_id]);
    $totalEvents = (int)$stmtEvents->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Dashboard - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module_1/adminDashboard_CSS.css">
</head>

<body>
    <?php include '../topbar.php'; ?>
    
    <div id="wrapper">
        <?php
            $_SESSION['current_module'] = 'committee';
            include '../sidebar.php';
        ?>

        <!-- ========================================== -->
        <!-- [COMMITTEE DASHBOARD INTERFACE] -->
        <!-- ========================================== -->
        <div id="content">

            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 fw-bold mb-0">Committee Dashboard</h1>
                    <span class="text-muted"><?php echo date('l, jS F Y'); ?></span>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-gradient-primary text-white shadow">
                            <div class="card-body py-4">
                                <h6 class="text-uppercase mb-1">Assigned Club</h6>
                                <h3 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($club_name); ?>"><?php echo htmlspecialchars($club_name); ?></h3>
                                <span class="badge bg-light text-primary fw-bold"><?php echo htmlspecialchars($committee_role); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-gradient-success text-white shadow">
                            <div class="card-body py-4">
                                <h6 class="text-uppercase mb-1">Total Club Members</h6>
                                <h2 class="display-6 fw-bold"><?php echo $totalMembers; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-gradient-info text-white shadow">
                            <div class="card-body py-4">
                                <h6 class="text-uppercase mb-1">Club Events</h6>
                                <h2 class="display-6 fw-bold"><?php echo $totalEvents; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-person-workspace text-info display-1 mb-4"></i>
                        <h2 class="fw-bold text-primary">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                        <p class="text-muted mx-auto" style="max-width: 600px;">Use this portal to manage your club
                            activities and memberships. You can view your club details and assign roles through the
                            sidebar navigation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>