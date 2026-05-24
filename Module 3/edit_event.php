<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: ../Module 1/index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['errorMessage'] = "Invalid event selected.";
    header("Location: event_management.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM event WHERE Event_ID = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['errorMessage'] = "Event not found.";
    header("Location: event_management.php");
    exit();
}

$clubs = $pdo->query("
    SELECT Club_ID, clubName 
    FROM club 
    ORDER BY clubName ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eventTitle = trim($_POST['eventTitle']);
    $eventDescription = trim($_POST['eventDescription']);
    $eventDate = $_POST['eventDate'];
    $eventStartTime = $_POST['eventStartTime'];
    $eventEndTime = $_POST['eventEndTime'];
    $eventVenue = trim($_POST['eventVenue']);
    $eventMaxParticipant = $_POST['eventMaxParticipant'];
    $clubID = $_POST['Club_ID'];

    try {

        $stmt = $pdo->prepare("
            UPDATE event SET
                eventTitle = ?,
                eventDescription = ?,
                eventDate = ?,
                eventStartTime = ?,
                eventEndTime = ?,
                eventVenue = ?,
                eventMaxParticipant = ?,
                Club_ID = ?
            WHERE Event_ID = ?
        ");

        $stmt->execute([
            $eventTitle,
            $eventDescription,
            $eventDate,
            $eventStartTime,
            $eventEndTime,
            $eventVenue,
            $eventMaxParticipant,
            $clubID,
            $id
        ]);

        $_SESSION['successMessage'] = "Event updated successfully.";

        header("Location: event_management.php");
        exit();

    } catch (PDOException $e) {

        $_SESSION['errorMessage'] = "Failed to update event.";

        header("Location: event_management.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body {
            background: #f8f9fb;
        }

        #content {
            width: 100%;
            padding: 2rem;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 10px 14px;
        }

        .btn-primary {
            background-color: #003ca0;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
            font-weight: 600;
        }

        .btn-secondary {
            border-radius: 10px;
            padding: 10px 22px;
            font-weight: 600;
        }
    </style>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper" class="d-flex">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>
                    <h2 class="fw-bold mb-1">Edit Event</h2>

                    <p class="text-muted mb-0">
                        Update existing event information.
                    </p>
                </div>

                <a href="event_management.php"
                   class="btn btn-secondary">

                    <i class="bi bi-arrow-left"></i> Back

                </a>

            </div>

            <div class="form-card">

                <form method="POST">

                    <div class="row g-4">

                        <div class="col-md-12">
                            <label class="form-label">
                                Event Title
                            </label>

                            <input type="text"
                                   name="eventTitle"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($event['eventTitle']); ?>"
                                   required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">
                                Event Description
                            </label>

                            <textarea name="eventDescription"
                                      class="form-control"
                                      rows="4"
                                      required><?php echo htmlspecialchars($event['eventDescription']); ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Event Date
                            </label>

                            <input type="date"
                                   name="eventDate"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($event['eventDate']); ?>"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Start Time
                            </label>

                            <input type="time"
                                   name="eventStartTime"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($event['eventStartTime']); ?>"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                End Time
                            </label>

                            <input type="time"
                                   name="eventEndTime"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($event['eventEndTime']); ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Venue
                            </label>

                            <input type="text"
                                   name="eventVenue"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($event['eventVenue']); ?>"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                Max Participant
                            </label>

                            <input type="number"
                                   name="eventMaxParticipant"
                                   class="form-control"
                                   min="1"
                                   value="<?php echo htmlspecialchars($event['eventMaxParticipant']); ?>"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                Club
                            </label>

                            <select name="Club_ID"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Select Club
                                </option>

                                <?php foreach ($clubs as $club): ?>

                                    <option value="<?php echo $club['Club_ID']; ?>"
                                        <?php echo $club['Club_ID'] == $event['Club_ID'] ? 'selected' : ''; ?>>

                                        <?php echo htmlspecialchars($club['clubName']); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">

                        <a href="event_management.php"
                           class="btn btn-secondary">

                            Cancel

                        </a>

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="bi bi-save"></i>
                            Update Event

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>