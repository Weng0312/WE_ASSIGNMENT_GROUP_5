<?php
// Start session to match admin dashboard configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['current_module'] = 'admin';

// 1. Establish Database Connection matching admin dashboard
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Fallback logic check for connection identifier mapping
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

// Security Check verification logic layout
$isAuthorized = isset($_SESSION['user_id']) && $_SESSION['role'] === 'Administrator';

// Intercept security failure directly if request is an asynchronous pipeline fetch
if (!$isAuthorized && isset($_GET['action'])) {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Unauthenticated session status context. Please log in again.']);
    exit;
}

// Redirect standard web view browsers to index fallback if validation checks fail
if (!$isAuthorized) {
    header("Location: index.php");
    exit();
}

// Update club membership role from club profile page popup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_membership_role_id'], $_POST['return_club_id'], $_POST['new_membership_role'])) {
    $editMembershipId = intval($_POST['edit_membership_role_id']);
    $returnClubId = intval($_POST['return_club_id']);
    $newMembershipRole = trim($_POST['new_membership_role']);

    $allowedRoles = [
        'President',
        'Vice President',
        'Secretary',
        'Treasurer',
        'Committee',
        'Event Coordinator',
        'Normal Committee'
    ];

    if ($editMembershipId > 0 && $returnClubId > 0 && in_array($newMembershipRole, $allowedRoles, true)) {
        $stmtUpdateRole = $pdo->prepare("
            UPDATE club_membership
            SET membershipRole = ?
            WHERE Membership_ID = ?
            AND Club_ID = ?
        ");
        $stmtUpdateRole->execute([$newMembershipRole, $editMembershipId, $returnClubId]);
    }

    header("Location: ?view_page=" . $returnClubId);
    exit();
}

// Demote committee member back to normal club member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demote_membership_id'], $_POST['return_club_id'])) {
    $demoteMembershipId = intval($_POST['demote_membership_id']);
    $returnClubId = intval($_POST['return_club_id']);

    if ($demoteMembershipId > 0 && $returnClubId > 0) {
        $stmtDemoteMember = $pdo->prepare("
            UPDATE club_membership
            SET membershipRole = 'Member'
            WHERE Membership_ID = ?
            AND Club_ID = ?
        ");
        $stmtDemoteMember->execute([$demoteMembershipId, $returnClubId]);
    }

    header("Location: ?view_page=" . $returnClubId);
    exit();
}

// Remove normal club member from club profile page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_membership_id'], $_POST['return_club_id'])) {
    $removeMembershipId = intval($_POST['remove_membership_id']);
    $returnClubId = intval($_POST['return_club_id']);

    if ($removeMembershipId > 0 && $returnClubId > 0) {
        $stmtRemoveMember = $pdo->prepare("
            DELETE FROM club_membership
            WHERE Membership_ID = ?
            AND Club_ID = ?
        ");
        $stmtRemoveMember->execute([$removeMembershipId, $returnClubId]);
    }

    header("Location: ?view_page=" . $returnClubId);
    exit();
}

// Helper wrapper function declaration context check
if (!function_exists('json_api_respond')) {
    function json_api_respond($success, $data = null, $message = '')
    {
        return json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    }
}

