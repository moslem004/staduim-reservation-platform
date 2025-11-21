<?php
session_start();
include 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = $_SESSION['user']['id'];
    $id_terrain = $_POST['terrain'];
    $date = $_POST['date'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $stmt = $pdo->prepare("INSERT INTO reservations (id_client, id_terrain, date, heure_debut, heure_fin, statut)
        VALUES (?, ?, ?, ?, ?, 'confirmée')");
    $stmt->execute([$id_client, $id_terrain, $date, $heure_debut, $heure_fin]);

    $prix = $pdo->query("SELECT prix_heure FROM terrains WHERE id = $id_terrain")->fetchColumn();
    $duree = (strtotime($heure_fin) - strtotime($heure_debut)) / 3600;
    $montant = $prix * $duree;
    $commission = $montant * 0.1;
    $stmt2 = $pdo->prepare("INSERT INTO revenus (id_reservation, montant_total, commission_admin, montant_proprietaire)
                            VALUES (LAST_INSERT_ID(), ?, ?, ?)");
    $stmt2->execute([$montant, $commission, $montant - $commission]);
    echo "Réservation confirmée.";
}
// Recherche et tri
$where = '';
$params = [];
$order = '';
$hasLocation = false;

// Essayer de récupérer la position de l'utilisateur automatiquement
if (empty($_GET['sort']) && empty($_GET['localisation']) && empty($_GET['q'])) {
    if (!empty($_GET['lat']) && !empty($_GET['lng'])) {
        $lat = floatval($_GET['lat']);
        $lng = floatval($_GET['lng']);
        $hasLocation = true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Vous pourriez aussi essayer de deviner la position par IP ici
        // Mais c'est moins précis que la géolocalisation HTML5
    }

    if ($hasLocation) {
        $order = "ORDER BY (6371 * acos(
            cos(radians(:lat_order)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(:lng_order)) +
            sin(radians(:lat_order)) * sin(radians(latitude))
        )) ASC";
        $params[':lat_order'] = $lat;
        $params[':lng_order'] = $lng;
    }
}

// Filtres manuels
if (!empty($_GET['q'])) {
    $where = "WHERE nom LIKE :q OR localisation LIKE :q";
    $params[':q'] = '%' . $_GET['q'] . '%';
}

if (!empty($_GET['localisation'])) {
    if ($_GET['localisation'] == 'nearby' && !empty($_GET['lat']) && !empty($_GET['lng'])) {
        $lat = floatval($_GET['lat']);
        $lng = floatval($_GET['lng']);
        $hasLocation = true;
        
        $where .= ($where ? " AND " : "WHERE ") .
            "(6371 * acos(
                cos(radians(:lat)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(:lng)) +
                sin(radians(:lat)) * sin(radians(latitude))
            )) < :radius";
        $params[':lat'] = $lat;
        $params[':lng'] = $lng;
        $params[':radius'] = 500;
    } else {
        $where .= ($where ? " AND " : "WHERE ") . "localisation = :localisation";
        $params[':localisation'] = $_GET['localisation'];
    }
}

// Tri manuel (prioritaire)
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] == 'prix_asc') $order = "ORDER BY prix_heure ASC";
    if ($_GET['sort'] == 'prix_desc') $order = "ORDER BY prix_heure DESC";
}

$sql = "SELECT *, 
        (6371 * acos(
            cos(radians(:lat_calc)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(:lng_calc)) +
            sin(radians(:lat_calc)) * sin(radians(latitude))
        )) AS distance
        FROM terrains $where $order";
$params[':lat_calc'] = $hasLocation ? $lat : 0;
$params[':lng_calc'] = $hasLocation ? $lng : 0;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$terrains = $stmt->fetchAll();

// Récupérer toutes les localisations distinctes
$localisations = $pdo->query("SELECT DISTINCT localisation FROM terrains")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reserve&Play - Terrains</title>
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
            <h1 class="display-2 text-dark mb-4 animated slideInDown">Terrains</h1>
        </div>
    </div>
    <!-- Page Header End -->


    <div class="container">
        <div class="section-title text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
            <p class="fs-5 fw-medium fst-italic text-success">Nos terrains</p>
            <h1 class="display-6">Trouvez un terrain, <br> réservez & jouez</h1>
        </div>
        <!-- Recherche et tri -->
