<?php
include("softwareManagement.php");

$version = 1; // Joomla application
$sitename = 'Mon test';
$user = 'util21';
$password = 'mot2passe';
$db = 'util21';
$dbprefix = 'joomla';
$mailfrom = 'version01@mapetiteboutique.pro';
$fromname = 'Ma Petite Boutique Version01';

createUser($user, $password);
installSoftware($version, $sitename, $user, $password, $db, $dbprefix, $mailfrom, $fromname);

echo "<br/><a href=\"http://sites.localhost/". $db . "/\">" . $db . "</a>";
?>
