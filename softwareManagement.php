<?php
/*
   Compress current directories and sub-directories:

   $ tar cvf software1.tar *
   $ bzip2  software1.tar
   $ mv software1.tar.bz2 software1.tbz2
 */

/*
   Uncompress .bz2 archive
 */

function uncompressBz2Archive($version, $user){
    $compressedArchive = new PharData("../sites/" . $user . "/software" . $version . ".tbz2", 0);
    $compressedArchive->decompress();

    $archive = new PharData("../sites/" . $user . "/software" . $version . ".tar");
    $archive->extractTo("../sites/" . $user . "/", null, true);
    unlink("../sites/" . $user . "/software" . $version . ".tar"); 
    unlink("../sites/" . $user . "/software" . $version . ".tbz2"); 
}

/*
   Password generation functions 
Joomla : 2.5.7
 */

/*
File: ./libraries/joomla/crypt/crypt.php
 */

/**
 * Generate random bytes.
 *
 * @param   integer  $length  Length of the random data to generate
 *
 * @return  string  Random binary data
 *
 * @since  12.1
 */

function genRandomBytes($length = 16)
{
    $sslStr = '';
    /*
     * if a secure randomness generator exists and we don't
     * have a buggy PHP version use it.
     */
    if (function_exists('openssl_random_pseudo_bytes')
            && (version_compare(PHP_VERSION, '5.3.4') >= 0 || IS_WIN))
    {
        $sslStr = openssl_random_pseudo_bytes($length, $strong);
        if ($strong)
        {
            return $sslStr;
        }
    }

    /*
     * Collect any entropy available in the system along with a number
     * of time measurements of operating system randomness.
     */
    $bitsPerRound = 2;
    $maxTimeMicro = 400;
    $shaHashLength = 20;
    $randomStr = '';
    $total = $length;

    // Check if we can use /dev/urandom.
    $urandom = false;
    $handle = null;

    // This is PHP 5.3.3 and up
    if (function_exists('stream_set_read_buffer') && @is_readable('/dev/urandom'))
    {
        $handle = @fopen('/dev/urandom', 'rb');
        if ($handle)
        {
            $urandom = true;
        }
    }

    while ($length > strlen($randomStr))
    {
        $bytes = ($total > $shaHashLength)? $shaHashLength : $total;
        $total -= $bytes;
        /*
         * Collect any entropy available from the PHP system and filesystem.
         * If we have ssl data that isn't strong, we use it once.
         */
        $entropy = rand() . uniqid(mt_rand(), true) . $sslStr;
        $entropy .= implode('', @fstat(fopen(__FILE__, 'r')));
        $entropy .= memory_get_usage();
        $sslStr = '';
        if ($urandom)
        {
            stream_set_read_buffer($handle, 0);
            $entropy .= @fread($handle, $bytes);
        }
        else
        {
            /*
             * There is no external source of entropy so we repeat calls
             * to mt_rand until we are assured there's real randomness in
             * the result.
             *
             * Measure the time that the operations will take on average.
             */
            $samples = 3;
            $duration = 0;
            for ($pass = 0; $pass < $samples; ++$pass)
            {
                $microStart = microtime(true) * 1000000;
                $hash = sha1(mt_rand(), true);
                for ($count = 0; $count < 50; ++$count)
                {
                    $hash = sha1($hash, true);
                }
                $microEnd = microtime(true) * 1000000;
                $entropy .= $microStart . $microEnd;
                if ($microStart > $microEnd)
                {
                    $microEnd += 1000000;
                }
                $duration += $microEnd - $microStart;
            }
            $duration = $duration / $samples;

            /*
             * Based on the average time, determine the total rounds so that
             * the total running time is bounded to a reasonable number.
             */
            $rounds = (int) (($maxTimeMicro / $duration) * 50);

            /*
             * Take additional measurements. On average we can expect
             * at least $bitsPerRound bits of entropy from each measurement.
             */
            $iter = $bytes * (int) ceil(8 / $bitsPerRound);
            for ($pass = 0; $pass < $iter; ++$pass)
            {
                $microStart = microtime(true);
                $hash = sha1(mt_rand(), true);
                for ($count = 0; $count < $rounds; ++$count)
                {
                    $hash = sha1($hash, true);
                }
                $entropy .= $microStart . microtime(true);
            }
        }

        $randomStr .= sha1($entropy, true);
    }

    if ($urandom)
    {
        @fclose($handle);
    }

    return substr($randomStr, 0, $length);
}

/*
File: libraries/joomla/user/helper.php 
Function called from: ./models/configuration.php
Code: line 74: $registry->set('secret', JUserHelper::genRandomPassword(16));
 */

/**
 * Generate a random password
 *
 * @param   integer  $length  Length of the password to generate
 *
 * @return  string  Random Password
 *
 * @since   11.1
 */

function genRandomPassword($length = 8)
{
    $salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $base = strlen($salt);
    $makepass = '';

    /*
     * Start with a cryptographic strength random string, then convert it to
     * a string with the numeric base of the salt.
     * Shift the base conversion on each character so the character
     * distribution is even, and randomize the start shift so it's not
     * predictable.
     */
    $random = genRandomBytes($length + 1);
    $shift = ord($random[0]);
    for ($i = 1; $i <= $length; ++$i)
    {
        $makepass .= $salt[($shift + ord($random[$i])) % $base];
        $shift += ord($random[$i]);
    }

    return $makepass;
}

/*
   Modify configuration file
 */
