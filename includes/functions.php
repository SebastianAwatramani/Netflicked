
<?php 
function addMovieToDB($DBConnect, $movieInfo)
{
    $poster_path = getAndSavePoster($DBConnect, $movieInfo);
    $movieID = mysqli_real_escape_string($DBConnect, $movieInfo["movieID"]);
    $title = mysqli_real_escape_string($DBConnect, $movieInfo["title"]);
    $overview = mysqli_real_escape_string($DBConnect, $movieInfo["overview"]);
    $tagline = mysqli_real_escape_string($DBConnect, $movieInfo["tagline"]);
    $vote_average = mysqli_real_escape_string($DBConnect, $movieInfo["vote_average"]);

    if ($resultSet = mysqli_query($DBConnect, "SELECT movieID FROM movies WHERE movieID = '$movieID'")) {
        $row = mysqli_fetch_assoc($resultSet);
        if(empty($row['movieID'])) {
            $tableName = "movies";
            $SQLstring = "  INSERT INTO $tableName
                            (movieID, title, overview, tagline, vote_average, poster_path) 
                            VALUES
                            ('$movieID', '$title', '$overview', '$tagline', '$vote_average', '$poster_path')";
            $QueryResult = mysqli_query($DBConnect, $SQLstring);
            if ($QueryResult === FALSE) {
                echo "<p>Unable to execute the query.</p>" . "<p>Error code " . mysqli_errno($DBConnect) . ": " . mysqli_error($DBConnect) . "</p>";
            }
        }
        $resultSet = mysqli_query($DBConnect, "SELECT movieID FROM movies WHERE movieID = '$movieID'");
        $row = mysqli_fetch_assoc($resultSet);
        if (!empty($row['movieID'])) {
            $tableName = "linkingtable";
            $userID = $_SESSION["userID"];
            $SQLstring = "INSERT INTO $tableName (userID, movieID) VALUES ('$userID', '$movieID')";
            $QueryResult = mysqli_query($DBConnect, $SQLstring);
            if ($QueryResult === FALSE) {
                echo "<p>Unable to execute the query.</p>" . "<p>Error code " . mysqli_errno($DBConnect) . ": " . mysqli_error($DBConnect) . "</p>";
            }
        }
    }
}

function createAccount($DBConnect, $userName, $password)
{
    $userName = mysqli_real_escape_string($DBConnect, $userName);
    $tableName = "users";
    $SQLstring = "SELECT userName FROM users where userName = '$userName'";
    if ($resultSet = mysqli_query($DBConnect, $SQLstring)) {
        if(mysqli_num_rows($resultSet) == 0) {
            $SQLstring = "INSERT INTO $tableName (userName, passwordHash) VALUES ('$userName', '$password')";

            $QueryResult = mysqli_query($DBConnect, $SQLstring);
            if ($QueryResult === true) {
                echo "Account " . $userName . " successfully created.  Please <a href=\"index.php?login\">Login</a>";
            }
        } else {
            echo "Username is not available.  Please try again\n";
            //Call function to print form
        }
    } else {
        echo "There was an error processing your request.  Please try again later\n";
        echo "<p>Error code " . mysqli_errno($DBConnect) . ": " . mysqli_error($DBConnect) . "</p>";
    }
}

function getAndSavePoster($DBConnect, $movieInfo)
{
    if(isset($movieInfo["poster_path"])) {
        $poster_path = mysqli_real_escape_string($DBConnect, $movieInfo["poster_path"]); 
        file_put_contents("images/{$movieInfo["poster_path"]}", file_get_contents("http://image.tmdb.org/t/p/w396/" . $movieInfo["poster_path"])); //Save image locally
    } else {
        $poster_path = "blank.jpg"; //If there were no images, we assign a default image for the database
    }

    return $poster_path;
}

function getMovieInfo($DBConnect, $movieID)
{

    if ($resultSet = mysqli_query($DBConnect, "SELECT * FROM movies WHERE movieID = '$movieID'")) {
        if (mysqli_num_rows($resultSet) !== 0) {
            $movieInfo = mysqli_fetch_assoc($resultSet);
            $movieInfo['isLocal'] = true;
            return $movieInfo;
        } else {

            $url = "http://api.themoviedb.org/3/movie/" . urlencode($movieID) . "?api_key=" . API_KEY;
            session_write_close();

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
            curl_setopt($curl, CURLOPT_TIMEOUT, 8);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $curlData = curl_exec($curl);
            
            if(curl_exec($curl) === false) {
                return curl_error($curl);
            }

            curl_close($curl);

            $movieInfo = json_decode($curlData, true);
            $movieInfo['movieID'] = $movieInfo['id'];
            unset($movieInfo['id']);
            $movieInfo['isLocal'] = false;
            return $movieInfo;
        }
    }
}

function pre($output) 
{
    echo "<pre>";
    print_r($output);
    echo "</pre>";

}

