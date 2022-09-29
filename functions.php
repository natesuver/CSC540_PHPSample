<?php
    function getConnection() {
		//$conn = new mysqli("localhost", "root","B00mB00m","warzone");
		$conn = new mysqli("localhost", "id230181_cartesian_user","fertgut","id230181_cartesian");
		if ($conn->connect_errno) {
            printf("Connection failed: %s\n", $conn->connect_error);
            exit();
            }
        return $conn;
    }

function execSingleResult($sql) {
    $conn = getConnection();
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Database Error [{$conn->errno}] {$conn->error}");
    }
    $data = $result->fetch_assoc();
    $conn->close();
    return $data;
}

function execResults($sql) {
    $conn = getConnection();
    $data = array();
    $result = $conn->query($sql);

    while($row =$result->fetch_assoc()) {
    array_push($data, $row);
    }
    $conn->close();
    return $data;
}

function execNoResult($sql) {
    $conn = getConnection();
    $result = $conn->query($sql);
    if ($conn->error) {
    throw new Exception("Database Error [{$conn->errno}] {$conn->error}");
    }
    $conn->close();
    return;
}
//User Management
function checkLogin($username, $pw) {
    //will return 0 rows if no valid login/pw found, 1 row with a single column showing if they are logged in
    $sql = "Select loggedIn from users where username='".$username."' and password='".$pw."'";
    return execResults($sql);
}

function userCount($username) {
    $sql = "Select count(*) as cnt from users where username='".$username."'";
    return (int) execSingleResult($sql)["cnt"];
}

function createUser($username, $pw) {
    $sql = "insert into users (username, password, loggedIn) VALUES ('".$username."','".$pw."',1)";
    execNoResult($sql);
}
function loginUser($username) {
    $sql = "update users set loggedIn = 1 WHERE username= '".$username."'";
    execNoResult($sql);
}
function logoutUser() {
    if ($_SESSION['username']) { //TODO: swap with isset.
        $sql = "update users set loggedIn = 0 WHERE username= '".$_SESSION['username']."'";
        execNoResult($sql);
        $sql = "update games set status = 4 WHERE primary_user= '".$_SESSION['username']."' OR secondary_user= '".$_SESSION['username']."'"; //mark any open games as "forfeit" that involve you.
        execNoResult($sql);
        $_SESSION['username'] = "";
        header('Refresh: 1; URL = loggedout.php');
    }
    else {
        return "No login found!";
    }

}

function getPlayers() {
    //games.status = 0=> pending invite 1=> placing boats 2=> active game in progress 3=> game over. 4=>forfeit
    //State 1: You initiated the game, and are waiting for a response from this user
    //State 2: This user is attempting to initiate a game with you, but you have not responded.
    //State 3: You are neither the primary or secondary_user on this game, they are in other game with someone else.
    //State 4: This user is actively in a game with you.
    //State 5: user is not invited to any games, and is not active in any games.



    $username = $_SESSION['username'];
    $sql = "Select username as username,
    CASE
    WHEN games.id is not null AND games.status = 0 AND games.primary_user = '".$username."' THEN 1
    WHEN games.id is not null AND games.status = 0 AND games.secondary_user = '".$username."' THEN 2
    WHEN games.id is not null AND games.secondary_user <> '".$username."' AND games.primary_user <> '".$username."' THEN 3
    WHEN games.id is not null AND games.status IN (1,2) AND (games.secondary_user = '".$username."' OR games.primary_user = '".$username."') THEN 4
    ELSE 5
    END as inviteState,
    COALESCE(games.id, 0) as gameId
    from users
    left outer join games on
        games.status < 3 AND
        (games.primary_user = username OR games.secondary_user = username)
    where loggedIn = 1 and username <> '".$username."' ORDER BY username";
    return execResults($sql);
}

