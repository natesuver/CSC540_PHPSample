<?php
    session_start();
    require 'functions.php';
    echo json_encode(getOpponentBoard($_POST['gameId']));
?>
