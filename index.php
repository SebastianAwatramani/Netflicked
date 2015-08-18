<?php session_start(); 
//To do:
////Implement rating system
//Personal review box
//Fix session issue
//Implement delete functionality
//Find a way to check if the imagefile exists for blank.jpg
//Save foreign titles
//Add year
//Fix document expired
//Javascript validation
//Add code dealing with images that don't exist in high resolution

    require("includes/header.php");


    //Search form
    if((empty($_POST["searchQuery"])) && (isset($_POST['search']))) { //If user submitted an empty field, remind them that a name is required
            echo "<p class=\"header\">Please enter a movie name</p>";
    }
    echo "</div>\n
    <div id=\"wrapper\">\n";


    if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { //If user is logged in
        if(isset($_GET['movieID'])) { //If a movie has been selected
            echo "<div id=\"infoWrapper\">\n";      
            $movieID = $_GET['movieID'];
            $isLocal = true;
            $movieInfo = getMovieInfo($DBConnect, $movieID); //Pull movie info
            printMovieInfo($DBConnect, $movieInfo); //Print movie info
            echo "</div>";
        }
        if((!empty($_POST["searchQuery"])) && (isset($_POST['search']))) {  //If user searched for a movie
                echo "<div class=\"listWrapper\">";
                $searchQuery = $_POST["searchQuery"]; 
                $searchResults = searchTMDB($searchQuery); //Request movie data
                printSearchResults($searchResults); //Print search results
                echo "</div>";
        }
        if((isset($_POST['movieID'])) && (isset($_POST['confirmed']) || (isset($_POST['addFromSearch'])))) {  //If user elects to add movie to their personal catalogue
            $movieID = $_POST["movieID"];
            $movieInfo = getMovieInfo($DBConnect, $movieID);    
            addMovieToDB($DBConnect, $movieInfo);
        }
        printItemsInDB($DBConnect);  //Print all movies in personal catalogue
    } elseif (isset($_GET['createAccount']) && $_GET['createAccount'] == 1) { //Create account
        if(!isset($_POST['createAccount'])) { //If an account creation request was not sent previously. print account creation form
            printCreateAccountForm();
        } else {
            if(!empty($_POST['userName']) && !empty($_POST['password'])) { //If user entered a user name and password for account creation
                $userName = $_POST['userName'];
                $password = hash('md5', $_POST['password']);
                createAccount($DBConnect, $userName, $password);
            } else {
                printCreateAccountForm();
                echo "<p class=\"createLink\">Please enter both a username and password to create a new account.  Or <a href=\"index.php\">log in</a></p>";
            }
        }
    } else { //Print log in form
        echo "<form method=\"post\" action=\"index.php\" class=\"accountForm\">
                <input type=\"text\" name=\"userName\" placeholder=\"Username\" />
                <input type=\"password\" name=\"password\" placeholder=\"password\" />
                <input type=\"submit\" name=\"login\" value=\"Login\" />
                </form>
               <p class=\"createLink\"> <a href=\"index.php?createAccount=1\" >Create account</a></p>";
    }
    if(isset($loginResult) && $loginResult === false) { //Print error message if user entered bad credentials
                    echo "<script>
                    window.addEventListener('load', function() {
                        var accountForm = document.querySelector('.accountForm');
                        if(accountForm) {
                            var badCredentialsError = document.createElement('p');
                            badCredentialsError.className = 'createLink';
                            badCredentialsError.innerHTML = 'The username and password combination you entered is incorrect';
                            accountForm.parentNode.appendChild(badCredentialsError);
                        }
                    }, false);
                    </script>";
    }
require("includes/footer.php");
?>
