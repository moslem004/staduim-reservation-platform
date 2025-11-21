<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    // --- MODIFICATION ---
    $edit_id = $_POST['edit_id'];
    $nom = $_POST['edit_nom'];
    $localisation = $_POST['edit_localisation'];
    $prix = $_POST['edit_prix'];
    $maps = $_POST['edit_maps'];
    $photo = null;

    // Gérer l'upload de la nouvelle photo si fournie
    if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $photoName = uniqid() . '_' . basename($_FILES['edit_photo']['name']);
        $uploadFile = $uploadDir . $photoName;
        if (move_uploaded_file($_FILES['edit_photo']['tmp_name'], $uploadFile)) {
            $photo = $photoName;
        }
    }

    if ($photo) {
        $stmt = $pdo->prepare("UPDATE terrains SET nom=?, localisation=?, prix_heure=?, maps=?, photo=? WHERE id=?");
        $stmt->execute([$nom, $localisation, $prix, $maps, $photo, $edit_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE terrains SET nom=?, localisation=?, prix_heure=?, maps=? WHERE id=?");
        $stmt->execute([$nom, $localisation, $prix, $maps, $edit_id]);
    }
    echo "<script>location.href='mes_terrains.php';</script>";
    exit;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'])) {
    // --- AJOUT ---
    $nom = $_POST['nom'];
    $localisation = $_POST['localisation'];
    $prix = $_POST['prix'];
    $maps = $_POST['maps'];
    $id = $_SESSION['user']['id'];

    // Handle image upload
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $photoName = uniqid() . '_' . basename($_FILES['photo']['name']);
        $uploadFile = $uploadDir . $photoName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
            $photo = $photoName;
        }
    }

    $latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

    $stmt = $pdo->prepare("INSERT INTO terrains (nom, localisation, prix_heure, id_proprietaire, photo, maps, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $localisation, $prix, $id, $photo, $maps, $latitude, $longitude]);
    echo "Terrain ajouté avec succès.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reserve&Play - Gérer mes terrains</title>
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
.terrains-container {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    margin-top: 30px;
    justify-content: center;
    align-items: flex-start;
    min-height: 60vh;
}

.terrain-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.09);
    border: 1px solid #e0e0e0;
    padding: 22px 18px;
    width: 300px;
    transition: box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 340px;
    justify-content: flex-start;
    height: auto;
}

.terrain-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.13);
}

.terrain-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 12px;
    background: #f5f5f5;
}

.terrain-card strong {
    color: #333;
}

.terrain-actions {
    margin-top: 14px;
    width: 100%;
    display: flex;
    justify-content: space-between;
}

.terrain-actions a {
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.15s;
}

.terrain-actions a:first-child {
    background: #e3f0ff;
    color: #1976d2;
    border: 1px solid #90caf9;
}

.terrain-actions a:last-child {
    background: #ffeaea;
    color: #d32f2f;
    border: 1px solid #ffcdd2;
}

.terrain-actions a:hover {
    opacity: 0.8;
}

#add-form-card {
    width: 350px;
    min-width: 300px;
    max-width: 100%;
    background: #e8f5e9; /* vert très clair */
    border: 2px solid #43a047; /* vert foncé */
    border-radius: 14px;
    box-shadow: 0 2px 16px rgba(67, 160, 71, 0.09);
    margin-bottom: 20px;
}

#add-form-card form {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    align-items: stretch;
}

#add-form-card input[type="text"],
#add-form-card input[type="number"],
#add-form-card input[type="file"],
#add-form-card input[type="url"] {
    padding: 8px 12px;
    border: 1px solid #81c784; /* vert clair */
    border-radius: 6px;
    font-size: 15px;
    background: #fff;
    margin-top: 2px;
}

#add-form-card label {
    font-weight: 500;
    color: #388e3c; /* vert foncé */
    margin-bottom: 2px;
}

#add-form-card button[type="submit"] {
    background: #43a047; /* vert foncé */
    color: #fff;
    border: none;
    padding: 9px 0;
    border-radius: 6px;
    font-weight: bold;
    margin-top: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

#add-form-card button[type="submit"]:hover {
    background: #2e7031; /* vert plus foncé */
}

#add-form-card button#cancel-add,
#edit-form-card button#cancel-edit {
    background: #ffebee;         /* rouge très clair */
    color: #d32f2f;              /* rouge vif */
    border: 1px solid #ffcdd2;   /* rouge clair */
    margin-top: 0;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, border 0.2s;
}

#add-form-card button#cancel-add:hover,
#edit-form-card button#cancel-edit:hover {
    background: #d32f2f;         /* rouge vif */
    color: #fff;
    border: 1px solid #d32f2f;
}

#edit-form-card {
    width: 350px;
    min-width: 300px;
    max-width: 100%;
    background: #f3f7ff;
    border: 2px solid #1976d2;
    border-radius: 14px;
    box-shadow: 0 2px 16px rgba(25, 118, 210, 0.09);
    margin-bottom: 20px;
}

