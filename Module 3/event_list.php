<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Module 1/index.php");
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
                COUNT(er.EventRegistration_ID) AS totalRegistered
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

            // CHECK DUPLICATE REGISTRATION
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM event_registration 
                WHERE User_ID = ? AND Event_ID = ?
            ");

            $checkStmt->execute([$userID, $eventID]);
            $alreadyRegistered = $checkStmt->fetchColumn();

            if ($alreadyRegistered > 0) {

                $message = "You already registered for this event.";
                $messageType = "warning";

            } else {

                // INSERT REGISTRATION
                $insertStmt = $pdo->prepare("
                    INSERT INTO event_registration 
                    (
                        eventRegistrationStatus,
                        eventRegistrationDate,
                        User_ID,
                        Event_ID
                    )
                    VALUES
                    (?, ?, ?, ?)
                ");

                $insertStmt->execute([
                    'Approved',
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

// SEARCH FILTERS
$search = $_GET['search'] ?? '';
$club = $_GET['club'] ?? '';
$date = $_GET['date'] ?? '';

// FETCH EVENTS
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
        COUNT(er.EventRegistration_ID) AS registeredCount
    FROM event e
    LEFT JOIN club c 
        ON e.Club_ID = c.Club_ID
    LEFT JOIN event_registration er 
        ON e.Event_ID = er.Event_ID
    WHERE 1=1
";

$params = [];

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

// FETCH CLUBS
$clubStmt = $pdo->query("
    SELECT Club_ID, clubName 
    FROM club 
    ORDER BY clubName ASC
");

$clubs = $clubStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event List Page</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module 3/event_list_CSS.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <main id="content">

        <h1>Event List Page</h1>

        <p class="subtitle">
            Browse and register for upcoming events organized by clubs.
        </p>

        <?php if (!empty($message)): ?>

            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>

        <!-- FILTER -->
        <form method="GET" class="filter-box">

            <div class="filter-item">

                <label>Search Events</label>

                <div class="input-icon">

                    <i class="bi bi-search"></i>

                    <input 
                        type="text" 
                        name="search"
                        placeholder="Search by event name..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>

            </div>

            <div class="filter-item">

                <label>Club</label>

                <select name="club">

                    <option value="">All Clubs</option>

                    <?php foreach ($clubs as $c): ?>

                        <option 
                            value="<?= $c['Club_ID'] ?>"
                            <?= ($club == $c['Club_ID']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($c['clubName']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-item">

                <label>Date</label>

                <input 
                    type="date" 
                    name="date"
                    value="<?= htmlspecialchars($date) ?>"
                >

            </div>

            <button type="submit" class="search-btn">
                Search
            </button>

        </form>

        <!-- EVENT LIST -->
        <div class="event-list">

            <?php if (!empty($events)): ?>

                <?php foreach ($events as $event): ?>

                    <?php
                    $registered = (int)$event['registeredCount'];
                    $max = (int)$event['eventMaxParticipant'];
                    $seatsLeft = $max - $registered;
                    ?>

                    <div class="event-card">

                        <div class="event-image">
                            <i class="bi bi-calendar-event"></i>
                        </div>

                        <div class="event-info">

                            <h3>
                                <?= htmlspecialchars($event['eventTitle']) ?>
                            </h3>

                            <p class="event-desc">
                                <?= htmlspecialchars($event['eventDescription']) ?>
                            </p>

                            <div class="event-details">

                                <div>

                                    <i class="bi bi-calendar3"></i>

                                    <span>
                                        <?= date("F j, Y", strtotime($event['eventDate'])) ?>
                                        <br>

                                        <small>
                                            <?= date("h:i A", strtotime($event['eventStartTime'])) ?>
                                        </small>
                                    </span>

                                </div>

                                <div>

                                    <i class="bi bi-geo-alt"></i>

                                    <span>
                                        <?= htmlspecialchars($event['eventVenue']) ?>
                                        <br>

                                        <small>
                                            <?= htmlspecialchars($event['clubName'] ?? 'Unknown Club') ?>
                                        </small>
                                    </span>

                                </div>

                                <div>

                                    <i class="bi bi-people"></i>

                                    <span>
                                        <?= $seatsLeft ?> Seats Left
                                        <br>

                                        <small>
                                            of <?= $max ?>
                                        </small>
                                    </span>

                                </div>

                            </div>

                        </div>

                        <div class="event-action">

                            <?php if ($seatsLeft > 0): ?>

                                <form method="POST">

                                    <input 
                                        type="hidden"
                                        name="register_event_id"
                                        value="<?= $event['Event_ID'] ?>"
                                    >

                                    <button type="submit" class="register-btn">
                                        Register
                                    </button>

                                </form>

                            <?php else: ?>

                                <button class="full-btn" disabled>
                                    Full
                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-event">
                    No event found.
                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>