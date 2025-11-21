<?php
session_start();
include 'includes/db.php';

$id_proprietaire = $_SESSION['user']['id'];

// Récupérer les terrains du propriétaire
$stmt = $pdo->prepare("SELECT id, nom FROM terrains WHERE id_proprietaire = ?");
$stmt->execute([$id_proprietaire]);
$terrains = $stmt->fetchAll();

// Traitement des filtres
$filtre_jour = $_GET['filtre_jour'] ?? '';
$filtre_mois = $_GET['filtre_mois'] ?? '';

// Construction de la clause WHERE dynamique
$where = "r.id_terrain IN (SELECT id FROM terrains WHERE id_proprietaire = ?)";
$params = [$id_proprietaire];

if ($filtre_jour) {
    $where .= " AND DATE(r.date) = ?";
    $params[] = $filtre_jour;
} elseif ($filtre_mois) {
    $where .= " AND DATE_FORMAT(r.date, '%Y-%m') = ?";
    $params[] = $filtre_mois;
}

// Statistiques globales filtrées
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(r.id) as total_reservations,
        SUM(rev.montant_total) as total_revenus,
        SUM(rev.commission_admin) as total_commission,
        SUM(rev.montant_proprietaire) as total_proprietaire
    FROM reservations r
    JOIN revenus rev ON r.id = rev.id_reservation
    WHERE $where
");
$stmtStats->execute($params);
$stats = $stmtStats->fetch();

