<?php
session_start();
include 'includes/db.php';
if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['id'])) {
    echo "Aucun terrain sélectionné.";
    exit;
}

$id_terrain = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM terrains WHERE id = ?");
$stmt->execute([$id_terrain]);
$terrain = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$terrain) {
    echo "Terrain introuvable.";
    exit;
}

// Récupérer les réservations existantes pour ce terrain
$reservations = $pdo->prepare("SELECT date, heure_debut, heure_fin FROM reservations WHERE id_terrain = ?");
$reservations->execute([$id_terrain]);
$reservations = $reservations->fetchAll(PDO::FETCH_ASSOC);

// Préparer les réservations pour JS
$reservations_js = [];
foreach ($reservations as $r) {
    $reservations_js[$r['date']][] = [
        'start' => $r['heure_debut'],
        'end' => $r['heure_fin']
    ];
}

// Récupérer les informations du propriétaire
$stmt_owner = $pdo->prepare("SELECT telephone FROM users WHERE id = ?");
$stmt_owner->execute([$terrain['id_proprietaire']]);
$owner = $stmt_owner->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reserve&Play - Réserver</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
        <link rel="icon" type="image/png" href="logo.png">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
    </div>
    <!-- Spinner End -->

    
    <!-- Navbar Start -->
    <div class="container-fluid bg-white sticky-top">
        <div class="container">
            <nav class="navbar navbar-expand-lg bg-white navbar-light py-2 py-lg-0">
                <a href="index.php" class="navbar-brand">
                    <img class="img-fluid" src="img/logo.png" alt="Logo">
                </a>
                <button type="button" class="navbar-toggler ms-auto me-0" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto">
                        <a href="index.php" class="nav-item nav-link">Accueil</a>
                        <a href="terrains.php" class="nav-item nav-link">Terrain</a>
                        <a href="my_reservations.php" class="nav-item nav-link">Réservations</a>
                    </div>
                    <?php if (isset($_SESSION['user'])): ?>
    <div class="user-dropdown-wrapper" style="position: relative; display: inline-block;">
        <span class="navbar-text ms-3 fw-bold text-success user-dropdown-toggle" style="cursor:pointer;">
            <?= htmlspecialchars($_SESSION['user']['nom']) ?>
        </span>
        <div class="user-dropdown-menu">
            <?php if ($_SESSION['user']['type'] === 'proprietaire'): ?>
                <a href="mes_terrains.php">Gérer mes terrains</a>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" id="logoutBtn">Logout</a>
        </div>
    </div>
<?php else: ?>
    <a href="login.php" class="btn btn-success ms-3">Se connecter</a>
<?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->


    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container text-center py-5">
            <h1 class="display-2 text-dark mb-4 animated slideInDown"><?= htmlspecialchars($terrain['nom']) ?></h1>
        </div>
    </div>
    <!-- Page Header End -->


    <!-- Article Start -->
<div class="booking-container">
    <!-- Booking Summary -->
    <div class="terrain-details">
        <h2>Résumé de la réservation</h2>
        <?php if ($terrain['photo']): ?>
            <img src="../uploads/<?= htmlspecialchars($terrain['photo']) ?>" alt="Photo">
        <?php endif; ?>
        <ul class="summary-list">
            <li><strong>Terrain :</strong> <?= htmlspecialchars($terrain['nom']) ?></li>
            <li><strong>Localisation :</strong> <?= htmlspecialchars($terrain['localisation']) ?></li>
                    <?php if (!empty($terrain['maps'])): ?>
    <li><a href="<?= htmlspecialchars($terrain['maps']) ?>" target="_blank" style="color:#1976d2;word-break:break-all;">
        Voir sur Google Maps
    </a><br>
