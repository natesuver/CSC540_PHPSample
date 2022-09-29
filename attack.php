<?php
    session_start();
    require 'functions.php';
    echo json_encode(attack($_POST['gameId'],$_POST['x'],$_POST['y']));
?>
