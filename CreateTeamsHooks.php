<?php

namespace Liquipedia\CreateTeams;

class Hooks {

	public static function onLPExtensionMenu( &$extensionsMenu, $skin ) {
		$extensionsMenu[ 'createteams' ] = 'CreateTeams';
	}

}
