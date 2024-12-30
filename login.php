<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Initialisation des messages d'erreur
$errorMessage = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars(trim($_POST['password']));

    if (empty($username) || empty($password)) {
        $errorMessage = "Tous les champs sont obligatoires.";
    } else {
        try {
            // Vérifier si l'utilisateur existe
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id']; // Stocker l'ID de l'utilisateur dans la session
                header('Location: index.php'); // Redirection vers l'accueil ou une autre page
                exit;
            } else {
                $errorMessage = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de la connexion : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Connexion</h1>

        <!-- Affichage des messages -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form action="" method="POST" class="mx-auto" style="max-width: 400px;">
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur :</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe :</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>

        <div class="text-center mt-3">
            <p>Pas encore inscrit ? <a href="enregistrement.php">Créer un compte</a></p>
        </div>
    </div>
</body>
</html>
