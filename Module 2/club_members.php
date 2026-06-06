<?php
session_start();
$successMessage = $_SESSION['successMessage'] ?? '';
$errorMessage = $_SESSION['errorMessage'] ?? '';

unset($_SESSION['successMessage']);
unset($_SESSION['errorMessage']);

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Helper function
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Get Club ID from URL first, then session, then auto-detect
$myClubID = $_GET['club_id'] ?? $_SESSION['Club_ID'] ?? $_SESSION['club_id'] ?? 0;
$myClubID = (int) $myClubID;

// If still 0, check committee_club_id (same as attendance_management.php)
if ($myClubID == 0 && !empty($_SESSION['committee_club_id'])) {
    $myClubID = (int) $_SESSION['committee_club_id'];
} 
// If still 0, auto-detect from user's club membership
elseif ($myClubID == 0 && isset($_SESSION['user_id'])) {
    try {
        $autoDetectQuery = "
            SELECT Club_ID FROM club_membership 
            WHERE User_ID = ? AND membershipStatus = 'Active'
            LIMIT 1
        ";
        $autoDetectStmt = $pdo->prepare($autoDetectQuery);
        $autoDetectStmt->execute([$_SESSION['user_id']]);
        $autoDetectResult = $autoDetectStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($autoDetectResult) {
            $myClubID = (int) $autoDetectResult['Club_ID'];
        }
    } catch (PDOException $e) {
        // Silent fail
    }
}

$myClubID = (int) $myClubID;

// Fetch club name
$clubName = 'Unknown Club';
if ($myClubID > 0) {
    try {
        $clubStmt = $pdo->prepare("SELECT clubName FROM club WHERE Club_ID = ?");
        $clubStmt->execute([$myClubID]);
        $clubInfo = $clubStmt->fetch(PDO::FETCH_ASSOC);
        if ($clubInfo) {
            $clubName = $clubInfo['clubName'];
        }
    } catch (PDOException $e) {
        // Use default
    }
}