function modifyJoomlaConfigurationFile($version, $sitename, $user, $password, $db, $dbprefix, $mailfrom, $fromname, $sitepath){
    $joomlaConfigurationFile = fopen("../sites/" . $user . "/configuration.php", "a+");

    $configuration  = "    public \$sitename = '" . $sitename . "';\n";
    $configuration .= "    public \$user = '" . $user . "';\n";
    $configuration .= "    public \$password = '" . $password . "';\n";
    $configuration .= "    public \$db = '" . $db . "';\n";
    $configuration .= "    public \$dbprefix = '" . $dbprefix . "_';\n";
    $configuration .= "    public \$secret = '" . genRandomPassword(16) . "';\n";
    $configuration .= "    public \$mailfrom = '" . $mailfrom . "';\n";
    $configuration .= "    public \$fromname = '" . $fromname . "';\n";
    $configuration .= "    public \$log_path = '" . $sitepath . $db . "/logs';\n";
    $configuration .= "    public \$tmp_path = '" . $sitepath . $db . "/tmp';\n";
    $configuration .= "}";

    fwrite($joomlaConfigurationFile, $configuration);   

    fclose($joomlaConfigurationFile);
}


/*
   Copy archive
 */
function copyArchive($from, $to){
    copy($from, $to);
}

/*
   Insert sql request file
 */

function insertSqlRequestFile($user, $password){
    $conn = new mysqli('localhost', $user, $password);

    if (mysqli_connect_errno()) {
        exit('Connection failed'. mysqli_connect_error());
    }

    $sql = "USE " . $user . ";";

    if ($conn->query($sql) === TRUE) {
        echo "Database selected: " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }

    // TODO: Change the call of command line mysql by the mysql php

    echo "Insert request file<br/>";	
    system("../../bin/mysql -h localhost -u " . $user . " -p" . $password . " " . $user . " < " . "../sites/" . $user . "/bdd/requests.sql");

    unlink("../sites/" . $user . "/bdd/requests.sql");  
    rmdir("../sites/" . $user . "/bdd/");
}

/*
   Create Sql user
  Only mysql admin can do that.
 */
function createSqlUser($user, $password){
    $conn = new mysqli("localhost", "root", "mot2passe");

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
    $conn = new mysqli("localhost", "root", "mot2passe");

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
    $conn = new mysqli("localhost", "root", "mot2passe");

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
   Create user site
 */

function createUserSite($user){
    mkdir("../sites/" . $user);
}

/*
   Remove software by user
 */

function removeSoftwareByUser($user, $software) {
    $dir = "../sites/" . $user . "/" . $software;
    if (is_dir ($dir)) {
        $dh = opendir ($dir); 
    }else {     
        exit;
    }

    while (($file = readdir ($dh)) !== false ) { 
        if ($file !== '.' && $file !== '..') { 
            $path =$dir.'/'.$file;
            if (is_dir ($path)) {           
                supprimerRepertoire($path); 
                rmdir($path);
            }else {   
                unlink($path);
            }
        }
    }
    closedir ($dh); 
}

/*
   Remove user site
 */

function removeUserSite($site) {	
    if (is_dir ($site)) {
        $dh = opendir ($site); 
    }else {     
        exit;
    }

    while (($file = readdir ($dh)) !== false ) { 
        if ($file !== '.' && $file !== '..') { 
            $path = $site.'/'.$file;
            if (is_dir ($path)) {           
                removeUserSite($path); 
                rmdir($path);
            }else {   
                unlink($path);
            }
        }
    }
    closedir ($dh); 

}

/*
   Remove user database
   Only mysql admin can do that
 */

function removeUserDatabase($user){
    $conn = new mysqli("localhost", "root", "mot2passe");

    if (mysqli_connect_errno()) {
        exit('Connection failed: '. mysqli_connect_error());
    }

    $sql = "DROP DATABASE " . $user . ";";

    if ($conn->query($sql) === TRUE) {
        echo "Database removed " . $user . "<br/>";
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
   Create user
 */

function createUser($user, $password){
    createUserSite($user);
    createSqlUser($user, $password);
    createUserDatabase($user);
	setPermissionUserDatabase($user, $password);
}

/*
   Install software
 */

function installSoftware($version, $sitename, $user, $password, $db, $dbprefix, $mailfrom, $fromname){
    copyArchive("./templates/software" . $version . ".tbz2", "../sites/" . $user . "/software" . $version . ".tbz2");
    uncompressBz2Archive($version, $user);
    modifyJoomlaConfigurationFile($version, $sitename, $user, $password, $db, $dbprefix, $mailfrom, $fromname, "/home/lesanglier/IMAUGIS/lampp/htdocs/");
    insertSqlRequestFile($user, $password);
}

/*
	Remove User in database	
*/

function removeUserInDatabase($user, $password){
    $conn = new mysqli("localhost", "root", "mot2passe");

    if (mysqli_connect_errno()) {
        exit('Connection failed: '. mysqli_connect_error());
    }

    $sql = "DELETE FROM `mysql`.`user` WHERE `user`.`User` =  '" . $user . "';";

    if ($conn->query($sql) === TRUE) {
        echo "Database removed " . $user . "<br/>";
    } else {
        echo "Error: " . $conn->error;
    }
}

/*
  Remove UserDatabase in database
*/

function removeUserDatabaseInDatabase($user, $password){
    $conn = new mysqli("localhost", "root", "mot2passe");

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

/*
   Delete user site
 */

function deleteUserSite($user, $password){
    // Permission du site : 777
    removeUserSite("../sites/" . $user);
    rmdir("../sites/" . $user);
    removeUserDatabase($user, $password);
	removeUserInDatabase($user, $password);
	removeUserDatabaseInDatabase($user, $password);
}

/*
   Desactivate user site
 */

function activateUserSite($user){
}
?>
