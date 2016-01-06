<?php
# Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install CreateTeams, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/CreateTeams/CreateTeams.php" );
EOT;
	exit( 1 );
}

function randomFunction() {
	global $wgOut;
}


$wgExtensionCredits[ 'specialpage' ][] = array(
	'path' => __FILE__,
	'name' => 'CreateTeams',
	'author' => array('hainrich', 'Chapatiyaq'),
	'url' => 'http://www.tolueno.fr',
	'descriptionmsg' =>'createteams-desc',
	'version' => '0.2.1',
);

$wgAutoloadClasses[ 'SpecialCreateTeams' ] = __DIR__ . '/SpecialCreateTeams.php'; # Location of the SpecialCreateTeams class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles[ 'CreateTeams' ] = __DIR__ . '/CreateTeams.i18n.php'; # Location of a messages file (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles[ 'CreateTeamsAlias' ] = __DIR__ . '/CreateTeams.alias.php'; # Location of an aliases file (Tell MediaWiki to load this file)
$wgSpecialPages[ 'CreateTeams' ] = 'SpecialCreateTeams'; # Tell MediaWiki about the new special page and its class name
$wgSpecialPageGroups[ 'CreateTeams' ] = 'pagetools';

$wgResourceModules[ 'ext.createteams.SpecialPage' ] = array(
	'styles' => 'ext.createteams.SpecialPage.css',
	'messages' => 'createteams-templates.json',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'CreateTeams',
);
