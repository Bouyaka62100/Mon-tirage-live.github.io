<?php
$file = "participants.txt";

// Lire les participants depuis le fichier
$participants = file($file, FILE_IGNORE_NEW_LINES);

// Afficher la liste des participants
foreach ($participants as $participant) {
    echo "<li>$participant</li>";
}
?>