try {
    // FIXED DIRECT ISOLATION QUERY:
    // Matches ONLY active members registered under the specific Club_ID.
    $query = "
        SELECT 
            cm.Membership_ID,
            cm.joinDate,
            cm.membershipStatus,
            cm.membershipRole,
            u.userName,
            u.userEmail,
            u.userPhoneNumber
        FROM club_membership cm
        INNER JOIN user u ON cm.User_ID = u.User_ID
        WHERE cm.Club_ID = ? AND cm.membershipStatus = 'Active'
        ORDER BY 
            CASE 
                WHEN LOWER(cm.membershipRole) LIKE '%president%' THEN 1
                WHEN LOWER(cm.membershipRole) LIKE '%vice%' THEN 2
                WHEN LOWER(cm.membershipRole) LIKE '%treasurer%' THEN 3
                WHEN LOWER(cm.membershipRole) LIKE '%secretary%' THEN 4
                ELSE 5 
            END ASC, 
            u.userName ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$myClubID]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errorMessage = "Error fetching members list: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Members Directory - FK Student Club</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f7fb;
            color: #191c1e;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #content {
            padding: 2rem;
            width: 100%;
            margin-top: 70px;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #191c1e;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1rem;
            color: #6e7781;
            margin: 0;
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .members-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .members-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background-color: #fafbfc;
        }

        .members-card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #191c1e;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .members-card-header h2 i {
            color: #0969da;
        }

        .members-table-wrapper {
            overflow-x: auto;
        }

        .members-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .members-table thead {
            background-color: #f3f4f6;
            border-bottom: 2px solid #e5e7eb;
        }

        .members-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
        }

        .members-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }

        .members-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .members-table tbody tr:last-child {
            border-bottom: none;
        }

        .members-table td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
        }

        .member-name {
            font-weight: 600;
            color: #191c1e;
            font-size: 0.95rem;
        }

        .member-contact {
            color: #6e7781;
            font-size: 0.875rem;
        }

        .member-contact p {
            margin: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .member-contact i {
            color: #8b949e;
            width: 1.2rem;
        }

        .badge-role {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-role.officer {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-role.member {
            background-color: #f3e8ff;
            color: #6b21a8;
        }

        .badge-role i {
            margin-right: 0.3rem;
        }

        .badge-status {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: #dcfce7;
            color: #166534;
            white-space: nowrap;
        }

        .badge-status i {
            margin-right: 0.3rem;
        }

        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            color: #6e7781;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d9e0;
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state p {
            font-size: 1rem;
            margin: 0;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .stat-card i {
            font-size: 1.75rem;
            color: #0969da;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #191c1e;
            margin: 0.5rem 0;
        }

        .stat-card .stat-label {
            font-size: 0.875rem;
            color: #6e7781;
            margin: 0;
        }

        .row-number {
            font-family: 'Courier New', monospace;
            color: #8b949e;
            font-weight: 500;
        }

        footer {
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 2rem;
            text-align: center;
            color: #6e7781;
            font-size: 0.875rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            #content {
                padding: 1rem;
                margin-top: 60px;
            }

            .page-header h1 {
                font-size: 1.75rem;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .members-table th,
            .members-table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../topbar.php'; ?>

    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="bi bi-people-fill"></i> Club Members Directory</h1>
                    <p>Manage and view all active members of <strong><?php echo e($clubName); ?></strong></p>
                </div>

                <!-- Alerts -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <?php echo e($errorMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo e($successMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <?php if (!empty($members)): ?>
                    <div class="stats-cards">
                        <div class="stat-card">
                            <i class="bi bi-people"></i>
                            <div class="stat-number"><?php echo count($members); ?></div>
                            <p class="stat-label">Total Members</p>
                        </div>
                        <div class="stat-card">
                            <i class="bi bi-shield-check"></i>
                            <div class="stat-number">
                                <?php 
                                $officers = array_filter($members, function($m) {
                                    $role = strtolower($m['membershipRole']);
                                    return strpos($role, 'president') !== false || 
                                           strpos($role, 'vice') !== false || 
                                           strpos($role, 'treasurer') !== false || 
                                           strpos($role, 'secretary') !== false;
                                });
                                echo count($officers);
                                ?>
                            </div>
                            <p class="stat-label">Committee Members</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Members Table Card -->
                <div class="members-card">
                    <div class="members-card-header">
                        <h2>
                            <i class="bi bi-list-ul"></i>
                            Active Members List
                        </h2>
                    </div>

                    <div class="members-table-wrapper">
                        <table class="members-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Member Name</th>
                                    <th>Contact Information</th>
                                    <th>Position</th>
                                    <th style="width: 120px;">Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($members)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p>No active members found for this club.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $counter = 1;
                                    foreach ($members as $member):
                                        $displayRole = $member['membershipRole'] ?? 'Member';
                                        $lowerRole = strtolower($displayRole);
                                        
                                        $isOfficer = strpos($lowerRole, 'president') !== false || 
                                                   strpos($lowerRole, 'vice') !== false || 
                                                   strpos($lowerRole, 'treasurer') !== false || 
                                                   strpos($lowerRole, 'secretary') !== false;
                                        
                                        $badgeClass = $isOfficer ? 'officer' : 'member';
                                    ?>
                                        <tr>
                                            <td class="row-number"><?php echo sprintf("%02d", $counter++); ?></td>
                                            <td>
                                                <div class="member-name">
                                                    <?php echo e($member['userName']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="member-contact">
                                                    <p>
                                                        <i class="bi bi-envelope"></i>
                                                        <?php echo e($member['userEmail']); ?>
                                                    </p>
                                                    <p>
                                                        <i class="bi bi-telephone"></i>
                                                        <?php echo e($member['userPhoneNumber'] ?? 'N/A'); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-role <?php echo $badgeClass; ?>">
                                                    <i class="bi bi-person-badge"></i>
                                                    <?php echo e($displayRole); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge-status">
                                                    <i class="bi bi-check-circle"></i>
                                                    <?php echo e($member['membershipStatus']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <footer>
                <p>&copy; 2026 FK Student Club Management System. All rights reserved.</p>
            </footer>

        </div>
    </div>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>