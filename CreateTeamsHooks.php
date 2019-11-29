<?php

namespace Liquipedia\CreateTeams;

class Hooks {

	public static function onLPExtensionMenu( &$extensionsMenu, $skin ) {
		if ( $skin->getUser()->isAllowed( 'edit' ) ) {
			$extensionsMenu[ 'createteams' ] = 'CreateTeams';
		}
	}

}
