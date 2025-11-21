<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$user_type = $_SESSION['user']['type'] ?? 'joueur';

if ($user_type === 'proprietaire') {
    // Afficher les réservations des terrains du propriétaire
    $res = $pdo->prepare("
        SELECT reservations.*, terrains.nom
        FROM reservations
        JOIN terrains ON reservations.id_terrain = terrains.id
        WHERE terrains.id_proprietaire = ?
    ");
    $res->execute([$user_id]);
} else {
    // Afficher les réservations faites par l'utilisateur (joueur)
    $res = $pdo->prepare("
        SELECT reservations.*, terrains.nom
        FROM reservations
        JOIN terrains ON reservations.id_terrain = terrains.id
        WHERE reservations.id_client = ?
    ");
    $res->execute([$user_id]);
}

$events = [];
foreach ($res as $r) {
    $events[] = [
        'id'    => $r['id'], // <-- ajoute cette ligne
        'title' => $r['nom'] . " - " . $r['nom_client'] . " (" . $r['tel_client'] . ")",
        'start' => $r['date'] . 'T' . $r['heure_debut'],
        'end'   => $r['date'] . 'T' . $r['heure_fin'],
        'description' => "Nom: " . $r['nom_client'] . " | Tel: " . $r['tel_client']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reserve&Play - Mes réservations</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
        <link rel="icon" type="image/png" href="logo.png">
        <link href="https://fonts.googleapis.com/css2?family=Urbanist&display=swap" rel="stylesheet">


    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">

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
            <style>
         /* Calendrier responsive */
#calendar {
    max-width: 1100px;
    margin: 50px auto;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(60, 120, 60, 0.2);
    padding: 30px 30px 10px 30px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(60, 120, 60, 0.2);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
    min-height: 400px;
}

#calendar:hover {
    box-shadow: 0 14px 48px rgba(60, 120, 60, 0.3);
    transform: scale(1.01);
}

.fc-toolbar-title {
    font-size: 2.2rem !important;
    color: #2e7d32;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

.fc-button {
    background: linear-gradient(145deg, #43a047, #2e7d32) !important;
    border: none !important;
    color: #fff !important;
    border-radius: 8px !important;
    padding: 8px 18px !important;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 4px 10px rgba(67,160,71,0.3);
    transition: background 0.25s, transform 0.2s;
}

.fc-button:hover, .fc-button:focus {
    background: linear-gradient(145deg, #2e7d32, #1b5e20) !important;
    transform: scale(1.05);
}

.fc-event {
    background: #66bb6a !important;
    border: none !important;
    color: #1b5e20 !important;
    border-radius: 8px !important;
    font-size: 1rem;
    font-weight: 500;
    padding: 4px 8px;
    box-shadow: 0 3px 10px rgba(102,187,106,0.3);
    transition: all 0.2s;
    word-break: break-word;
}

.fc-event:hover {
    background: #81c784 !important;
    color: #0b3d0b !important;
    transform: scale(1.03);
}

.fc-timegrid-slot-label, .fc-col-header-cell-cushion {
    color: #388e3c !important;
    font-weight: 600;
    font-size: 0.95rem;
}

.fc-scrollgrid {
    border-radius: 14px;
    overflow: hidden;
}

/* Responsive */
@media (max-width: 900px) {
    #calendar {
        padding: 10px 2px 2px 2px;
        max-width: 100vw;
        min-height: 350px;
    }
    .fc-toolbar-title {
        font-size: 1.3rem !important;
    }
    .fc-button {
        padding: 6px 10px !important;
        font-size: 0.85rem;
    }
    .fc-event {
        font-size: 0.85rem;
        padding: 2px 4px;
    }
    .fc-col-header-cell, .fc-timegrid-slot-label {
        font-size: 0.8rem !important;
    }
}

@media (max-width: 600px) {
    #calendar {
        padding: 4px 0 0 0;
        max-width: 100vw;
        min-height: 250px;
    }
    .fc-toolbar-title {
        font-size: 1rem !important;
    }
    .fc-button {
        padding: 4px 6px !important;
        font-size: 0.75rem;
    }
    .fc-event {
        font-size: 0.75rem;
        padding: 1px 2px;
    }
    .fc-col-header-cell, .fc-timegrid-slot-label {
        font-size: 0.7rem !important;
    }
    .fc-header-toolbar {
        flex-direction: column !important;
        gap: 4px;
    }
}
    </style>
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
            <h1 class="display-2 text-dark mb-4 animated slideInDown">Mes réservations</h1>

        </div>
    </div>
    <!-- Page Header End -->
<!-- Filtre par terrain --> <center>
<div class="container mb-3">
    <label for="terrainFilter" class="form-label fw-bold">Filtrer par terrain :</label>
    <select id="terrainFilter" class="form-select" style="max-width:300px;display:inline-block;">
        <option value="all">Tous les terrains</option>
        <?php
        // Récupère la liste des terrains pour le filtre
        if ($user_type === 'proprietaire') {
            $terrains = $pdo->prepare("SELECT id, nom FROM terrains WHERE id_proprietaire = ?");
            $terrains->execute([$user_id]);
        } else {
            $terrains = $pdo->query("SELECT id, nom FROM terrains");
        }
        foreach ($terrains as $t) {
            echo '<option value="' . htmlspecialchars($t['nom']) . '">' . htmlspecialchars($t['nom']) . '</option>';
        }
        ?>
    </select>
</div></center>

   <div id="calendar"></div>


        <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="fw-medium" href="#">Reserve & Play</a>, All Right Reserved.
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
                    Designed By <a class="fw-medium" href="#">Mohamed Aziz Arfaoui</a> Founder of <a class="fw-medium" >Tout ce qui est informatique</a>
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

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    var selectedReservationId = null;

    var calendarEl = document.getElementById('calendar');
    var allEvents = <?php echo json_encode($events); ?>;

function getFilteredEvents(terrain) {
    if (terrain === "all") return allEvents;
    return allEvents.filter(function(ev) {
        // Le nom du terrain est avant le premier " - "
        return ev.title.startsWith(terrain + " ");
    });
}

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridDay,timeGridWeek'
        },
        buttonText: {
            timeGridDay: 'Jour',
            timeGridWeek: 'Semaine'
        },
        events: getFilteredEvents(document.getElementById('terrainFilter').value),
        eventDidMount: function(info) {
            if (info.event.extendedProps.description) {
                info.el.title = info.event.extendedProps.description;
            }
        },
        eventClick: function(info) {
            // Récupérer les infos depuis l'événement
            var title = info.event.title; // format: terrain - nom (tel)
            var description = info.event.extendedProps.description; // format: Nom: ... | Tel: ...
            var start = info.event.start;
            // Extraction des infos
            var terrain = title.split(' - ')[0];
            var nom = description.match(/Nom: ([^|]+)/) ? description.match(/Nom: ([^|]+)/)[1].trim() : '';
            var tel = description.match(/Tel: (.+)/) ? description.match(/Tel: (.+)/)[1].trim() : '';
            var date = start.toLocaleDateString('fr-FR');
            var heure = start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

            // Affichage formaté
            document.getElementById('reservationDetails').innerHTML =
                "<strong>Terrain :</strong> " + terrain + "<br>" +
                "<strong>Nom du réservateur :</strong> " + nom + "<br>" +
                "<strong>Numéro de téléphone :</strong> " + tel + "<br>" +
                "<strong>Date et heure :</strong> " + date + " à " + heure;

            selectedReservationId = info.event.id; // <-- stocke l'ID de la réservation

            var myModal = new bootstrap.Modal(document.getElementById('reservationModal'));
            myModal.show();
        }
    });
    calendar.render();

    // Gestion du clic sur le bouton d'annulation
    document.getElementById('cancelReservationBtn').onclick = function() {
        if (selectedReservationId && confirm("Êtes-vous sûr de vouloir annuler cette réservation ?")) {
            window.location.href = "annuler_reservation.php?id=" + selectedReservationId;
        }
    };

    // Filtrage dynamique
    document.getElementById('terrainFilter').addEventListener('change', function() {
        var selected = this.value;
        calendar.removeAllEvents();
        calendar.addEventSource(getFilteredEvents(selected));
    });
});
</script>

<!-- Modal Bootstrap -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reservationModalLabel">Détails de la réservation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <p id="reservationDetails"></p>
        <button id="cancelReservationBtn" class="btn btn-danger">Annuler la réservation</button>
      </div>
    </div>
  </div>
</div>
</body>
</html>