<?php endif; ?></li>
            <li><strong>Prix :</strong> <?= htmlspecialchars($terrain['prix_heure']) ?> DT / heure et demie</li>
            <li><strong>Date :</strong> <span id="summary-date">Sélectionnez une date</span></li>
            <li><strong>Créneau :</strong> <span id="selected-time">Sélectionnez un créneau</span></li>
            <li><strong>Propriétaire (téléphone) :</strong> <?= htmlspecialchars($owner['telephone']) ?></li>
        </ul>

        <h3>Créneaux réservés</h3>
        <ul id="reserved-slots-list">
            <li>Sélectionnez une date pour voir les créneaux réservés.</li>
        </ul>
    </div>

    <!-- Booking Form -->
    <div id="time-error" class="text-danger text-center mb-3"></div>
    <form class="booking-form" method="POST" action="confirmer_reservation.php">
        <h2>Complétez votre réservation</h2>
        <input type="hidden" name="terrain" value="<?= $terrain['id'] ?>">
        <label>Nom complet</label>
        <input type="text" name="fullname" placeholder="Votre nom" required>
        <label>Numéro de téléphone</label>
        <input type="tel" name="phone" placeholder="Votre numéro de téléphone" required>
        <label>Date</label>
        <input type="date" name="date" id="date" required onchange="document.getElementById('summary-date').innerText=this.value;">
        <div class="time-slots">
            <?php
            // توليد الأوقات كل نصف ساعة من 08:00 إلى 23:00
            $start = strtotime('08:00');
            $end = strtotime('23:00');
            $times = [];
            for ($t = $start; $t <= $end; $t += 30 * 60) {
                $times[] = date('H:i', $t);
            }
            $periods = [
                'Matin' => ['08:00', '11:30'],
                'Après-midi' => ['12:00', '17:30'],
                'Soir' => ['18:00', '23:00']
            ];
            foreach ($periods as $label => $range):
                echo '<div style="margin-bottom:18px">';
                echo '<div style="font-weight:bold;margin:10px 0 8px 0;">'.$label.'</div>';
                echo '<div class="time-buttons" style="display:flex;gap:6px;flex-wrap:wrap;">';
                foreach ($times as $t) {
                    if ($t >= $range[0] && $t <= $range[1]) {
                        echo '<button type="button" class="time-btn" id="btn-'.str_replace(':','',$t).'" onclick="selectTime(\''.$t.'\')">'.$t.'</button>';
                    }
                }
                echo '</div></div>';
            endforeach;
            ?>
        </div>
        <input type="hidden" name="heure_debut" id="heure_debut" required>
        <input type="hidden" name="heure_fin" id="heure_fin" required>
        <button type="submit">Confirmer la réservation</button>
    </form>
</div>

    <div class="container-fluid copyright py-4">
        <div class="container">
                    <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
                    Designed By <a class="fw-medium" href="https://htmlcodex.com">Mohamed Aziz Arfaoui</a> Founder of <a class="fw-medium" >Tout ce qui est informatique</a>
                </div>
            </div>
        </div>
    </div>


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script>
    // Met à jour le résumé de la date
    document.getElementById('date').addEventListener('change', function() {
        document.getElementById('summary-date').innerText = this.value;
    });
    // Pour sélectionner un créneau horaire
   function selectTime(time) {
    const btn = document.getElementById('btn-'+time.replace(':',''));
    const errorDiv = document.getElementById('time-error');
    
    // Efface le message d'erreur
    errorDiv.textContent = "";

    // Désélectionner tous les boutons
    document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('selected'));

    // Sélectionner ce bouton
    btn.classList.add('selected');

    // Remplir les champs cachés
    document.getElementById('heure_debut').value = time;
    const endTime = addOneHourAndHalf(time);
    document.getElementById('heure_fin').value = endTime;

    // Mettre à jour le résumé
    document.getElementById('selected-time').innerText = time + " - " + endTime;
    
}

function hideIncompatibleSlots(selectedTime) {
    const selectedMinutes = convertTimeToMinutes(selectedTime);
    const minAllowedTime = selectedMinutes - 90; // 1h30 avant le créneau sélectionné

    document.querySelectorAll('.time-btn').forEach(btn => {
        const btnTime = btn.textContent;
        const btnMinutes = convertTimeToMinutes(btnTime);
        
        // Masquer les créneaux qui se chevauchent avec la réservation
        if (btnMinutes < selectedMinutes && btnMinutes > minAllowedTime) {
            btn.style.display = 'none';
        } else if (btn.style.display === 'none' && 
                   !btn.disabled && 
                   !isTimeReserved(btnTime)) {
            // Réafficher les boutons qui ne sont pas réservés et qui ne sont pas incompatibles
            btn.style.display = 'inline-block';
        }
    });
}

function isTimeReserved(time) {
    const date = document.getElementById('date').value;
    if (!reservations[date]) return false;

    const timeMinutes = convertTimeToMinutes(time);
    
    for (const reservation of reservations[date]) {
        const startMinutes = convertTimeToMinutes(reservation.start);
        const endMinutes = convertTimeToMinutes(reservation.end);
        
        if (timeMinutes >= startMinutes && timeMinutes < endMinutes) {
            return true;
        }
    }
    return false;
}
    function addOneHourAndHalf(time) {
        let [h, m] = time.split(':').map(Number);
        m += 30;
        if (m >= 60) {
            h += 1;
            m -= 60;
        }
        h += 1;
        return h.toString().padStart(2, '0') + ':' + m.toString().padStart(2, '0');
    }

const reservations = <?= json_encode($reservations_js) ?>;

