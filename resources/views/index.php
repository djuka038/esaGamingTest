<html>

<head>
    <!-- w3js -->
    <script src="https://www.w3schools.com/lib/w3.js"></script>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous" />
</head>

<body>
    <div class="container fluid">
        <hr>
        <div>
            <label for="name">Army name</label>
            <input type="text" id="name" name="name"><br><br>
            <label for="units">Units</label>
            <input type="number" id="units" name="units"><br><br>
            <label for="units">Strategy</label>
            <select id="strategy" name="strategy">
                <option value="strongest">Strongest</option>
                <option value="weakest">Weakest</option>
                <option value="random">Random</option>
            </select>
        </div>
        <button class="btn btn-primary" onclick="addArmy()">Add army</button>
        <table id="armies">
            <thead>
                <tr>
                    <th scope="col">Army id</th>
                    <th scope="col">Army name</th>
                    <th scope="col">Strategy</th>
                </tr>
            </thead>
            <tbody>
                <tr w3-repeat="armies">
                    <th>{{ id }}</th>
                    <td>{{ name }}</td>
                    <td>{{ strategy }}</td>
                </tr>
            </tbody>
        </table>
        <hr>
        <button class="btn btn-primary" onclick="addGame()">Add game</button>
        <table id="games">
            <thead>
                <tr>
                    <th scope="col">Game id</th>
                </tr>
            </thead>
            <tbody>
                <tr w3-repeat="games">
                    <th>{{ id }}</th>
                </tr>
            </tbody>
        </table>
        <hr>
        <div>
            <label for="game_id">Game id</label>
            <input type="number" id="game_id" game_id="game_id"><br><br>
            <label for="participant_id">Participant id</label>
            <input type="number" id="participant_id" name="participant_id"><br><br>
        </div>
        <button class="btn btn-primary" onclick="addParticipant()">Add participant</button>
        <table id="participants">
            <thead>
                <tr>
                    <th scope="col">Game id</th>
                    <th scope="col">Participant id</th>
                </tr>
            </thead>
            <tbody>
                <tr w3-repeat="participants">
                    <th>{{ game_id }}</th>
                    <td>{{ participant_id }}</td>
                </tr>
            </tbody>
        </table>
        <hr>
        <button class="btn btn-primary" onclick="run()">
            RUN
        </button>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="autoRun" onclick="stopAutoRun()">
            <label class="form-check-label" for="autoRun">AutoRun</label>
        </div>
        <table id="gameStats">
            <thead>
                <tr>
                    <th scope="col">Game id</th>
                    <th scope="col">Army name</th>
                    <th scope="col">Units alive</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr w3-repeat="gameStats">
                    <th>{{ id }}</th>
                    <td>{{ name }}</td>
                    <td>{{ units }}</td>
                    <td>{{ status }}</td>
                </tr>
            </tbody>
        </table>
        <hr>
        <br>
    </div>
</body>

</html>
<script>
    "use strict";

    var httpRequestStats;
    var httpRequestArmies
    var httpRequestGames;
    var httpRequestParticipants;
    var httpRequest;
    var repeatId;

    window.onload = function() {
        onLoad();
    };

    function onLoad() {
        getArmies();
        getGames();
        getParticipants();
        getGameStats();
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function addArmy() {
        var data = new FormData();
        data.append('name', document.getElementById('name').value);
        data.append('units', document.getElementById('units').value);
        data.append('strategy', document.getElementById('strategy').value);

        var httpRequestArmies = new XMLHttpRequest();
        httpRequestArmies.open('POST', '/v1/addArmy', true);
        httpRequestArmies.onreadystatechange = getArmies;
        httpRequestArmies.send(data);
    }

    function getArmies() {
        httpRequestArmies = new XMLHttpRequest();
        httpRequestArmies.onreadystatechange = renderArmies;
        httpRequestArmies.open("GET", "/v1/armies");
        httpRequestArmies.send();
    }

    function renderArmies() {
        if (httpRequestArmies.readyState === XMLHttpRequest.DONE) {
            if (httpRequestArmies.status === 200) {
                w3.displayObject("armies", {
                    armies: JSON.parse(httpRequestArmies.responseText)
                });
            } else {
                alert("There was a problem with the request.");
            }
        }
    }

    function addGame() {
        var httpRequestGames = new XMLHttpRequest();
        httpRequestGames.open('POST', '/v1/createGame', true);
        httpRequestGames.onreadystatechange = getGames;
        httpRequestGames.send();
    }

    function getGames() {
        httpRequestGames = new XMLHttpRequest();
        httpRequestGames.onreadystatechange = renderGames;
        httpRequestGames.open("GET", "/v1/games");
        httpRequestGames.send();
    }

    function renderGames() {
        if (httpRequestGames.readyState === XMLHttpRequest.DONE) {
            if (httpRequestGames.status === 200) {
                w3.displayObject("games", {
                    games: JSON.parse(httpRequestGames.responseText)
                });
            } else {
                alert("There was a problem with the request.");
            }
        }
    }

    function addParticipant() {
        var data = new FormData();
        data.append('game_id', document.getElementById('game_id').value);
        data.append('participant_id', document.getElementById('participant_id').value);

        var httpRequestParticipants = new XMLHttpRequest();
        httpRequestParticipants.open('POST', '/v1/addParticipant', true);
        httpRequestParticipants.onreadystatechange = getParticipants;
        httpRequestParticipants.send(data);
    }

    function getParticipants() {
        httpRequestParticipants = new XMLHttpRequest();
        httpRequestParticipants.onreadystatechange = renderGameParticipants;
        httpRequestParticipants.open("GET", "/v1/gameParticipants");
        httpRequestParticipants.send();
    }

    function renderGameParticipants() {
        if (httpRequestParticipants.readyState === XMLHttpRequest.DONE) {
            if (httpRequestParticipants.status === 200) {
                w3.displayObject("participants", {
                    participants: JSON.parse(httpRequestParticipants.responseText)
                });
            } else {
                alert("There was a problem with the request.");
            }
        }
    }

    function getGameStats() {
        httpRequestStats = new XMLHttpRequest();
        httpRequestStats.onreadystatechange = renderGameStats;
        httpRequestStats.open("GET", "/v1/gameStats");
        httpRequestStats.send();
    }

    function renderGameStats() {
        if (httpRequestStats.readyState === XMLHttpRequest.DONE) {
            if (httpRequestStats.status === 200) {
                w3.displayObject("gameStats", {
                    gameStats: JSON.parse(httpRequestStats.responseText)
                });
            } else {
                alert("There was a problem with the request.");
            }
        }
    }

    function run() {
        nextTurn();
    }

    function nextTurn() {
        httpRequest = new XMLHttpRequest();
        httpRequest.onreadystatechange = renderNextTurn;
        httpRequest.open("GET", "/v1/run");
        httpRequest.send();
    }

    function renderNextTurn() {
        if (httpRequest.readyState === XMLHttpRequest.DONE) {
            if (httpRequest.status === 200) {
                var autoRunCheckbox = document.getElementById("autoRun");
                getGameStats();
                if (autoRunCheckbox.checked == true) {
                    run();
                }
            } else {
                alert("There was a problem with the request.");
            }
        }
    }
</script>