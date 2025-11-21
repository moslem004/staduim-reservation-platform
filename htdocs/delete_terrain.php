<?php
session_start();
include 'includes/db.php';

if (isset($_GET['id']) && isset($_SESSION['user']['id'])) {
    $id = intval($_GET['id']);
    $user_id = $_SESSION['user']['id'];

    // Supprimer la photo du serveur si elle existe
    $stmt = $pdo->prepare("SELECT photo FROM terrains WHERE id = ? AND id_proprietaire = ?");
    $stmt->execute([$id, $user_id]);
    $terrain = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($terrain && $terrain['photo']) {
        $photoPath = 'uploads/' . $terrain['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }

    // Récupérer les id des réservations liées à ce terrain
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id_terrain = ?");
    $stmt->execute([$id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Supprimer les revenus liés à ces réservations
    if ($reservations) {
        $in = str_repeat('?,', count($reservations) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM revenus WHERE id_reservation IN ($in)");
        $stmt->execute($reservations);
    }

    // Supprimer les réservations liées à ce terrain
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id_terrain = ?");
    $stmt->execute([$id]);

    // Supprimer le terrain de la base de données
    $stmt = $pdo->prepare("DELETE FROM terrains WHERE id = ? AND id_proprietaire = ?");
    $stmt->execute([$id, $user_id]);
}

header('Location: mes_terrains.php');
exit;
?>