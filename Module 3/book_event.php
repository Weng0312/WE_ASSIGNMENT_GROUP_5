<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../");
    exit();
}

$message = '';
$messageType = '';

// Fetch all available events for the selection dropdown
$events = $pdo->query("SELECT Event_ID, eventTitle, eventVenue, eventDate FROM event")->fetchAll();

// Capture event_id from URL GET parameter if available (for auto-selecting)
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];
    $reg_status = 'Pending';
    $reg_date = date('Y-m-d');

    try {
        // STEP 1: Check if a registration entry already exists for this user and event
        $checkStmt = $pdo->prepare("SELECT EventRegistration_ID, eventRegistrationStatus FROM event_registration WHERE User_ID = ? AND Event_ID = ?");
        $checkStmt->execute([$user_id, $event_id]);
        $existingReg = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingReg) {
            // STEP 2: If it exists but is Cancelled, update it back to Pending
            if (strcasecmp($existingReg['eventRegistrationStatus'], 'Cancelled') === 0) {
                $updateStmt = $pdo->prepare("UPDATE event_registration SET eventRegistrationStatus = ?, eventRegistrationDate = ? WHERE EventRegistration_ID = ?");
                $updateStmt->execute([$reg_status, $reg_date, $existingReg['EventRegistration_ID']]);
                
                $message = "Successfully re-registered for the event!";
                $messageType = "success";
            } else {
                // If status is already Pending or Approved, block duplicate submission
                $message = "You are already registered for this event (Status: " . $existingReg['eventRegistrationStatus'] . ").";
                $messageType = "warning";
            }
        } else {
            // STEP 3: If no record exists, insert a brand new row safely
            $stmt = $pdo->prepare("INSERT INTO event_registration (eventRegistrationStatus, eventRegistrationDate, User_ID, Event_ID) VALUES (?, ?, ?, ?)");
            $stmt->execute([$reg_status, $reg_date, $user_id, $event_id]);

            $message = "Successfully registered for the event!";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Error registering for event: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Event - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../topbar.php'; ?>
    <div id="wrapper">
        <?php include '../sidebar.php'; ?>
        <div id="content" class="w-100 p-4">
            <div class="container-fluid" style="margin-top: 40px;">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-success text-white p-3 fw-bold">
                                <i class="bi bi-calendar-plus me-2"></i> Register for an Event
                            </div>
                            <div class="card-body p-4">
                                <form action="" method="POST">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Choose an Available Event</label>
                                        <select name="event_id" class="form-select" required>
                                            <option value="">-- Select Event --</option>
                                            <?php foreach ($events as $ev): ?>
                                                <option value="<?php echo $ev['Event_ID']; ?>" <?php echo ($selected_event_id == $ev['Event_ID']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ev['eventTitle']); ?> (<?php echo $ev['eventVenue']; ?> - <?php echo date('d M Y', strtotime($ev['eventDate'])); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success fw-bold py-2">Submit Registration</button>
                                        <a href="my_event_registration.php" class="btn btn-outline-secondary">View My Bookings</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>
</html>