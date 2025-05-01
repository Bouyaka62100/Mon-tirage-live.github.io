<?php
session_start();
$mot_de_passe = "secret123";

if (isset($_POST['deconnecter'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['admin']) && (!isset($_POST['password']) || $_POST['password'] !== $mot_de_passe)) {
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Connexion Admin</title><style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f2f2f2; }
        form { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        input, button { padding: 10px; margin-bottom: 20px; width: 200px; }
        button { background: #007BFF; color: white; border: none; border-radius: 5px; }
    </style></head><body><form method="post">
        <h2>Connexion Admin</h2>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">Se connecter</button>
    </form></body></html>';
    exit;
}

$_SESSION['admin'] = true;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$gagnants = [];
$message = '';
$lockFile = 'lock.flag';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tirer'])) {
        $participants = file_exists('participants.txt') ? file('participants.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $nombre = isset($_POST['nombre_gagnants']) ? intval($_POST['nombre_gagnants']) : 1;
        $nombre = min($nombre, count($participants));
        if ($nombre > 0 && !empty($participants)) {
            $clefs = array_rand($participants, $nombre);
            if (!is_array($clefs)) $clefs = [$clefs];
            foreach ($clefs as $index) {
                $gagnants[] = $participants[$index];
            }
        } else {
            $message = "Pas assez de participants pour ce tirage.";
        }
    }

    if (isset($_POST['reset'])) {
        file_put_contents('participants.txt', '');
        file_put_contents('ips.txt', '');
        file_put_contents('reset.flag', time());
    }

    if (isset($_POST['toggle_lock'])) {
        $etat = file_exists($lockFile) && trim(file_get_contents($lockFile)) === '1' ? '0' : '1';
        file_put_contents($lockFile, $etat);
    }
}

$verrouille = file_exists($lockFile) && trim(file_get_contents($lockFile)) === '1';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tirage au sort</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; height: 100vh; overflow: hidden; }
        .video-background { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .container { max-width: 700px; margin: auto; background: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin-top: 30px; z-index: 1; position: relative; }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo img { width: 250px; }
        .btn { padding: 10px 20px; font-size: 14px; border: none; border-radius: 6px; cursor: pointer; }
        .tirer { background-color: #28a745; color: white; }
        .reset { background-color: #dc3545; color: white; }
        .toggle { background-color: #ff9900; color: white; min-width: 220px; }
        .deconnexion { position: absolute; top: 20px; right: 20px; background-color: #dc3545; color: white; padding: 10px 15px; border-radius: 6px; }
        .input-small { width: 60px; padding: 5px; }
        .participants-box { background: #f4f4f4; padding: 15px; border-radius: 8px; height: 200px; overflow-y: auto; margin-top: 20px; }
        #search { width: 100%; padding: 8px; margin-top: 10px; border-radius: 6px; border: 1px solid #ccc; }
        #timer { font-size: 30px; font-weight: bold; position: absolute; top: 20px; left: 20px; color: #fff; background-color: rgba(0, 0, 0, 0.7); padding: 10px 15px; border-radius: 6px; }
        #gagnantsMessage {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 128, 0, 0.9);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.4);
            font-size: 18px;
            text-align: center;
            z-index: 10;
        }
        #gagnantsMessage button {
            background-color: #dc3545;
            border: none;
            padding: 10px 20px;
            color: white;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        #gagnantsList {
            font-size: 36px;
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<video class="video-background" autoplay muted loop>
    <source src="video/fond.mp4" type="video/mp4">
</video>

<div class="container">
    <form method="post">
        <button class="deconnexion" type="submit" name="deconnecter" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?');">Se déconnecter</button>
    </form>

    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h2 style="text-align:center;">Interface Administrateur</h2>

    <form method="post">
        <div style="margin-bottom:10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <label>Nombre de gagnants :</label>
            <input type="number" name="nombre_gagnants" class="input-small" value="1" min="1" required>

            <div style="display: flex; gap: 10px;">
                <button class="btn tirer" type="submit" name="tirer" id="tirer_button">Tirer au sort</button>
                <button class="btn reset" type="submit" name="reset" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser les participants ?');">Réinitialiser</button>
                <button class="btn toggle" type="submit" name="toggle_lock" onclick="return confirm('Voulez-vous vraiment <?= $verrouille ? 'déverrouiller' : 'verrouiller' ?> les participations ?');"><?= $verrouille ? "Déverrouiller" : "Verrouiller" ?> les participations</button>
            </div>
        </div>
    </form>

    <?php if (!empty($message)) echo "<p style='color:red;font-weight:bold;'>$message</p>"; ?>
    <h3>Liste des participants</h3>
    <input type="text" id="search" placeholder="Rechercher un participant...">
    <div id="compteur">Chargement...</div>
    <div class="participants-box" id="participants">Chargement...</div>
</div>

<div id="timer">10 secondes</div>

<div id="gagnantsMessage">
    <h3>Les gagnants sont :</h3>
    <p id="gagnantsList"></p>
    <button onclick="closeMessage()">Fermer</button>
</div>

<script>
// Son de compte à rebours
let countdownSound = new Audio('audio/compte_a_rebours.mp3'); // Ajouter la source du son ici

let participants = [];
let filteredParticipants = [];
let countdown = 10;
let timer;
let timerRunning = false;
let winnerCount = 1;

function loadParticipants() {
    fetch('participants.txt?ts=' + new Date().getTime())
        .then(response => response.text())
        .then(data => {
            participants = data.trim().split('\n').filter(Boolean);
            const searchTerm = document.getElementById('search').value.toLowerCase();
            filteredParticipants = participants.filter(p => p.toLowerCase().includes(searchTerm));
            updateParticipantsList();
        });
}

function updateParticipantsList() {
    const html = filteredParticipants.map(p => `👤 ${p}`).join('<br>');
    document.getElementById('participants').innerHTML = html || "Aucun participant.";
    document.getElementById('compteur').innerText = "Nombre de participants : " + filteredParticipants.length;
}

document.getElementById('search').addEventListener('input', function () {
    const terme = this.value.toLowerCase();
    filteredParticipants = participants.filter(line => line.toLowerCase().includes(terme));
    updateParticipantsList();
});

setInterval(loadParticipants, 3000);
window.onload = loadParticipants;

document.getElementById('tirer_button').addEventListener('click', function(event) {
    event.preventDefault();
    
    winnerCount = parseInt(document.querySelector('[name="nombre_gagnants"]').value) || 1;

    if (!timerRunning) {
        timerRunning = true;
        countdown = 10;
        document.getElementById('timer').innerText = countdown + " secondes";
        
        // Joue le son au début du décompte
        countdownSound.play();  // Joue le son de compte à rebours

        timer = setInterval(function() {
            if (countdown <= 0) {
                clearInterval(timer);
                timerRunning = false;
                document.getElementById('timer').style.display = 'none';  // Cache le chronomètre
                const winners = [];
                const winnerIndexes = [];
                for (let i = 0; i < winnerCount; i++) {
                    let index;
                    do {
                        index = Math.floor(Math.random() * filteredParticipants.length);
                    } while (winnerIndexes.includes(index));
                    winnerIndexes.push(index);
                    winners.push(filteredParticipants[index]);
                }
                document.getElementById('gagnantsList').innerHTML = winners.sort().join("<br>");
                document.getElementById('gagnantsMessage').style.display = 'block';
                return;
            }
            countdown--;
            document.getElementById('timer').innerText = countdown + " secondes";
        }, 1000);
    }
});

function closeMessage() {
    // Cacher le message des gagnants
    document.getElementById('gagnantsMessage').style.display = 'none';
    
    // Réafficher le chronomètre
    document.getElementById('timer').style.display = 'block';
    
    // Réinitialiser le chronomètre à 10 secondes
    countdown = 10;
    document.getElementById('timer').innerText = countdown + " secondes";
}

</script>

</body>
</html>
