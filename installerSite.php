<?php
include("fonctionGestion.php");

$version = 1;
$sitedesc = 'Mon test';
$utilisateur = 'root';
$motpasse = 'mot2passe';
$sitenom = 'test';
$prefixbd = 'joomla';
$courriel = 'version01@mapetiteboutique.pro';
$nomcourriel = 'Ma Petite Boutique Version01';

installerSite($version, $sitedesc, $utilisateur, $motpasse, $sitenom, $prefixbd, $courriel, $nomcourriel);

echo "<br/><a href=\"http://sites.localhost". $sitenom . "/\">" . $sitenom . "</a>";
?>
