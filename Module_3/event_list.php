<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Module_1/index.php");
    exit();
}

$userID = $_SESSION['user_id'];

$message = '';
$messageType = '';

// REGISTER EVENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {

    $eventID = $_POST['register_event_id'];
    
    try {

        // CHECK EVENT CAPACITY
        $capacityStmt = $pdo->prepare("
            SELECT 
                e.eventMaxParticipant,
                COUNT(CASE WHEN er.eventRegistrationStatus != 'Cancelled' THEN er.EventRegistration_ID END) AS totalRegistered
            FROM event e
            LEFT JOIN event_registration er 
                ON e.Event_ID = er.Event_ID
            WHERE e.Event_ID = ?
            GROUP BY e.Event_ID
        ");

        $capacityStmt->execute([$eventID]);
        $eventData = $capacityStmt->fetch(PDO::FETCH_ASSOC);

        $maxParticipant = (int)$eventData['eventMaxParticipant'];
        $totalRegistered = (int)$eventData['totalRegistered'];

        // IF EVENT FULL
        if ($totalRegistered >= $maxParticipant) {

            $message = "Registration rejected. Event is full.";
            $messageType = "danger";

        } else {

            // CHECK FOR ANY EXISTING REGISTRATION RECORD
            $checkStmt = $pdo->prepare("
                SELECT EventRegistration_ID, eventRegistrationStatus 
                FROM event_registration 
                WHERE User_ID = ? 
                AND Event_ID = ?
            ");

            $checkStmt->execute([$userID, $eventID]);
            $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                // If it exists and is Cancelled, revive it
                if (strcasecmp($existingRecord['eventRegistrationStatus'], 'Cancelled') === 0) {
                    $updateStmt = $pdo->prepare("
                        UPDATE event_registration 
                        SET eventRegistrationStatus = 'Pending', 
                            eventRegistrationDate = ? 
                        WHERE EventRegistration_ID = ?
                    ");
                    $updateStmt->execute([date('Y-m-d'), $existingRecord['EventRegistration_ID']]);

                    $message = "Event registered successfully (Registration Restored)!";
                    $messageType = "success";
                } else {
                    // It exists and is already Approved or Pending
                    $message = "You already registered for this event.";
                    $messageType = "warning";
                }
            } else {

                // INSERT BRAND NEW REGISTRATION
                $insertStmt = $pdo->prepare("
                    INSERT INTO event_registration 
                    (
                        eventRegistrationStatus,
                        eventRegistrationDate,
                        User_ID,
                        Event_ID,
                        waitingList,
                        waitingListStatus
                    )
                    VALUES
                    (?, ?, ?, ?, 'NA', 'NA')
                ");

                $insertStmt->execute([
                    'Pending',
                    date('Y-m-d'),
                    $userID,
                    $eventID
                ]);

                $message = "Event registered successfully!";
                $messageType = "success";
            }
        }

    } catch (PDOException $e) {

        $message = "Registration failed: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Helper function
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// SEARCH FILTERS
$search = $_GET['search'] ?? '';
$club = $_GET['club'] ?? '';
$date = $_GET['date'] ?? '';

$clubs = $pdo->query("SELECT Club_ID, clubName FROM club ORDER BY clubName ASC")->fetchAll(PDO::FETCH_ASSOC);

// FETCH EVENTS (Updated to track current user's registration status)
$sql = "
    SELECT 
        e.Event_ID,
        e.eventTitle,
        e.eventDescription,
        e.eventDate,
        e.eventStartTime,
        e.eventEndTime,
        e.eventVenue,
        e.eventMaxParticipant,
        e.Club_ID,
        c.clubName,
        COUNT(CASE WHEN er.eventRegistrationStatus != 'Cancelled' THEN er.EventRegistration_ID END) AS registeredCount,
        MAX(CASE WHEN er.User_ID = ? THEN er.eventRegistrationStatus END) AS currentUserStatus
    FROM event e
    LEFT JOIN club c 
        ON e.Club_ID = c.Club_ID
    LEFT JOIN event_registration er 
        ON e.Event_ID = er.Event_ID
    WHERE 1=1
";

// CRITICAL: Put the $userID as the very first parameter since it is evaluated in the SELECT clause expression
$params = [$userID]; 

if (!empty($search)) {
    $sql .= " AND e.eventTitle LIKE ?";
    $params[] = "%$search%";
}

if (!empty($club)) {
    $sql .= " AND e.Club_ID = ?";
    $params[] = $club;
}

if (!empty($date)) {
    $sql .= " AND e.eventDate = ?";
    $params[] = $date;
}

$sql .= "
    GROUP BY e.Event_ID
    ORDER BY e.eventDate ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events - FK Student Club</title>

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

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .filter-section {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .filter-section h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #191c1e;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #434654;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid #d1d9e0;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            color: #191c1e;
            background-color: #ffffff;
            transition: border-color 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #0969da;
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon i {
            position: absolute;
            left: 0.75rem;
            color: #8b949e;
            pointer-events: none;
        }

        .input-with-icon input {
            padding-left: 2.5rem;
        }

        .search-btn {
            padding: 0.75rem 1.5rem;
            background-color: #0969da;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-btn:hover {
            background-color: #0860ca;
            box-shadow: 0 4px 12px rgba(9, 105, 218, 0.3);
        }

        .events-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .event-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
            border-color: #0969da;
        }

        .event-card-header {
            background: linear-gradient(135deg, #0969da 0%, #0860ca 100%);
            color: #ffffff;
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            min-height: 140px;
        }

        .event-card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .event-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #191c1e;
            line-height: 1.4;
        }

        .event-description {
            font-size: 0.875rem;
            color: #6e7781;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-meta-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #6e7781;
        }

        .event-meta-item i {
            color: #0969da;
            min-width: 1.25rem;
            text-align: center;
        }

        .event-capacity {
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .capacity-label {
            font-size: 0.85rem;
            color: #6e7781;
        }

        .capacity-bar {
            width: 100%;
            height: 6px;
            background-color: #d1d9e0;
            border-radius: 3px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .capacity-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0969da 0%, #0860ca 100%);
        }

        .seats-info {
            font-size: 0.875rem;
            font-weight: 600;
            color: #191c1e;
        }

        .event-card-footer {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.5rem;
        }

        .btn-register {
            flex: 1;
            padding: 0.75rem;
            background-color: #0969da;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-register:hover {
            background-color: #0860ca;
            box-shadow: 0 4px 12px rgba(9, 105, 218, 0.3);
        }

        .btn-register.disabled {
            background-color: #d1d9e0;
            color: #6e7781;
            cursor: not-allowed;
        }

        .btn-registered {
            background-color: #dcfce7;
            color: #166534;
        }

        .btn-registered:hover {
            background-color: #bbf7d0;
        }

        .btn-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .btn-pending:hover {
            background-color: #fde68a;
        }

        .btn-full {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .empty-state {
            grid-column: 1 / -1;
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

            .filter-row {
                grid-template-columns: 1fr;
            }

            .events-container {
                grid-template-columns: 1fr;
            }

            .event-card-header {
                font-size: 2rem;
                min-height: 100px;
            }
        }
    </style>
</head>

<body>
    <?php include '../topbar.php'; ?>

    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <main id="content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="bi bi-calendar-event"></i> Browse Events</h1>
                    <p>Discover and register for upcoming events organized by student clubs</p>
                </div>

                <!-- Alerts -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'danger' ? 'exclamation-circle-fill' : 'info-circle-fill'); ?>"></i>
                        <span><?php echo e($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Filter Section -->
                <form method="GET" class="filter-section">
                    <h3><i class="bi bi-funnel"></i> Filter Events</h3>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Events</label>
                            <div class="input-with-icon">
                                <i class="bi bi-search"></i>
                                <input 
                                    type="text" 
                                    id="search"
                                    name="search"
                                    placeholder="Event name..."
                                    value="<?php echo e($search); ?>"
                                >
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="club">Select Club</label>
                            <select id="club" name="club">
                                <option value="">All Clubs</option>
                                <?php foreach ($clubs as $c): ?>
                                    <option value="<?php echo $c['Club_ID']; ?>" <?php echo ($club == $c['Club_ID']) ? 'selected' : ''; ?>>
                                        <?php echo e($c['clubName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="date">Select Date</label>
                            <input 
                                type="date" 
                                id="date"
                                name="date"
                                value="<?php echo e($date); ?>"
                            >
                        </div>

                        <div class="filter-group">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Events Grid -->
                <div class="events-container">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <?php
                            $registered = (int)$event['registeredCount'];
                            $max = (int)$event['eventMaxParticipant'];
                            $seatsLeft = $max - $registered;
                            $capacity = $max > 0 ? min(100, round(($registered / $max) * 100)) : 0;
                            $userStatus = $event['currentUserStatus'] ?? '';
                            ?>

                            <div class="event-card">
                                <!-- Header -->
                                <div class="event-card-header">
                                    <i class="bi bi-calendar-event"></i>
                                </div>

                                <!-- Body -->
                                <div class="event-card-body">
                                    <h3 class="event-title"><?php echo e($event['eventTitle']); ?></h3>
                                    
                                    <p class="event-description"><?php echo e($event['eventDescription']); ?></p>

                                    <div class="event-meta-list">
                                        <div class="event-meta-item">
                                            <i class="bi bi-calendar3"></i>
                                            <span>
                                                <?php echo date('M d, Y', strtotime($event['eventDate'])); ?> at
                                                <?php echo date('g:i A', strtotime($event['eventStartTime'])); ?>
                                            </span>
                                        </div>

                                        <div class="event-meta-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <span><?php echo e($event['eventVenue']); ?></span>
                                        </div>

                                        <div class="event-meta-item">
                                            <i class="bi bi-building"></i>
                                            <span><?php echo e($event['clubName'] ?? 'Unknown Club'); ?></span>
                                        </div>
                                    </div>

                                    <div class="event-capacity">
                                        <div>
                                            <div class="capacity-label">Capacity</div>
                                            <div class="capacity-bar">
                                                <div class="capacity-bar-fill" style="width: <?php echo $capacity; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="seats-info">
                                            <?php echo $seatsLeft > 0 ? $seatsLeft : '0'; ?> / <?php echo $max; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="event-card-footer">
                                    <?php if (strcasecmp($userStatus, 'Approved') === 0): ?>
                                        <button class="btn-register btn-registered disabled" disabled>
                                            <i class="bi bi-check-circle"></i> Registered
                                        </button>

                                    <?php elseif (strcasecmp($userStatus, 'Pending') === 0): ?>
                                        <button class="btn-register btn-pending disabled" disabled>
                                            <i class="bi bi-clock-history"></i> Pending Approval
                                        </button>

                                    <?php elseif (strcasecmp($userStatus, 'Cancelled') === 0): ?>
                                        <form method="POST" style="display: contents;">
                                            <input type="hidden" name="register_event_id" value="<?php echo $event['Event_ID']; ?>">
                                            <button type="submit" class="btn-register">
                                                <i class="bi bi-arrow-repeat"></i> Register Again
                                            </button>
                                        </form>

                                    <?php elseif ($seatsLeft > 0): ?>
                                        <form method="POST" style="display: contents;">
                                            <input type="hidden" name="register_event_id" value="<?php echo $event['Event_ID']; ?>">
                                            <button type="submit" class="btn-register">
                                                <i class="bi bi-check-circle"></i> Register
                                            </button>
                                        </form>

                                    <?php else: ?>
                                        <button class="btn-register btn-full disabled" disabled>
                                            <i class="bi bi-x-circle"></i> Event Full
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No events found. Try adjusting your filters or check back soon!</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Footer -->
            <footer>
                <p>&copy; 2026 FK Student Club Management System. All rights reserved.</p>
            </footer>

        </main>

    </div>

</body>

</html>