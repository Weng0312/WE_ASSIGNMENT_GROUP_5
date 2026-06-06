<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Allow Student and Committee
if (
    !isset($_SESSION['user_id']) ||
    (
        $_SESSION['role'] !== 'Student' &&
        strpos($_SESSION['role'], 'Committee') === false
    )
) {
    header("Location: ../Module 1/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

// Cancel Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registration_id'])) {

    $registration_id = $_POST['cancel_registration_id'];

    $stmt = $pdo->prepare("
        UPDATE event_registration
        SET eventRegistrationStatus = 'Cancelled'
        WHERE EventRegistration_ID = ?
        AND User_ID = ?
    ");

    $stmt->execute([$registration_id, $user_id]);

    $message = "Registration cancelled successfully.";
    $messageType = "success";
}

// Read Registration Data
$stmt = $pdo->prepare("
    SELECT 
        er.EventRegistration_ID,
        er.eventRegistrationStatus,
        e.Event_ID,
        e.eventTitle,
        e.eventDescription,
        e.eventDate,
        e.eventStartTime,
        e.eventEndTime,
        c.clubName
    FROM event_registration er
    INNER JOIN event e 
        ON er.Event_ID = e.Event_ID
    LEFT JOIN club c 
        ON e.Club_ID = c.Club_ID
    WHERE er.User_ID = ?
    ORDER BY e.eventDate ASC
");

$stmt->execute([$user_id]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getStatusClass($status)
{
    $status = strtolower($status);

    if ($status == 'approved' || $status == 'confirmed') {
        return 'approved';
    }

    if ($status == 'pending' || $status == 'waiting list') {
        return 'pending';
    }

    if ($status == 'cancelled') {
        return 'cancelled';
    }

    return 'pending';
}

function getStatusIcon($status)
{
    $status = strtolower($status);
    
    if ($status == 'approved' || $status == 'confirmed') {
        return 'bi-check-circle-fill';
    }
    
    if ($status == 'pending' || $status == 'waiting list') {
        return 'bi-clock-history';
    }
    
    if ($status == 'cancelled') {
        return 'bi-x-circle-fill';
    }
    
    return 'bi-question-circle-fill';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Event Registrations - FK Student Club</title>

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
            padding: 2rem;
            width: 100%;
            margin-top: 70px;
        }

        .container-fluid {
            max-width: 1200px;
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

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .event-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        }

        .event-card-content {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
        }

        .event-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0969da 0%, #0860ca 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .event-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .event-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #191c1e;
        }

        .event-meta {
            font-size: 0.875rem;
            color: #6e7781;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-meta-item i {
            color: #8b949e;
        }

        .event-description {
            color: #6e7781;
            font-size: 0.875rem;
            line-height: 1.4;
            margin-top: 0.25rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.approved {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.cancelled {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .status-badge i {
            font-size: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .btn-cancel:hover {
            background-color: #fecaca;
        }

        .btn-register {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .btn-register:hover {
            background-color: #bfdbfe;
        }

        .empty-state {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            padding: 1.25rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
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

        @media (max-width: 768px) {
            #content {
                padding: 1rem;
                margin-top: 60px;
            }

            .page-header h1 {
                font-size: 1.75rem;
            }

            .event-card-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .event-icon {
                margin-bottom: 0.5rem;
            }

            .action-buttons {
                width: 100%;
            }

            .btn-action {
                flex: 1;
            }
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
                    <h1><i class="bi bi-calendar-check"></i> My Event Registrations</h1>
                    <p>View your registered events, check their status, and manage your registrations</p>
                </div>

                <!-- Success Alert -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo e($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <?php if (count($registrations) > 0): ?>
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($registrations); ?></div>
                            <div class="stat-label">Total Registrations</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                $approved = count(array_filter($registrations, function($r) {
                                    return strtolower($r['eventRegistrationStatus']) === 'approved' || 
                                           strtolower($r['eventRegistrationStatus']) === 'confirmed';
                                }));
                                echo $approved;
                                ?>
                            </div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                $pending = count(array_filter($registrations, function($r) {
                                    return strtolower($r['eventRegistrationStatus']) === 'pending' || 
                                           strtolower($r['eventRegistrationStatus']) === 'waiting list';
                                }));
                                echo $pending;
                                ?>
                            </div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Event Registrations -->
                <?php if (count($registrations) > 0): ?>
                    <?php foreach ($registrations as $registration): ?>
                        <?php
                        $statusClass = getStatusClass($registration['eventRegistrationStatus']);
                        $isCancelled = strtolower($registration['eventRegistrationStatus']) == 'cancelled';
                        $statusIcon = getStatusIcon($registration['eventRegistrationStatus']);
                        $eventDate = date('M d, Y', strtotime($registration['eventDate']));
                        $startTime = date('g:i A', strtotime($registration['eventStartTime']));
                        $endTime = date('g:i A', strtotime($registration['eventEndTime']));
                        ?>

                        <div class="event-card">
                            <div class="event-card-content">
                                <!-- Icon -->
                                <div class="event-icon">
                                    <i class="bi bi-calendar-event"></i>
                                </div>

                                <!-- Event Details -->
                                <div class="event-details">
                                    <div class="event-title">
                                        <?php echo e($registration['eventTitle']); ?>
                                    </div>
                                    <div class="event-description">
                                        <?php echo e($registration['eventDescription']); ?>
                                    </div>
                                    <div class="event-meta">
                                        <div class="event-meta-item">
                                            <i class="bi bi-calendar"></i>
                                            <?php echo $eventDate; ?>
                                        </div>
                                        <div class="event-meta-item">
                                            <i class="bi bi-clock"></i>
                                            <?php echo $startTime; ?> - <?php echo $endTime; ?>
                                        </div>
                                        <div class="event-meta-item">
                                            <i class="bi bi-building"></i>
                                            <?php echo e($registration['clubName']); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="status-badge <?php echo $statusClass; ?>">
                                    <i class="bi <?php echo $statusIcon; ?>"></i>
                                    <?php echo e($registration['eventRegistrationStatus']); ?>
                                </div>

                                <!-- Action Button -->
                                <div class="action-buttons">
                                    <?php if (!$isCancelled): ?>
                                        <form method="POST" style="display: contents;">
                                            <input 
                                                type="hidden" 
                                                name="cancel_registration_id"
                                                value="<?php echo $registration['EventRegistration_ID']; ?>"
                                            >
                                            <button 
                                                type="submit"
                                                class="btn-action btn-cancel"
                                                onclick="return confirm('Cancel this registration?')"
                                            >
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a 
                                            href="book_event.php?event_id=<?php echo $registration['Event_ID']; ?>" 
                                            class="btn-action btn-register"
                                        >
                                            <i class="bi bi-plus-circle"></i> Register
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>No event registrations found. <a href="event_list.php" style="color: #0969da; text-decoration: none;"><strong>Browse events</strong></a></p>
                    </div>
                <?php endif; ?>

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