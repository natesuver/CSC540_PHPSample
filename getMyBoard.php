<?php
    session_start();
    require 'functions.php';
    echo json_encode(getMyBoard($_POST['gameId']));
?>
