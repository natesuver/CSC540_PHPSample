<?php session_start();
    if (!isset($_SESSION['username']))
        header( 'Location: login.php' );
?>
<html>
<head>
    <title>Welcome to the Lobby</title>
    <link href="style.css" type="text/css" rel="stylesheet">
    <script
            src="https://code.jquery.com/jquery-1.12.4.min.js"
            integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="
            crossorigin="anonymous"></script>
    <script>

        var lobbyInterval;

        function startPolling() {
            lobbyInterval= setInterval(requestPlayers, 2000);
        }
        //suspendPolling is needed to stop polling when a request to join/cancel/create game is made, unpredictable things can happen if the application is polling while a request is in transit.
        function suspendPolling() {
            if (lobbyInterval)
                clearInterval(lobbyInterval);
        }

        function requestPlayers() {
            $.ajax( {type:"Get", url:"players.php", success: function(result){
                var players = JSON.parse(result);
                displayUsers(players);
                }
            } );
        }


        function displayUsers(players) {
            var div = document.getElementById('players');
            if (players.length===0) {
                div.innerHTML="<p>No other players are currently online</p>";
            }
            else {
                var table = "<table><tr><th>Player</th><th>Status</th><th>Action</th></tr>";
                var pendingRequest = getInvite(players, 1); //we found a pending request from you, to another user.  This will cause all the buttons in the table to disable, except the one that allows you to cancel the pending invite
                var pendingInvite = getInvite(players, 2); //another user sent you a pending request, you have the opportunity to accept or reject the invite.
                var activeGame = getInvite(players, 4); //we found an active game in progress, go right to the game.

                if (pendingInvite) {
                    var result = confirm(pendingInvite.username + " has invited you to a game.  Accept?");
                    if (result == true) {
                        joinGame(pendingInvite.gameId);
                    } else {
                        cancelGame(pendingInvite.gameId);
                    }
                }
                if (activeGame) {
                    gotoGame(activeGame.gameId);
                }

                for (var i = 0; i < players.length; i++) {
                    var player = players[i];
                    table += "<tr><td>" + player.username + "</td><td>" + getPlayStatus(player) + "</td><td>" + getPlayAction(player,pendingRequest) + "</td></tr>";
                }
                table += "</table>";
                div.innerHTML = table;
            }
        }

        function getInvite(players, inviteState) {
            //State 1: You initiated the game, and are waiting for a response from this user
            //State 2: This user is attempting to initiate a game with you, but you have not responded.
            //State 3: You are neither the primary or secondary_user on this game, they are in other game with someone else.
            //State 4: This user is actively in a game with you.
            //State 5: user is not invited to any games, and is not active in any games.
            for (var i = 0; i < players.length; i++) {
                var player = players[i];
                if (parseInt(player.inviteState)==inviteState) return player;
            }
            return undefined;
        }
        function getPlayStatus(player) {
            switch (parseInt(player.inviteState)) {
                case 1:
                    return "<span class='requestPending'><b>Waiting..</b><small>&nbsp; (game " + player.gameId + ")</small></span>";
                case 2:
                    return "<span class='requestPending'><b>This user wants to play!</b><small>&nbsp;(game " + player.gameId + ")</small></span>";
                case 3:
                    return "<span class='cannotRequest'>This user is playing someone else and is unavailable</span>";
                case 4:
                    return "<span class='newInvite'><b>You have an active game!</b><small>&nbsp;(game " + player.gameId + ")</small></span>";
                case 5:
                    return "<span class='newInvite'><b>Not Playing</b></span>";
                default:
                    return "";
            }
        }

        function getPlayAction(player,pendingRequest) {
            var disabled = "disabled";
            if (!pendingRequest) disabled = "";
            switch (parseInt(player.inviteState)) {
                case 1:
                    return ""; //I used to have a 'cancel' button here, but that was outside scope and caused a litany of other strange problems.  <button "+ disabled + "name='" + player.username + "." + player.gameId + "' onclick='cancelGame(" + player.gameId + ")' >Cancel</button>"; //this button state is never disabled
                case 2:
                    return "<button " + disabled + " name='" + player.username + "." + player.gameId + "' onclick='joinGame(" + player.gameId + ",this)' >Join</button>";
                case 4:
                    return "<button " + disabled + " name='" + player.username + "." + player.gameId + "' onclick='gotoGame(" + player.gameId + ")' >Open</button>";
                case 5:
                    return "<button " + disabled + " name='" + player.username + "." + player.gameId + "' onclick='createRequest(this)' >Invite To Game</button>";
                default:
                    return "";
            }
        }

        function createRequest(button) {
            var playerName = button.name.split(".")[0];
            button.innerText = "Creating Invitation..";
            disableAllButtons();
            suspendPolling();
            $.ajax( {type:"Post", url:"requestGame.php",data: {username:playerName}, success: function(result) {
                displayUsers(JSON.parse(result));
                startPolling();
            } } );
        }

        function disableAllButtons() {
            $("button").each(function( index ) {
                this.disabled = true;
            });
        }

        function cancelGame(gameId) {
            disableAllButtons();
            suspendPolling();
            $.ajax( {type:"Post", url:"cancelGame.php",data: {gameId: gameId} , success: function(result) {
                displayUsers(JSON.parse(result));
                startPolling();
            }} );
        }
        function joinGame(gameId,button) {
            if (button) {
                button.innerText = "Joining Game..";
                button.disabled = true;
            }
            suspendPolling();
            $.ajax( {type:"Post", url:"joinGame.php",data: {gameId: gameId}, success: function(result) {
                gotoGame(gameId);
            } } );

        }
        function gotoGame(gameId) {
            window.location.href="game.php?gameId=" + gameId;
        }
        startPolling();
    </script>
</head>
<body>
<div class="title">
    <h1>WARZONE</h1>
</div>
<a href="logout.php" class="homeLink">Log Out</a>

<div class="lobbyBox">
    <?php echo "<h4>Welcome to the lobby, ".$_SESSION['username']."!</h4><span>Available players are listed below:</span><br><br>" ?>
    <div id="players">

    </div>
</div>
    <script>requestPlayers();</script>
</body>

</html>