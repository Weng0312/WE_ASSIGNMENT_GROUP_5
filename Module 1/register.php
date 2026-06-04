<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Security Check: Only Administrators can register new users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: index.php");
    exit();
}

$message = "";
$messageType = "";

// Fetch clubs for the dropdown
$clubs = $pdo->query("SELECT Club_ID, clubName FROM club WHERE clubStatus = 'Active'")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginID = strtoupper(trim($_POST['loginID']));
    $name = $_POST['name'];

    // Auto generate email by using ID + @gmail.com
    $email = strtolower($loginID) . '@gmail.com';

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $club_id = $_POST['club_id'] ?? null;
    $committee_pos = $_POST['committee_pos'] ?? null;

    try {
        $pdo->beginTransaction();

        // 1. Insert into user table
        $db_role = ($role === 'Committee') ? 'Student' : $role;
        $stmt = $pdo->prepare("INSERT INTO user (userName, userPassword, userEmail, userRole) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $password, $email, $db_role]);
        $user_id = $pdo->lastInsertId();

        // 2. Insert into specialization tables
        if ($role === 'Administrator') {
            $stmt = $pdo->prepare("INSERT INTO admin (User_ID, staffID) VALUES (?, ?)");
            $stmt->execute([$user_id, $loginID]);
        } else {

            // Read student ID
            $studentID = strtoupper(trim($loginID));

            // Detect programme from first 2 characters
            $programmeCode = substr($studentID, 0, 2);

            switch ($programmeCode) {
                case 'CB':
                    $programme = 'Software Engineering';
                    break;

                case 'CD':
                    $programme = 'Multimedia Software';
                    break;

                case 'CA':
                    $programme = 'Computer System & Networking';
                    break;

                case 'CF':
                    $programme = 'Cyber Security';
                    break;

                default:
                    $programme = 'Unknown';
                    break;
            }

            // Detect year from 3rd and 4th characters
            $yearCode = substr($studentID, 2, 2);

            switch ($yearCode) {
                case '25':
                    $studyYear = 'Year 1';
                    break;

                case '24':
                    $studyYear = 'Year 2';
                    break;

                case '23':
                    $studyYear = 'Year 3';
                    break;

                case '22':
                    $studyYear = 'Year 4';
                    break;

                default:
                    $studyYear = 'Unknown';
                    break;
            }

            // Insert into student table
            // Your real column names are programmeName and yearOfStudy
            $stmt = $pdo->prepare("
                INSERT INTO student 
                (User_ID, studentID, programmeName, yearOfStudy, totalPoints) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$user_id, $studentID, $programme, $studyYear]);

            // 3. If Committee, join club
            if (strpos($role, 'Committee') !== false && !empty($club_id)) {
                $stmt = $pdo->prepare("INSERT INTO club_membership (Club_ID, User_ID, membershipRole, joinDate, membershipStatus) VALUES (?, ?, ?, CURDATE(), 'Active')");
                $stmt->execute([$club_id, $user_id, $committee_pos]);
            }
        }

        $pdo->commit();
        $message = "User registered successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Registration failed: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <script>
        function toggleCommitteeOptions() {
            const role = document.getElementById('role').value;
            const commOptions = document.getElementById('committeeOptions');
            const loginLabel = document.getElementById('loginLabel');

            if (role === 'Committee') {
                commOptions.style.display = 'block';
                loginLabel.innerText = 'Student ID';
            } else {
                commOptions.style.display = 'none';
                loginLabel.innerText = (role === 'Administrator') ? 'Staff ID' : 'Student ID';
            }
        }

        function generateEmail() {
            const loginID = document.getElementById('loginID').value.trim();
            const email = document.getElementById('email');

            if (loginID !== '') {
                email.value = loginID.toLowerCase() + '@gmail.com';
            } else {
                email.value = '';
            }
        }
    </script>
</head>

<body>
    <?php include '../topbar.php'; ?>
    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content">

            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold">Register New System User</h5>
                            </div>

                            <div class="card-body p-4">
                                <?php if ($message): ?>
                                    <div class="alert alert-<?php echo $messageType; ?>">
                                        <?php echo $message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold" id="loginLabel">Student/Staff ID</label>
                                            <input type="text" name="loginID" id="loginID" class="form-control"
                                                placeholder="e.g. CB24001" oninput="generateEmail()" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">User Role</label>
                                            <select name="role" id="role" class="form-select"
                                                onchange="toggleCommitteeOptions()" required>
                                                <option value="Student">Student</option>
                                                <option value="Committee">Committee Member</option>
                                                <option value="Administrator">Administrator</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="committeeOptions" style="display:none;"
                                        class="bg-light p-3 rounded mb-3 border">
                                        <h6 class="fw-bold mb-3">
                                            <i class="bi bi-diagram-3 me-2"></i>Committee Assignment
                                        </h6>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold">Assigned Club</label>
                                                <select name="club_id" class="form-select">
                                                    <option value="">-- Select Club --</option>
                                                    <?php foreach ($clubs as $club): ?>
                                                        <option value="<?= $club['Club_ID'] ?>">
                                                            <?= htmlspecialchars($club['clubName']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold">Committee Position</label>
                                                <select name="committee_pos" class="form-select">
                                                    <option value="President">President</option>
                                                    <option value="Secretary">Secretary</option>
                                                    <option value="Treasurer">Treasurer</option>
                                                    <option value="Committee Member">Committee Member</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Full Name</label>
                                        <input type="text" name="name" class="form-control"
                                            placeholder="Enter full name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control"
                                            placeholder="Auto generated email" readonly required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Initial Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary fw-bold py-2">
                                            Create Account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>