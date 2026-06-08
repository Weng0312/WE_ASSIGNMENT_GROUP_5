<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    !isset($_GET['club_id'])
) {
    header("Location: ../Module_1/index.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

$user_id = $_SESSION['user_id'];
$club_id = $_GET['club_id'];

$stmt = $pdo->prepare("
    SELECT membershipRole
    FROM club_membership
    WHERE User_ID = ?
    AND Club_ID = ?
    AND membershipStatus = 'Active'
");

$stmt->execute([$user_id, $club_id]);

$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if ($membership) {

    $committeeRoles = [
        'President',
        'Vice President',
        'Secretary',
        'Treasurer',
        'Committee',
        'Committee Member',
        'Event Coordinator',
        'Normal Committee'
    ];

    if (in_array($membership['membershipRole'], $committeeRoles)) {

        $_SESSION['role'] = 'Committee';
        $_SESSION['current_module'] = 'committee';
        $_SESSION['committee_club_id'] = $club_id;
        $_SESSION['membershipRole'] = $membership['membershipRole'];

        header("Location: ../Module_1/committee_dashboard.php");
        exit();
    }
}

$_SESSION['role'] = 'Student';
$_SESSION['current_module'] = 'student';

$_SESSION['error_message'] = "You are not a committee member of this club.";

header("Location: my_membership.php");
exit();
?>