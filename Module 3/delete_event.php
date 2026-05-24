<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: ../Module 1/index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['errorMessage'] = "Invalid event selected.";
    header("Location: event_management.php");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM event_registration WHERE Event_ID = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM event WHERE Event_ID = ?");
    $stmt->execute([$id]);

    $_SESSION['successMessage'] = "Event deleted successfully.";
    header("Location: event_management.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['errorMessage'] = "Failed to delete event.";
    header("Location: event_management.php");
    exit();
}
?>