function login($DBConnect)
{
$userName = mysqli_real_escape_string($DBConnect, $_POST["userName"]);
    $password = hash('md5', $_POST["password"]);
    $tableName = "users";
    $SQLquery = "SELECT * FROM users WHERE userName = '$userName'";

    if($resultSet = mysqli_query($DBConnect, $SQLquery))
    {
        $row = mysqli_fetch_assoc($resultSet);
        if($password === $row["passwordHash"]) {
            $_SESSION["loggedin"] = true;
            $_SESSION["userName"] = $row["userName"];
            $_SESSION["userID"] = $row["userID"];
        } else {
            echo "The username and password combination you entered is incorrect";
        }
    }
}

function printItemsInDB($DBConnect)
{
    $userID = $_SESSION["userID"];
    $SQLstring = "SELECT m.title, m.movieID, m.poster_path FROM movies m, linkingtable l WHERE m.movieID = l.movieID AND l.userID = '$userID' ORDER BY title ASC";
    echo "<div class=\"listWrapper\">";
    if ($resultSet = mysqli_query($DBConnect, $SQLstring)) {
        while ($row = mysqli_fetch_assoc($resultSet)) {
            echo "<div class=\"posterDiv\"><a class = \"movieLink\" href=\"index.php?movieID={$row['movieID']}\"><img class=\"posterImg\" src=\"images/{$row['poster_path']}\">\n
                    <p class=\"pTitle\">{$row['title']}</p></a>
                    </div>\n";
        }
    }
    echo "</div>";
}

function printMovieInfo($DBConnect, $movieInfo)
{
    $isLocal = $movieInfo['isLocal'];
    $poster_path = ($isLocal) ? "images{$movieInfo["poster_path"]}" :  "http://image.tmdb.org/t/p/w396/{$movieInfo["poster_path"]}";
    echo "<h1>" . htmlentities($movieInfo["title"]) . " - Average Rating: " . "{$movieInfo["vote_average"]}</h1>";
    echo "<img class=\"posterBig\" src=\"{$poster_path}\">";
    echo "<p>" . htmlentities($movieInfo["overview"]) . "</p>";
    /*   
    if($isLocal) {

        //echo "<a class=\"deleteMovie\" href=\"index.php?movieID={$movieInfo['movieID']}&delete=1\">Delete?</a>";
            echo "<a id=\"deleteMovie\" href=\"#\">Delete?</a>";
            if(isset($_GET['delete']) && $_GET['delete'] == 1) { //Delete movie
                $SQLstring = "DELETE FROM movies WHERE movieID='$movieID'";
                $QueryResult = mysqli_query($DBConnect, $SQLstring);
                if ($QueryResult === FALSE) {
                    die ("Error: " . mysqli_error($DBConnect));
            

*/
    $inUserLibrary = inUserLibrary($DBConnect, $movieInfo['movieID']);
    if ($inUserLibrary == false) {

        echo "  <form method=\"POST\" action=\"index.php\">
                <input type = \"hidden\" name = \"movieID\" value = \"{$movieInfo["movieID"]}\">
                    <input type=\"submit\" value=\"Add to My Movies\" name=\"confirmed\">
                    <input type=\"submit\" value=\"Cancel\" name=\"Cancel\"> 
                </form>";
    }

}

function inUserLibrary($DBConnect, $movieID)
{
    $tableName = "linkingtable";
    $userID = $_SESSION['userID'];
    $SQLstring = "SELECT * FROM $tableName WHERE userID = '$userID' AND movieID = '$movieID'";

    $resultSet = mysqli_query($DBConnect, $SQLstring);
    if ($resultSet) {
        if (mysqli_num_rows($resultSet) == 0) {
            return false;
        }
    } return true;
}

function printSearchResults($searchResults)
{
    for($i = 0; $i < count($searchResults["results"]); $i++) {    
        echo "<div class=\"posterDiv\">
                <a class = \"movieLink\" href=\"index.php?movieID={$searchResults["results"][$i]["id"]}\">
                <img class=\"posterImg\" src=\"http://image.tmdb.org/t/p/w185/{$searchResults["results"][$i]["poster_path"]}\">\n
                <p class=\"pTitle\">{$searchResults["results"][$i]["title"]}</p></a>
                <form method=\"POST\" action=\"index.php\">
                    <input type=\"hidden\" name=\"movieID\" value=\"{$searchResults["results"][$i]["id"]}\">
                    <input type=\"submit\" value=\"Add to my movies\" name=\"addFromSearch\" />
                </form>
            </div>\n";
    }
}

function searchTMDB($name)
{
    $url="http://api.themoviedb.org/3/search/movie?query=" . urlencode($name) . "&api_key=" . API_KEY; //API request URL w/ key
    //Send request 
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $curlData = curl_exec($curl);
    if(curl_exec($curl) === false) {
        return curl_error($curl);
    }
    curl_close($curl);

    return json_decode($curlData, true); //Decode the retreived data
}
?>