// 2. Async API AJAX Payload Controller Handling Routing
if (isset($_GET['action'])) {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    try {
        if ($action === 'list') {
            $sql = "SELECT c.*, COUNT(m.Membership_ID) as total_members 
                    FROM club c 
                    LEFT JOIN club_membership m ON c.Club_ID = m.Club_ID 
                    GROUP BY c.Club_ID 
                    ORDER BY c.Club_ID DESC";

            $stmt = $pdo->query($sql);
            $rawClubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $normalizedClubs = [];

            foreach ($rawClubs as $row) {
                $normalizedClubs[] = [
                    'Club_ID' => $row['Club_ID'] ?? null,
                    'clubName' => $row['clubName'] ?? 'Unnamed Club',
                    'clubAdvisorName' => $row['clubAdvisorName'] ?? 'No Advisor',
                    'clubDescription' => $row['clubDescription'] ?? '',
                    'clubStatus' => $row['clubStatus'] ?? 'Active',
                    'total_members' => $row['total_members'] ?? 0
                ];
            }

            echo json_api_respond(true, $normalizedClubs);
            exit;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $club_id = !empty($_POST['Club_ID']) ? intval($_POST['Club_ID']) : null;
            $name = trim($_POST['clubName'] ?? '');
            $description = trim($_POST['clubDescription'] ?? '');
            $advisor = trim($_POST['clubAdvisorName'] ?? '');
            $status = trim($_POST['clubStatus'] ?? 'Active');

            if (empty($name) || empty($advisor)) {
                echo json_api_respond(false, null, 'Please supply both a Club Name and an Advisor name.');
                exit;
            }

            if ($club_id) {
                $sql = "UPDATE club 
                        SET clubName = ?, clubDescription = ?, clubAdvisorName = ?, clubStatus = ? 
                        WHERE Club_ID = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $advisor, $status, $club_id]);

                echo json_api_respond(true, null, 'Club properties successfully updated.');
            } else {
                $sql = "INSERT INTO club (clubName, clubDescription, clubAdvisorName, clubStatus) 
                        VALUES (?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $advisor, $status]);

                echo json_api_respond(true, null, 'New club registered successfully.');
            }

            exit;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['Club_ID'])) {
                $stmt = $pdo->prepare("DELETE FROM club WHERE Club_ID = ?");
                $stmt->execute([$_POST['Club_ID']]);

                echo json_api_respond(true, null, 'Club safely dropped from record.');
            } else {
                echo json_api_respond(false, null, 'Invalid request parameter.');
            }

            exit;
        }

        if ($action === 'toggle_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['Club_ID']) && isset($_POST['current_status'])) {
                $newStatus = ($_POST['current_status'] === 'Active') ? 'Inactive' : 'Active';

                $stmt = $pdo->prepare("UPDATE club SET clubStatus = ? WHERE Club_ID = ?");
                $stmt->execute([$newStatus, $_POST['Club_ID']]);

                echo json_api_respond(true, ['new_status' => $newStatus], 'Status updated successfully.');
            } else {
                echo json_api_respond(false, null, 'Incomplete operational data.');
            }

            exit;
        }

    } catch (Exception $e) {
        echo json_api_respond(false, null, 'Database Critical Error: ' . $e->getMessage());
        exit;
    }
}

