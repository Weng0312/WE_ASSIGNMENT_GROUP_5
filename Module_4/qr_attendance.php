<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

$UMPSA_LAT = 3.5436412;
$UMPSA_LNG = 103.4288926;

$ALLOWED_RADIUS_METERS = 2000;

$eventID = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

$locationSessionKey = "qr_location_verified_event_" . $eventID;
$isLocationVerified = !empty($_SESSION[$locationSessionKey]);

$message = "";
$messageType = "";
$serverLocationBlocked = false;

function clean($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function calculateDistanceMeters($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371000;

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);

    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLng = deg2rad($lng2 - $lng1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
        cos($lat1Rad) * cos($lat2Rad) *
        sin($deltaLng / 2) * sin($deltaLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

function getPointValue($attendanceStatus)
{
    if ($attendanceStatus === 'Present') {
        return 10;
    }

    if ($attendanceStatus === 'Late') {
        return 5;
    }

    if ($attendanceStatus === 'Volunteer') {
        return 5;
    }

    if ($attendanceStatus === 'Absent') {
        return -10;
    }

    return 0;
}

if ($eventID <= 0) {
    $serverLocationBlocked = true;
    $message = "Invalid QR code link. Event ID is missing.";
    $messageType = "error";
}

$event = null;
$participants = [];

if (!$serverLocationBlocked) {
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
            WHERE Event_ID = ?
            LIMIT 1
        ");
        $stmt->execute([$eventID]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $serverLocationBlocked = true;
            $message = "Event not found. Please scan a valid QR code.";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        $serverLocationBlocked = true;
        $message = "Error loading event: " . $e->getMessage();
        $messageType = "error";
    }
}

if (!$serverLocationBlocked && $event) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                er.EventRegistration_ID,
                s.studentID,
                u.userName
            FROM event_registration er
            INNER JOIN user u 
                ON er.User_ID = u.User_ID
            INNER JOIN student s 
                ON u.User_ID = s.User_ID
            WHERE er.Event_ID = ?
              AND er.eventRegistrationStatus = 'Approved'
            ORDER BY s.studentID ASC
        ");
        $stmt->execute([$eventID]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $participants = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$serverLocationBlocked && $event) {
    $studentID = trim($_POST['studentID'] ?? '');
    $attendanceRole = trim($_POST['attendanceRole'] ?? 'Participant');

    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
    $accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : 0;

    $locationAllowedForSubmit = $isLocationVerified;

    if (!$locationAllowedForSubmit) {
        if ($latitude == 0 || $longitude == 0) {
            $message = "Location not detected. Please allow location access and try again.";
            $messageType = "error";
        } else {
            $distance = calculateDistanceMeters($latitude, $longitude, $UMPSA_LAT, $UMPSA_LNG);

            $accuracyBonus = min(max($accuracy, 0), 1000);
            $serverAllowedDistance = $ALLOWED_RADIUS_METERS + $accuracyBonus;

            if ($distance > $serverAllowedDistance) {
                $serverLocationBlocked = true;
                $message = "You are not inside UMPSA Pekan. Attendance form cannot be accessed outside the campus area.";
                $messageType = "error";
            } else {
                $_SESSION[$locationSessionKey] = true;
                $isLocationVerified = true;
                $locationAllowedForSubmit = true;
            }
        }
    }

    if ($locationAllowedForSubmit && $messageType !== "error") {
        if ($studentID === '') {
            $message = "Please enter your Student ID.";
            $messageType = "error";
        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        er.EventRegistration_ID,
                        u.User_ID,
                        u.userName,
                        s.studentID
                    FROM event_registration er
                    INNER JOIN user u 
                        ON er.User_ID = u.User_ID
                    INNER JOIN student s 
                        ON u.User_ID = s.User_ID
                    WHERE s.studentID = ?
                      AND er.Event_ID = ?
                      AND er.eventRegistrationStatus = 'Approved'
                    LIMIT 1
                ");
                $stmt->execute([$studentID, $eventID]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    $message = "Student ID is not approved for this event.";
                    $messageType = "error";
                } else {
                    $eventRegistrationID = $student['EventRegistration_ID'];

                    $stmt = $pdo->prepare("
                        SELECT Attendance_ID
                        FROM event_attendance
                        WHERE EventRegistrationID = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$eventRegistrationID]);
                    $existingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingAttendance) {
                        $message = "Attendance already recorded for this student.";
                        $messageType = "error";
                    } else {
                        $checkInTime = date('Y-m-d H:i:s');

                        $currentDateTime = strtotime(date('Y-m-d H:i:s'));
                        $eventStartDateTime = strtotime($event['eventDate'] . ' ' . $event['eventStartTime']);

                        if ($attendanceRole === 'Volunteer') {
                            $attendanceStatus = 'Volunteer';
                        } else {
                            if ($currentDateTime <= $eventStartDateTime) {
                                $attendanceStatus = 'Present';
                            } else {
                                $attendanceStatus = 'Late';
                            }
                        }

                        $pointsValue = getPointValue($attendanceStatus);
                        $currentQrUrl = "qr_attendance.php?event_id=" . $eventID;

                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare("
                            INSERT INTO event_attendance
                            (
                                attendanceType,
                                attendanceQR,
                                attendanceStatus,
                                checkInTime,
                                EventRegistrationID
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                        ");

                        $stmt->execute([
                            'QR',
                            $currentQrUrl,
                            $attendanceStatus,
                            $checkInTime,
                            $eventRegistrationID
                        ]);

                        $attendanceID = $pdo->lastInsertId();

                        $stmt = $pdo->prepare("
                            INSERT INTO points
                            (
                                pointsValue,
                                Attendance_ID
                            )
                            VALUES
                            (
                                ?,
                                ?
                            )
                        ");

                        $stmt->execute([
                            $pointsValue,
                            $attendanceID
                        ]);

                        $pdo->commit();

                        $message = "Attendance recorded successfully. Status: " . $attendanceStatus;
                        $messageType = "success";
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $message = "Error saving attendance: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

$participantMap = [];

foreach ($participants as $participant) {
    $participantMap[$participant['studentID']] = $participant['userName'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../STYLE/CSS/Module 4/qr_attendance_CSS.css">
    <title>QR Attendance Form</title>
</head>

<body class="attendance-page">

<?php if ($serverLocationBlocked): ?>

    <div class="attendance-card error-screen">
        <div class="error-icon">!</div>
        <h1>Access Denied</h1>
        <p><?php echo clean($message); ?></p>
        <p>Please scan the QR code again inside UMPSA Pekan.</p>
    </div>

<?php else: ?>

    <div class="attendance-card">

        <div id="locationCheck" class="location-check" style="display: block;">
            <div class="spinner"></div>
            <h2>Checking Your Location...</h2>
            <p>Please allow location access to continue attendance.</p>
            <p class="small-note">
                The attendance form will only appear when you are inside UMPSA Pekan.
            </p>
        </div>

        <div id="locationError" class="error-screen" style="display: none;">
            <div class="error-icon">!</div>
            <h1>Access Denied</h1>
            <p id="locationErrorMessage">
                You are not inside UMPSA Pekan.
            </p>
            <p>The attendance form cannot be accessed outside the campus area.</p>
            <p class="distance-text" id="distanceText"></p>
        </div>

        <div id="attendanceForm" class="form-box" style="display: none;">
            <div class="header">
                <h1>QR Attendance</h1>
                <p>FK Student Club & Event Management System</p>
            </div>

            <div class="event-info">
                <h2><?php echo clean($event['eventTitle']); ?></h2>
                <p><strong>Date:</strong> <?php echo clean($event['eventDate']); ?></p>
                <p>
                    <strong>Time:</strong>
                    <?php echo clean($event['eventStartTime']); ?>
                    -
                    <?php echo clean($event['eventEndTime']); ?>
                </p>
                <p><strong>Venue:</strong> <?php echo clean($event['eventVenue']); ?></p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="message <?php echo clean($messageType); ?>">
                    <?php echo clean($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="qr_attendance.php?event_id=<?php echo clean($eventID); ?>">

                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="accuracy" id="accuracy">

                <div class="form-group">
                    <label for="studentID">Student ID</label>
                    <input
                        type="text"
                        name="studentID"
                        id="studentID"
                        list="studentList"
                        placeholder="Example: CB24116"
                        autocomplete="off"
                        required
                    >

                    <datalist id="studentList">
                        <?php foreach ($participants as $participant): ?>
                            <option value="<?php echo clean($participant['studentID']); ?>">
                                <?php echo clean($participant['userName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="studentName">Student Name</label>
                    <input
                        type="text"
                        id="studentName"
                        class="readonly"
                        placeholder="Name will appear automatically"
                        readonly
                    >
                </div>

                <div class="form-group">
                    <label for="attendanceRole">Attendance Role</label>
                    <select name="attendanceRole" id="attendanceRole">
                        <option value="Participant">Participant</option>
                        <option value="Volunteer">Volunteer</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Submit Attendance</button>

                <p class="small-note">
                    Your attendance status will be automatically calculated as Present or Late based on the event start time.
                </p>
            </form>
        </div>

    </div>

    <script>
        const UMPSA_LAT = <?php echo json_encode($UMPSA_LAT); ?>;
        const UMPSA_LNG = <?php echo json_encode($UMPSA_LNG); ?>;
        const ALLOWED_RADIUS_METERS = <?php echo json_encode($ALLOWED_RADIUS_METERS); ?>;
        const LOCATION_ALREADY_VERIFIED = <?php echo json_encode($isLocationVerified); ?>;

        const participants = <?php echo json_encode($participantMap); ?>;

        const locationCheck = document.getElementById("locationCheck");
        const locationError = document.getElementById("locationError");
        const attendanceForm = document.getElementById("attendanceForm");
        const locationErrorMessage = document.getElementById("locationErrorMessage");
        const distanceText = document.getElementById("distanceText");

        const latitudeInput = document.getElementById("latitude");
        const longitudeInput = document.getElementById("longitude");
        const accuracyInput = document.getElementById("accuracy");

        const studentIDInput = document.getElementById("studentID");
        const studentNameInput = document.getElementById("studentName");

        function calculateDistanceMeters(lat1, lng1, lat2, lng2) {
            const earthRadius = 6371000;

            const lat1Rad = lat1 * Math.PI / 180;
            const lat2Rad = lat2 * Math.PI / 180;

            const deltaLat = (lat2 - lat1) * Math.PI / 180;
            const deltaLng = (lng2 - lng1) * Math.PI / 180;

            const a =
                Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
                Math.cos(lat1Rad) * Math.cos(lat2Rad) *
                Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);

            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return earthRadius * c;
        }

        function showAttendanceForm(latitude = "", longitude = "", accuracy = "") {
            latitudeInput.value = latitude;
            longitudeInput.value = longitude;
            accuracyInput.value = accuracy;

            locationCheck.style.display = "none";
            locationError.style.display = "none";
            attendanceForm.style.display = "block";
        }

        function showLocationError(message, distance = null, accuracy = null, allowedDistance = null) {
            locationCheck.style.display = "none";
            attendanceForm.style.display = "none";
            locationError.style.display = "block";

            locationErrorMessage.textContent = message;

            if (distance !== null) {
                let text = "Distance from UMPSA point: " + Math.round(distance) + " meters.";

                distanceText.textContent = text;
            } else {
                distanceText.textContent = "";
            }
        }

        function checkLocation() {
            locationCheck.style.display = "block";
            attendanceForm.style.display = "none";
            locationError.style.display = "none";

            if (!navigator.geolocation) {
                showLocationError("Your browser does not support location access.");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const accuracy = position.coords.accuracy || 0;

                    const distance = calculateDistanceMeters(
                        userLat,
                        userLng,
                        UMPSA_LAT,
                        UMPSA_LNG
                    );

                    const accuracyBonus = Math.min(Math.max(accuracy, 0), 1000);
                    const allowedDistance = ALLOWED_RADIUS_METERS + accuracyBonus;

                    if (distance <= allowedDistance) {
                        showAttendanceForm(userLat, userLng, accuracy);
                    } else {
                        showLocationError(
                            "You are not inside UMPSA Pekan.",
                            distance,
                            accuracy,
                            allowedDistance
                        );
                    }
                },
                function (error) {
                    let errorMessage = "Location permission denied. Please allow location access to submit attendance.";

                    if (error.code === error.TIMEOUT) {
                        errorMessage = "Location checking timeout. Please refresh the page and allow location access.";
                    }

                    if (error.code === error.POSITION_UNAVAILABLE) {
                        errorMessage = "Your current location cannot be detected. Please turn on GPS and try again.";
                    }

                    showLocationError(errorMessage);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 60000
                }
            );
        }

        studentIDInput.addEventListener("input", function () {
            const studentID = studentIDInput.value.trim();

            if (participants[studentID]) {
                studentNameInput.value = participants[studentID];
            } else {
                studentNameInput.value = "";
            }
        });

        window.addEventListener("load", function () {
            if (LOCATION_ALREADY_VERIFIED) {
                showAttendanceForm();
            } else {
                checkLocation();
            }
        });
    </script>

<?php endif; ?>

</body>
</html>