<?php
// Activer les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Créer les fichiers si nécessaire
if (!file_exists('participants.txt')) file_put_contents('participants.txt', '');
if (!file_exists('ips.txt')) file_put_contents('ips.txt', '');
if (!file_exists('reset.flag')) file_put_contents('reset.flag', time());
if (!file_exists('lock.flag')) file_put_contents('lock.flag', '0'); // Par défaut, participations déverrouillées

$message = '';
$user_ip = $_SERVER['REMOTE_ADDR'];
$used_ips = file('ips.txt', FILE_IGNORE_NEW_LINES);

// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pseudo'])) {
    if (in_array($user_ip, $used_ips)) {
        $message = "<span style='color: red;'>Une seule participation par IP est autorisée, vous avez déjà participé.</span>";
    } else {
        $pseudo = trim($_POST['pseudo']);
        if ($pseudo !== '') {
            file_put_contents('participants.txt', $pseudo . PHP_EOL, FILE_APPEND);
            file_put_contents('ips.txt', $user_ip . PHP_EOL, FILE_APPEND);
            $message = "Merci, vous êtes inscrit au tirage avec le pseudo : $pseudo";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tirage au sort - Participation</title>
    <style>
        /* Vidéo en arrière-plan */
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef2f7;
            padding: 20px;
            text-align: center;
            margin: 0;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px; /* Espace entre le logo et le formulaire */
        }

        #logo {
            width: 300px; /* Ajuste la taille du logo selon tes préférences */
            height: auto;
        }

        .form-box {
            background: rgba(255, 255, 255, 0.8); /* Fond semi-transparent pour le formulaire */
            padding: 30px;
            max-width: 400px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        input[type="text"] {
            padding: 10px;
            width: 80%;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 20px;
            font-weight: bold;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .message {
            margin-top: 20px;
            font-weight: bold;
            color: green; /* Remplace 'green' par la couleur que tu préfères */
        }

        #compteur {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }

        .lock-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-top: 20px;
            font-size: 16px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<!-- Vidéo en arrière-plan -->
<video id="background-video" autoplay loop muted>
    <source src="video/fond.mp4" type="video/mp4">
    Votre navigateur ne supporte pas la vidéo.
</video>

<!-- Logo -->
<div class="logo-container">
    <img src="images/logo.png" alt="Logo" id="logo">
</div>

<div class="form-box">
    <h2>Participer au tirage au sort</h2>
    <form method="post">
        <input type="text" name="pseudo" placeholder="Entrez votre pseudo" required id="pseudoInput">
        <br>
        <button type="submit">Participer</button>
    </form>

    <div class="message" id="message"><?= $message ?></div>
    <div id="compteur">Chargement...</div>
    
    <!-- Message de verrouillage -->
    <div id="lockMessage" class="lock-message" style="display: none;">
        Les participations sont actuellement verrouillées. Merci de revenir plus tard.
    </div>
</div>

<script>
// Charger le nombre de participants
function chargerCompteur() {
    fetch('participants.txt?ts=' + new Date().getTime())
        .then(res => res.text())
        .then(data => {
            const count = data.trim().split('\n').filter(Boolean).length;
            document.getElementById('compteur').innerText = `Nombre de participants : ${count}`;
        });
}

// Vérifie le reset
let lastReset = null;
function loadResetFlag() {
    fetch('reset.flag?ts=' + new Date().getTime())
        .then(res => res.text())
        .then(flag => {
            if (lastReset === null) lastReset = flag;
            else if (flag !== lastReset) {
                lastReset = flag;
                document.getElementById('message').innerText = '';
            }
        });
}

// Vérifie si les participations sont verrouillées ou non
let lastLockStatus = null;
function checkLockStatus() {
    fetch('lock.flag?ts=' + new Date().getTime())
        .then(res => res.text())
        .then(flag => {
            const locked = flag.trim() === '1';
            if (lastLockStatus === null) {
                lastLockStatus = locked;
            } else if (locked !== lastLockStatus) {
                // Si l'état a changé, rafraîchir la page immédiatement
                location.reload();
            }

            // Désactive le champ de saisie si les participations sont verrouillées
            document.getElementById('pseudoInput').disabled = locked;
            
            // Affiche ou masque le message de verrouillage
            document.getElementById('lockMessage').style.display = locked ? 'block' : 'none';
        });
}

setInterval(() => {
    chargerCompteur();
    loadResetFlag();
    checkLockStatus();
}, 3000);

window.onload = () => {
    chargerCompteur();
    loadResetFlag();
    checkLockStatus();
};
</script>

</body>
</html>
