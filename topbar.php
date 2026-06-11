<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$BASE = '..';

$name = $_SESSION['name'] ?? 'User';
$studentID = $_SESSION['studentID'] ?? '';
$role = $_SESSION['role'] ?? 'Student';
$currentMode = $_SESSION['current_module'] ?? 'student';
$membershipRole = $_SESSION['membershipRole'] ?? '';
$profilePicture = $_SESSION['userProfilePicture'] ?? '';

$profilePicturePath = $BASE . '/Module_1/uploads/' . $profilePicture;

/*
    Display role in topbar.

    If user is in committee mode, show actual committee position:
    Example: Committee - President

    If user goes back to student mode, show Student.
*/
if ($currentMode === 'committee' && !empty($membershipRole)) {
    $displayRole = 'Committee - ' . $membershipRole;
} elseif ($currentMode === 'committee') {
    $displayRole = 'Committee';
} elseif ($role === 'Administrator') {
    $displayRole = 'Administrator';
} else {
    $displayRole = 'Student';
}
?>

<link rel="stylesheet" href="../STYLE/CSS/topBar_CSS.css?v=11">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="topbar">
    <div class="topbar-left">
        <a href="https://fk.umpsa.edu.my/" class="topbar-logo-link" target="_blank">
            <img src="<?= $BASE ?>/Images/logo.png" alt="Logo" class="topbar-logo">
        </a>
        
        <h5>FK STUDENT CLUB & EVENT MANAGEMENT SYSTEM</h5>
    </div>

    <div class="user-profile-dropdown dropdown">
        <div class="user-info-text d-none d-lg-flex">
            <span class="user-name"><?= htmlspecialchars($name); ?></span>

            <?php if (!empty($studentID)): ?>
                <span class="user-id"><?= htmlspecialchars($studentID); ?></span>
            <?php endif; ?>

            <span class="user-role"><?= htmlspecialchars($displayRole); ?></span>
        </div>

        <?php if (!empty($profilePicture)): ?> 
            <img src="<?= htmlspecialchars($profilePicturePath); ?>" 
                 class="profile-pic dropdown-toggle"
                 id="userDropdown"
                 data-bs-toggle="dropdown"
                 aria-expanded="false"
                 style="object-fit: cover;"
                 alt="Profile Picture">
        <?php else: ?>
            <div class="profile-pic empty-profile dropdown-toggle"
                 id="userDropdown"
                 data-bs-toggle="dropdown"
                 aria-expanded="false"></div>
        <?php endif; ?>

        <div class="dropdown">
            <ul class="dropdown-menu shadow border-0" aria-labelledby="userDropdown">
                <li>
                    <a class="dropdown-item py-2" href="<?= $BASE ?>/Module_1/profile.php">
                        <i class="bi bi-person me-2 text-gray-400"></i> My Profile
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item py-2 text-danger" href="<?= $BASE ?>/Module_1/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>