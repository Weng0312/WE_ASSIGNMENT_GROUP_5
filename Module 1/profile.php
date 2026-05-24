<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// 1. Fetch current user data
$stmt = $pdo->prepare("SELECT u.*, s.studentID, a.staffID FROM user u 
                       LEFT JOIN student s ON u.User_ID = s.User_ID 
                       LEFT JOIN admin a ON u.User_ID = a.User_ID 
                       WHERE u.User_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $new_password = $_POST['new_password'];

    try {
        $pdo->beginTransaction();

        // Update user table
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE user SET userEmail = ?, userName = ?, userPassword = ? WHERE User_ID = ?";
            $pdo->prepare($update_sql)->execute([$email, $name, $hashed_password, $user_id]);
        } else {
            $update_sql = "UPDATE user SET userEmail = ?, userName = ? WHERE User_ID = ?";
            $pdo->prepare($update_sql)->execute([$email, $name, $user_id]);
        }

        // Handle Profile Picture Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {

        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);

        $file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;

        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {

            $pdo->prepare("UPDATE user SET userProfilePicture = ? WHERE User_ID = ?")->execute([$file_name, $user_id]);
            $_SESSION['userProfilePicture'] = $file_name;
        }
}

        $pdo->commit();
        $message = "Profile updated successfully!";
        $messageType = "success";

        // Refresh local data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error updating profile: " . $e->getMessage();
        $messageType = "danger";
    }
}

// 3. Fetch Club Memberships
$membership_sql = "SELECT m.*, c.clubName 
                   FROM club_membership m 
                   JOIN club c ON m.Club_ID = c.Club_ID 
                   WHERE m.User_ID = ? AND m.membershipStatus = 'Active'";
$m_stmt = $pdo->prepare($membership_sql);
$m_stmt->execute([$user_id]);
$memberships = $m_stmt->fetchAll();

$display_id = ($user['userRole'] === 'Administrator') ? $user['staffID'] : $user['studentID'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module1/profile_CSS.css">

</head>

<body>
    <?php include '../topbar.php'; ?>

    <div id="wrapper">
    <?php
    if ($_SESSION['current_module'] === 'committee') {
        $dashboardType = 'committee';
    }
    elseif ($_SESSION['current_module'] === 'student') {
        $dashboardType = 'student';
    }
    ?>

    <?php include '../sidebar.php'; ?>

        <div id="content">

            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 mt-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold">My Profile Settings</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($message): ?>
                                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                                <?php endif; ?>

                                <form action="profile.php" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-4 text-center mb-4">
                                            <div class="mb-3">
                                                <?php if (!empty($user['userProfilePicture'])): ?>
                                                    <?php
                                                        $profilePic = !empty($user['userProfilePicture'])
                                                        ? "uploads/" . $user['userProfilePicture']
                                                        : "uploads/default.png";
                                                    ?>
                                                    <img src="<?php echo $profilePic; ?>" class="profile-page-pic" alt="">
                                                <?php else: ?>
                                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center border shadow-sm"
                                                        style="width: 150px; height: 150px;">
                                                        <i class="bi bi-person text-secondary display-1"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <input type="file" name="profile_pic" class="form-control form-control-sm">
                                            <small class="text-muted">Upload a new profile picture</small>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Login ID
                                                    (<?php echo ($user['userRole'] === 'Administrator') ? 'Staff ID' : 'Student ID'; ?>)</label>
                                                <input type="text" class="form-control bg-light"
                                                    value="<?php echo htmlspecialchars($display_id); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Full Name</label>
                                                <input type="text" name="name" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['userName']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Email Address</label>
                                                <input type="email" name="email" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['userEmail']); ?>"
                                                    required>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">New Password</label>
                                                <input type="password" name="new_password" class="form-control"
                                                    placeholder="Leave blank to keep current password">
                                            </div>

                                            <!-- Club Memberships Section -->
                                            <div class="mb-4">
                                                <label class="form-label fw-bold border-bottom w-100 pb-1">My Club
                                                    Memberships</label>
                                                <?php if (count($memberships) > 0): ?>
                                                    <ul class="list-group list-group-flush">
                                                        <?php foreach ($memberships as $m): ?>
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                                                <div>
                                                                    <span
                                                                        class="fw-bold"><?= htmlspecialchars($m['clubName']) ?></span>
                                                                    <div class="small text-muted">Role:
                                                                        <?= htmlspecialchars($m['membershipRole'] ?? 'Member') ?>
                                                                    </div>
                                                                </div>
                                                                <span class="badge bg-success rounded-pill">Joined
                                                                    <?= date('M Y', strtotime($m['joinDate'])) ?></span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="small text-muted mb-0">No active club memberships found.</p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary fw-bold py-2">Save Profile
                                                    Changes</button>
                                            </div>
                                        </div>
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