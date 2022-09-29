<?php
			session_start();
            require 'functions.php';
            $loginError = '';
            if (isset($_SESSION['username'])) {
                header( 'Location: ./lobby.php' ); //user is already logged in, go right to lobby.
            }
			if (isset($_POST['submit']) && isset($_POST['username']) && !empty($_POST['username']) && !empty($_POST['pw'])) {
			    $loginData = checkLogin($_POST['username'], $_POST['pw']);
			   // $loginError = $loginData;
			   // return;
			    if (count($loginData)==0) {
			        $loginError = 'Invalid username or password';
			    }
			    else if ($loginData[0]["loggedIn"]=="1") {
			        $loginError = 'User is already logged in';
			    }
				else {
					loginUser($_POST['username']);
                    $_SESSION['username'] = $_POST['username'];
					header( 'Location: lobby.php' );
				}
			} else {
				$loginError = '';
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
<form class="loginBox" role="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
    <label for="username">Username:</label>
    <br>
    <input id="username" name="username" class="loginInput" type="text" />
    <br>
    <label for="pw">Password:</label>
    <br>
    <input id="pw" name="pw" type="password" class="loginInput" />

    <br>
    <button type="submit" name="submit" >Login</button>
    <br>
    <span class="loginError"><?php echo $loginError; ?></span>
    <br>
    <hr>
    <br>
    <a href="newuser.php" class="newUser">Create new user</a>
</form>
</body>

</html>