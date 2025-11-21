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
$terrains = $pdo->query("SELECT * FROM terrains")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
<title>Reserve and Play - Réservation de terrains de football en Tunisie</title>

<meta name="description" content="Réservez vos terrains de sport en Tunisie avec Reserve and Play : Simple, rapide et gratuit. Essayez maintenant !">

<meta name="keywords" content="réservation terrain, sport, foot, reserve and play, Reserve&Play, Tunisie">

<meta name="author" content="Aziz Arfoui">

<meta name="robots" content="index, follow">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
<link rel="icon" href="logo.png" type="image/png">
<link rel="shortcut icon" href="logo.png" type="image/png">
<link rel="apple-touch-icon" href="logo.png">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist&display=swap" rel="stylesheet">


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
                        <a href="index.php" class="nav-item nav-link active">Accueil</a>
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


    <!-- Carousel Start -->
    <div class="container-fluid px-0 mb-5">
        <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100" src="img/bg1.png" alt="Image"> 
                    <div class="carousel-caption">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-lg-7 text-center d-flex flex-column align-items-center">
    <img src="img/log.png" alt="" style="width:200px; height: 160px;margin-top:-100px" class="mb-3 order-1 order-md-1">
    <h1 class="display-1 text-dark mb-4 animated zoomIn order-2 order-md-2">Le foot comme tu l’aimes</h1>
</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100" src="img/bg1.png" alt="Image">
                    <div class="carousel-caption">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-lg-7 text-center d-flex flex-column align-items-center">
    <img src="img/log.png" alt="" style="width:200px; height: 160px;margin-top:-100px" class="mb-3 order-1 order-md-1">
                                    <h1 class="display-1 text-dark mb-4 animated zoomIn order-2 order-md-2">Réservez ,<br> Jouez ,<br>  Gagnez.</h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel"
                data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    <!-- Carousel End -->




    <!-- Products Start -->
<div class="container-fluid product py-5 bg-light">
    <div class="container py-5">
        <div class="section-title text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
            <p class="fs-5 fw-medium fst-italic text-success">Nos Terrains</p>
            <h1 class="display-6">Réservez votre terrain idéal facilement</h1>
        </div>
        <div class="owl-carousel product-carousel wow fadeInUp" data-wow-delay="0.5s">
            <?php foreach ($terrains as $t): ?>
                <a href="reserver_terrain.php?id=<?= $t['id'] ?>" class="d-block product-item rounded shadow-sm">
                    <img style="width: 430px;height :280px" src="uploads/<?= htmlspecialchars($t['photo']) ?>"  alt="<?= htmlspecialchars($t['nom']) ?>">
                    <div class="bg-white text-center p-4 position-relative mt-n5 mx-4 border">
                        <h4 class="text-success"><?= htmlspecialchars($t['nom']) ?></h4>
                        <span class="text-body"><i class="fa fa-map-marker-alt me-1"></i> <?= htmlspecialchars($t['localisation']) ?></span><br>
                        <strong class="text-primary"><?= htmlspecialchars($t['prix_heure']) ?> DT / heure et demi</strong>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
          <div class="col-12 text-center wow fadeInUp" data-wow-delay="0.1s">
                <a href="terrains.php" class="btn btn-outline-success rounded-pill py-3 px-5 mt-4">Voir tous les terrains</a>
            </div>
    </div>
</div>
    <!-- Products End -->

    <!-- Contact Start -->
<br><br>    <div class="container">
        <div class="section-title text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
            <p class="fs-5 fw-medium fst-italic text-success">Pourquoi nous choisir ?</p>
            <h1 class="display-6">Réservez un terrain n’a jamais été aussi simple</h1>
        </div>
        <div class="row g-5 mt-4 justify-content-center">
            <div class="col-md-6 col-lg-3 text-center wow fadeInUp" data-wow-delay="0.2s">
                <div class="btn-square bg-success text-white mb-3 mx-auto" style="width: 60px; height: 60px;">
                    <i class="fa fa-map-marker-alt fa-2x"></i>
                </div>
                <h5 class="mb-2">Trouvez un terrain près de chez vous</h5>
                <p class="text-muted">Recherchez rapidement les terrains disponibles dans votre ville ou quartier.</p>
            </div>
            <div class="col-md-6 col-lg-3 text-center wow fadeInUp" data-wow-delay="0.3s">
                <div class="btn-square bg-success text-white mb-3 mx-auto" style="width: 60px; height: 60px;">
                    <i class="fa fa-mobile-alt fa-2x"></i>
                </div>
                <h5 class="mb-2">Réservez depuis votre téléphone</h5>
                <p class="text-muted">Un système simple et rapide accessible depuis n’importe quel appareil.</p>
            </div>
            <div class="col-md-6 col-lg-3 text-center wow fadeInUp" data-wow-delay="0.4s">
                <div class="btn-square bg-success text-white mb-3 mx-auto" style="width: 60px; height: 60px;">
                    <i class="fa fa-clock fa-2x"></i>
                </div>
                <h5 class="mb-2">Gagnez du temps</h5>
                <p class="text-muted">Plus besoin d’appeler ou de vous déplacer, tout se fait en ligne.</p>
            </div>
            <div class="col-md-6 col-lg-3 text-center wow fadeInUp" data-wow-delay="0.5s">
                <div class="btn-square bg-success text-white mb-3 mx-auto" style="width: 60px; height: 60px;">
                    <i class="fa fa-check-circle fa-2x"></i>
                </div>
                <h5 class="mb-2">Recevez une confirmation</h5>
                <p class="text-muted">Une fois votre réservation validée, vous recevez un message de confirmation.</p>
            </div>
        </div>
    </div>
</div>
    <!-- Copyright Start -->
    <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="fw-medium" href="#">Reserve and Play</a>, All Right Reserved.
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
                    Designed By <a class="fw-medium" href="">Mohamed Aziz Arfaoui</a> Founder of <a class="fw-medium" >Tout ce qui est informatique</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Copyright End -->


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
</body>

</html>