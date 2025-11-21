<?php
session_start();
include 'includes/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'includes/PHPMailer/src/PHPMailer.php';
require_once 'includes/PHPMailer/src/SMTP.php';
require_once 'includes/PHPMailer/src/Exception.php';


if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$id_client = $_SESSION['user']['id'];
$id_terrain = $_POST['terrain'];
$date = $_POST['date'];
$heure_debut = $_POST['heure_debut'];
$heure_fin = $_POST['heure_fin'];
$fullname = $_POST['fullname'];
$phone = $_POST['phone'];

// Vérifier si le créneau est disponible
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations 
    WHERE id_terrain = ? AND date = ? 
    AND (
        (heure_debut < ? AND heure_fin > ?) OR
        (heure_debut < ? AND heure_fin > ?) OR
        (heure_debut >= ? AND heure_fin <= ?)
    )");
$stmt->execute([
    $id_terrain, $date,
    $heure_fin, $heure_fin, // chevauchement début
    $heure_debut, $heure_debut, // chevauchement fin
    $heure_debut, $heure_fin // inclus dans la plage
]);
$existe = $stmt->fetchColumn();

if ($existe > 0) {
    echo "
<div style='
    max-width: 400px;
    margin: 40px auto;
    padding: 22px 12px;
    background: #fff3f3;
    border: 1px solid #e57373;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    text-align: center;
    font-family: \"Segoe UI\", Arial, sans-serif;
'>
    <p style='color:#d32f2f; font-size:1.15em; margin-bottom:20px; line-height:1.5;'>
        <strong>Ce créneau est déjà réservé.</strong><br>
        Veuillez choisir un autre horaire.
    </p>
    <a href='javascript:history.back()' style='
        display: block;
        width: 100%;
        max-width: 260px;
        margin: 0 auto;
        padding: 14px 0;
        background: #1976d2;
        color: #fff;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.08em;
        letter-spacing: 0.03em;
        box-shadow: 0 1px 4px rgba(25,118,210,0.08);
        transition: background 0.2s;
    ' 
    onmouseover=\"this.style.background='#1565c0'\" 
    onmouseout=\"this.style.background='#1976d2'\"
    >Retour</a>
</div>
<style>
@media (max-width: 480px) {
    div[style*='max-width: 400px'] {
        max-width: 100vw !important;
        min-width: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        padding: 18vw 2vw !important;
        position: fixed !important;
        top: 0; left: 0; right: 0;
        z-index: 9999;
    }
    a[style*='padding: 14px 0'] {
        width: 100% !important;
        font-size: 1.3em !important;
        padding: 20px 0 !important;
    }
    p[style*='color:#d32f2f'] {
        font-size: 1.3em !important;
    }
    body {
        overflow: hidden !important;
    }
}
</style>
";
    exit;
}

// Insérer la réservation
$stmt = $pdo->prepare("INSERT INTO reservations (id_client, id_terrain, nom_client, tel_client, date, heure_debut, heure_fin, statut)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmée')");
$stmt->execute([$id_client, $id_terrain, $fullname, $phone, $date, $heure_debut, $heure_fin]);

// Calcul du montant et insertion dans revenus
$prix = $pdo->query("SELECT prix_heure FROM terrains WHERE id = $id_terrain")->fetchColumn();
$duree = (strtotime($heure_fin) - strtotime($heure_debut)) / 3600;
$montant = $prix; // Multiplie seulement par 1
$commission = $montant * 0.05;
$stmt2 = $pdo->prepare("INSERT INTO revenus (id_reservation, montant_total, commission_admin, montant_proprietaire)
                        VALUES (LAST_INSERT_ID(), ?, ?, ?)");
$stmt2->execute([$montant, $commission, $montant - $commission]);

// Récupérer l'email et le nom du propriétaire
$stmtOwner = $pdo->prepare("SELECT u.email, u.nom, t.nom AS terrain_nom FROM terrains t JOIN users u ON t.id_proprietaire = u.id WHERE t.id = ?");
$stmtOwner->execute([$id_terrain]);
$owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

if ($owner && !empty($owner['email'])) {
    
    $mail = new PHPMailer(true);
    try {
        // Paramètres SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // à adapter selon ton fournisseur
        $mail->SMTPAuth = true;
        $mail->Username = 'lappestoryaziz@gmail.com'; // ton email SMTP
        $mail->Password = 'ovte rdch oxzn ybfh'; // ton mot de passe SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Debug SMTP
        $mail->SMTPDebug = 0; // Affiche les logs SMTP pour déboguer
        $mail->Debugoutput = 'html';

        // Destinataire
        $mail->setFrom('lappestoryaziz@gmail.com', 'Reserve&Play');
        $mail->addAddress($owner['email'], $owner['nom']);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';


        // Contenu
        $mail->isHTML(true);
        $mail->Subject = 'Nouvelle réservation pour votre terrain';
        $mail->Body = "
            Bonjour <b>{$owner['nom']}</b>,<br><br>
            Votre terrain <b>{$owner['terrain_nom']}</b> vient d'être réservé.<br>
            <b>Date :</b> {$date}<br>
            <b>Créneau :</b> {$heure_debut} - {$heure_fin}<br>
            <b>Client :</b> {$fullname} ({$phone})<br><br>
               Vous pouvez consulter vos réservations ici :<br>
            <a href='https://reserveandplay.ct.ws/my_reservations.php' target='_blank'>Voir mes réservations</a><br><br>
            Merci d'utiliser Reserve&Play.
        ";

        $mail->send();
        // Optionnel : log ou message de succès
    } catch (Exception $e) {
        echo "<div style='color:red'>Erreur d'envoi de mail : {$mail->ErrorInfo}</div>";
    }
}

// Message de confirmation avant redirection
echo "
<div style='
    max-width: 400px;
    margin: 40px auto;
    padding: 22px 12px;
    background: #e8f5e9;
    border: 1px solid #66bb6a;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    text-align: center;
    font-family: \"Segoe UI\", Arial, sans-serif;
'>
    <p style='color:#388e3c; font-size:1.15em; margin-bottom:20px; line-height:1.5;'>
        <strong>Réservation effectuée avec succès !</strong><br>
        Vous allez être redirigé vers tes réservations...
    </p>
</div>
<style>
@media (max-width: 480px) {
    div[style*='max-width: 400px'] {
        max-width: 100vw !important;
        min-width: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        padding: 18vw 2vw !important;
        position: fixed !important;
        top: 0; left: 0; right: 0;
        z-index: 9999;
    }
    a[style*='padding: 14px 0'] {
        width: 100% !important;
        font-size: 1.3em !important;
        padding: 20px 0 !important;
    }
    p[style*='color:#388e3c'] {
        font-size: 1.3em !important;
    }
    body {
        overflow: hidden !important;
    }
}
</style>
<script>
setTimeout(function() {
    window.location.href = 'my_reservations.php';
}, 1500);
</script>
";
exit;