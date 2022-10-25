//board functions
//NTS I want to show some additional comments here.
//NTS I want to show that I know how to save stuff to files.
var squareWidth = 40;
var xSquareCount = 30;//20  30
var ySquareCount=10;//7  10
function getCanvas(id) { return document.getElementById(id);}
function getContext(id) { return getCanvas(id).getContext('2d');}

function clearBoard(canvas, context) {
    context.clearRect(0, 0, canvas.width, canvas.height);
}

function clearBoards(){
    clearBoard(getCanvas('me'), getContext('me'));
    clearBoard(getCanvas('opponent'), getContext('opponent'));
}

function boardX(id) {
    var canvas = getCanvas(id);
    var borderWidth = parseInt(canvas.style.borderWidth);
    return canvas.parentElement.offsetLeft + borderWidth;
}
function boardY(id) {
    var canvas = getCanvas(id);
    var borderWidth = parseInt(canvas.style.borderWidth);
    return canvas.parentElement.offsetTop + borderWidth;
}

function drawGridLines(id) {
    var context = getContext(id);
    var canvas = getCanvas(id);
    context.lineWidth=1;
    context.strokeStyle = 'black';
    context.globalAlpha = 0.5;
    var img=document.getElementById("water");
    context.drawImage(img,0,0, img.width,img.height);
    context.globalAlpha = 1;
    for (var x=squareWidth;x<canvas.width;x+=squareWidth) {
        context.beginPath();
        context.moveTo(x, 0);
        context.lineTo(x, canvas.height);
        context.stroke();
    }
    for (var y=squareWidth;y<canvas.height;y+=squareWidth) {
        context.beginPath();
        context.moveTo(0, y);
        context.lineTo(canvas.width, y);
        context.stroke();
    }
}

function on_opponent_click(ev) {
    var cell = getCell('opponent',ev);
    attack(cell.x,cell.y);

}

function attack(cellX,cellY) {
    if (gameStatus==1) {
        alert('Game has not started yet; place your boats first.');
        return;
    }
    if (gameStatus==3) {
        alert('Game is over!');
        return;
    }
    if (activePlayer.toUpperCase()!==currentPlayer.toUpperCase()) {
        alert('It is not your turn!');
        return;
    }
    $.ajax( {type:"Post", url:"attack.php",data: {"gameId":gameId,x:cellX,y:cellY}, success: function(result) {
        var status = JSON.parse(result)[0];
        logEvent("Attacked cell ("+ (cellX+1) + "," + (cellY+1) + ") => " + status.message);
        drawSquare(cellX,cellY,status.squareStatus,'opponent');
        updateGameData(status);
    } } );
}

function getGameData() {
    $.ajax( {type:"Post", url:"getGameData.php",data: {"gameId":gameId}, success: function(result) {
        var gameData = JSON.parse(result)[0];
        gameStatus = parseInt(gameData.status);
        if (gameStatus==4) { //game was forfeit.  Redirect to lobby.
            window.location.href="lobby.php";
            return;
        }
        updateGameData(gameData);
        gameStatus = parseInt(gameData.status);
        activePlayer = gameData.active_user;
        if (gameStatus==1)
            getMyBoard(); //if we are in game status 1, we are placing our ships.. we only need to update our own board.
        else if (gameStatus>=2) { //this game is active or won.  This is very "chatty" with the server, and should probably be optimized based on the
            getOpponentBoard();
            getMyBoard();
        }

    } } );
}

