<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: ../Module_1/index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: event_management.php");
    exit();
}

$successMessage = '';
$errorMessage = '';

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['registration_id'])) {
    $action = $_POST['action'];
    $registrationId = $_POST['registration_id'];

    try {
        if ($action === 'approve') {
            $updateStmt = $pdo->prepare("
                UPDATE event_registration 
                SET eventRegistrationStatus = 'Approved'
                WHERE EventRegistration_ID = ? AND Event_ID = ?
            ");
            $updateStmt->execute([$registrationId, $id]);
            $successMessage = "Registration approved successfully!";
        } elseif ($action === 'reject') {
            $updateStmt = $pdo->prepare("
                UPDATE event_registration 
                SET eventRegistrationStatus = 'Rejected'
                WHERE EventRegistration_ID = ? AND Event_ID = ?
            ");
            $updateStmt->execute([$registrationId, $id]);
            $successMessage = "Registration rejected successfully!";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error updating registration: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT 
        e.*,
        c.clubName
    FROM event e
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    WHERE e.Event_ID = ?
");

$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: event_management.php");
    exit();
}

// Get all registrations with user details
$regStmt = $pdo->prepare("
    SELECT 
        er.EventRegistration_ID,
        er.eventRegistrationStatus,
        er.eventRegistrationDate,
        u.User_ID,
        u.userName,
        u.userEmail,
        u.userPhoneNumber
    FROM event_registration er
    INNER JOIN user u ON er.User_ID = u.User_ID
    WHERE er.Event_ID = ?
    ORDER BY 
        CASE 
            WHEN er.eventRegistrationStatus = 'Pending' THEN 1
            WHEN er.eventRegistrationStatus = 'Approved' THEN 2
            WHEN er.eventRegistrationStatus = 'Rejected' THEN 3
            ELSE 4
        END ASC,
        er.eventRegistrationDate ASC
");

$regStmt->execute([$id]);
$registrations = $regStmt->fetchAll(PDO::FETCH_ASSOC);

// Count registrations by status
$totalRegistered = count($registrations);
$pendingCount = count(array_filter($registrations, function($r) { return strtolower($r['eventRegistrationStatus']) === 'pending'; }));
$approvedCount = count(array_filter($registrations, function($r) { return strtolower($r['eventRegistrationStatus']) === 'approved'; }));
$rejectedCount = count(array_filter($registrations, function($r) { return strtolower($r['eventRegistrationStatus']) === 'rejected'; }));

$capacity = (int)$event['eventMaxParticipant'];
$percentage = $capacity > 0 ? min(100, round(($approvedCount / $capacity) * 100)) : 0;

$eventTimestamp = strtotime($event['eventDate']);
$currentTimestamp = strtotime(date('Y-m-d'));

if ($eventTimestamp == $currentTimestamp) {
    $statusBadge = '<span class="badge bg-success">Ongoing</span>';
} elseif ($eventTimestamp > $currentTimestamp) {
    $statusBadge = '<span class="badge bg-primary">Upcoming</span>';
} else {
    $statusBadge = '<span class="badge bg-secondary">Completed</span>';
}

// Helper function
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - FK Student Club</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

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
        }

        #wrapper {
            display: flex;
        }

        #content {
            width: 100%;
            padding: 2rem;
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

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn-custom {
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-edit {
            background-color: #0969da;
            color: #ffffff;
        }

        .btn-edit:hover {
            background-color: #0860ca;
        }

        .btn-back {
            background-color: #6b7280;
            color: #ffffff;
        }

        .btn-back:hover {
            background-color: #4b5563;
        }

        .detail-card {
            background: #ffffff;
            border-radius: 0.875rem;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
        }

        .info-label {
            font-size: 0.75rem;
            color: #6e7781;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #191c1e;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .section-divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 2rem 0;
        }

        .section-title {
            font-size: 1.125rem;
            color: #191c1e;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: #0969da;
            font-size: 1.5rem;
        }

        .description-box {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            line-height: 1.8;
            color: #6e7781;
        }

        .progress {
            height: 8px;
            border-radius: 999px;
            background-color: #d1d9e0;
        }

        .progress-bar {
            background: linear-gradient(90deg, #0969da 0%, #0860ca 100%);
        }

        .stat-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: center;
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: #191c1e;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6e7781;
            margin-top: 0.5rem;
        }

        .table-container {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .table-header {
            background-color: #f3f4f6;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-header h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #191c1e;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-header h3 i {
            color: #0969da;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .custom-table thead {
            background-color: #f9fafb;
        }

        .custom-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
            border-bottom: 2px solid #e5e7eb;
        }

        .custom-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .custom-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .user-name {
            font-weight: 600;
            color: #191c1e;
        }

        .user-email {
            font-size: 0.875rem;
            color: #6e7781;
            margin-top: 0.25rem;
        }

        .badge-status {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-sm-action {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-approve {
            background-color: #dcfce7;
            color: #166534;
        }

        .btn-approve:hover {
            background-color: #bbf7d0;
        }

        .btn-reject {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .btn-reject:hover {
            background-color: #fecaca;
        }

        .btn-approve:disabled,
        .btn-reject:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert i {
            font-size: 1.25rem;
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
            }

            .page-header h1 {
                font-size: 1.75rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .action-buttons-cell {
                flex-direction: column;
            }

            .btn-sm-action {
                width: 100%;
                justify-content: center;
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div class="page-header" style="margin-bottom: 0;">
                        <h1><i class="bi bi-calendar-event"></i> <?php echo e($event['eventTitle']); ?></h1>
                        <p>Manage event details and participant approvals</p>
                    </div>

                    <div class="action-buttons">
                        <a href="edit_event.php?id=<?php echo $event['Event_ID']; ?>" class="btn-custom btn-edit">
                            <i class="bi bi-pencil"></i> Edit Event
                        </a>
                        <a href="event_management.php" class="btn-custom btn-back">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo e($successMessage); ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo e($errorMessage); ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Event Details Card -->
                <div class="detail-card">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="bi bi-calendar3"></i> Event Date</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($event['eventDate'])); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label"><i class="bi bi-clock"></i> Start Time</div>
                            <div class="info-value"><?php echo date('h:i A', strtotime($event['eventStartTime'])); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label"><i class="bi bi-clock"></i> End Time</div>
                            <div class="info-value"><?php echo date('h:i A', strtotime($event['eventEndTime'])); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label"><i class="bi bi-geo-alt"></i> Venue</div>
                            <div class="info-value"><?php echo e($event['eventVenue']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label"><i class="bi bi-building"></i> Club</div>
                            <div class="info-value"><?php echo e($event['clubName']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label"><i class="bi bi-tag"></i> Status</div>
                            <div class="info-value"><?php echo $statusBadge; ?></div>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <div class="section-title">
                            <i class="bi bi-file-text"></i> Event Description
                        </div>
                        <div class="description-box">
                            <?php echo nl2br(e($event['eventDescription'])); ?>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <!-- Capacity Overview -->
                    <div>
                        <div class="section-title">
                            <i class="bi bi-people"></i> Capacity Overview
                        </div>

                        <div class="stat-boxes">
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $pendingCount; ?></div>
                                <div class="stat-label">Pending Approval</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $approvedCount; ?></div>
                                <div class="stat-label">Approved</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $rejectedCount; ?></div>
                                <div class="stat-label">Rejected</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $capacity; ?></div>
                                <div class="stat-label">Max Capacity</div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                                <span style="font-weight: 600; color: #191c1e;">Capacity Filled</span>
                                <span style="font-weight: 700; color: #191c1e;"><?php echo $approvedCount; ?> / <?php echo $capacity; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%;" role="progressbar"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            <i class="bi bi-list-check"></i>
                            Participant Registrations (<?php echo count($registrations); ?> total)
                        </h3>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Participant Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registration Date</th>
                                    <th class="text-center" style="width: 120px;">Status</th>
                                    <th class="text-end" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($registrations)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p>No registrations yet for this event.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $counter = 1;
                                    foreach ($registrations as $reg):
                                        $statusLower = strtolower($reg['eventRegistrationStatus']);
                                        
                                        if ($statusLower === 'pending') {
                                            $statusBadgeClass = 'badge-pending';
                                        } elseif ($statusLower === 'approved') {
                                            $statusBadgeClass = 'badge-approved';
                                        } else {
                                            $statusBadgeClass = 'badge-rejected';
                                        }
                                        
                                        $isPending = $statusLower === 'pending';
                                    ?>
                                        <tr>
                                            <td style="color: #8b949e; font-family: 'Courier New', monospace; font-weight: 500;">
                                                <?php echo sprintf("%02d", $counter++); ?>
                                            </td>
                                            <td>
                                                <div class="user-name"><?php echo e($reg['userName']); ?></div>
                                            </td>
                                            <td>
                                                <div class="user-email"><?php echo e($reg['userEmail']); ?></div>
                                            </td>
                                            <td>
                                                <div class="user-email"><?php echo e($reg['userPhoneNumber'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td>
                                                <div class="user-email"><?php echo date('d M Y', strtotime($reg['eventRegistrationDate'])); ?></div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge-status <?php echo $statusBadgeClass; ?>">
                                                    <?php echo e($reg['eventRegistrationStatus']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons-cell">
                                                    <?php if ($isPending): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['EventRegistration_ID']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn-sm-action btn-approve" title="Approve Registration">
                                                                <i class="bi bi-check-circle"></i> Approve
                                                            </button>
                                                        </form>

                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['EventRegistration_ID']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn-sm-action btn-reject" title="Reject Registration" onclick="return confirm('Are you sure you want to reject this registration?');">
                                                                <i class="bi bi-x-circle"></i> Reject
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color: #6e7781; font-size: 0.875rem;">—</span>
                                                    <?php endif; ?>
                                                </div>
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