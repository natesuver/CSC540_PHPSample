<?php session_start();
            if (!isset($_SESSION['username']))
                header( 'Location: login.php' );
?>
<html>
    <head>
        <title>Welcome to Warzone!</title>
        <link href="style.css" type="text/css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
        <script src="game.js" ></script>
        <script>
            var gameId = window.location.href.substr(window.location.href.lastIndexOf("=")+1);
        </script>
    </head>
    <body>

        <span class="homeLink">Logged in as <?php echo "<span id='currentUser'>".$_SESSION['username']."</span>" ?>&nbsp;&nbsp;<a href="logout.php">Log Out</a></span>

        <div id="gameData" class="panel">
            <label id="winnerLabel">Current Player: </label><b id="currentPlayer"></b>
            <br>
            <label>Remaining Moves: </label><b id="remainingMoves"></b>
            <br>
            <label>Game Status: </label><b id="gameStatus"></b>
            <br>
            <b id="whoseMove"></b>
            <div class="status" id="log"></div>
        </div>
        <div id="boats" class="panel">
            <h4>Click and drag to <br>place your boats</h4>
            <h4 id="readyToPlay"></h4>
            <table>
                <tr>
                    <td>Battleships</td>
                    <td><div class="shipYard" draggable="true" id="battleship" onclick="addBattleship();">x</div></td>
                </tr>
                <tr>
                    <td>Cruisers</td>
                        <td><div class="shipYard" draggable="true" id="cruiser" onclick="addCruiser();">x</div></td>
                </tr>
                <tr>
                    <td>Destroyers</td>
                    <td><div class="shipYard" draggable="true" id="destroyer" onclick="addDestroyer();">x</div></td>
                </tr>
                <tr>
                    <td>Subs</td>
                    <td><div class="shipYard" draggable="true" id="sub" onclick="addSub();">x</div></td>
                </tr>
            </table>
        </div>
        <h4 id="myLabel">Me</h4>
        <canvas height="400" width="1200" id="me" class="board" ondrop="drop(event)" ondragover="allowDrop(event)"></canvas> <!-- height="400" width="1200" -->
        <br>
        <h4 id="opponentLabel">Opponent</h4>
        <canvas height="400" width="1200" id="opponent" class="board"></canvas> <!-- 280,800 height="400" width="1200" -->
        <br>
        <img class="gameImage" id="water" height="400" width="1200" src="img/water.png">
        <img class="gameImage" id="hit" width="20" height="auto" src="img/explosion_small_ship.png">
        <img class="gameImage" id="battleshipHit" width="20" height="auto" src="img/hit_B_small.png">
        <img class="gameImage" id="cruiserHit" width="20" height="auto" src="img/hit_C_small.png">
        <img class="gameImage" id="destroyerHit" width="20" height="auto" src="img/hit_D_small.png">
        <img class="gameImage" id="subHit" width="20" height="auto" src="img/hit_S_small.png">
        <img class="gameImage" id="miss" width="20" height="auto" src="img/green.png">
        <img class="gameImage" id="myBoat" width="20" height="auto" src="img/sub_h_small.png">

        <img class="gameImage" id="battleshipImg" width="20" height="auto" src="img/battleship.png">
        <img class="gameImage" id="destroyerImg" width="20" height="auto" src="img/destroyer.png">
        <img class="gameImage" id="cruiserImg" width="20" height="auto" src="img/cruiser.png">
        <img class="gameImage" id="subImg" width="20" height="auto" src="img/sub.png">


        <script>
            var activePlayer, gameDataInterval, gameStatus, selectedBoat, mySquares, currentPlayer;
            var ships = {battleship:1, cruiser: 2, destroyer: 3, sub: 4}; //TODO: possibly add this to persistant storage, right now, it's possible to simply refresh the page to get more boats.
            document.getElementById('gameData').style.visibility = "hidden";
            document.getElementById('boats').style.visibility = "hidden";
            window.onload = function() { //only perform this setup after all images are loaded
                drawGridLines('me');
                drawGridLines('opponent');
                getCanvas('opponent').addEventListener('click', on_opponent_click, false);
                getMyBoard();
                getOpponentBoard();
                getGameData();
                pollGameStatus();
                addEventHandler('battleship','battleship_h_small.png');
                addEventHandler('cruiser','cruiser_h_small.png');
                addEventHandler('destroyer','destroyer_h_small.png');
                addEventHandler('sub','sub_h_small.png');
                currentPlayer = document.getElementById('currentUser').innerText;
                updateShipButtons();
            };

        </script>
    </body>

</html>