function updateGameData(gameData) {
    //games.status = 0=> pending invite 1=> placing boats 2=> active game in progress 3=> game over.
    gameStatus = parseInt(gameData.status);
    if (gameStatus==1) {
        document.getElementById('gameData').style.visibility = "hidden";
        document.getElementById('boats').style.visibility = "visible";
    }
    else {
        document.getElementById('gameData').style.visibility = "visible";
        document.getElementById('boats').style.visibility = "hidden";
    }

    document.getElementById('currentPlayer').innerHTML=gameData.active_user;
    document.getElementById('remainingMoves').innerHTML=gameData.remaining_moves;
    var opponent,you ;
    activePlayer = gameData.active_user;
    document.getElementById('whoseMove').innerHTML = ""; //clear this out in case the game is over
    if (gameStatus==2) { //a cheap way to find out whose turn it is.
        if (gameData.active_user.toUpperCase() == currentPlayer.toUpperCase()) {
            document.getElementById('whoseMove').style.color = "green";
            document.getElementById('whoseMove').innerHTML = "(It's your turn)"
            document.getElementById('opponent').style.cursor= 'crosshair';
        }
        else {
            document.getElementById('whoseMove').style.color = "red";
            document.getElementById('whoseMove').innerHTML = "(It's their turn)"
            document.getElementById('opponent').style.cursor= 'wait';
        }

    }

    if (gameData.primary_user.toUpperCase()==currentPlayer.toUpperCase()) {
        opponent = gameData.secondary_user + ", Invited Player";
        you = gameData.primary_user + ", Inviting Player";
    } else {
        opponent = gameData.primary_user + ", Inviting Player";
        you = gameData.secondary_user + ", Invited Player";
    }
    document.getElementById('opponentLabel').innerHTML="Enemy Territory (" + opponent + ")";
    document.getElementById('myLabel').innerHTML="Your Territory (" + you + ")";
    switch(gameStatus) {
        case 1:
            document.getElementById('gameStatus').innerHTML="Placing Boats";
            break;
        case 2:
            document.getElementById('gameStatus').innerHTML="In Progress";
            break;
        case 3:
            clearInterval(gameDataInterval);
            document.getElementById('gameStatus').innerHTML="Game Over";
            document.getElementById('winnerLabel').innerHTML="Winning Player:";
            alert(gameData.active_user + " has won the game!");
            break;
    }
}

function getMyBoard() {
    $.ajax( {type:"Post", url:"getMyBoard.php",data: {"gameId":gameId}, success: function(result) {
        var squares = JSON.parse(result);
       // logEvent(result);
        if (!squares) return;
        mySquares = squares; //keep track of all the marked squares, we can use this to determine boat overlaps if needed
        for (var i=0;i<squares.length;i++) {
            drawSquare(squares[i].x,squares[i].y,squares[i].squareStatus,'me');
        }

    } } );
}

function getOpponentBoard() {
    $.ajax( {type:"Post", url:"getOpponentBoard.php",data: {"gameId":gameId}, success: function(result) {
        var squares = JSON.parse(result);
       // logEvent(result);
        if (!squares) return;
        for (var i=0;i<squares.length;i++) {
            drawSquare(squares[i].x,squares[i].y,squares[i].squareStatus,'opponent');
        }

    } } );
}

function addBoat(coords, status) {
    //TODO: User should probably be stopped from dragging a new boat before this request returns from the server, esp when latency is bad.  This can mess up the count.
    $.ajax( {type:"Post", url:"addBoat.php",data: {"gameId":gameId, coords: JSON.stringify(coords), status:status}, success: function(result) {
        console.log(result);
        ships[selectedBoat]--;
        updateShipButtons();
        getMyBoard();
        var status = JSON.parse(result)[0];
        updateGameData(status);
    } } );
}

function logEvent(event) {
    var ele = document.getElementById('log');
    ele.innerHTML= (event + '<br>') + ele.innerHTML;
    console.log(event);
}

function drawSquare(cellX,cellY, squareStatus, boardId) {
    cellX = parseInt(cellX);
    cellY = parseInt(cellY);
    squareStatus = parseInt(squareStatus);
    var imgId = getSquareImageId(squareStatus,boardId);
    if (imgId!= undefined) {
        var img=document.getElementById(imgId);
        var context = getContext(boardId);
        var x = cellX*squareWidth; //translate cell position to relative x coordinate, upper left corner of cell
        var y = cellY*squareWidth;
        context.drawImage(img,x+1,y+1, squareWidth-2,squareWidth-2); //make the picture width slightly smaller so it doesn't cover cell lines
    }


}

function getSquareImageId(status, boardId) {
    //boards.status = 0=> empty. 1=>miss 2=>battleship 3=>cruiser 4=>destroyer 5=>sub 6=>battleshipHit 7=>cruiserHit 8=>destroyerHit 9=>subHit
    if (boardId=='opponent') {
        switch (status) {
            case 1:
                return 'miss';
            case 6:
                return 'battleshipHit';
            case 7:
                return 'cruiserHit';
            case 8:
                return 'destroyerHit';
            case 9:
                return 'subHit';
            default:
                return undefined;
        }
    } else {
        switch (status) {
            case 1:
                return 'miss';
            case 2: //fallthrough
            case 3:
            case 4:
            case 5:
                return 'myBoat';
                break;
            case 6: //fallthrough
            case 7:
            case 8:
            case 9:
                return 'hit';
                break;
            default:
                return undefined;
        }
    }
}

