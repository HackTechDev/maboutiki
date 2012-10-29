<?php

/*
 Compression répertoire et sous-répertoire courant : 

	$ tar cvf site1.tar *
	$ bzip2  site1.tar
	$ mv site1.tar.bz2 site1.tbz2

 */


/*
 Décompression d'un fichier .bz2
*/


function decompresserArchiveBz2($version, $sitenom){
	$fichierCompresser = new PharData("../sites/" . $sitenom . "/site" . $version . ".tbz2", 0);
	$fichierCompresser->decompress();

	$fichierArchive = new PharData("../sites/" . $sitenom . "/site" . $version . ".tar");
	$fichierArchive->extractTo("../sites/" . $sitenom . "/", null, true);
    unlink("../sites/" . $sitenom . "/site" . $version . ".tar"); 
    unlink("../sites/" . $sitenom . "/site" . $version . ".tbz2"); 
}

/*
 Fonctions de génération de mot de passe. 
 Joomla : 2.5.7
*/

/*
 Fichier : ./libraries/joomla/crypt/crypt.php
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
  Fichier libraries/joomla/user/helper.php 
  Fonction appelé depuis : ./models/configuration.php
  Code : ligne 74 : $registry->set('secret', JUserHelper::genRandomPassword(16));
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
  Modification du fichier de configuration
 */
function modifierFichierConfigurationJoomla($version, $sitedesc, $utilisateur, $motpasse, $sitenom, $prefixbd, $courriel, $nomcourriel, $hebergement){
    $fichierConfigurationPhp = fopen("../sites/" . $sitenom . "/configuration.php", "a+");

	$configuration  = "    public \$sitename = '" . $sitedesc . "';\n";
	$configuration .= "    public \$user = '" . $utilisateur . "';\n";
	$configuration .= "    public \$password = '" . $motpasse . "';\n";
	$configuration .= "    public \$db = '" . $sitenom . "';\n";
	$configuration .= "    public \$dbprefix = '" . $prefixbd . "_';\n";
	$configuration .= "    public \$secret = '" . genRandomPassword(16) . "';\n";
	$configuration .= "    public \$mailfrom = '" . $courriel . "';\n";
	$configuration .= "    public \$fromname = '" . $nomcourriel . "';\n";
	$configuration .= "    public \$log_path = '" . $hebergement . $sitenom."/logs';\n";
	$configuration .= "    public \$tmp_path = '" . $hebergement . $sitenom."/tmp';\n";
	$configuration .= "}";

    fwrite($fichierConfigurationPhp, $configuration);   

    fclose($fichierConfigurationPhp);
}

/*
   Création du répertoire du site
 */

function creerRepertoireSite($sitenom){
	// Permission /sites/ : 777 

	mkdir($sitenom);
}

/*
   Déplacement du fichier archive .tbz2
 */
function copierArchiveZip($version, $sitenom){
    $de = "./squelettes/site" . $version . ".tbz2";
    $vers = "../sites/" . $sitenom . "/site" . $version . ".tbz2";

	copy($de, $vers);
}

/*
  Insertion du fichier des requêtes sql
 */

function insererRequetesSQL($version, $sitenom, $utilisateur, $motpasse){
	$conn = new mysqli('localhost', $utilisateur, $motpasse);

	if (mysqli_connect_errno()) {
	  exit('Echec connexion '. mysqli_connect_error());
	}

	$sql = "CREATE DATABASE " . $sitenom .";";

	if ($conn->query($sql) === TRUE) {
	  echo "Base de donn&eacute;e " . $sitenom . " cr&eacute;&eacute;e<br/>";
	} else {
	 echo "Erreur : " . $conn->error;
	}

	$sql = "USE " . $sitenom .";";

	if ($conn->query($sql) === TRUE) {
	  echo "Base de donn&eacute;e " . $sitenom . " s&eacute;lectionn&eacute;e<br/>";
	} else {
	 echo "Erreur : " . $conn->error;
	}

	// AFAIRE : A recoder en php
    // En prod : changer l'appel de la commande mysql
	echo "Insertion des donn&eacute;e<br/>";	
	system("../../bin/mysql -h localhost -u ".$utilisateur." -p".$motpasse." " . $sitenom . " < " . "../sites/" . $sitenom . "/bdd/site" . $version . ".sql");

	unlink("../sites/" . $sitenom . "/bdd/site" . $version . ".sql");  
	rmdir("../sites/" . $sitenom . "/bdd/");
}

/*
   Installation d'un site
 */

function installerSite($version, $sitedesc, $utilisateur, $motpasse, $sitenom, $prefixdb, $courriel, $nomcourriel){
	echo "Installation site<br/>";

	echo "Cr&eacute;ation du r&eacute;pertoire du site<br/>";
	creerRepertoireSite("../sites/" . $sitenom);
    echo "Copie de l'archive<br/>";
    copierArchiveZip($version, $sitenom);
    echo "Decompression de l'archive " .  "<br/>";
    decompresserArchiveBz2($version, $sitenom);
	echo "Modification du fichier de configuration Joomla<br>";
	modifierFichierConfigurationJoomla($version, $sitedesc, $utilisateur, $motpasse, $sitenom, $prefixdb, $courriel, $nomcourriel, "/home/lesanglier/IMAUGIS/lampp/htdocs/");
	insererRequetesSQL($version, $sitenom, $utilisateur, $motpasse);
}

$version = 1;
$sitedesc = 'Mon test';
$utilisateur = 'root';
$motpasse = 'mot2passe';
$sitenom = 'test';
$prefixbd = 'joomla';
$courriel = 'version01@mapetiteboutique.pro';
$nomcourriel = 'Ma Petite Boutique Version01';

installerSite($version, $sitedesc, $utilisateur, $motpasse, $sitenom, $prefixbd, $courriel, $nomcourriel);
echo "<br/><a href=\"http://localhost/sites/". $sitenom . "/\">" . $sitenom . "</a>";
?>
