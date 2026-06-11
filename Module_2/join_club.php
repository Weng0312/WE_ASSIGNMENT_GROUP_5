<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Module_1/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$club_id = $_GET['id'] ?? 0;

if (!$club_id) {
    header("Location: club_list.php");
    exit();
}

try {

    // CHECK ALREADY JOINED
    $check = $pdo->prepare("
        SELECT *
        FROM club_membership
        WHERE User_ID = ?
        AND Club_ID = ?
    ");

    $check->execute([$user_id, $club_id]);

    if ($check->rowCount() > 0) {

        $_SESSION['error'] = "You already joined this club.";

        header("Location: club_details.php?id=" . $club_id);
        exit();
    }

    // INSERT MEMBERSHIP
    $insert = $pdo->prepare("
        INSERT INTO club_membership
        (
            joinDate,
            membershipStatus,
            membershipRole,
            User_ID,
            Club_ID
        )
        VALUES
        (
            CURDATE(),
            'Active',
            'Member',
            ?,
            ?
        )
    ");

    $insert->execute([$user_id, $club_id]);

    $_SESSION['success'] = "Successfully joined the club!";

} catch (PDOException $e) {

    $_SESSION['error'] = "Database Error: " . $e->getMessage();
}

header("Location: club_details.php?id=" . $club_id);
exit();
?>