<?php
session_start();
$successMessage = $_SESSION['successMessage'] ?? '';
$errorMessage = $_SESSION['errorMessage'] ?? '';

unset($_SESSION['successMessage']);
unset($_SESSION['errorMessage']);

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: ../Module 1/index.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM event");
    $totalEvents = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM event WHERE eventDate > CURDATE()");
    $upcomingEvents = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM event WHERE eventDate <= CURDATE()");
    $completedEvents = $stmt->fetchColumn();

    $cancelledEvents = 0;
} catch (PDOException $e) {
    die("Database summary aggregation error: " . $e->getMessage());
}

$statusFilter = $_GET['status'] ?? 'All Statuses';

try {
    $query = "SELECT * FROM event ORDER BY eventDate DESC";
    $stmt = $pdo->query($query);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching events list: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - FK Student Club</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f8f9fb;
            color: #191c1e;
        }

        #content {
            padding: 2rem;
            width: 100%;
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid rgba(115, 118, 134, 0.15);
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .metric-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .table-container {
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid rgba(115, 118, 134, 0.15);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .custom-table thead {
            background-color: #f2f4f6;
        }

        .custom-table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #434654;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .custom-table td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
        }

        .badge-status {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.35em 0.8em;
            border-radius: 50rem;
        }
    </style>
</head>

