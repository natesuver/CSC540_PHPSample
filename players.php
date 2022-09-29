<?php
    session_start();
    require 'functions.php';
    $players = getPlayers();
    echo json_encode($players);
?>