#edit-form-card form {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    align-items: stretch;
}

#edit-form-card input[type="text"],
#edit-form-card input[type="number"],
#edit-form-card input[type="file"],
#edit-form-card input[type="url"] {
    padding: 8px 12px;
    border: 1px solid #90caf9;
    border-radius: 6px;
    font-size: 15px;
    background: #fff;
    margin-top: 2px;
}

#edit-form-card label {
    font-weight: 500;
    color: #1976d2;
    margin-bottom: 2px;
}

#edit-form-card button[type="submit"] {
    background: #1976d2;
    color: #fff;
    border: none;
    padding: 9px 0;
    border-radius: 6px;
    font-weight: bold;
    margin-top: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

#edit-form-card button[type="submit"]:hover {
    background: #125ea2;
}

#edit-form-card button#cancel-edit {
    background: #eee;
    color: #333;
    border: 1px solid #ccc;
    margin-top: 0;
    cursor: pointer;
    transition: background 0.2s;
}

#edit-form-card button#cancel-edit:hover {
    background: #e57373;
    color: #fff;
    border: 1px solid #e57373;
}

.user-dropdown-wrapper {
    position: relative;
}

.user-dropdown-toggle:after {
    content: " ▼";
    font-size: 0.8em;
    color: #388e3c;
}

.user-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 120%;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 18px rgba(25, 118, 210, 0.08);
    min-width: 180px;
    z-index: 1000;
    padding: 8px 0;
    transition: opacity 0.15s;
}

.user-dropdown-menu a {
    display: block;
    padding: 10px 22px;
    color: #1976d2;
    text-decoration: none;
    font-weight: 500;
    border-radius: 4px;
    transition: background 0.15s;
}

.user-dropdown-menu a:last-child {
    color: #d32f2f;
}

.user-dropdown-menu a:hover {
    background: #f5f5f5;
}

.user-dropdown-wrapper:hover .user-dropdown-menu,
.user-dropdown-menu:hover {
    display: block;
}

