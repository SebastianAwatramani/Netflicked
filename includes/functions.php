
<?php 
function addMovieToDB($DBConnect, $movieInfo) //Adds movie to main db and user's personal catalogue
{   
    if(isset($movieInfo["poster_path"])) {
        getAndSavePoster($DBConnect, $movieInfo);
        $poster_path = mysqli_real_escape_string($DBConnect, $movieInfo["poster_path"]); 
    } else {
        $poster_path = "/blank.jpg"; //If TMDB didn't return a poster
    }
    //Sanatize DB inputs
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
                echo "There was an error processing your request.  Please try again later\n";
            }
        }
        $resultSet = mysqli_query($DBConnect, "SELECT movieID FROM movies WHERE movieID = '$movieID'"); //Checks that movie was succesfully stored, and if so, adds to user's catalogue
        $row = mysqli_fetch_assoc($resultSet);
        if (!empty($row['movieID'])) {
            $tableName = "linkingtable";
            $userID = $_SESSION["userID"];
            $SQLstring = "SELECT * FROM $tableName WHERE userID = $userID AND movieID = $movieID";
            $resultSet = mysqli_query($DBConnect, $SQLstring);
            if(mysqli_num_rows($resultSet) === 0) {
                $SQLstring = "INSERT INTO $tableName (userID, movieID) VALUES ('$userID', '$movieID')";
                $QueryResult = mysqli_query($DBConnect, $SQLstring);
                if ($QueryResult === FALSE) {
                    echo "There was an error processing your request.  Please try again later\n";
                }
            } else {
                echo "<p>The movie you're trying to add is already in your collection</p>";
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
            printCreateAccountForm();
            echo "Username is not available.  Please try again\n";
            //Call function to print form
        }
    } else {
        echo "There was an error processing your request.  Please try again later\n";
    }
}

function getAndSavePoster($DBConnect, $movieInfo) //Store image locally
{   
        $localPath = "images" . $movieInfo['poster_path'];
        if(!file_exists($localPath)) {            
            file_put_contents("images/{$movieInfo["poster_path"]}", file_get_contents("http://image.tmdb.org/t/p/w396" . $movieInfo["poster_path"])); //Save image locally
        }   
}

function getMovieInfo($DBConnect, $movieID) //Check local DB for movie, and if !exist cURL it
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
            $movieInfo['isLocal'] = false;
            return $movieInfo;
        }
    }
}


function login($DBConnect) //Login function
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
            return false; //Will print a bad credentials error if this function returns false.
        }
    }
}

function printItemsInDB($DBConnect) //Print user's catalogue
{
    $userID = $_SESSION["userID"];
    $SQLstring = "SELECT m.title, m.movieID, m.poster_path FROM movies m, linkingtable l WHERE m.movieID = l.movieID AND l.userID = '$userID' ORDER BY title ASC";
    echo "<div class=\"listWrapper\">";
    if ($resultSet = mysqli_query($DBConnect, $SQLstring)) {
        if(mysqli_num_rows($resultSet) === 0 ) {
            echo "<p>Search for a movie to add to your collection</p>";
        } else {
            echo "<h2>My Movies</h2>";
        while ($row = mysqli_fetch_assoc($resultSet)) {
            echo "<div class=\"posterDiv\"><a class = \"movieLink\" href=\"index.php?movieID={$row['movieID']}\"><img class=\"posterImg\" src=\"images{$row['poster_path']}\">\n
                    <p class=\"pTitle\">{$row['title']}</p></a>
                    </div>\n";
        }
    }
}
    echo "</div>";
}

function printMovieInfo($DBConnect, $movieInfo) //Function to print large movie poster and info
{
    $isLocal = $movieInfo['isLocal'];
    $poster_path = ($isLocal) ? "images{$movieInfo["poster_path"]}" :  "http://image.tmdb.org/t/p/w396{$movieInfo["poster_path"]}";
    echo "<h1>" . htmlentities($movieInfo["title"]) . "</h1>";
    echo "<div class='ratingContainer'>";
    for($i = 0; $i < $movieInfo["vote_average"]; $i++) {
        echo "<img src='assets/star.png' class='star'>";
    }
    echo "</div>";

    echo "<img class=\"posterBig\" src=\"{$poster_path}\">";
    echo "<p>" . htmlentities($movieInfo["overview"]) . "</p>";
    /*   
    
    //Option to delete movie, not currently implemented

    if($isLocal) {

        //echo "<a class=\"deleteMovie\" href=\"index.php?movieID={$movieInfo['movieID']}&delete=1\">Delete?</a>";
            echo "<a id=\"deleteMovie\" href=\"#\">Delete?</a>";
            if(isset($_GET['delete']) && $_GET['delete'] == 1) { //Delete movie
                $SQLstring = "DELETE FROM movies WHERE movieID='$movieID'";
                $QueryResult = mysqli_query($DBConnect, $SQLstring);
                if ($QueryResult === FALSE) {
                    die ("Error: " . mysqli_error($DBConnect));
            

*/
    $inUserLibrary = inUserLibrary($DBConnect, $movieInfo['movieID']); //Checks if in user's catalogue
    if ($inUserLibrary == false) {

        echo "  <form method=\"POST\" action=\"index.php\">
                <input type = \"hidden\" name = \"movieID\" value = \"{$movieInfo["movieID"]}\">
                    <input type=\"submit\" value=\"Add to My Movies\" name=\"confirmed\">
                    <input type=\"submit\" value=\"Cancel\" name=\"Cancel\"> 
                </form>";
    }

}

function inUserLibrary($DBConnect, $movieID) //Checks if in user's catalogue
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

function printSearchResults($searchResults) //Prints search results
{
    for($i = 0; $i < count($searchResults["results"]); $i++) {    
        echo "<div class=\"posterDiv\">
                <a class = \"movieLink\" href=\"index.php?movieID={$searchResults["results"][$i]["id"]}\">";
                if(!empty($searchResults["results"][$i]["poster_path"])) {
                    echo  "<img class=\"posterImg\" src=\"http://image.tmdb.org/t/p/w185{$searchResults["results"][$i]["poster_path"]}\">\n";
                } else {
                    echo "<img class=\"posterImg\" src=\"assets/blank.jpg\">\n";
                }
                echo "<p class=\"pTitle\">{$searchResults["results"][$i]["title"]}</p></a>
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


function printCreateAccountForm() { //Form to create account
    echo "<form method=\"post\" action=\"index.php?createAccount=1\" class=\"accountForm\">
                    <input type=\"text\" name=\"userName\" placeholder=\"Username\" />
                    <input type=\"password\" name=\"password\" placeholder=\"Password\" />
                    <input type=\"submit\" name=\"createAccount\" value=\"Create Account\" />
                    </form>
                    <p class=\"createLink\"><a href=\"index.php\">Log in</a></p>";
}
?>
