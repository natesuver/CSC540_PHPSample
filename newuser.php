<?php
        session_start();
        require 'functions.php';
        $creationError = '';
        if (isset($_POST['submit'])) {
            if (!isset($_POST['username']) || empty($_POST['username']))
                $creationError = 'Username not specified';
            else if (!isset($_POST['pw']) || empty($_POST['pw']))
                $creationError = 'Password not specified';
            else if (!isset($_POST['pw2']) || empty($_POST['pw2']))
                $creationError = 'Verification password not specified';
            else if ($_POST['pw']!=$_POST['pw2'])
                $creationError = 'Passwords do not match!';
            else if (userCount($_POST['username']) > 0)
                $creationError = "User '".$_POST['username']."' already exists";
            else {
                createUser($_POST['username'], $_POST['pw']);
                $_SESSION['username'] = $_POST['username'];
                header( 'Location: lobby.php' );
                }
            }
?>
<html>
<head>
    <link href="style.css" type="text/css" rel="stylesheet">
</head>
<body>

    <div class="title">
        <h1>WARZONE</h1>
    </div>
    <a href="login.php" class="homeLink">Return to Login</a>
    <form class="loginBox" role="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <label for="username">Enter a Username:</label>
        <br>
        <input id="username" name="username" class="loginInput" type="text" />
        <br>
        <label for="pw">Password:</label>
        <br>
        <input id="pw" name="pw" type="password" class="loginInput" />
        <label for="pw">Re-enter your Password:</label>
        <br>
        <input id="pw2" name="pw2" type="password" class="loginInput" />
        <br>
        <br>
        <button type="submit" name="submit" >Submit</button>
        <br>
        <span class="loginError"><?php echo $creationError; ?></span>

    </form>
</body>

</html>