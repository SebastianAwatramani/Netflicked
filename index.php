<?php session_start(); 
//To do:
//Implement user accounts
////Implement rating system
//Personal review box
//Fix session issue
//Implement delete functionality
//Find a way to check if the imagefile exists for blank.jpg
//Save foreign titles
//Add year
//Prevent linking table from taking duplicate entries
//Login failed message
//Fix document expired
//Javascript validation
    require("includes/functions.php"); 
    require("includes/dbconnection.php");
    require("includes/header.php");
    //Search form
    if((empty($_POST["searchQuery"])) && (isset($_POST['search']))) { //If user submitted an empty field, remind them that a name is required
            echo "<p class=\"header\">Please enter a movie name</p>";
    }
    echo "</div>\n
    <div id=\"wrapper\">\n";
    if(isset($_GET["logout"])) {
        session_unset();
    }
    if(isset($_POST["login"]))
    {
        login($DBConnect);
    }
    if($_SESSION["loggedin"] === true) {
        if(isset($_GET['movieID'])) {
            echo "<div id=\"infoWrapper\">\n";      
            $movieID = $_GET['movieID'];
            $isLocal = true;
            $movieInfo = getMovieInfo($DBConnect, $movieID);
            printMovieInfo($DBConnect, $movieInfo);
            echo "</div>";
        }
        if((!empty($_POST["searchQuery"])) && (isset($_POST['search']))) { 
                echo "<div class=\"listWrapper\">";
                $searchQuery = $_POST["searchQuery"]; 
                $searchResults = searchTMDB($searchQuery); //Request movie data
                printSearchResults($searchResults);
                echo "</div>";
        }
        if((isset($_POST['movieID'])) && (isset($_POST['confirmed']) || (isset($_POST['addFromSearch'])))) { 
            $movieID = $_POST["movieID"];
            $movieInfo = getMovieInfo($DBConnect, $movieID);    
            addMovieToDB($DBConnect, $movieInfo);
        }
        printItemsInDB($DBConnect);
    } elseif ($_GET['createAccount'] == 1) {
        if(!isset($_POST['createAccount'])) {
            echo "<form method=\"post\" action=\"index.php?createAccount=1\">
                    <input type=\"text\" name=\"userName\" />
                    <input type=\"password\" name=\"password\">
                    <input type=\"submit\" name=\"createAccount\" value=\"Create\" />
                    </form>";
        } else {
            $userName = $_POST['userName'];
            $password = hash('md5', $_POST['password']);
            createAccount($DBConnect, $userName, $password);
        }
    } else {
        echo "<form method=\"post\" action=\"index.php\">
                <input type=\"text\" name=\"userName\" />
                <input type=\"password\" name=\"password\">
                <input type=\"submit\" name=\"login\" value=\"Login\" />
                </form>
                <a href=\"index.php?createAccount=1\"><p>Create account</p>";
    }

    
require("includes/footer.php");
?>