<?php
        require 'functions.php';
        session_start();
        $error = logoutUser();
        session_destroy();
    ?>
<html>
<head>
    <link href="style.css" type="text/css" rel="stylesheet">
</head>
<body>


    <div class="loginBox reLogin">
        Logging you out..
        <h4><span class="loginError"><?php echo $error; ?></span><br> <a href="login.php">Click here to log back in</a></h4>
    </div>

</body>
</html>