<form method="get" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Rechercher un terrain..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
    </div>
    <div class="col-md-3">
        <select name="localisation" class="form-select" id="localisation-select">
    <option value="">Toutes les localisations</option>
    <?php foreach ($localisations as $loc): ?>
        <option value="<?= htmlspecialchars($loc) ?>" <?= (isset($_GET['localisation']) && $_GET['localisation'] == $loc) ? 'selected' : '' ?>>
            <?= htmlspecialchars($loc) ?>
        </option>
    <?php endforeach; ?>
</select>
<input type="hidden" name="lat" id="lat">
<input type="hidden" name="lng" id="lng">
    </div>
    <div class="col-md-3">
        <select name="sort" class="form-select">
            <option value="">Trier par</option>
            <option value="prix_asc" <?= (isset($_GET['sort']) && $_GET['sort']=='prix_asc')?'selected':'' ?>>Prix croissant</option>
            <option value="prix_desc" <?= (isset($_GET['sort']) && $_GET['sort']=='prix_desc')?'selected':'' ?>>Prix décroissant</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-success w-100">Rechercher</button>
    </div>
</form>
        <div class="row g-4">
     <?php foreach ($terrains as $t): ?>
    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
        <div class="store-item position-relative text-center shadow-sm border rounded overflow-hidden">
            <img class="img-fluid" src="uploads/<?= htmlspecialchars($t['photo']) ?>" alt="Terrain" style="width=100%; height: 200px; object-fit: cover;">
            <div class="p-4 bg-white">
                <div class="text-center mb-2">
                    <small class="fa fa-star text-warning"></small>
                    <small class="fa fa-star text-warning"></small>
                    <small class="fa fa-star text-warning"></small>
                    <small class="fa fa-star text-warning"></small>
                    <small class="fa fa-star-half-alt text-warning"></small>
                </div>
                <h4 class="mb-2"><?= htmlspecialchars($t['nom']) ?></h4>
                <p class="mb-1 text-muted"><i class="fa fa-map-marker-alt me-2"></i><?= htmlspecialchars($t['localisation']) ?></p>
                <?php if ($hasLocation && isset($t['distance'])): ?>
                    <p class="mb-1 text-muted"><i class="fa fa-location-arrow me-2"></i><?= round($t['distance'], 1) ?> km</p>
                <?php endif; ?>
                <h5 class="text-primary mb-3"><?= htmlspecialchars($t['prix_heure']) ?> DT / heure</h5>
            </div>
            <div class="store-overlay d-flex justify-content-center align-items-center">
                <a href="reserver_terrain.php?id=<?= $t['id'] ?>" class="btn btn-success rounded-pill py-2 px-4 m-2">
                    Réserver <i class="fa fa-calendar-check ms-2"></i>
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
        </div>
    </div>
</div>
<br><br>
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
// Obtenir la position au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    if (navigator.geolocation && !window.location.search.includes('lat=')) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const url = new URL(window.location.href);
                url.searchParams.set('lat', position.coords.latitude);
                url.searchParams.set('lng', position.coords.longitude);
                window.location.href = url.toString();
            },
            function(error) {
                console.log("Géolocalisation non disponible", error);
            },
            { timeout: 5000 }
        );
    }
});

// Gestion du filtre "Autour de moi"
document.getElementById('localisation-select').addEventListener('change', function() {
    if (this.value === 'nearby') {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('lat').value = position.coords.latitude;
                    document.getElementById('lng').value = position.coords.longitude;
                    document.querySelector('form').submit();
                },
                function(error) {
                    alert("Erreur de géolocalisation : " + error.message);
                    this.value = '';
                }.bind(this)
            );
        } else {
            alert("La géolocalisation n'est pas supportée par ce navigateur.");
            this.value = '';
        }
    }
});
</script>
</body>

</html>