function updateDisabledTimes() {
    const date = document.getElementById('date').value;

    // Réinitialiser tous les boutons
    document.querySelectorAll('.time-btn').forEach(btn => {
        const btnTime = btn.textContent;
        if (isTimeSlotAvailableFor90Min(btnTime, date)) {
            btn.style.display = 'inline-block';
            btn.disabled = false;
        } else {
            btn.style.display = 'none';
        }
    });

    // Si un créneau est déjà sélectionné, cacher ceux qui se chevauchent
    const selectedTime = document.getElementById('heure_debut').value;
    if (selectedTime) {
        hideIncompatibleSlots(selectedTime);
    }
}

function isTimeSlotAvailableFor90Min(startTime, date) {
    const startMinutes = convertTimeToMinutes(startTime);
    const endMinutes = startMinutes + 90;

    const dayReservations = reservations[date] || [];

    for (const reservation of dayReservations) {
        const reservedStart = convertTimeToMinutes(reservation.start);
        const reservedEnd = convertTimeToMinutes(reservation.end);

        // Si un chevauchement existe avec l'intervalle 90 minutes
        if ((startMinutes >= reservedStart && startMinutes < reservedEnd) ||
            (endMinutes > reservedStart && endMinutes <= reservedEnd) ||
            (startMinutes <= reservedStart && endMinutes >= reservedEnd)) {
            return false;
        }
    }
    return true;
}


// Fonction utilitaire pour convertir HH:MM en minutes
function convertTimeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}

// Met à jour le résumé de la date et désactive les créneaux réservés
document.getElementById('date').addEventListener('change', function() {
    document.getElementById('summary-date').innerText = this.value;
    updateDisabledTimes();
    updateReservedSlotsList(); // <-- Ajout ici
});

// Désactive les créneaux réservés au chargement si une date est déjà sélectionnée
window.onload = function() {
    updateDisabledTimes();
    updateReservedSlotsList(); // <-- Ajout ici
}

// Ajoutez ceci pour afficher les créneaux réservés de la date sélectionnée
function updateReservedSlotsList() {
    const date = document.getElementById('date').value;
    const list = document.getElementById('reserved-slots-list');
    list.innerHTML = '';
    
    if (reservations[date] && reservations[date].length > 0) {
        reservations[date].forEach(reservation => {
            const li = document.createElement('li');
            li.textContent = `${reservation.start} - ${reservation.end}`;
            list.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = "Aucun créneau réservé pour cette date.";
        list.appendChild(li);
    }
}

// Empêche la soumission si aucun créneau valide n'est choisi
document.querySelector('.booking-form').addEventListener('submit', function(e) {
    const date = document.getElementById('date').value;
    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;
    const errorDiv = document.getElementById('time-error');

    if (!heureDebut || !heureFin) {
        errorDiv.textContent = "Veuillez sélectionner un créneau horaire disponible avant de confirmer la réservation.";
        e.preventDefault();
        return;
    }

    // Vérification plus poussée des conflits
    if (reservations[date]) {
        const selectedStart = convertTimeToMinutes(heureDebut);
        const selectedEnd = convertTimeToMinutes(heureFin);
        
        for (const reservation of reservations[date]) {
            const reservedStart = convertTimeToMinutes(reservation.start);
            const reservedEnd = convertTimeToMinutes(reservation.end);
            
            if ((selectedStart >= reservedStart && selectedStart < reservedEnd) || 
                (selectedEnd > reservedStart && selectedEnd <= reservedEnd) ||
                (selectedStart <= reservedStart && selectedEnd >= reservedEnd)) {
                errorDiv.textContent = "Ce créneau vient d'être réservé. Veuillez choisir un autre créneau.";
                e.preventDefault();
                return;
            }
        }
    }

    errorDiv.textContent = "";
});
    </script>
 <script>
document.getElementById('date').addEventListener('change', function () {
    const selectedDate = this.value;
    const reservedSlots = reservations[selectedDate] || [];

    // Réinitialiser tous les boutons
    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.disabled = false;
        btn.style.display = 'inline-block';
    });

    // Convertir l'heure en minutes
    const timeToMinutes = (timeStr) => {
        const [h, m] = timeStr.split(':').map(Number);
        return h * 60 + m;
    };

    // Marquer les heures réservées + les heures avant et après
    const toHide = new Set();
    reservedSlots.forEach(slot => {
        const start = timeToMinutes(slot.start);
        const end = timeToMinutes(slot.end);
for (let t = start - 60; t < end; t += 30) {
            if (t >= 480 && t <= 1380) { // entre 08:00 et 23:00
                const h = String(Math.floor(t / 60)).padStart(2, '0');
                const m = String(t % 60).padStart(2, '0');
                toHide.add(`${h}:${m}`);
            }
        }
    });

    // Masquer les boutons correspondants
    toHide.forEach(time => {
        const btn = document.getElementById('btn-' + time.replace(':', ''));
        if (btn) {
            btn.style.display = 'none';
        }
    });
});
</script>


    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>