// Revenus par mois (pour le graphique)
$stmtGraph = $pdo->prepare("
    SELECT DATE_FORMAT(r.date, '%Y-%m') as mois, SUM(rev.montant_proprietaire) as total
    FROM reservations r
    JOIN revenus rev ON r.id = rev.id_reservation
    WHERE r.id_terrain IN (SELECT id FROM terrains WHERE id_proprietaire = ?)
    GROUP BY mois
    ORDER BY mois
");
$stmtGraph->execute([$id_proprietaire]);
$revenusMois = $stmtGraph->fetchAll(PDO::FETCH_ASSOC);
$labels = [];
$values = [];
foreach ($revenusMois as $row) {
    $labels[] = $row['mois'];
    $values[] = $row['total'];
}

// Revenus par jour pour le dernier mois
$dernierMois = end($labels); // format YYYY-MM
if ($dernierMois) {
    $stmtDay = $pdo->prepare("
        SELECT DATE(r.date) as jour, SUM(rev.montant_proprietaire) as total
        FROM reservations r
        JOIN revenus rev ON r.id = rev.id_reservation
        WHERE r.id_terrain IN (SELECT id FROM terrains WHERE id_proprietaire = ?)
        AND DATE_FORMAT(r.date, '%Y-%m') = ?
        GROUP BY jour
        ORDER BY jour
    ");
    $stmtDay->execute([$id_proprietaire, $dernierMois]);
    $revenusJours = $stmtDay->fetchAll(PDO::FETCH_ASSOC);
    $labelsJours = [];
    $valuesJours = [];
    foreach ($revenusJours as $row) {
        $labelsJours[] = $row['jour'];
        $valuesJours[] = $row['total'];
    }
} else {
    $labelsJours = [];
    $valuesJours = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reserve&Play - Dashboard</title>
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
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        table th,
        table td {
            padding: 12px 16px;
            text-align: center;
        }

        table th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        table tr:nth-child(even) {
            background: #f2f6fc;
        }

        table tr:hover {
            background: #e9ecef;
        }

        table td {
            border-bottom: 1px solid #dee2e6;
            color: #444;
        }

        /* Réduit la taille des graphiques */
    .chart-container {
        max-width: 800px;
        margin: 0 auto;
    }
    canvas {
        max-height: 400px !important; /* augmente la hauteur */
    }

    .form-filtre-stats {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 18px 24px 10px 24px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }
    .form-filtre-stats .row {
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    .form-filtre-stats .col-auto {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-filtre-stats label {
        font-weight: 500;
        color: #198754;
        margin-bottom: 0;
        min-width: 120px;
        text-align: right;
    }
    .form-filtre-stats input[type="date"],
    .form-filtre-stats input[type="month"] {
        min-width: 160px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        background: #fff;
        color: #333;
    }
    .form-filtre-stats .btn-success {
        margin-right: 8px;
    }
    .form-filtre-stats .btn-secondary {
        background: #e9ecef;
        color: #333;
        border: none;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table {
        min-width: 600px;
    }
    @media (max-width: 600px) {
        .table {
            font-size: 13px;
            min-width: 480px;
        }
        .table th, .table td {
            padding: 8px 6px;
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
    <a href="../login.php" class="btn btn-success ms-3">Se connecter</a>
<?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->


    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container text-center py-5">
            <h1 class="display-2 text-dark mb-4 animated slideInDown">Dashboard</h1>
        </div>
    </div>
    <!-- Page Header End -->
<br><br>
<!-- Filtres statistiques globales -->
<form method="get" class="mb-4 form-filtre-stats">
    <div class="row g-2 align-items-end">
        <div class="col-auto">
            <label for="filtre_jour" class="form-label mb-0">Filtrer par jour :</label>
            <input type="date" id="filtre_jour" name="filtre_jour" class="form-control" value="<?= htmlspecialchars($filtre_jour) ?>">
        </div>
        <div class="col-auto">
            <label for="filtre_mois" class="form-label mb-0">Filtrer par mois :</label>
            <input type="month" id="filtre_mois" name="filtre_mois" class="form-control" value="<?= htmlspecialchars($filtre_mois) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-success">Filtrer</button>
            <a href="dashboard.php" class="btn btn-secondary">Réinitialiser</a>
        </div>
    </div>
</form>
<!-- Statistiques globales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Total Réservations</h5>
                <p class="card-text fs-3"><?= $stats['total_reservations'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Revenus Totals</h5>
                <p class="card-text fs-3"><?= number_format($stats['total_revenus'], 2) ?? '0.00' ?> DT</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Commission Admin</h5>
                <p class="card-text fs-3"><?= number_format($stats['total_commission'], 2) ?? '0.00' ?> DT</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Revenu Propriétaire</h5>
                <p class="card-text fs-3"><?= number_format($stats['total_proprietaire'], 2) ?? '0.00' ?> DT</p>
            </div>
        </div>
    </div>
</div>
<div class="row mb-4">
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Revenus par Jour (<?= $dernierMois ?: 'N/A' ?>)</h5>
                    <div class="chart-container">
                        <canvas id="revenuJourChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Revenus par Mois</h5>
                    <div class="chart-container">
                        <canvas id="revenuChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('revenuChart').getContext('2d');
    const revenuChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Revenu Propriétaire (DT)',
                data: <?= json_encode($values) ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    </script>

    <script>
    const ctxJour = document.getElementById('revenuJourChart').getContext('2d');
    const revenuJourChart = new Chart(ctxJour, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsJours) ?>,
            datasets: [{
                label: 'Revenu Propriétaire (DT)',
                data: <?= json_encode($valuesJours) ?>,
                backgroundColor: 'rgba(40,167,69,0.5)',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    </script>

    <?php foreach ($terrains as $terrain): ?>
    <h3>Terrain : <?= htmlspecialchars($terrain['nom']) ?></h3>
    <?php
    // Préparer la requête avec filtre
    $whereTerrain = "r.id_terrain = ?";
    $paramsTerrain = [$terrain['id']];
    if ($filtre_jour) {
        $whereTerrain .= " AND DATE(r.date) = ?";
        $paramsTerrain[] = $filtre_jour;
    } elseif ($filtre_mois) {
        $whereTerrain .= " AND DATE_FORMAT(r.date, '%Y-%m') = ?";
        $paramsTerrain[] = $filtre_mois;
    }
    $stmt2 = $pdo->prepare("
        SELECT r.date, r.heure_debut, r.heure_fin, rev.montant_total, rev.commission_admin, rev.montant_proprietaire
        FROM reservations r
        JOIN revenus rev ON r.id = rev.id_reservation
        WHERE $whereTerrain
        ORDER BY r.date DESC, r.heure_debut DESC
    ");
    $stmt2->execute($paramsTerrain);
    $revenus = $stmt2->fetchAll();
    ?>
    <?php if ($revenus): ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <tr>
                    <th>Date</th>
                    <th>Heure début</th>
                    <th>Heure fin</th>
                    <th>Montant total</th>
                    <th>Commission admin</th>
                    <th>Revenu propriétaire</th>
                </tr>
                <?php foreach ($revenus as $rev): ?>
                    <tr>
                        <td><?= htmlspecialchars($rev['date']) ?></td>
                        <td><?= htmlspecialchars($rev['heure_debut']) ?></td>
                        <td><?= htmlspecialchars($rev['heure_fin']) ?></td>
                        <td><?= number_format($rev['montant_total'], 2) ?> DT</td>
                        <td><?= number_format($rev['commission_admin'], 2) ?> DT</td>
                        <td><strong><?= number_format($rev['montant_proprietaire'], 2) ?> DT</strong></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <p>Aucun revenu pour ce terrain.</p>
    <?php endif; ?>
<?php endforeach; ?>


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
</body>

</html>