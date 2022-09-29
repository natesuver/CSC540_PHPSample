<?php
    session_start();
    require 'functions.php';
    json_encode(joinGame($_POST['gameId']));
?>