function requestGame($requestedPlayer) {

    $boardXCount = 30;
    $boardYCount = 10;
    $myUser = $_SESSION['username'];
    if (userCurrentGameCount($myUser) > 0) {
        return getPlayers(); //user is already involved in another game.. this is a failsafe to prevent duplicate inserts.
    }
    $sql = "INSERT INTO games (active_user, primary_user, secondary_user, status, remaining_moves) VALUES ('".$myUser."','".$myUser."','".$requestedPlayer."',0,3);";
    execNoResult($sql);
    $gameId = execSingleResult("SELECT max(id) as id from games;","id")["id"]; //LAST_INSERT_ID() isn't working for some reason.
    setupBoard($myUser, $gameId,$boardXCount,$boardYCount);
    setupBoard($requestedPlayer, $gameId,$boardXCount,$boardYCount);
    return getPlayers();
}

function setupBoard($user,$gameId, $boardXCount, $boardYCount) {
    for ($x=0;$x<$boardXCount;$x++) {
        for ($y=0;$y<$boardYCount;$y++) {
            $sql = "INSERT INTO boards(game_id,username,x,y,status) VALUES (".$gameId.",'".$user."',".$x.",".$y.",0);";
            execNoResult($sql);
        }
    }
}

function cancelGame($gameId) {
    updateGameStatus($gameId,4);
   // $sql = "DELETE FROM games WHERE id = ".$gameId;
  //  execNoResult($sql);
  //  $sql = "DELETE FROM boards where game_id = ".$gameId;
  //  execNoResult($sql);
    return getPlayers();
}

function joinGame($gameId) {
    //games.status = 0=> pending invite 1=> placing boats 2=> active game in progress 3=> game over 4=>forfeit
    $sql = "UPDATE games SET status = 1 WHERE id = ".$gameId;
    execNoResult($sql);
    return getPlayers();
}

function updateGameStatus($gameId, $status) {
    //games.status = 0=> pending invite 1=> placing boats 2=> active game in progress 3=> game over 4=>forfeit
    $sql = "UPDATE games SET status = $status WHERE id=".$gameId.";";
    execNoResult($sql);
}



function getOpponentBoard($gameId) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    //the opponents board should only show you hits and misses, so purposely exclude any boat related squares that haven't already been hit.  We also don't care about empty squares
    $opponent = getOpposingPlayer($gameId);
    $sql = "Select status as squareStatus,x,y from boards where game_id =".$gameId." and username='".$opponent."' and status IN (1,6,7,8,9);"; //get the coordinates of only misses and hits for your opponent, do not expose boat locations.
    return execResults($sql);
}

function getMyBoard($gameId) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    //for my board show me everything, except for empty cells.
    $myUser = $_SESSION['username'];
    $sql = "Select status as squareStatus,x,y from boards where game_id =".$gameId." and username='".$myUser."' and status >0;"; //get the coordinates of boats, misses and hits for me
    return execResults($sql);
}

function attack($gameId, $x, $y) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    $opponent = getOpposingPlayer($gameId);
    $currentActivePlayer = getActivePlayer($gameId);
    $message="";

    $squareStatus = getSquareStatus($gameId, $opponent, $x, $y);
    if ($squareStatus==0) {
        $squareStatus = 1; //miss
        $message = "Miss!";
    } else if ($squareStatus>=2 && $squareStatus<=5 ) {
        $squareStatus = $squareStatus+4; //hit, incrementing the status by 4 "promotes" the square to the corresponding hit.
        $message = "Hit!";
    } else {
        $gameData = getGameData($gameId);
        $gameData[0]["message"] = "Already Attacked Here!";
        $gameData[0]["squareStatus"] = $squareStatus;
        return $gameData;
    }

    updateSquareStatus($gameId, $opponent, $x, $y, $squareStatus);
    $boatSquares = remainingBoatSquares($opponent,$gameId);
    if ($boatSquares==0) {
        updateGameStatus($gameId,3);
    }
    else {
        $remaining_moves = decrementMoves($gameId);
        if ($remaining_moves==0) {
            $currentActivePlayer = swapPlayers($gameId,$opponent,$currentActivePlayer);
            $remaining_moves = 3;
        }
    }
    $gameData = getGameData($gameId);
    $gameData[0]["message"] = $message;
    $gameData[0]["squareStatus"] = $squareStatus;
    return $gameData;
}

