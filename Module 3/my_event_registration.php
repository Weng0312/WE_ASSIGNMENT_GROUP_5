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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Event Registration Page</title>

    <link rel="stylesheet" href="../STYLE/BOOTSTRAP/bootstrap.min.css">

    <style>

        body{
            background: #f5f7fb;
        }

        #content{
            padding: 30px;
            width: 100%;
        }

        .page-title{
            font-size: 52px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .page-subtitle{
            color: #64748b;
            font-size: 22px;
            margin-bottom: 35px;
        }

        .registration-card{
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .registration-table{
            width: 100%;
        }

        .registration-table thead{
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .registration-table th{
            padding: 25px;
            color: #334155;
            font-size: 18px;
            font-weight: 700;
        }

        .registration-table td{
            padding: 25px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .event-info{
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .event-icon{
            width: 65px;
            height: 65px;
            background: #dbeafe;
            color: #2563eb;
            border-radius: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            font-weight: bold;
        }

        .event-title{
            font-size: 30px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .event-description{
            color: #64748b;
            font-size: 18px;
            max-width: 500px;
        }

        .date-text{
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .time-text{
            font-size: 18px;
            color: #94a3b8;
        }

        .club-text{
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
        }

        .status-badge{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 999px;
            font-size: 20px;
            font-weight: 700;
        }

        .approved{
            background: #dcfce7;
            color: #16a34a;
        }

        .pending{
            background: #fef3c7;
            color: #d97706;
        }

        .cancelled{
            background: #f1f5f9;
            color: #64748b;
        }

        .status-small{
            margin-top: 8px;
            font-size: 15px;
            color: #94a3b8;
        }

        .cancel-btn{
            border: 2px solid #fecaca;
            background: white;
            color: #ef4444;
            border-radius: 14px;
            padding: 12px 28px;
            font-size: 20px;
            font-weight: 700;
            transition: 0.3s;
        }

        .cancel-btn:hover{
            background: #fee2e2;
        }

        .cancel-btn:disabled{
            opacity: 0.4;
            cursor: not-allowed;
        }

        .bottom-text{
            padding: 20px 25px;
            color: #64748b;
            font-size: 18px;
        }

        .success-message{
            background: #dcfce7;
            color: #15803d;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

    </style>

</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid">

            <h1 class="page-title">
                My Event Registration Page
            </h1>

            <p class="page-subtitle">
                View your registered events, check their status, and cancel registrations if needed.
            </p>

            <?php if(!empty($message)): ?>
                <div class="success-message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="registration-card">

                <table class="registration-table">

                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Club</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if(count($registrations) > 0): ?>

                        <?php foreach($registrations as $row): ?>

                            <?php
                                $statusClass = getStatusClass($row['eventRegistrationStatus']);
                                $isCancelled = strtolower($row['eventRegistrationStatus']) == 'cancelled';
                            ?>

                            <tr>

                                <td>
                                    <div class="event-info">

                                        <div class="event-icon">
                                            E
                                        </div>

                                        <div>

                                            <div class="event-title">
                                                <?php echo htmlspecialchars($row['eventTitle']); ?>
                                            </div>

                                            <div class="event-description">
                                                <?php echo htmlspecialchars($row['eventDescription']); ?>
                                            </div>

                                        </div>

                                    </div>
                                </td>

                                <td>

                                    <div class="date-text">
                                        <?php echo date("M d, Y", strtotime($row['eventDate'])); ?>
                                    </div>

                                    <div class="time-text">
                                        <?php echo date("g:i A", strtotime($row['eventStartTime'])); ?>
                                        -
                                        <?php echo date("g:i A", strtotime($row['eventEndTime'])); ?>
                                    </div>

                                </td>

                                <td>

                                    <div class="club-text">
                                        <?php echo htmlspecialchars($row['clubName']); ?>
                                    </div>

                                </td>

                                <td>

                                    <div class="status-badge <?php echo $statusClass; ?>">

                                        <?php
                                            if($statusClass == 'approved'){
                                                echo "✓";
                                            }
                                            elseif($statusClass == 'pending'){
                                                echo "!";
                                            }
                                            else{
                                                echo "×";
                                            }
                                        ?>

                                        <?php echo htmlspecialchars($row['eventRegistrationStatus']); ?>

                                    </div>

                                    <div class="status-small">

                                        <?php
                                            if($statusClass == 'approved'){
                                                echo "You're all set!";
                                            }
                                            elseif($statusClass == 'pending'){
                                                echo "Waiting for approval.";
                                            }
                                            else{
                                                echo "This registration was cancelled.";
                                            }
                                        ?>

                                    </div>

                                </td>

                                <td>

                                    <?php if(!$isCancelled): ?>

                                        <form method="POST">

                                            <input 
                                                type="hidden" 
                                                name="cancel_registration_id"
                                                value="<?php echo $row['EventRegistration_ID']; ?>"
                                            >

                                            <button 
                                                type="submit"
                                                class="cancel-btn"
                                                onclick="return confirm('Cancel this registration?')"
                                            >
                                                Cancel
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <button class="cancel-btn" disabled>
                                            Cancel
                                        </button>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8; font-size:22px;">
                                No event registration found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

                <div class="bottom-text">
                    Showing 
                    <strong>
                        <?php echo count($registrations) > 0 ? 1 : 0; ?>
                    </strong>
                    to
                    <strong>
                        <?php echo count($registrations); ?>
                    </strong>
                    of
                    <strong>
                        <?php echo count($registrations); ?>
                    </strong>
                    entries
                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>