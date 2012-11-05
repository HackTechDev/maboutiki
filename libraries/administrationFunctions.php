<?php 

$adminLogin = "root";
$adminPassword = "mot2passe";

/*
   Create Sql user
  Only mysql admin can do that.
 */

function createSqlUser($user, $password){
	global $adminLogin, $adminPassword;
    
    $conn = new mysqli("localhost", $adminLogin, $adminPassword);

    if (mysqli_connect_errno()) {
        exit('Connection failed'. mysqli_connect_error());
    }

	$sql = "CREATE USER '$user'@'localhost' IDENTIFIED BY '$password';";

    if ($conn->query($sql) === TRUE) {
        echo "User in Database selected: " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error . "<br/>";
    }
}

/*
   Create user database
   Only mysql admin can do that.
 */

function createUserDatabase($user){
	global $adminLogin, $adminPassword;
    $conn = new mysqli("localhost", $adminLogin, $adminPassword);

    if (mysqli_connect_errno()) {
        exit('Connection failed'. mysqli_connect_error());
    }

	$sql = "CREATE DATABASE `$user`;";

    if ($conn->query($sql) === TRUE) {
        echo "User Database created: " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

/*
   Set permission user database
   Only mysql admin can do that.
 */

function setPermissionUserDatabase($user, $password){
	global $adminLogin, $adminPassword;
    $conn = new mysqli("localhost", $adminLogin, $adminPassword);

    if (mysqli_connect_errno()) {
        exit('Connection failed'. mysqli_connect_error());
    }
	$sql = "GRANT ALL PRIVILEGES ON `$user`.* TO '$user'@'localhost';";

    if ($conn->query($sql) === TRUE) {
        echo "User Permission Database created: " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

/*
   Remove user database
   Only mysql admin can do that
 */

function removeUserDatabase($user){
	global $adminLogin, $adminPassword;
    $conn = new mysqli("localhost", $adminLogin, $adminPassword);

    if (mysqli_connect_errno()) {
        exit('Connection failed: '. mysqli_connect_error());
    }

    $sql = "DROP DATABASE " . $user . ";";

    if ($conn->query($sql) === TRUE) {
        echo "User Database removed " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

/*
   Remove software database by user
 */
function removeSoftwareDatabaseByUser($user, $password, $software){
    $conn = new mysqli("localhost", $user, $password);

    if (mysqli_connect_errno()) {
        exit('Connection failed'. mysqli_connect_error());
    }

    $sql = "USE " . $user . ";";

    if ($conn->query($sql) === TRUE) {
        echo "Database selected: " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }

    $sql = "DROP TABLE " . $software . "_* ;";

    if ($conn->query($sql) === TRUE) {
        echo "Software selected: " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

/*
	Remove User in database	
*/

function removeUserInDatabase($user, $password){
	global $adminLogin, $adminPassword;
    $conn = new mysqli("localhost", $adminLogin, $adminPassword);

    if (mysqli_connect_errno()) {
        exit('Connection failed: '. mysqli_connect_error());
    }

    $sql = "DELETE FROM `mysql`.`user` WHERE `user`.`User` =  '" . $user . "';";

    if ($conn->query($sql) === TRUE) {
        echo "User Database in Database removed " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

/*
  Remove UserDatabase in database
*/

function removeUserDatabaseInDatabase($user, $password){
	global $adminLogin, $adminPassword;
    $conn = new mysqli("localhost", $adminLogin, $adminPassword);

    if (mysqli_connect_errno()) {
        exit('Connection failed: '. mysqli_connect_error());
    }

    $sql = "DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '" . $user . "' AND `db`.`User` =   '" . $user . "';";

    if ($conn->query($sql) === TRUE) {
        echo "Database removed " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

?>