function remainingBoatSquares($opponent,$gameId) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    //we are considered a winner when your opponent has no more boat squares left (count will be zero)
    $sql = "Select count(*) as cnt from boards where game_id =".$gameId." and username='".$opponent."' and status IN (2,3,4,5);";
    return (int) execSingleResult($sql)["cnt"];

}


function addBoat($gameId, $coords, $status) { //$status contains the type of boat being created, 2=>battleship 3=>cruiser 4=>destroyer 5=>sub
    $myUser = $_SESSION['username'];
    for ($i=0;$i<count($coords);$i++) {
        updateSquareStatus($gameId,$myUser,$coords[$i]['x'],$coords[$i]['y'] ,$status);
    }
    $remaining = boatSquaresRemaining($gameId);
    if ($remaining <=0) {
        updateGameStatus($gameId,2);
    }
    return getGameData($gameId);
}

function getSquareStatus($gameId, $opponent, $x, $y) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    //Get the status of one of your opponents squares
    $sql = "Select status from boards where game_id=".$gameId." AND username='".$opponent."' AND x=".$x." AND y=".$y.";";
    $result = execResults($sql);
    return $result[0]["status"];
}

function updateSquareStatus($gameId, $user, $x, $y, $status) {
    $sql = "update boards set status=".$status." where game_id=".$gameId." AND username='".$user."' AND x=".$x." AND y=".$y.";";
    $result = execNoResult($sql);
}


function getOpposingPlayer($gameId) {
    $myUser = $_SESSION['username'];
    $sql = "Select primary_user, secondary_user from games where id=".$gameId.";";
    $result = execResults($sql);
   // if ($result[0]["primary_user"]!=$myUser) return $result[0]["primary_user"];
    if (strcasecmp($result[0]["primary_user"],$myUser)!=0) return $result[0]["primary_user"];
    return $result[0]["secondary_user"];
}

function getActivePlayer($gameId) {
    $myUser = $_SESSION['username'];
    $sql = "Select active_user from games where id=".$gameId.";";
    $result = execResults($sql);
    return $result[0]["active_user"];
}

function getGameData($gameId) {
    $sql = "Select active_user, remaining_moves, status, primary_user, secondary_user from games where id=".$gameId.";";
    return execResults($sql);
}

function userCurrentGameCount($username) {
    $sql = "Select count(*) as cnt from games where status IN (0,1,2) AND (active_user='".$username."' or secondary_user='".$username."')";
    return (int) execSingleResult($sql)["cnt"];
}

function boatSquaresRemaining($gameId) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    $totalPossibleBoatSquares = 40; //the sum of all boat squares from both players, when the boards are full.
    $sql = "Select count(*) as cnt from boards where game_id=".$gameId." AND status IN (2,3,4,5);";
    $result = (int) execSingleResult($sql)["cnt"];
    return ($totalPossibleBoatSquares-$result);

}

function swapPlayers($gameId,$opponent, $currentActivePlayer) {
    $myUser = $_SESSION['username'];
    if (strcasecmp($currentActivePlayer,$myUser)==0)
        $newPlayer = $opponent;
    else
        $newPlayer = $myUser;
    $sql = "update games set active_user='".$newPlayer."', remaining_moves=3 where id=".$gameId.";";
    $result = execNoResult($sql);
    return $newPlayer;
}
function decrementMoves($gameId) {
    $myUser = $_SESSION['username'];
    $sql = "update games set remaining_moves=remaining_moves-1 where id=".$gameId.";";
    $result = execNoResult($sql);
    $sql = "select remaining_moves from games where id=".$gameId.";";
    $result = execResults($sql);
    return (int) $result[0]["remaining_moves"];
}


?>