<body>
    <?php include '../topbar.php'; ?>

    <div id="wrapper" class="d-flex">
        <?php include '../sidebar.php'; ?>

        <div id="content" style="margin-top: 10px;">
            <div class="container-fluid">

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($successMessage); ?>

                        <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($errorMessage); ?>

                        <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                        </button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-1" style="font-size: 32px; letter-spacing: -0.02em;">Event Management</h2>
                        <p class="text-muted mb-0">Coordinate, track, and manage all student club activities.</p>
                    </div>

                    <a href="create_event.php" class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2 fw-bold shadow-sm" style="background-color: #003ca0; border: none; border-radius: 0.5rem;">
                        <i class="bi bi-plus-lg"></i> Create New Event
                    </a>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="metric-card p-4 d-flex align-items-center gap-3">
                            <div class="metric-icon" style="background-color: rgba(0, 60, 160, 0.1); color: #003ca0;">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-medium">Total Events</small>
                                <span class="h3 fw-bold mb-0"><?php echo $totalEvents; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="metric-card p-4 d-flex align-items-center gap-3">
                            <div class="metric-icon" style="background-color: rgba(0, 109, 55, 0.1); color: #006d37;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-medium">Upcoming</small>
                                <span class="h3 fw-bold mb-0"><?php echo $upcomingEvents; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="metric-card p-4 d-flex align-items-center gap-3">
                            <div class="metric-icon" style="background-color: #edeef0; color: #434654;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-medium">Completed</small>
                                <span class="h3 fw-bold mb-0"><?php echo $completedEvents; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="metric-card p-4 d-flex align-items-center gap-3">
                            <div class="metric-icon" style="background-color: #ffdad5; color: #8d0404;">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-medium">Cancelled</small>
                                <span class="h3 fw-bold mb-0"><?php echo $cancelledEvents; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-container mb-4">
                    <div class="p-4 border-b d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <h4 class="fw-bold mb-0" style="font-size: 20px;">Event List</h4>

                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center bg-light px-3 py-1 rounded border">
                                <i class="bi bi-filter text-muted me-2"></i>

                                <select class="form-select form-select-sm border-0 bg-transparent shadow-none p-0 pe-4" onchange="location = this.value;">
                                    <option value="?status=All Statuses" <?php echo $statusFilter === 'All Statuses' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="?status=Upcoming" <?php echo $statusFilter === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="?status=Ongoing" <?php echo $statusFilter === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="?status=Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2 fw-medium">
                                <i class="bi bi-download"></i> Export PDF
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table custom-table mb-0 table-hover">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 60px;">#</th>
                                    <th scope="col">Event Title</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Venue</th>
                                    <th scope="col" class="text-center">Capacity</th>
                                    <th scope="col" class="text-center">Registered</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
                                            No event records found in the database system.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $counter = 1;

                                    foreach ($events as $event):

                                        $eventTimestamp = strtotime($event['eventDate']);
                                        $currentTimestamp = strtotime(date('Y-m-d'));

                                        if ($eventTimestamp == $currentTimestamp) {
                                            $eventStatus = 'Ongoing';
                                            $statusBadge = '<span class="badge badge-status bg-success bg-opacity-10 text-success">Ongoing</span>';
                                        } elseif ($eventTimestamp > $currentTimestamp) {
                                            $eventStatus = 'Upcoming';
                                            $statusBadge = '<span class="badge badge-status bg-primary bg-opacity-10 text-primary">Upcoming</span>';
                                        } else {
                                            $eventStatus = 'Completed';
                                            $statusBadge = '<span class="badge badge-status bg-secondary bg-opacity-10 text-secondary">Completed</span>';
                                        }

                                        if ($statusFilter !== 'All Statuses' && $statusFilter !== $eventStatus) {
                                            continue;
                                        }

                                        $regStmt = $pdo->prepare("SELECT COUNT(*) FROM event_registration WHERE Event_ID = ?");
                                        $regStmt->execute([$event['Event_ID']]);
                                        $registeredCount = $regStmt->fetchColumn();

                                        $capacity = (int)$event['eventMaxParticipant'] > 0 ? (int)$event['eventMaxParticipant'] : 1;
                                        $percentage = min(100, round(($registeredCount / $capacity) * 100));
                                    ?>
                                        <tr>
                                            <td class="text-muted font-monospace"><?php echo sprintf("%02d", $counter++); ?></td>

                                            <td>
                                                <p class="fw-bold mb-0 text-dark">
                                                    <?php echo htmlspecialchars($event['eventTitle']); ?>
                                                </p>
                                                <p class="text-muted mb-0" style="font-size: 12px;">
                                                    Tech & Engineering Series
                                                </p>
                                            </td>

                                            <td class="text-nowrap">
                                                <?php echo date('d M Y', strtotime($event['eventDate'])); ?>
                                            </td>

                                            <td>
                                                <?php echo htmlspecialchars($event['eventVenue']); ?>
                                            </td>

                                            <td class="text-center font-monospace">
                                                <?php echo htmlspecialchars($event['eventMaxParticipant']); ?>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <span class="fw-bold"><?php echo $registeredCount; ?></span>

                                                    <div class="progress d-none d-md-flex" style="width: 64px; height: 6px; border-radius: 999px;">
                                                        <div class="progress-bar" role="progressbar"
                                                            style="width: <?php echo $percentage; ?>%; background-color: #003ca0;"
                                                            aria-valuenow="<?php echo $percentage; ?>"
                                                            aria-valuemin="0"
                                                            aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td><?php echo $statusBadge; ?></td>

                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="view_event.php?id=<?php echo $event['Event_ID']; ?>"
                                                        class="btn btn-sm btn-light text-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                        style="width: 36px; height: 36px;"
                                                        title="View Details">
                                                        <i class="bi bi-eye" style="font-size: 16px;"></i>
                                                    </a>

                                                    <a href="edit_event.php?id=<?php echo $event['Event_ID']; ?>"
                                                        class="btn btn-sm btn-light text-success rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                        style="width: 36px; height: 36px;"
                                                        title="Edit Event">
                                                        <i class="bi bi-pencil" style="font-size: 16px;"></i>
                                                    </a>

                                                    <a href="delete_event.php?id=<?php echo $event['Event_ID']; ?>"
                                                        class="btn btn-sm btn-light text-danger rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                        style="width: 36px; height: 36px;"
                                                        title="Delete Event"
                                                        onclick="return confirm('Are you sure you want to completely erase this event record?');">
                                                        <i class="bi bi-trash" style="font-size: 16px;"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if ($counter === 1): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-5">
                                                No event found for selected status.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t d-flex justify-content-between align-items-center">
                        <span class="text-muted" style="font-size: 14px;">
                            Showing <?php echo max(0, $counter - 1); ?> of <?php echo $totalEvents; ?> entries
                        </span>

                        <nav aria-label="Table Data navigation">
                            <ul class="pagination pagination-sm mb-0 gap-1">
                                <li class="page-item disabled">
                                    <a class="page-link border rounded" href="#">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <li class="page-item active">
                                    <a class="page-link border rounded" style="background-color: #003ca0; border-color: #003ca0;" href="#">1</a>
                                </li>

                                <li class="page-item disabled">
                                    <a class="page-link border rounded" href="#">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>