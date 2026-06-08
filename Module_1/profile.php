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
$showPasswordModal = false;

// 1. Fetch current user data
$stmt = $pdo->prepare("SELECT u.*, s.studentID, a.staffID FROM user u 
                       LEFT JOIN student s ON u.User_ID = s.User_ID 
                       LEFT JOIN admin a ON u.User_ID = a.User_ID 
                       WHERE u.User_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Handle Profile Update / Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'update_profile';

    try {
        $pdo->beginTransaction();

        if ($action === 'change_password') {

            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';

            // Get current password from database
            $password_stmt = $pdo->prepare("SELECT userPassword FROM user WHERE User_ID = ?");
            $password_stmt->execute([$user_id]);
            $currentUser = $password_stmt->fetch(PDO::FETCH_ASSOC);

            // Check old password
            if (empty($old_password) || !password_verify($old_password, $currentUser['userPassword'])) {
                throw new Exception("Old password is incorrect.");
            }

            // Check password strength
            if (
                strlen($new_password) < 8 ||
                !preg_match('/[A-Z]/', $new_password) ||
                !preg_match('/[a-z]/', $new_password) ||
                !preg_match('/[0-9]/', $new_password) ||
                !preg_match('/[\W_]/', $new_password)
            ) {
                throw new Exception("New password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_sql = "UPDATE user SET userPassword = ? WHERE User_ID = ?";
            $pdo->prepare($update_sql)->execute([$hashed_password, $user_id]);

            $message = "Password updated successfully!";
            $messageType = "success";

        } else {

            $email = trim($_POST['email']);
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone']);

            $update_sql = "UPDATE user SET userEmail = ?, userName = ?, userPhoneNumber = ? WHERE User_ID = ?";
            $pdo->prepare($update_sql)->execute([$email, $name, $phone, $user_id]);

            // Handle Profile Picture Upload
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {

                $upload_dir = 'uploads/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {

                    $pdo->prepare("UPDATE user SET userProfilePicture = ? WHERE User_ID = ?")
                        ->execute([$file_name, $user_id]);

                    $_SESSION['userProfilePicture'] = $file_name;
                }
            }

            $message = "Profile updated successfully!";
            $messageType = "success";
        }

        $pdo->commit();

        // Refresh full local data
        $refresh_stmt = $pdo->prepare("SELECT u.*, s.studentID, a.staffID FROM user u 
                                       LEFT JOIN student s ON u.User_ID = s.User_ID 
                                       LEFT JOIN admin a ON u.User_ID = a.User_ID 
                                       WHERE u.User_ID = ?");
        $refresh_stmt->execute([$user_id]);
        $user = $refresh_stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e->getMessage();
        $messageType = "danger";

        if ($action === 'change_password') {
            $showPasswordModal = true;
        }
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

$userRole = $user['userRole'] ?? ($_SESSION['role'] ?? '');

$display_id = ($userRole === 'Administrator')
    ? ($user['staffID'] ?? '')
    : ($user['studentID'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module_1/profile_CSS.css">
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

                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h1 class="h5 mb-0 fw-bold">My Profile Settings</h1>

                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    Change Password
                                </button>
                            </div>

                            <div class="card-body p-4">
                                <?php if ($message): ?>
                                    <div class="alert alert-<?php echo $messageType; ?>">
                                        <?php echo $message; ?>
                                    </div>
                                <?php endif; ?>

                                <form action="profile.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="update_profile">

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
                                                    (<?php echo ($userRole === 'Administrator') ? 'Staff ID' : 'Student ID'; ?>)
                                                </label>
                                                <input type="text" class="form-control bg-light"
                                                    value="<?php echo htmlspecialchars($display_id); ?>" readonly>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Full Name</label>
                                                <input type="text" name="name" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['userName']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Telephone Number</label>
                                                <input type="tel" name="phone" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['userPhoneNumber'] ?? ''); ?>"
                                                    placeholder="Enter telephone number">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Email Address</label>
                                                <input type="email" name="email" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['userEmail']); ?>" required>
                                            </div>

                                            <!-- Club Memberships Section -->
                                            <?php if (($_SESSION['role'] ?? '') !== 'Administrator'): ?>

                                                <div class="mb-4">
                                                    <label class="form-label fw-bold border-bottom w-100 pb-1">
                                                        My Club Memberships
                                                    </label>

                                                    <?php if (count($memberships) > 0): ?>
                                                        <ul class="list-group list-group-flush">
                                                            <?php foreach ($memberships as $m): ?>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                                                    <div>
                                                                        <span class="fw-bold">
                                                                            <?= htmlspecialchars($m['clubName']) ?>
                                                                        </span>

                                                                        <div class="small text-muted">
                                                                            Role: <?= htmlspecialchars($m['membershipRole'] ?? 'Member') ?>
                                                                        </div>
                                                                    </div>

                                                                    <span class="badge bg-success rounded-pill">
                                                                        Joined <?= date('M Y', strtotime($m['joinDate'])) ?>
                                                                    </span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <p class="small text-muted mb-0">No active club memberships found.</p>
                                                    <?php endif; ?>
                                                </div>

                                            <?php endif; ?>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary fw-bold py-2">
                                                    Save Profile Changes
                                                </button>
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

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="changePasswordModalLabel">Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">

                        <?php if ($showPasswordModal && $message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Old Password</label>
                            <input type="password" name="old_password" class="form-control"
                                placeholder="Enter old password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control"
                                placeholder="Enter new password" required>

                            <small id="passwordHelp" class="text-muted">
                                Password must contain uppercase, lowercase, number, special character, and at least 8 characters.
                            </small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Save Password
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

    <script>
        const newPassword = document.getElementById('new_password');
        const passwordHelp = document.getElementById('passwordHelp');

        if (newPassword) {
            newPassword.addEventListener('input', function () {
                const value = newPassword.value;

                const hasUppercase = /[A-Z]/.test(value);
                const hasLowercase = /[a-z]/.test(value);
                const hasNumber = /[0-9]/.test(value);
                const hasSpecial = /[\W_]/.test(value);
                const hasLength = value.length >= 8;

                if (value.length === 0) {
                    passwordHelp.className = "text-muted";
                    passwordHelp.innerHTML = "Password must contain uppercase, lowercase, number, special character, and at least 8 characters.";
                } 
                else if (hasUppercase && hasLowercase && hasNumber && hasSpecial && hasLength) {
                    passwordHelp.className = "text-success";
                    passwordHelp.innerHTML = "Strong password.";
                } 
                else {
                    passwordHelp.className = "text-danger";
                    passwordHelp.innerHTML = "Weak password. Use uppercase, lowercase, number, special character, and at least 8 characters.";
                }
            });
        }

        <?php if ($showPasswordModal): ?>
            const passwordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            passwordModal.show();
        <?php endif; ?>
    </script>
</body>

</html>