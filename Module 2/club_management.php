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
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Unauthenticated session status context. Please log in again.']);
    exit;
}

// Redirect standard web view browsers to index fallback if validation checks fail
if (!$isAuthorized) {
    header("Location: index.php");
    exit();
}

// Helper wrapper function declaration context check
if (!function_exists('json_api_respond')) {
    function json_api_respond($success, $data = null, $message = '') {
        return json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    }
}

// 2. Async API AJAX Payload Controller Handling Routing
if (isset($_GET['action'])) {
    // Clear any previous buffer outputs to ensure pure JSON
    if (ob_get_length()) ob_clean();
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
                    'clubName' => $row['clubName'] ?? $row['club_name'] ?? $row['ClubName'] ?? 'Unnamed Club',
                    'clubAdvisorName' => $row['clubAdvisorName'] ?? $row['club_advisor_name'] ?? $row['ClubAdvisorName'] ?? 'No Advisor',
                    'clubDescription' => $row['clubDescription'] ?? $row['club_description'] ?? $row['ClubDescription'] ?? '',
                    'clubStatus' => $row['clubStatus'] ?? $row['club_status'] ?? $row['ClubStatus'] ?? 'Active',
                    'total_members' => $row['total_members'] ?? 0
                ];
            }

            echo json_api_respond(true, $normalizedClubs);
            exit;
        }

        if ($action === 'view' && isset($_GET['id'])) {
            $sql = "SELECT c.*, COUNT(m.Membership_ID) as total_members 
                    FROM club c 
                    LEFT JOIN club_membership m ON c.Club_ID = m.Club_ID 
                    WHERE c.Club_ID = ?
                    GROUP BY c.Club_ID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_GET['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $normalized = [
                    'Club_ID' => $row['Club_ID'] ?? null,
                    'clubName' => $row['clubName'] ?? $row['club_name'] ?? $row['ClubName'] ?? 'Unnamed Club',
                    'clubAdvisorName' => $row['clubAdvisorName'] ?? $row['club_advisor_name'] ?? $row['ClubAdvisorName'] ?? 'No Advisor',
                    'clubDescription' => $row['clubDescription'] ?? $row['club_description'] ?? $row['ClubDescription'] ?? '',
                    'clubStatus' => $row['clubStatus'] ?? $row['club_status'] ?? $row['ClubStatus'] ?? 'Active',
                    'total_members' => $row['total_members'] ?? 0
                ];
                echo json_api_respond(true, $normalized);
            } else {
                echo json_api_respond(false, null, 'Club record could not be tracked.');
            }
            exit;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $club_id     = !empty($_POST['Club_ID']) ? intval($_POST['Club_ID']) : null;
            $name        = trim($_POST['clubName'] ?? '');
            $description = trim($_POST['clubDescription'] ?? '');
            $advisor     = trim($_POST['clubAdvisorName'] ?? '');
            $status      = trim($_POST['clubStatus'] ?? 'Active');

            if (empty($name) || empty($advisor)) {
                echo json_api_respond(false, null, 'Please supply both a Club Name and an Advisor name.');
                exit;
            }

            $check = $pdo->query("SELECT * FROM club LIMIT 1");
            $sample = $check->fetch(PDO::FETCH_ASSOC);
            
            $colName = isset($sample['club_name']) ? 'club_name' : (isset($sample['ClubName']) ? 'ClubName' : 'clubName');
            $colDesc = isset($sample['club_description']) ? 'club_description' : (isset($sample['ClubDescription']) ? 'ClubDescription' : 'clubDescription');
            $colAdv  = isset($sample['club_advisor_name']) ? 'club_advisor_name' : (isset($sample['ClubAdvisorName']) ? 'ClubAdvisorName' : 'clubAdvisorName');
            $colStat = isset($sample['club_status']) ? 'club_status' : (isset($sample['ClubStatus']) ? 'ClubStatus' : 'clubStatus');

            if ($club_id) {
                $sql = "UPDATE club SET $colName = ?, $colDesc = ?, $colAdv = ?, $colStat = ? WHERE Club_ID = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $advisor, $status, $club_id]);
                echo json_api_respond(true, null, 'Club properties successfully updated.');
            } else {
                $sql = "INSERT INTO club ($colName, $colDesc, $colAdv, $colStat) VALUES (?, ?, ?, ?)";
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
                
                $check = $pdo->query("SELECT * FROM club LIMIT 1");
                $sample = $check->fetch(PDO::FETCH_ASSOC);
                $colStat = isset($sample['club_status']) ? 'club_status' : (isset($sample['ClubStatus']) ? 'ClubStatus' : 'clubStatus');

                $stmt = $pdo->prepare("UPDATE club SET $colStat = ? WHERE Club_ID = ?");
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management - FK System</title>
    <link rel="stylesheet" href="../STYLE/CSS/Module1/adminDashboard_CSS.css">
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <style>
        .table-container { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); background: #ffffff; border-radius: 8px; }
        .badge-active { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-inactive { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .btn-custom-create { background-color: #0d6efd; color: white; border: none; font-weight: 600; }
        .btn-custom-create:hover { background-color: #0b5ed7; color: white; }
    </style>
</head>

<body>
    <?php include '../topbar.php'; ?>
    
    <div id="wrapper">
        <?php include '../sidebar.php'; ?>

        <div id="content">
            <div class="container-fluid">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Club Management Page</h2>
                        <p class="text-muted mb-0 small">Administrators can view all clubs, create clubs, edit clubs, delete clubs, and activate or deactivate clubs.</p>
                    </div>
                    <span class="text-muted"><?php echo date('l, jS F Y'); ?></span>
                </div>

                <div class="row g-3 align-items-center justify-content-between mb-4">
                    <div class="col-sm-auto">
                        <button onclick="openCreateModal()" class="btn btn-custom-create py-2 px-4 shadow-sm d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Create Club
                        </button>
                    </div>
                    <div class="col-sm-auto d-flex gap-2 match-filters-width">
                        <div class="position-relative" style="min-width: 260px;">
                            <input id="tableSearch" onkeyup="filterTable()" class="form-control text-sm" placeholder="Search clubs by name or advisor..." type="text"/>
                        </div>
                        <div>
                            <select id="statusFilter" onchange="filterTable()" class="form-select text-sm bg-white" style="min-width: 140px;">
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
                            <input type="text" id="formClubAdvisor" name="clubAdvisorName" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small mb-1">Description</label>
                            <textarea id="formClubDescription" name="clubDescription" rows="3" class="form-control"></textarea>
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
                        <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clubViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold fs-6">Club Detailed Specifications</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-3">
                        <div class="bg-light text-primary rounded-3 d-flex align-items-center justify-content-center border" style="width:48px; height:48px; font-size:1.25rem;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h4 id="viewClubName" class="fw-bold mb-0 text-dark h5"></h4>
                            <span id="viewClubStatus" class="badge mt-1 inline-block fs-7 font-bold"></span>
                        </div>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-12">
                            <p class="text-muted mb-0 fw-medium">Assigned Club Advisor</p>
                            <p id="viewClubAdvisor" class="text-dark fw-bold mt-0.5 mb-0"></p>
                        </div>
                        <div class="col-12">
                            <p class="text-muted mb-0 fw-medium">Total Active Members</p>
                            <p id="viewClubMembers" class="text-dark fw-bold mt-0.5 mb-0"></p>
                        </div>
                        <div class="col-12">
                            <p class="text-muted mb-0 fw-medium">Description</p>
                            <p id="viewClubDescription" class="text-secondary mt-1 lh-sm style-desc" style="white-space: pre-line;"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Close Panel</button>
                </div>
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
                    <p class="text-muted small mb-4">Are you sure you want to completely erase <span id="deleteTargetName" class="fw-bold text-dark"></span> from structural records?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                        <button id="confirmDeleteBtn" type="button" class="btn btn-sm btn-danger px-3">Delete Record</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let formModalInstance, viewModalInstance, deleteModalInstance;

        document.addEventListener("DOMContentLoaded", () => {
            formModalInstance = new bootstrap.Modal(document.getElementById('clubFormModal'));
            viewModalInstance = new bootstrap.Modal(document.getElementById('clubViewModal'));
            deleteModalInstance = new bootstrap.Modal(document.getElementById('clubDeleteModal'));
            
            refreshClubViewRecords();
        });

        function refreshClubViewRecords() {
            fetch('?action=list')
                .then(res => {
                    if (!res.ok) throw new Error('HTTP network pipeline payload initialization break.');
                    return res.json();
                })
                .then(res => { if(res.success) renderTableStructure(res.data); else alert(res.message); })
                .catch(err => console.error('Data pipeline error:', err));
        }

        function renderTableStructure(clubs) {
            const tbody = document.getElementById('clubTableBody');
            tbody.innerHTML = '';
            if(!clubs || clubs.length === 0) {
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
                            <div class="bg-light text-primary rounded d-flex align-items-center justify-content-center border" style="width:32px; height:32px; font-size:0.85rem;"><i class="fa-solid fa-shield-halved"></i></div>
                            <span class="fw-bold text-dark small">${escapeHtml(club.clubName)}</span>
                        </div>
                    </td>
                    <td class="small text-secondary">${escapeHtml(club.clubAdvisorName)}</td>
                    <td class="small text-center fw-bold text-secondary">${club.total_members}</td>
                    <td class="text-center">
                        <span class="badge ${isActive ? 'badge-active' : 'badge-inactive'} font-bold btn-sm fs-8 px-2.5 py-1">${club.clubStatus}</span>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex justify-content-center gap-1">
                            <button onclick="viewClubEntity(${club.Club_ID})" class="btn btn-sm btn-outline-primary py-1 px-2 fs-8"><i class="fa-regular fa-eye"></i> View</button>
                            <button onclick="openEditModal(${club.Club_ID})" class="btn btn-sm btn-outline-warning py-1 px-2 fs-8"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                            <button onclick="triggerDeletionConfirmation(${club.Club_ID}, '${escapeJsString(club.clubName)}')" class="btn btn-sm btn-outline-danger py-1 px-2 fs-8"><i class="fa-regular fa-trash-can"></i> Delete</button>
                            <button onclick="toggleClubActiveState(${club.Club_ID}, '${club.clubStatus}')" class="btn btn-sm ${isActive ? 'btn-outline-dark' : 'btn-outline-success'} py-1 px-2 fs-8">${isActive ? 'Deactivate' : 'Activate'}</button>
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
            fetch(`?action=view&id=${id}`).then(res => res.json()).then(res => {
                if(res.success) {
                    const club = res.data;
                    document.getElementById('modalTitle').innerText = "Edit Club Details";
                    document.getElementById('formClubId').value = club.Club_ID;
                    document.getElementById('formClubName').value = club.clubName;
                    document.getElementById('formClubAdvisor').value = club.clubAdvisorName;
                    document.getElementById('formClubDescription').value = club.clubDescription;
                    document.getElementById('formClubStatus').value = club.clubStatus;
                    formModalInstance.show();
                }
            });
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            fetch('?action=save', { method: 'POST', body: new FormData(document.getElementById('clubForm')) })
            .then(res => res.json()).then(res => { 
                if(res.success) { 
                    formModalInstance.hide(); 
                    refreshClubViewRecords(); 
                } else {
                    alert(res.message);
                }
            });
        }

        function viewClubEntity(id) {
            fetch(`?action=view&id=${id}`).then(res => res.json()).then(res => {
                if(res.success) {
                    const club = res.data;
                    document.getElementById('viewClubName').innerText = club.clubName;
                    document.getElementById('viewClubAdvisor').innerText = club.clubAdvisorName;
                    document.getElementById('viewClubMembers').innerText = club.total_members;
                    document.getElementById('viewClubDescription').innerText = club.clubDescription || 'No description added yet.';
                    const badge = document.getElementById('viewClubStatus');
                    badge.innerText = club.clubStatus;
                    badge.className = `badge mt-1 inline-block fs-7 font-bold ${club.clubStatus === 'Active' ? 'badge-active' : 'badge-inactive'}`;
                    viewModalInstance.show();
                }
            });
        }

        function triggerDeletionConfirmation(id, name) {
            document.getElementById('deleteTargetName').innerText = name;
            deleteModalInstance.show();
            document.getElementById('confirmDeleteBtn').onclick = function() {
                const fd = new FormData(); fd.append('Club_ID', id);
                fetch('?action=delete', { method: 'POST', body: fd }).then(res => res.json()).then(res => { 
                    if(res.success) { 
                        deleteModalInstance.hide(); 
                        refreshClubViewRecords(); 
                    } 
                });
            };
        }

        function toggleClubActiveState(id, currentStatus) {
            const fd = new FormData(); fd.append('Club_ID', id); fd.append('current_status', currentStatus);
            fetch('?action=toggle_status', { method: 'POST', body: fd }).then(res => res.json()).then(res => { if(res.success) refreshClubViewRecords(); });
        }

        function filterTable() {
            const s = document.getElementById('tableSearch').value.toLowerCase();
            const statusVal = document.getElementById('statusFilter').value;
            document.querySelectorAll('#clubTableBody tr').forEach(row => {
                if (row.cells.length < 6) return;
                const matchSearch = row.cells[1].innerText.toLowerCase().includes(s) || row.cells[2].innerText.toLowerCase().includes(s);
                const matchStatus = statusVal === "" || row.cells[4].innerText.trim() === statusVal;
                if(matchSearch && matchStatus) row.style.display = ""; else row.style.display = "none";
            });
        }

        function escapeHtml(str) { return str ? str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") : ''; }
        function escapeJsString(str) { return str ? str.replace(/'/g, "\\'").replace(/"/g, '\\"') : ''; }
    </script>
    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>