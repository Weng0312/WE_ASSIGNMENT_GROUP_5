<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Module_1/index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            Event_ID,
            eventTitle,
            eventDate,
            eventStartTime,
            eventEndTime,
            eventVenue
        FROM event
        ORDER BY eventDate DESC, eventTime DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error loading events: " . $e->getMessage());
}

$baseUrl = "http://localhost/WE_ASSIGNMENT/Module_4/qr_attendance.php?event_id=";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance QR Code</title>

    <link rel="stylesheet" href="../STYLE/CSS/qr_attendance_CSS.css">
</head>

<body>

<div class="container">

    <h1>Attendance QR Code</h1>
    <p class="subtitle">Show QR code for students to scan and submit attendance.</p>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>QR Code</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $row): ?>
                    <?php
                    $eventID = $row['Event_ID'];
                    $qrLink = $baseUrl . urlencode($eventID);
                    $qrImage = "https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=" . urlencode($qrLink);
                    ?>

                    <tr>
                        <td><?php echo htmlspecialchars($row['eventName']); ?></td>
                        <td><?php echo htmlspecialchars($row['eventDate']); ?></td>
                        <td><?php echo htmlspecialchars($row['eventTime']); ?></td>
                        <td><?php echo htmlspecialchars($row['eventVenue'] ?? 'N/A'); ?></td>
                        <td>
                            <button 
                                type="button" 
                                class="btn btn-primary"
                                onclick="showQR(
                                    '<?php echo htmlspecialchars($qrImage, ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['eventName'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($qrLink, ENT_QUOTES); ?>'
                                )"
                            >
                                Show QR
                            </button>
                        </td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty">No events found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="qrModal" class="qr-modal">
    <div class="qr-box">
        <h2 id="modalEventName">Scan QR</h2>
        <p>Students scan this QR code to submit attendance.</p>

        <img id="qrImage" src="" alt="Attendance QR Code">

        <div id="qrLinkText" class="qr-link"></div>

        <button type="button" class="btn btn-secondary" onclick="closeQR()">Close</button>
    </div>
</div>

<script>
function showQR(qrImage, eventName, qrLink) {
    document.getElementById("qrImage").src = qrImage;
    document.getElementById("modalEventName").innerText = eventName;
    document.getElementById("qrLinkText").innerText = qrLink;
    document.getElementById("qrModal").style.display = "flex";
}

function closeQR() {
    document.getElementById("qrModal").style.display = "none";
}

document.getElementById("qrModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeQR();
    }
});
</script>

</body>
</html>