<?php
    session_start();
    require 'functions.php';
    echo json_encode(cancelGame($_POST['gameId']));
?>
