<?php
    session_start();
    require 'functions.php';
    echo json_encode(addBoat($_POST['gameId'], json_decode($_POST['coords'],true),$_POST['status']));
?>
