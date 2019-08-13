<?php

namespace Liquipedia\CreateTeams;

class Hooks {

	public static function onLPExtensionMenu( &$extensionsMenu, $skin ) {
		if ( wfMessage( 'createteams-disabled' )->text() == 'yes' ) {
			return;
		}
		if ( $skin->getUser()->isAllowed( 'edit' ) ) {
			$extensionsMenu[ 'createteams' ] = 'CreateTeams';
		}
	}

}
