<?php
    session_start();
    require 'functions.php';
    echo json_encode(getGameData($_POST['gameId']));
?>
