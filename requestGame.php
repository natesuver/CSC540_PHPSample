<?php
    session_start();
    require 'functions.php';
    echo json_encode(requestGame($_POST['username']));
?>