@media (max-width: 600px) {
    .user-dropdown-menu {
        left: 0 !important;
        right: 0 !important;
        min-width: 100vw !important;
        border-radius: 0 0 12px 12px;
        top: 110%;
        box-shadow: 0 8px 24px rgba(25, 118, 210, 0.13);
        padding: 12px 0;
    }
    .user-dropdown-menu a {
        font-size: 18px;
        padding: 16px 28px;
        text-align: left;
    }
    .user-dropdown-wrapper {
        width: 100vw;
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
            <h1 class="display-2 text-dark mb-4 animated slideInDown">Gérer mes terrains</h1>
        </div>
    </div>
    <!-- Page Header End -->

<div class="terrains-container">

    <!-- Bouton Ajouter un terrain -->
    <div class="terrain-card" id="show-add-form" style="justify-content:center;align-items:center;cursor:pointer;opacity:0.85;">
        <div style="display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#1976d2;">
            <i class="fas fa-plus-circle" style="font-size:48px;margin-bottom:12px;"></i>
            <span style="font-weight:bold;">Ajouter un terrain</span>
        </div>
    </div>

    <!-- Formulaire d'ajout caché au début -->
    <div class="terrain-card" id="add-form-card" style="display:none;">
        <form method="POST" enctype="multipart/form-data">
            <h1>Ajouter un terrain</h1>
            <input type="text" id="nom" name="nom" required placeholder="Nom et prenom">

            <input type="text" id="localisation" name="localisation" required placeholder="Localisation">

            <input type="number" id="prix" name="prix" step="0.1" required placeholder="Prix">

            <input type="url" id="maps" name="maps" placeholder="Lien Google Maps">

            <label for="photo">Photo</label>
            <input type="file" id="photo" name="photo" accept="image/*">

            <input type="hidden" id="latitude" name="latitude" required>
<input type="hidden" id="longitude" name="longitude" required>


<button type="submit" id="ajouter-btn">Ajouter</button>
            <button type="button" id="cancel-add" style="margin-left:10px;">Annuler</button>
        </form>
    </div>

    <!-- Formulaire d'édition caché au début -->
    <div class="terrain-card" id="edit-form-card" style="display:none;">
        <form method="POST" enctype="multipart/form-data" id="edit-form">
            <h1>Modifier le terrain</h1>
            <input type="hidden" id="edit-id" name="edit_id">
            <label for="edit-nom">Nom</label>
            <input type="text" id="edit-nom" name="edit_nom" required>
            <label for="edit-localisation">Localisation</label>
            <input type="text" id="edit-localisation" name="edit_localisation" required>
            <label for="edit-prix">Prix</label>
            <input type="number" id="edit-prix" name="edit_prix" step="0.1" required>
            <label for="edit-maps">Lien Google Maps</label>
            <input type="url" id="edit-maps" name="edit_maps">
            <label for="edit-photo">Photo (laisser vide pour ne pas changer)</label>
            <input type="file" id="edit-photo" name="edit_photo" accept="image/*">
            <button type="submit">Enregistrer</button>
            <button type="button" id="cancel-edit" style="margin-left:10px;">Annuler</button>
        </form>
    </div>

<?php
if (isset($_SESSION['user']['id'])) {
    $id = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT * FROM terrains WHERE id_proprietaire = ?");
    $stmt->execute([$id]);
    $terrains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($terrains as $terrain):
?>
    <div class="terrain-card">
        <?php if ($terrain['photo']): ?>
            <img src="uploads/<?= htmlspecialchars($terrain['photo']) ?>" alt="Photo">
        <?php endif; ?>
        <strong>Nom :</strong> <?= htmlspecialchars($terrain['nom']) ?><br>
        <strong>Localisation :</strong> <?= htmlspecialchars($terrain['localisation']) ?><br>
        <strong>Prix/heure :</strong> <?= htmlspecialchars($terrain['prix_heure']) ?> DT<br>
        <?php if (!empty($terrain['maps'])): ?>
    <a href="<?= htmlspecialchars($terrain['maps']) ?>" target="_blank" style="color:#1976d2;word-break:break-all;">
        Voir sur Google Maps
    </a><br>
<?php endif; ?>
        <div class="terrain-actions">
            <a href="#" class="edit-btn" 
               data-id="<?= $terrain['id'] ?>"
               data-nom="<?= htmlspecialchars($terrain['nom'], ENT_QUOTES) ?>"
               data-localisation="<?= htmlspecialchars($terrain['localisation'], ENT_QUOTES) ?>"
               data-prix="<?= $terrain['prix_heure'] ?>"
               data-maps="<?= htmlspecialchars($terrain['maps'], ENT_QUOTES) ?>"
               data-photo="<?= htmlspecialchars($terrain['photo'], ENT_QUOTES) ?>">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="delete_terrain.php?id=<?= $terrain['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce terrain ?');">
                <i class="fas fa-trash-alt"></i> Supprimer
            </a>
        </div>
    </div>
<?php
    endforeach;
}
?>
</div>

<br><br>
    <!-- Copyright Start -->
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
    <script>
        document.getElementById('show-add-form').onclick = function() {
    document.getElementById('show-add-form').style.display = 'none';
    document.getElementById('add-form-card').style.display = 'flex';
};
document.getElementById('cancel-add').onclick = function() {
    document.getElementById('add-form-card').style.display = 'none';
    document.getElementById('show-add-form').style.display = 'flex';
};

// Script pour le formulaire d'édition
const editBtns = document.querySelectorAll('.edit-btn');
const editFormCard = document.getElementById('edit-form-card');
const editForm = document.getElementById('edit-form');
const cancelEditBtn = document.getElementById('cancel-edit');

editBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const terrainId = this.getAttribute('data-id');
        const terrainNom = this.getAttribute('data-nom');
        const terrainLocalisation = this.getAttribute('data-localisation');
        const terrainPrix = this.getAttribute('data-prix');
        const terrainMaps = this.getAttribute('data-maps');
        const terrainPhoto = this.getAttribute('data-photo');

        document.getElementById('edit-id').value = terrainId;
        document.getElementById('edit-nom').value = terrainNom;
        document.getElementById('edit-localisation').value = terrainLocalisation;
        document.getElementById('edit-prix').value = terrainPrix;
        document.getElementById('edit-maps').value = terrainMaps;

        editFormCard.style.display = 'flex';
        document.getElementById('add-form-card').style.display = 'none';
    });
});

cancelEditBtn.addEventListener('click', function() {
    editFormCard.style.display = 'none';
    document.getElementById('show-add-form').style.display = 'flex';
});

$('.edit-btn').on('click', function(e) {
    e.preventDefault();
    $('#edit-id').val($(this).data('id'));
    $('#edit-nom').val($(this).data('nom'));
    $('#edit-localisation').val($(this).data('localisation'));
    $('#edit-prix').val($(this).data('prix'));
    $('#edit-maps').val($(this).data('maps'));
    $('#edit-form-card').css('display', 'flex');
    $('#add-form-card').hide();
    $('#show-add-form').hide();
});
$('#cancel-edit').on('click', function() {
    $('#edit-form-card').hide();
    $('#show-add-form').css('display', 'flex');
});

document.querySelector('#add-form-card form').addEventListener('submit', function(e) {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    if (!latInput.value || !lngInput.value) {
        e.preventDefault();
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                latInput.value = position.coords.latitude;
                lngInput.value = position.coords.longitude;
                // Submit à nouveau après avoir mis à jour les champs
                e.target.submit();
            }, function(error) {
                alert("Impossible de récupérer la position : " + error.message);
            });
        } else {
            alert("La géolocalisation n'est pas supportée par ce navigateur.");
        }
    }
});
    </script>
</body>

</html>