<?php
include 'includes/db.php';
$email = $_GET['email'] ?? '';
$telephone = $_GET['telephone'] ?? '';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR telephone = ?");
$stmt->execute([$email, $telephone]);
$exists = $stmt->fetchColumn();
echo json_encode(['exists' => $exists > 0]);