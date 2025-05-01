<?php
$file = "participants.txt";
$ip_file = "ips.txt"; // Fichier pour enregistrer les IPs
$message = "";

// Récupérer l'adresse IP de l'utilisateur
$user_ip = $_SERVER['REMOTE_ADDR'];

// Vérifier si l'IP a déjà participé
$used_ips = file($ip_file, FILE_IGNORE_NEW_LINES);

if (in_array($user_ip, $used_ips)) {
    $message = "Une seule participation par IP est autorisée, vous avez déjà participé.";
} else {
    // Récupérer le pseudo
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pseudo'])) {
        $pseudo = $_POST['pseudo'];  // Récupère le pseudo envoyé par le formulaire

        // Ajouter le pseudo au fichier participants.txt
        file_put_contents($file, $pseudo . PHP_EOL, FILE_APPEND);

        // Ajouter l'IP au fichier ips.txt
        file_put_contents($ip_file, $user_ip . PHP_EOL, FILE_APPEND);

        $message = "Merci, vous êtes inscrit au tirage avec le pseudo : $pseudo";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tirage au Sort</title>
    <style>
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Participer au Tirage au So