function pollGameStatus() {
    gameDataInterval = setInterval(getGameData,2000);
}

//ship placement functions
function updateShipButtons() {
    updateShipButton('battleship');
    updateShipButton('cruiser');
    updateShipButton('destroyer');
    updateShipButton('sub');
}
function updateShipButton(id) {
    var remaining = ships[id];
    document.getElementById(id).innerHTML=remaining;
    if (remaining<=0) {
        document.getElementById(id).style.backgroundColor="green";
        document.getElementById(id).innerHTML="OK";
        removeEventHandler(id); //disallow further drags, you are out of this type of boat!
        document.getElementById(id).draggable = false;
    }
    if (remainingBoatsToPlace() <=0) {
        document.getElementById("readyToPlay").innerHTML = "Ready to play!<br>Waiting for other player";
    }
}

function remainingBoatsToPlace() {
    return ships.battleship + ships.cruiser + ships.destroyer + ships.sub;

}
function addEventHandler(id) {
    document.getElementById(id).addEventListener('dragstart', dragStart, false);
}
function removeEventHandler(id) {
    document.getElementById(id).removeEventListener('dragstart', dragStart, false);
}
function dragStart(e) {
    //Unfortunately, none of this stuff seems to work in either IE11 or edge, and that is well documented.
    var id = e.target.id;
    var img = document.getElementById(id+"Img");
    e.dataTransfer.setDragImage(img, 0, 0);
    selectedBoat = id;
    // e.dataTransfer.setData("text", e.target.id); //data transfer not available in chrome on onDragOver, so just store in a global
}
function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text");
    var cell = getCell('me',ev);
    if (detectOverlap(cell)) { //attempted to do this on allowDrop, didn't seem to work well, very non performant.
        return;
    }
    addBoat(getBoatCoords(selectedBoat,cell), getShipStatus(selectedBoat));
}

function allowDrop(ev) {
    if (gameStatus==1 && remainingBoatsToPlace()>0) ev.preventDefault(); //disallow users from dragging during play.  gameStatus=1 means they are boat placement mode, and dragging is allowed, but only if they have remaining boats.
}

function detectOverlap(cell) {
    var shipSize = getShipSize(selectedBoat);
    var boatCoords = getBoatCells(cell, shipSize);
    if (cell.x + shipSize > xSquareCount) { //only allow user to drop of the cell they are dropping in is far enough away from the edge of the board.
        alert('Boat is too close to the edge.')
        return true;
    }
    for (var i=0;i<mySquares.length;i++) {
        for (var j=0;j<boatCoords.length;j++) {
            if (mySquares[i].x==boatCoords[j].x && mySquares[i].y==boatCoords[j].y) {
                alert('Overlapping boats!  Try again.')
                return true; //if the proposed drop location occurs on a previously occupied square, disallow the drop.
            }
        }

    }
    return false;
}

function getBoatCells(cell, shipSize) {
    var boatCoords = [];
    for (var i=0;i<shipSize;i++) {
        boatCoords.push({x:cell.x+i, y: cell.y});
    }
    return boatCoords;
}
function getCell(boardId, ev) {
    var canvas = getCanvas(boardId);
    var x = ev.clientX - canvas.offsetLeft + document.body.scrollLeft; //accomodates case when user is scrolling horizontally
    var y = ev.clientY - canvas.offsetTop + document.body.scrollTop; //accomodates case when user is scrolling vertically
    var cellX = Math.floor((x/canvas.clientWidth) * xSquareCount);
    var cellY = Math.floor((y/canvas.clientHeight) * ySquareCount);
    return {x:cellX,y:cellY};
}

//Gets the size of the given ship, in cell units.
function getShipSize(id) {
    switch (id) {
        case "battleship": return 4;
        case "cruiser": return 3;
        case "destroyer": return 2;
        case "sub": return 1;
    }
}

function getShipStatus(id) { //2=>battleship 3=>cruiser 4=>destroyer 5=>sub
    switch (id) {
        case "battleship": return 2;
        case "cruiser": return 3;
        case "destroyer": return 4;
        case "sub": return 5;
    }
}
//return an array of coordinates based on the drop position and size of the boat
function getBoatCoords(id, cell) {
    var coords =[];
    for (var i=0;i<getShipSize(id);i++) {
        coords.push({x:cell.x+i,y:cell.y});
    }
    return coords;
}