// ==========================================
// VIEW PAGE SPLIT ENGINE
// ==========================================
if (isset($_GET['view_page'])) {
    $clubId = intval($_GET['view_page']);

    $sql = "SELECT c.*, COUNT(m.Membership_ID) as total_members 
            FROM club c 
            LEFT JOIN club_membership m ON c.Club_ID = m.Club_ID 
            WHERE c.Club_ID = ?
            GROUP BY c.Club_ID";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clubId]);
    $club = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$club) {
        die("Club record could not be tracked inside organizational scope.");
    }

    $clubName = $club['clubName'] ?? 'Unnamed Club';
    $clubAdvisor = $club['clubAdvisorName'] ?? 'No Advisor';
    $clubDesc = $club['clubDescription'] ?? 'No description added yet.';
    $clubStatus = $club['clubStatus'] ?? 'Active';

    $stmtComm = $pdo->prepare("
        SELECT 
            m.Membership_ID, 
            m.User_ID, 
            m.membershipRole, 
            u.userName, 
            u.userEmail 
        FROM club_membership m 
        JOIN user u ON m.User_ID = u.User_ID 
        WHERE m.Club_ID = ? 
        AND m.membershipRole IN (
            'President', 
            'Vice President', 
            'Secretary', 
            'Treasurer', 
            'Committee', 
            'Event Coordinator', 
            'Normal Committee'
        )
        ORDER BY FIELD(
            m.membershipRole, 
            'President', 
            'Vice President', 
            'Secretary', 
            'Treasurer', 
            'Committee', 
            'Event Coordinator', 
            'Normal Committee'
        ) ASC
    ");

    $stmtComm->execute([$clubId]);
    $committees = $stmtComm->fetchAll(PDO::FETCH_ASSOC);

    $stmtMem = $pdo->prepare("
        SELECT 
            m.Membership_ID, 
            m.User_ID, 
            m.joinDate, 
            u.userName, 
            u.userEmail 
        FROM club_membership m 
        JOIN user u ON m.User_ID = u.User_ID 
        WHERE m.Club_ID = ? 
        AND m.membershipRole = 'Member'
        ORDER BY m.joinDate DESC
    ");

    $stmtMem->execute([$clubId]);
    $memberships = $stmtMem->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($clubName); ?> - Profile Details</title>

        <link rel="stylesheet" href="../STYLE/CSS/Module_1/adminDashboard_CSS.css">
        <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

        <style>
            body {
                background-color: #f8f9fa;
                color: #333333;
            }

            .back-btn {
                font-size: 0.9rem;
                font-weight: 500;
                color: #6c757d;
                text-decoration: none;
                transition: color 0.2s;
            }

            .back-btn:hover {
                color: #0d6efd;
            }

            .profile-header-card {
                background: #ffffff;
                border: 1px solid #eef2f5;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            }

            .club-avatar-placeholder {
                width: 64px;
                height: 64px;
                background-color: #e7f1ff;
                color: #0d6efd;
                font-size: 1.5rem;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .meta-item-box {
                border-left: 3px solid #e9ecef;
                padding-left: 15px;
            }

            .meta-label {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #8c98a5;
                font-weight: 600;
                margin-bottom: 2px;
            }

            .meta-value {
                font-size: 0.95rem;
                font-weight: 600;
                color: #2d3748;
            }

            .clean-section-card {
                background: #ffffff;
                border: 1px solid #eef2f5;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
                margin-bottom: 30px;
                padding: 24px;
            }

            .section-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: #1a202c;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }

            .table-clean th {
                font-size: 0.75rem !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 600;
                color: #8c98a5;
                background-color: #fafbfc !important;
                border-bottom: 1px solid #edf2f7 !important;
                padding: 14px 16px !important;
            }

            .table-clean td {
                padding: 16px !important;
                font-size: 0.9rem;
                color: #4a5568;
                border-bottom: 1px solid #edf2f7 !important;
            }

            .table-clean tr:last-child td {
                border-bottom: none !important;
            }

            .badge-active-clean {
                background-color: #e6f4ea;
                color: #137333;
                font-weight: 600;
                font-size: 0.8rem;
                padding: 6px 16px;
                border-radius: 50px;
            }

            .badge-inactive-clean {
                background-color: #fce8e6;
                color: #c5221f;
                font-weight: 600;
                font-size: 0.8rem;
                padding: 6px 16px;
                border-radius: 50px;
            }

            .role-badge {
                background-color: #f1f3f5;
                color: #495057;
                border: 1px solid #e9ecef;
                font-weight: 500;
                font-size: 0.8rem;
                padding: 4px 10px;
                border-radius: 6px;
            }

            .table-action-group {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .table-action-group form {
                margin: 0;
            }

            .btn-action-mini {
                font-size: 0.75rem;
                font-weight: 600;
                padding: 5px 10px;
                border-radius: 6px;
            }
        </style>
    </head>

    <body>
        <?php include '../topbar.php'; ?>

        <div id="wrapper">
            <?php include '../sidebar.php'; ?>

            <div id="content">
                <div class="container py-4" style="max-width: 1000px;">

                    <div class="mb-3">
                        <a href="?" class="back-btn d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-arrow-left"></i> Return to Directory Management
                        </a>
                    </div>

                    <div class="profile-header-card p-4 mb-4">
                        <div
                            class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="club-avatar-placeholder">
                                    <i class="fa-solid fa-users-gear"></i>
                                </div>

                                <div>
                                    <h2 class="fw-bold text-dark mb-1" style="letter-spacing: -0.5px;">
                                        <?php echo htmlspecialchars($clubName); ?>
                                    </h2>

                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span
                                            class="<?php echo $clubStatus === 'Active' ? 'badge-active-clean' : 'badge-inactive-clean'; ?>">
                                            <?php echo htmlspecialchars($clubStatus); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: #e9ecef;">

                        <div class="row g-4">
                            <div class="col-sm-4">
                                <div class="meta-item-box">
                                    <div class="meta-label">Club Advisor Assigned</div>
                                    <div class="meta-value text-truncate">
                                        <?php echo htmlspecialchars($clubAdvisor); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="meta-item-box">
                                    <div class="meta-label">Active Database Roster</div>
                                    <div class="meta-value">
                                        <?php echo (count($committees) + count($memberships)); ?> Total Registered
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="meta-item-box">
                                    <div class="meta-label">Standard Members</div>
                                    <div class="meta-value text-success">
                                        <?php echo count($memberships); ?> Active
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clean-section-card">
                        <div class="section-title">
                            <i class="fa-solid fa-align-left text-muted" style="font-size: 0.95rem;"></i> Description /
                            About
                        </div>

                        <p class="text-secondary mb-0 lh-base" style="white-space: pre-line; font-size: 0.95rem;">
                            <?php echo htmlspecialchars($clubDesc); ?>
                        </p>
                    </div>

                    <div class="clean-section-card p-0 overflow-hidden">
                        <div class="p-4 pb-2">
                            <div class="section-title mb-0">
                                <i class="fa-solid fa-user-shield text-primary" style="font-size: 0.95rem;"></i> Executive
                                Committee Board
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-clean align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;">Executive Name</th>
                                        <th style="width: 22%;">Assigned Board Position</th>
                                        <th style="width: 28%;">Contact Email Address</th>
                                        <th style="width: 20%;" class="text-center">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (empty($committees)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small">
                                                No executive committee board members assigned.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($committees as $comm): ?>
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    <?php echo htmlspecialchars($comm['userName']); ?>
                                                </td>

                                                <td>
                                                    <span class="role-badge">
                                                        <?php echo htmlspecialchars($comm['membershipRole']); ?>
                                                    </span>
                                                </td>

                                                <td class="text-secondary">
                                                    <?php echo htmlspecialchars($comm['userEmail']); ?>
                                                </td>

                                                <td>
                                                    <div class="table-action-group justify-content-center">
                                                        <button type="button"
                                                            class="btn btn-outline-warning btn-action-mini membership-role-edit-btn"
                                                            data-membership-id="<?php echo htmlspecialchars($comm['Membership_ID']); ?>"
                                                            data-club-id="<?php echo htmlspecialchars($clubId); ?>"
                                                            data-current-role="<?php echo htmlspecialchars($comm['membershipRole']); ?>"
                                                            data-user-name="<?php echo htmlspecialchars($comm['userName']); ?>">
                                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                                        </button>

                                                        <form method="POST"
                                                            onsubmit="return confirm('Remove this committee role? This student will become a normal club member.');">
                                                            <input type="hidden" name="demote_membership_id"
                                                                value="<?php echo htmlspecialchars($comm['Membership_ID']); ?>">
                                                            <input type="hidden" name="return_club_id"
                                                                value="<?php echo htmlspecialchars($clubId); ?>">

                                                            <button type="submit" class="btn btn-outline-danger btn-action-mini">
                                                                <i class="fa-regular fa-trash-can"></i> Remove
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="clean-section-card p-0 overflow-hidden">
                        <div class="p-4 pb-2">
                            <div class="section-title mb-0">
                                <i class="fa-solid fa-address-book text-success" style="font-size: 0.95rem;"></i> General
                                Club Registry Log
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-clean align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">Membership ID</th>
                                        <th style="width: 34%;">Student Name</th>
                                        <th style="width: 25%;">Enrollment Date</th>
                                        <th style="width: 23%;" class="text-center">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if (empty($memberships)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small">
                                                No structural membership logs discovered.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($memberships as $mem): ?>
                                            <tr>
                                                <td class="text-mono text-muted">
                                                    #<?php echo htmlspecialchars($mem['Membership_ID']); ?>
                                                </td>

                                                <td class="fw-semibold text-dark">
                                                    <?php echo htmlspecialchars($mem['userName']); ?>
                                                    <br>
                                                    <span class="text-muted small" style="font-size:0.75rem; font-weight: normal;">
                                                        <?php echo htmlspecialchars($mem['userEmail']); ?>
                                                    </span>
                                                </td>

                                                <td class="text-secondary">
                                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($mem['joinDate']))); ?>
                                                </td>

                                                <td>
                                                    <div class="table-action-group justify-content-center">
                                                        <button type="button"
                                                            class="btn btn-outline-warning btn-action-mini membership-role-edit-btn"
                                                            data-membership-id="<?php echo htmlspecialchars($mem['Membership_ID']); ?>"
                                                            data-club-id="<?php echo htmlspecialchars($clubId); ?>"
                                                            data-current-role="Member"
                                                            data-user-name="<?php echo htmlspecialchars($mem['userName']); ?>">
                                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                                        </button>

                                                        <form method="POST"
                                                            onsubmit="return confirm('Are you sure you want to remove this member from this club?');">
                                                            <input type="hidden" name="remove_membership_id"
                                                                value="<?php echo htmlspecialchars($mem['Membership_ID']); ?>">
                                                            <input type="hidden" name="return_club_id"
                                                                value="<?php echo htmlspecialchars($clubId); ?>">

                                                            <button type="submit" class="btn btn-outline-danger btn-action-mini">
                                                                <i class="fa-regular fa-trash-can"></i> Remove
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="membershipRoleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow border-0">
                    <div class="modal-header bg-light py-3">
                        <h5 class="modal-title fw-bold text-dark">Edit Committee Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" id="membershipRoleForm">
                        <div class="modal-body p-4">
                            <input type="hidden" name="edit_membership_role_id" id="editMembershipRoleId">
                            <input type="hidden" name="return_club_id" id="editReturnClubId">

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small mb-1">Student Name</label>
                                <input type="text" id="editMembershipUserName" class="form-control" readonly>
                            </div>

                            <div class="mb-1">
                                <label class="form-label fw-semibold text-muted small mb-1">Choose Committee Role</label>

                                <select name="new_membership_role" id="editMembershipRoleSelect" class="form-select"
                                    required>
                                    <option value="" disabled>Choose committee role</option>
                                    <option value="President">President</option>
                                    <option value="Vice President">Vice President</option>
                                    <option value="Secretary">Secretary</option>
                                    <option value="Treasurer">Treasurer</option>
                                    <option value="Committee">Committee</option>
                                    <option value="Event Coordinator">Event Coordinator</option>
                                    <option value="Normal Committee">Normal Committee</option>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-0 py-3">
                            <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">
                                Cancel
                            </button>

                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                Save Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const roleModalElement = document.getElementById('membershipRoleModal');
                const roleModal = new bootstrap.Modal(roleModalElement);
                const roleButtons = document.querySelectorAll('.membership-role-edit-btn');

                roleButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        document.getElementById('editMembershipRoleId').value = button.dataset.membershipId;
                        document.getElementById('editReturnClubId').value = button.dataset.clubId;
                        document.getElementById('editMembershipUserName').value = button.dataset.userName;

                        const roleSelect = document.getElementById('editMembershipRoleSelect');
                        roleSelect.value = button.dataset.currentRole;

                        if (roleSelect.value === '') {
                            roleSelect.selectedIndex = 0;
                        }

                        roleModal.show();
                    });
                });
            });
        </script>
    </body>

    </html>

    <?php
    exit;
}

