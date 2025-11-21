<?php
include 'includes/db.php';
$register_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $type = $_POST['type'];

    // Vérifier si email ou téléphone existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR telephone = ?");
    $stmt->execute([$email, $telephone]);
    $exists = $stmt->fetchColumn();
    if ($exists > 0) {
        $register_error = "Cet email ou numéro de téléphone est déjà utilisé.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (nom, email, telephone, mot_de_passe, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $email, $telephone, $mot_de_passe, $type]);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $_SESSION['user'] = $user;
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reserve&Play - Créer un compte</title>
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
        /* Style du formulaire d'inscription (identique à login) */
        form {
            max-width: 350px;
            margin: 0 auto 40px auto;
            padding: 30px 25px;
            background: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
        }

        .form-label {
            font-weight: 600;
            color: #145c32;
            margin-bottom: 6px;
            display: block;
            font-size: 1.05rem;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form select {
            width: 100%;
            padding: 14px 12px;
            margin-bottom: 0;
            border: 1.5px solid #ced4da;
            border-radius: 8px;
            font-size: 1.08rem;
            background: #fff;
            color: #222;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(25, 135, 84, 0.04);
        }

        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form input[type="password"]:focus,
        form select:focus {
            border-color: #198754;
            outline: none;
            box-shadow: 0 0 0 2px #19875433;
            background: #f3fdf7;
        }

        form button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #198754;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 8px;
        }

        form button[type="submit"]:hover {
            background: #145c32;
        }

        .creer-compte-link {
            display: block;
            width: 100%;
            max-width: 350px;
            margin: 0 auto 20px auto;
            padding: 12px 0;
            background: #fff;
            color: #198754;
            border: 2px solid #198754;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 1.05rem;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .creer-compte-link:hover {
            background: #198754;
            color: #fff;
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
            <a href="mes_terrains.php">Gérer mes terrains</a>
            <a href="dashboard.php">Dashboard</a>
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
            <h1 class="display-2 text-dark mb-4 animated slideInDown">Créer un compte</h1>
        </div>
    </div>
    <!-- Page Header End -->

  <form method="POST" style="margin-bottom:30px;" id="registerForm">
    <div class="form-group">
        <label for="nom" class="form-label">Nom et Prenom :</label>
        <input type="text" name="nom" id="nom" required>
    </div>
    <div class="form-group">
        <label for="email" class="form-label">Email :</label>
        <input type="email" name="email" id="email" required>
    </div>
    <div class="form-group">
        <label for="telephone" class="form-label">Téléphone :</label>
        <input type="text" name="telephone" id="telephone" pattern="^[0-9+\s()-]{8,20}$" title="Numéro de téléphone valide" required>
    </div>
    <div class="form-group">
        <label for="mot_de_passe" class="form-label">Mot de passe :</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" required>
    </div>
    <div class="form-group">
        <label for="type" class="form-label">Type :</label>
        <select name="type" id="type" required>
            <option value="joueur">Joueur</option>
            <option value="proprietaire">Propriétaire</option>
        </select>
    </div>
       <div id="registerError" style="color:red; margin-top:10px; display:none;"></div>
    <?php if (!empty($register_error)): ?>
        <div id="phpRegisterError" style="color:red; margin-top:10px;"><?= htmlspecialchars($register_error) ?></div>
    <?php endif; ?><br>
    <button type="submit">Créer le compte</button>
      <div style="max-width:370px;margin:0 auto 20px auto;text-align:right;">
      <span>Déjà inscrit ? </span>
      <a href="login.php" style="color:#198754;font-weight:bold;text-decoration:underline;">Se connecter</a>
  </div>
  </form>


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
document.getElementById('registerForm').addEventListener('submit', function(e) {
    var nom = document.getElementById('nom').value.trim();
    var email = document.getElementById('email').value.trim();
    var tel = document.getElementById('telephone').value.trim();
    var mdp = document.getElementById('mot_de_passe').value.trim();
    var errorDiv = document.getElementById('registerError');
    var phpErrorDiv = document.getElementById('phpRegisterError');
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var telRegex = /^[0-9+\s()-]{8,20}$/;
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
    if (phpErrorDiv) phpErrorDiv.style.display = 'none';

    if (!nom || !email || !tel || !mdp) {
        errorDiv.textContent = "Veuillez remplir tous les champs.";
        errorDiv.style.display = 'block';
        e.preventDefault();
        return false;
    }
    if (!emailRegex.test(email)) {
        errorDiv.textContent = "Veuillez entrer une adresse email valide.";
        errorDiv.style.display = 'block';
        e.preventDefault();
        return false;
    }
    if (!telRegex.test(tel)) {
        errorDiv.textContent = "Veuillez entrer un numéro de téléphone valide.";
        errorDiv.style.display = 'block';
        e.preventDefault();
        return false;
    }

    // Vérification AJAX email/tel déjà utilisé
    e.preventDefault();
    fetch('verif_user.php?email=' + encodeURIComponent(email) + '&telephone=' + encodeURIComponent(tel))
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                errorDiv.textContent = "Cet email ou numéro de téléphone est déjà utilisé.";
                errorDiv.style.display = 'block';
            } else {
                // Soumettre le formulaire si tout est OK
                this.submit();
            }
        })
        .catch(() => {
            errorDiv.textContent = "Erreur lors de la vérification.";
            errorDiv.style.display = 'block';
        });
});
</script>
</body>

</html>