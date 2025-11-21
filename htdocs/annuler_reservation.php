<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Supprimer d'abord les revenus liés à cette réservation
    $stmt = $pdo->prepare("DELETE FROM revenus WHERE id_reservation = ?");
    $stmt->execute([$id]);
    // Puis supprimer la réservation
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: my_reservations.php');
exit;
?>