// ==========================================
// MAIN DASHBOARD VIEW
// ==========================================
$totalClubs = 0;
$activeClubs = 0;
$inactiveClubs = 0;

try {
    $statQuery = $pdo->query("SELECT clubStatus, COUNT(*) as count FROM club GROUP BY clubStatus");

    while ($statRow = $statQuery->fetch(PDO::FETCH_ASSOC)) {
        if ($statRow['clubStatus'] === 'Active') {
            $activeClubs = intval($statRow['count']);
        }

        if ($statRow['clubStatus'] === 'Inactive') {
            $inactiveClubs = intval($statRow['count']);
        }
    }

    $totalClubs = $activeClubs + $inactiveClubs;
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management - FK System</title>

    <link rel="stylesheet" href="../STYLE/CSS/Module_1/adminDashboard_CSS.css">
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <style>
        .table-container {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            background: #ffffff;
            border-radius: 8px;
        }

        .badge-active {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .badge-inactive {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .btn-custom-create {
            background-color: #0d6efd;
            color: white;
            border: none;
            font-weight: 600;
        }

        .btn-custom-create:hover {
            background-color: #0b5ed7;
            color: white;
        }

        .stat-card {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
        }
    </style>
</head>

<body>
    <?php include '../topbar.php'; ?>

    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Club Management Page</h2>
                        <p class="text-muted mb-0 small">
                            Administrators can view all clubs, create clubs, edit clubs, delete clubs, and activate or
                            deactivate clubs.
                        </p>
                    </div>

                    <span class="text-muted small fw-semibold">
                        <?php echo date('l, jS F Y'); ?>
                    </span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div
                            class="card stat-card bg-white p-3 shadow-sm d-flex flex-row align-items-center justify-content-between">
                            <div>
                                <span class="text-uppercase text-muted small fw-bold">Total Clubs</span>
                                <h3 class="fw-bold mb-0 mt-1 text-dark" id="statTotalClubs">
                                    <?php echo $totalClubs; ?>
                                </h3>
                            </div>

                            <div class="fs-2 text-primary opacity-50">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div
                            class="card stat-card bg-white p-3 shadow-sm d-flex flex-row align-items-center justify-content-between">
                            <div>
                                <span class="text-uppercase text-muted small fw-bold">Active Scope</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success" id="statActiveClubs">
                                    <?php echo $activeClubs; ?>
                                </h3>
                            </div>

                            <div class="fs-2 text-success opacity-50">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div
                            class="card stat-card bg-white p-3 shadow-sm d-flex flex-row align-items-center justify-content-between">
                            <div>
                                <span class="text-uppercase text-muted small fw-bold">Hidden / Inactive</span>
                                <h3 class="fw-bold mb-0 mt-1 text-danger" id="statInactiveClubs">
                                    <?php echo $inactiveClubs; ?>
                                </h3>
                            </div>

                            <div class="fs-2 text-danger opacity-50">
                                <i class="fa-solid fa-eye-slash"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-center justify-content-between mb-4">
                    <div class="col-sm-auto">
                        <button onclick="openCreateModal()"
                            class="btn btn-custom-create py-2 px-4 shadow-sm d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Create Club
                        </button>
                    </div>

                    <div class="col-sm-auto d-flex gap-2 match-filters-width">
                        <div class="position-relative" style="min-width: 260px;">
                            <input id="tableSearch" onkeyup="filterTable()" class="form-control text-sm"
                                placeholder="Search clubs by name or advisor..." type="text" />
                        </div>

                        <div>
                            <select id="statusFilter" onchange="filterTable()" class="form-select text-sm bg-white"
                                style="min-width: 140px;">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm overflow-hidden table-container mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="club-management-table">
                            <thead class="table-light text-uppercase tracking-wider text-muted small">
                                <tr>
                                    <th class="ps-4 py-3">#</th>
                                    <th class="py-3">Club Name</th>
                                    <th class="py-3">Advisor</th>
                                    <th class="py-3 text-center">Members Count</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3 text-center pe-4">Actions</th>
                                </tr>
                            </thead>

                            <tbody id="clubTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="clubFormModal" registrar-action-target="form" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-light py-3">
                    <h5 id="modalTitle" class="modal-title fw-bold text-dark">Create New Club</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="clubForm" onsubmit="handleFormSubmit(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" id="formClubId" name="Club_ID">

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small mb-1">Club Name</label>
                            <input type="text" id="formClubName" name="clubName" required class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small mb-1">Advisor Name</label>
                            <input type="text" id="formClubAdvisor" name="clubAdvisorName" required
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small mb-1">Description</label>
                            <textarea id="formClubDescription" name="clubDescription" rows="3"
                                class="form-control"></textarea>
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-semibold text-muted small mb-1">Status Setting</label>
                            <select id="formClubStatus" name="clubStatus" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 py-3">
                        <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-sm btn-primary px-3">
                            Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clubDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <div class="text-danger fs-1 mb-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>

                    <h5 class="fw-bold text-dark mb-2">Confirm Delete?</h5>

                    <p class="text-muted small mb-4">
                        Are you sure you want to completely erase
                        <span id="deleteTargetName" class="fw-bold text-dark"></span>
                        from structural records?
                    </p>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button id="confirmDeleteBtn" type="button" class="btn btn-sm btn-danger px-3">
                            Delete Record
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let formModalInstance;
        let deleteModalInstance;

        document.addEventListener("DOMContentLoaded", () => {
            formModalInstance = new bootstrap.Modal(document.getElementById('clubFormModal'));
            deleteModalInstance = new bootstrap.Modal(document.getElementById('clubDeleteModal'));

            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            if (searchParam) {
                document.getElementById('tableSearch').value = searchParam;
            }

            refreshClubViewRecords();
        });

        function updateMetricCounters(clubs) {
            if (!clubs) return;

            let active = 0;
            let inactive = 0;

            clubs.forEach(c => {
                if (c.clubStatus === 'Active') {
                    active++;
                } else {
                    inactive++;
                }
            });

            document.getElementById('statTotalClubs').innerText = clubs.length;
            document.getElementById('statActiveClubs').innerText = active;
            document.getElementById('statInactiveClubs').innerText = inactive;
        }

        function refreshClubViewRecords() {
            fetch('?action=list')
                .then(res => {
                    if (!res.ok) {
                        throw new Error('HTTP network pipeline payload initialization break.');
                    }

                    return res.json();
                })
                .then(res => {
                    if (res.success) {
                        renderTableStructure(res.data);
                        updateMetricCounters(res.data);
                    } else {
                        alert(res.message);
                    }
                })
                .catch(err => console.error('Data pipeline error:', err));
        }

        function renderTableStructure(clubs) {
            const tbody = document.getElementById('clubTableBody');
            tbody.innerHTML = '';

            if (!clubs || clubs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-5 text-center text-muted small">No matching student clubs found.</td></tr>`;
                return;
            }

            clubs.forEach((club, index) => {
                const isActive = club.clubStatus === 'Active';
                const row = document.createElement('tr');

                row.innerHTML = `
                    <td class="ps-4 small text-secondary">${index + 1}</td>

                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-light text-primary rounded d-flex align-items-center justify-content-center border" style="width:32px; height:32px; font-size:0.85rem;">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>

                            <span class="fw-bold text-dark small">
                                ${escapeHtml(club.clubName)}
                            </span>
                        </div>
                    </td>

                    <td class="small text-secondary">
                        ${escapeHtml(club.clubAdvisorName)}
                    </td>

                    <td class="small text-center fw-bold text-secondary">
                        ${club.total_members}
                    </td>

                    <td class="text-center">
                        <span class="badge ${isActive ? 'badge-active' : 'badge-inactive'} font-bold btn-sm fs-8 px-2.5 py-1">
                            ${club.clubStatus}
                        </span>
                    </td>

                    <td class="pe-4">
                        <div class="d-flex justify-content-center gap-1">
                            <a href="?view_page=${club.Club_ID}" class="btn btn-sm btn-outline-primary py-1 px-2 fs-8">
                                <i class="fa-regular fa-eye"></i> View
                            </a>

                            <button onclick="openEditModal(${club.Club_ID})" class="btn btn-sm btn-outline-warning py-1 px-2 fs-8">
                                <i class="fa-regular fa-pen-to-square"></i> Edit
                            </button>

                            <button onclick="triggerDeletionConfirmation(${club.Club_ID}, '${escapeJsString(club.clubName)}')" class="btn btn-sm btn-outline-danger py-1 px-2 fs-8">
                                <i class="fa-regular fa-trash-can"></i> Delete
                            </button>

                            <button onclick="toggleClubActiveState(${club.Club_ID}, '${club.clubStatus}')" class="btn btn-sm ${isActive ? 'btn-outline-dark' : 'btn-outline-success'} py-1 px-2 fs-8">
                                ${isActive ? 'Deactivate' : 'Activate'}
                            </button>
                        </div>
                    </td>
                `;

                tbody.appendChild(row);
            });

            filterTable();
        }

        function openCreateModal() {
            document.getElementById('clubForm').reset();
            document.getElementById('formClubId').value = "";
            document.getElementById('modalTitle').innerText = "Create New Club";
            formModalInstance.show();
        }

        function openEditModal(id) {
            fetch('?action=list')
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const club = res.data.find(c => c.Club_ID == id);

                        if (club) {
                            document.getElementById('modalTitle').innerText = "Edit Club Details";
                            document.getElementById('formClubId').value = club.Club_ID;
                            document.getElementById('formClubName').value = club.clubName;
                            document.getElementById('formClubAdvisor').value = club.clubAdvisorName;
                            document.getElementById('formClubDescription').value = club.clubDescription;
                            document.getElementById('formClubStatus').value = club.clubStatus;

                            formModalInstance.show();
                        }
                    }
                });
        }

        function handleFormSubmit(e) {
            e.preventDefault();

            fetch('?action=save', {
                method: 'POST',
                body: new FormData(document.getElementById('clubForm'))
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        formModalInstance.hide();
                        refreshClubViewRecords();
                    } else {
                        alert(res.message);
                    }
                });
        }

        function triggerDeletionConfirmation(id, name) {
            document.getElementById('deleteTargetName').innerText = name;
            deleteModalInstance.show();

            document.getElementById('confirmDeleteBtn').onclick = function () {
                const fd = new FormData();
                fd.append('Club_ID', id);

                fetch('?action=delete', {
                    method: 'POST',
                    body: fd
                })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            deleteModalInstance.hide();
                            refreshClubViewRecords();
                        }
                    });
            };
        }

        function toggleClubActiveState(id, currentStatus) {
            const fd = new FormData();
            fd.append('Club_ID', id);
            fd.append('current_status', currentStatus);

            fetch('?action=toggle_status', {
                method: 'POST',
                body: fd
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        refreshClubViewRecords();
                    }
                });
        }

        function filterTable() {
            const s = document.getElementById('tableSearch').value.toLowerCase();
            const statusVal = document.getElementById('statusFilter').value;

            document.querySelectorAll('#clubTableBody tr').forEach(row => {
                if (row.cells.length < 6) return;

                const matchSearch = row.cells[1].innerText.toLowerCase().includes(s) ||
                    row.cells[2].innerText.toLowerCase().includes(s);

                const matchStatus = statusVal === "" ||
                    row.cells[4].innerText.trim() === statusVal;

                if (matchSearch && matchStatus) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function escapeHtml(str) {
            return str ? str.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;") : '';
        }

        function escapeJsString(str) {
            return str ? str.replace(/'/g, "\\'")
                .replace(/"/g, '\\"') : '';
        }
    </script>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>