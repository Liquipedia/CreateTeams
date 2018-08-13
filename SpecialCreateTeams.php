<?php

class SpecialCreateTeams extends SpecialPage {

	private $templates;

	public function __construct() {
		parent::__construct( 'CreateTeams', 'edit' );
	}

	public function getGroupName() {
		return 'liquipedia';
	}

	private function getTemplates() {
		$json = json_decode(
			wfMessage( 'createteams-templates.json' )->inContentLanguage()->plain(), true
		);
		$this->templates = $json[ 'templates' ];
	}

	private function makeTeamTemplate( $template, $vars ) {
		$str = $template[ 'wikitext' ];
		foreach ( $vars as $tag => $replacement ) {
			$str = str_replace( '{$' . $tag . '$}', $replacement, $str );
		}
		$str .= '<noinclude>[[Category:' . $template[ 'category' ] . ']]</noinclude>';
		return $str;
	}

	private function makeHistoricalTeamTemplate( $prefix, $reqHistoricaltemplate, $reqHistoricalteam, $reqHistoricaltime, $reqHistoricalteamlength, $reqHistoricaltimelength ) {
		$str = '{{#vardefine:' . $reqHistoricaltemplate . 'time|{{#time:U|{{{1|{{#replace:{{#replace:{{#explode: {{#var:date|{{#var:edate|{{#var:sdate|{{CURRENTYEAR}}-{{CURRENTMONTH}}-{{CURRENTDAY2}}}}}}}}|<}}|-XX|}}|-??|}}}}}}}}}<!-- this variable name needs to be unique --><!--' . "\n";
		$str .= '-->{{#ifexpr:{{#time:U|' . $reqHistoricaltime[ 0 ] . '}} < {{#var:' . $reqHistoricaltemplate . 'time}}|{{' . $prefix . '/' . strtolower( $reqHistoricalteam[ 0 ] ) . '}}}}<!--' . "\n";
		if ( $reqHistoricalteamlength > 2 ) {
			for ( $i = 1; $i < $reqHistoricalteamlength - 1; $i++ ) {
				if ( $reqHistoricaltime[ $i - 1 ] != '' || $reqHistoricalteam[ $i ] != '' ) {
					$str .= '-->{{#ifexpr:{{#time:U|' . $reqHistoricaltime[ $i - 1 ] . '}} >= {{#var:' . $reqHistoricaltemplate . 'time}} AND {{#time:U|' . $reqHistoricaltime[ $i ] . '}} < {{#var:' . $reqHistoricaltemplate . 'time}}|{{' . $prefix . '/' . strtolower( $reqHistoricalteam[ $i ] ) . '}}}}<!--' . "\n";
				}
			}
		}
		$str .= '-->{{#ifexpr:{{#time:U|' . $reqHistoricaltime[ $reqHistoricaltimelength - 1 ] . '}} >= {{#var:' . $reqHistoricaltemplate . 'time}}|{{' . $prefix . '/' . strtolower( $reqHistoricalteam[ $reqHistoricalteamlength - 1 ] ) . '}}}}<!--' . "\n";
		$str .= '--><noinclude>[[Category:Historical ' . $prefix . ' team template]]</noinclude>';
		return $str;
	}

	public function execute( $par ) {
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}
		$output = $this->getOutput();
		$user = $this->getUser();
		$config = $this->getConfig();
		$this->setHeaders();
		$output->addModules( 'ext.createteams.SpecialPage' );
		$report = '';
		$e = '';
		$log = '';
		$preview = '';
		$this->getTemplates();

		$toc = array(
			array(
				'href' => 'Create_team_templates',
				'text' => $this->msg( 'createteams-create-teams-heading' )->text()
			),
			array(
				'href' => 'Create_historical_team_templates',
				'text' => $this->msg( 'createteams-create-historicalteam-heading' )->text()
			),
			array(
				'href' => 'Create_redirects',
				'text' => $this->msg( 'createteams-create-redirects-heading' )->text()
			),
		);
		if ( $user->isAllowed( 'move' ) ) {
			$toc[] = array(
				'href' => 'Move_team_templates',
				'text' => $this->msg( 'createteams-move-heading' )->text()
			);
		}
		$toc[] = array(
			'href' => 'View_team_templates',
			'text' => $this->msg( 'createteams-view-heading' )->text()
		);
		if ( $user->isAllowed( 'delete' ) ) {
			$toc[] = array(
				'href' => 'Delete_team_templates',
				'text' => $this->msg( 'createteams-delete-teams-heading' )->text()
			);
		}
		$output->addHTML( '<div id="toc" class="toc"><div class="toctitle"><h2>' . $this->msg( 'toc' )->text() . '</h2></div><ul>' );
		foreach ( $toc as $tocindex => $tocitem ) {
			$output->addHTML( '<li class="toclevel-1 tocsection-' . ( $tocindex + 1 ) . '"><a href="#' . $tocitem[ 'href' ] . '"><span class="tocnumber">' . ( $tocindex + 1 ) . '</span> <span class="toctext">' . $tocitem[ 'text' ] . '</span></a></li>' );
		}
		$output->addHTML( '</ul>
		</div>' );

		# Get request data from, e.g.
		# Do stuff
		# ...
		//$wgOut->setPageTitle( "create team templates" );
		$output->addHTML( '<h2><span class="mw-headline" id="Create_team_templates">' . $this->msg( 'createteams-create-teams-heading' )->text() . '</span></h2>' );
		$output->addHTML( $this->msg( 'createteams-create-teams-desc' )->parse() );

		$uploadNavigationUrl = $config->get( 'UploadNavigationUrl' );
		if ( $uploadNavigationUrl ) {
			$uploadMessage = $this->msg( 'createteams-create-teams-image-helper-remote' )->params( $uploadNavigationUrl )->parse();
		} else {
			$uploadMessage = $this->msg( 'createteams-create-teams-image-helper' )->parse();
		}
		$request = $this->getRequest();

		$reqTeam = $request->getText( 'team' );
		$reqTeamslug = $request->getText( 'teamslug' );
		$reqPagetitle = $request->getText( 'pagetitle' );
		$reqImage = $request->getText( 'image' );
		$reqTeamshort = $request->getText( 'teamshort' );
		$reqOverwrite = $request->getBool( 'overwrite' );

		$output->addHTML( '<form name="createform" id="createform" method="post" action="#Create_team_templates">
<table>
	<tr>
		<td class="input-label"><label for="team">' . $this->msg( 'createteams-create-teams-team-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="team" id="team" value="' . $reqTeam . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-team-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="team">' . $this->msg( 'createteams-create-teams-team-slug-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="teamslug" id="teamslug" value="' . $reqTeamslug . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-team-slug-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="pagetitle">' . $this->msg( 'createteams-create-teams-pagetitle-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="pagetitle" id="pagetitle" value="' . $reqPagetitle . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-pagetitle-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="teamshort">' . $this->msg( 'createteams-create-teams-teamshort-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="teamshort" id="teamshort" value="' . $reqTeamshort . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-teamshort-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="image">' . $this->msg( 'createteams-create-teams-image-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="image" id="image" value="' . $reqImage . '"></td>
		<td class="input-helper">' . $uploadMessage . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="overwrite">' . $this->msg( 'createteams-create-teams-overwrite-label' )->text() . '</label></td>
		<td><input type="checkbox" name="overwrite" id="overwrite"' . ( $reqOverwrite ? ' checked=""' : '' ) . '></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-overwrite-helper' )->text() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="submit" name="createbutton" value="' . $this->msg( 'createteams-create-teams-create-button' )->text() . '">
			<input type="submit" name="createpreviewbutton" value="' . $this->msg( 'createteams-create-teams-preview-button' )->text() . '">
		</td>
	</tr>
</table>
</form>' );

		if ( $request->getBool( 'createbutton' ) || $request->getBool( 'createpreviewbutton' ) ) {
			if ( $reqImage == '' ) {
				$reqImage = $reqTeam . 'logo std.png';
			}
			$wikiimage = 'File:' . $reqImage;
			$imagetitle = Title::newFromText( $wikiimage );
			$imagewikipage = new WikiFilePage( $imagetitle );
			$imagefile = $imagewikipage->getFile();

			if ( $reqTeam == '' ) {
				$e = $this->msg( 'createteams-create-teams-error-team-name-empty' )->text();
			} elseif ( preg_match( '/[a-z]*:\/\//', $reqTeam ) == 1 ) {
				$e = $this->msg( 'createteams-create-teams-error-team-name-url' )->text();
			} elseif ( $imagefile->exists() == false ) {
				$e = $this->msg( 'createteams-create-teams-error-image-not-found' )->text();
			} else {
				$lcname = strtolower( $reqTeam );
				$lcslug = strtolower( $reqTeamslug );
				if ( $lcslug != '' ) {
					$lcname = $lcslug;
				}

				$vars = array(
					'name' => $reqTeam,
					'shortname' => $reqTeamshort,
					'link' => '',
					'namewithlink' => '',
					'image' => $reqImage
				);
				if ( $reqPagetitle != $reqTeam && $reqPagetitle != '' ) {
					$vars[ 'namewithlink' ] = '[[' . $reqPagetitle . '|' . $reqTeam . ']]';
					$vars[ 'link' ] = $reqPagetitle;
				} else {
					$vars[ 'namewithlink' ] = '[[' . $reqTeam . ']]';
					$vars[ 'link' ] = $reqTeam;
				}
				if ( $vars[ 'image' ] == '' ) {
					$vars[ 'image' ] = $reqTeam . 'logo_std.png';
				}

				foreach ( $this->templates as $prefix => $template ) {
					$contents[ "Template:$prefix/$lcname" ] = self::makeTeamTemplate( $template, $vars );
				}

				$preview = '{| class="createteams-preview"' . "\n";
				foreach ( $contents as $key => $value ) {
					$title = Title::newFromText( $key );
					if ( $title == null ) {
						$e .= '*' . $this->msg( 'createteams-create-error-bad-title' )->params( $key )->text() . "\n";
					} else {
						$page = WikiPage::factory( $title );
						$content = \ContentHandler::makeContent( $value, $page->getTitle(), CONTENT_MODEL_WIKITEXT );
						if ( $request->getBool( 'createpreviewbutton' ) ) {
							$preview .= '|-' . "\n" . '![[' . $key . ']]' . "\n" . '|' . $value . "\n";
						} else {
							$errors = $title->getUserPermissionsErrors( 'edit', $user );
							if ( !$title->exists() ) {
								$errors = array_merge( $errors, $title->getUserPermissionsErrors( 'create', $user ) );
							}
							if ( count( $errors ) ) {
								$e .= '*' . $this->msg( 'createteams-create-error-permission' )->params( $key )->text() . "\n";
							} else {
								if ( $title->exists() ) {
									if ( $reqOverwrite ) {
										$status = $page->doEditContent( $content, $this->msg( 'createteams-create-summary-edit' )->text(), EDIT_UPDATE, false, $user, null );
										if ( $status->isOK() ) {
											$log .= '*' . $this->msg( 'createteams-create-log-edit-success' )->params( $key )->text() . "\n";
										} else {
											$e .= '*' . $this->msg( 'createteams-create-error-edit' )->params( $key )->text() . $status->getWikiText() . "\n";
										}
									} else {
										$e .= '*' . $this->msg( 'createteams-create-error-edit-already-exists' )->params( $key )->text() . "\n";
									}
								} else {
									$status = $page->doEditContent( $content, $this->msg( 'createteams-create-summary-creation' )->text(), EDIT_NEW, false, $user, null );
									if ( $status->isOK() ) {
										$log .= '*' . $this->msg( 'createteams-create-log-create-success' )->params( $key )->text() . "\n";
									} else {
										$e .= '*' . $this->msg( 'createteams-create-error-create' )->params( $key )->text() . $status->getWikiText() . "\n";
									}
								}
							}
						}
					}
				}
				$preview .= '|}';
			}
			if ( $e == '' ) {
				$report = $this->msg( 'createteams-create-teams-report-success' )->params( htmlspecialchars( $reqTeam ) )->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'createpreviewbutton' ) ) {
				$output->addHTML( '<h3>' . $this->msg( 'createteams-preview-heading' )->text() . '</h3>' );
				$output->addWikiText( $preview );
				$output->addHTML( '<h3>' . $this->msg( 'createteams-report-heading' )->text() . '</h3>' );
				$output->addWikiText( $report );
			} else {
				$output->addHTML( '<h3>' . $this->msg( 'createteams-report-heading' )->text() . '</h3>' );
				$output->addWikiText( $report );
			}
		}

		$reqHistoricaltemplate = $request->getText( 'historicaltemplate' );
		$reqHistoricalteam = $request->getArray( 'historicalteam' );
		$reqHistoricaltime = $request->getArray( 'historicaltime' );
		if ( is_array( $reqHistoricalteam ) ) {
			foreach ( $reqHistoricalteam as $index => $value ) {
				if ( $value == '' ) {
					unset( $reqHistoricalteam[ $index ] );
				}
			}
			$reqHistoricalteam = array_values( $reqHistoricalteam );
		}
		if ( is_array( $reqHistoricaltime ) ) {
			foreach ( $reqHistoricaltime as $index => $value ) {
				if ( $value == '' ) {
					unset( $reqHistoricaltime[ $index ] );
				}
			}
			$reqHistoricaltime = array_values( $reqHistoricaltime );
		}
		$reqHistoricalteamlength = count( $reqHistoricalteam );
		$reqHistoricaltimelength = count( $reqHistoricaltime );
		$reqHistoricaloverwrite = $request->getBool( 'historicaloverwrite' );

		$output->addHTML( '<h2><span class="mw-headline" id="Create_historical_team_templates">' . $this->msg( 'createteams-create-historicalteam-heading' )->text() . '</span></h2>' );

		$historicalteamform = '<form name="createhistoricalform" id="createhistoricalform" method="post" action="#Create_historical_team_templates">
<table>
	<tr>
		<td> </td>
		<td colspan="2" class="input-helper">' . $this->msg( 'createteams-create-teams-historicaltemplate-info' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicaltemplate">' . $this->msg( 'createteams-create-teams-historicaltemplate-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="historicaltemplate" id="historicaltemplate" value="' . $reqHistoricaltemplate . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicaltemplate-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicalteam">' . $this->msg( 'createteams-create-teams-historicalteam-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="historicalteam[]" value="' . $reqHistoricalteam[ 0 ] . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicalteam-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicaltime">' . $this->msg( 'createteams-create-teams-historicaltime-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="historicaltime[]" value="' . $reqHistoricaltime[ 0 ] . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicaltime-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicalteam">' . $this->msg( 'createteams-create-teams-historicalteam-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="historicalteam[]" value="' . $reqHistoricalteam[ 1 ] . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicalteam-helper' )->text() . '</td>
	</tr>';
		if ( $reqHistoricalteamlength > 2 ) {
			for ( $i = 2; $i < $reqHistoricalteamlength; $i++ ) {
				if ( $reqHistoricaltime[ $i - 1 ] != '' || $reqHistoricalteam[ $i ] != '' ) {
					$historicalteamform .= '<tr>
						<td class="input-label"><label for="historicaltime">' . $this->msg( 'createteams-create-teams-historicaltime-label' )->text() . '</label></td>
						<td class="input-container"><input type="text" name="historicaltime[]" value="' . $reqHistoricaltime[ $i - 1 ] . '"></td>
						<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicaltime-helper' )->text() . '</td>
					</tr>
					<tr>
						<td class="input-label"><label for="historicalteam">' . $this->msg( 'createteams-create-teams-historicalteam-label' )->text() . '</label></td>
						<td class="input-container"><input type="text" name="historicalteam[]" value="' . strtolower( $reqHistoricalteam[ $i ] ) . '"></td>
						<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicalteam-helper' )->text() . '</td>
					</tr>';
				}
			}
		}
		$historicalteamform .= '<tr id="historicaloverwriteline">
		<td class="input-label"><label for="historicaloverwrite">' . $this->msg( 'createteams-create-teams-historicaloverwrite-label' )->text() . '</label></td>
		<td><input type="checkbox" name="historicaloverwrite" id="historicaloverwrite"' . ( $reqHistoricaloverwrite ? ' checked=""' : '' ) . '></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-teams-historicaloverwrite-helper' )->text() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="button" name="createhistoricaladd" id="createhistoricaladd" value="' . $this->msg( 'createteams-create-teams-historicaladd-button' )->text() . '">
			<input type="submit" name="createhistoricalbutton" value="' . $this->msg( 'createteams-create-teams-historicalcreate-button' )->text() . '">
			<input type="submit" name="createhistoricalpreviewbutton" value="' . $this->msg( 'createteams-create-teams-historicalpreview-button' )->text() . '">
		</td>
	</tr>
</table>
</form>';

		$output->addHTML( $historicalteamform );
		if ( $request->getBool( 'createhistoricalbutton' ) || $request->getBool( 'createhistoricalpreviewbutton' ) ) {
			if ( $reqHistoricalteamlength == ( $reqHistoricaltimelength + 1 ) ) {
				if ( $reqHistoricaltemplate == '' ) {
					$e = $this->msg( 'createteams-create-teams-error-team-name-empty' )->text();
				} elseif ( preg_match( '/[a-z]*:\/\//', $reqHistoricaltemplate ) == 1 ) {
					$e = $this->msg( 'createteams-create-teams-error-team-name-url' )->text();
				} else {
					$lcname = strtolower( $reqHistoricaltemplate );

					foreach ( $this->templates as $prefix => $template ) {
						$contents[ "Template:$prefix/$lcname" ] = self::makeHistoricalTeamTemplate( $prefix, $lcname, $reqHistoricalteam, $reqHistoricaltime, $reqHistoricalteamlength, $reqHistoricaltimelength );
					}

					$preview = '{| class="createteams-preview"' . "\n";
					foreach ( $contents as $key => $value ) {
						$title = Title::newFromText( $key );
						if ( $title == null ) {
							$e .= '*' . $this->msg( 'createteams-create-error-bad-title' )->params( $key )->text() . "\n";
						} else {
							$page = WikiPage::factory( $title );
							$content = \ContentHandler::makeContent( $value, $page->getTitle(), CONTENT_MODEL_WIKITEXT );
							if ( $request->getBool( 'createhistoricalpreviewbutton' ) ) {
								$preview .= '|-' . "\n" . '![[' . $key . ']]' . "\n" . '|' . $value . "\n";
							} else {
								$errors = $title->getUserPermissionsErrors( 'edit', $user );
								if ( !$title->exists() ) {
									$errors = array_merge( $errors, $title->getUserPermissionsErrors( 'create', $user ) );
								}
								if ( count( $errors ) ) {
									$e .= '*' . $this->msg( 'createteams-create-error-permission' )->params( $key )->text() . "\n";
								} else {
									if ( $title->exists() ) {
										if ( $reqHistoricaloverwrite ) {
											$status = $page->doeditcontent( $content, $this->msg( 'createteams-create-summary-edit' )->text(), EDIT_UPDATE, false, $user, null );
											if ( $status->isOK() ) {
												$log .= '*' . $this->msg( 'createteams-create-log-edit-success' )->params( $key )->text() . "\n";
											} else {
												$e .= '*' . $this->msg( 'createteams-create-error-edit' )->params( $key )->text() . $status->getWikiText() . "\n";
											}
										} else {
											$e .= '*' . $this->msg( 'createteams-create-error-edit-already-exists' )->params( $key )->text() . "\n";
										}
									} else {
										$status = $page->doeditcontent( $content, $this->msg( 'createteams-create-summary-creation' )->text(), EDIT_NEW, false, $user, null );
										if ( $status->isOK() ) {
											$log .= '*' . $this->msg( 'createteams-create-log-create-success' )->params( $key )->text() . "\n";
										} else {
											$e .= '*' . $this->msg( 'createteams-create-error-create' )->params( $key )->text() . $status->getWikiText() . "\n";
										}
									}
								}
							}
						}
					}
				}
				$preview .= '|}';
			} else {
				$e .= '*' . $this->msg( 'createteams-create-error-historical-number-error' )->text() . "\n";
			}
			if ( $e == '' ) {
				$report = $this->msg( 'createteams-create-teams-report-success' )->params( htmlspecialchars( $reqHistoricaltemplate ) )->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'createhistoricalpreviewbutton' ) ) {
				$output->addHTML( '<h3>' . $this->msg( 'createteams-preview-heading' )->text() . '</h3>' );
				$output->addWikiText( $preview );
				$output->addHTML( '<h3>' . $this->msg( 'createteams-report-heading' )->text() . '</h3>' );
				$output->addWikiText( $report );
			} else {
				$output->addHTML( '<h3>' . $this->msg( 'createteams-report-heading' )->text() . '</h3>' );
				$output->addWikiText( $report );
			}
		}

		// Redirects

		$reqRedirect = $request->getText( 'redirect' );
		$reqRedirectteam = $request->getText( 'redirectteam' );
		$reqRedirectoverwrite = $request->getText( 'redirectoverwrite' );

		$output->addHTML( '<h2><span class="mw-headline" id="Create_redirects">' . $this->msg( 'createteams-create-redirects-heading' )->text() . '</span></h2>' );
		$redirectform = '<form name="redirectform" method="post" action="#Create_redirects">
<table>
	<tr>
		<td class="input-label"><label for="redirect">' . $this->msg( 'createteams-create-redirects-redirect-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="redirect" id="redirect" value="' . $reqRedirect . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-redirects-redirect-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="redirectteam">' . $this->msg( 'createteams-create-redirects-redirectteam-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="redirectteam" id="redirectteam" value="' . $reqRedirectteam . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-redirects-redirectteam-helper' )->text() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="redirectoverwrite">' . $this->msg( 'createteams-create-redirects-redirectoverwrite-label' )->text() . '</label></td>
		<td><input type="checkbox" name="redirectoverwrite" id="redirectoverwrite"' . ( $reqRedirectoverwrite ? ' checked=""' : '' ) . '></td>
		<td class="input-helper">' . $this->msg( 'createteams-create-redirects-redirectoverwrite-helper' )->text() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="submit" name="redirectbutton" value="' . $this->msg( 'createteams-create-redirects-create-button' )->text() . '">
			<input type="submit" name="redirectpreviewbutton" value="' . $this->msg( 'createteams-create-redirects-preview-button' )->text() . '">
		</td>
	</tr>
</table>
</form>';

		$output->addHTML( $redirectform );
		if ( $request->getBool( 'redirectbutton' ) || $request->getBool( 'redirectpreviewbutton' ) ) {
			if ( $reqRedirect == '' || $reqRedirectteam == '' ) {
				$e = $this->msg( 'createteams-create-redirects-error-source-or-destination-empty' )->text();
			} else {
				foreach ( array_keys( $this->templates ) as $prefix ) {
					$contents[ "Template:$prefix/" . strtolower( $reqRedirect ) ] = "#REDIRECT [[Template:$prefix/" . strtolower( $reqRedirectteam ) . "]]";
				}
				$preview = '{| class="createteams-preview"' . "\n";
				foreach ( $contents as $key => $value ) {
					$title = Title::newFromText( $key );
					if ( $title == null ) {
						$e .= '*' . $this->msg( 'createteams-create-error-bad-title' )->params( $key )->text() . "\n";
					} else {
						$page = WikiPage::factory( $title );
						$content = \ContentHandler::makeContent( $value, $page->getTitle(), CONTENT_MODEL_WIKITEXT );
						if ( $request->getBool( 'redirectpreviewbutton' ) ) {
							$preview .= '|-' . "\n" . '![[' . $key . ']]' . "\n" . '|<nowiki>' . $value . '</nowiki>' . "\n";
						} else {
							$errors = $title->getUserPermissionsErrors( 'edit', $user );
							if ( !$title->exists() ) {
								$errors = array_merge( $errors, $title->getUserPermissionsErrors( 'create', $user ) );
							}
							if ( count( $errors ) ) {
								$e .= '*' . $this->msg( 'createteams-create-error-permission' )->params( $key )->text() . "\n";
							} else {
								if ( $title->exists() ) {
									if ( $reqRedirectoverwrite ) {
										$status = $page->doeditcontent( $content, $this->msg( 'createteams-create-summary-edit' )->text(), EDIT_UPDATE, false, $user, null );
										if ( $status->isOK() ) {
											$log .= '*' . $this->msg( 'createteams-create-log-edit-success' )->params( $key )->text() . "\n";
										} else {
											$e .= '*' . $this->msg( 'createteams-create-error-edit' )->params( $key )->text() . $status->getWikiText() . "\n";
										}
									} else {
										$e .= '*' . $this->msg( 'createteams-create-error-edit-already-exists' )->params( $key )->text() . "\n";
									}
								} else {
									$status = $page->doeditcontent( $content, $this->msg( 'createteams-create-summary-creation' )->text(), EDIT_NEW, false, $user, null );
									if ( $status->isOK() ) {
										$log .= '*' . $this->msg( 'createteams-create-log-create-success' )->params( $key )->text() . "\n";
									} else {
										$e .= '*' . $this->msg( 'createteams-create-error-create' )->params( $key )->text() . $status->getWikiText() . "\n";
									}
								}
							}
						}
					}
				}
				$preview .= '|}';
			}
			if ( $e == '' ) {
				$report = $this->msg( 'createteams-create-redirects-report-success' )->params( array( htmlspecialchars( $reqRedirect ), htmlspecialchars( $reqRedirectteam ) ) )->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'redirectpreviewbutton' ) ) {
				$output->addWikiText( '<h3>' . $this->msg( 'createteams-preview-heading' )->text() . '</h3>' );
				$output->addWikiText( $preview );
			} else {
				$output->addWikiText( '<h3>' . $this->msg( 'createteams-report-heading' )->text() . '</h3>' );
				$output->addWikiText( $report );
			}
		}

		// Moves

		if ( $user->isAllowed( 'move' ) ) {
			$reqMove = $request->getText( 'move' );
			$reqMoveto = $request->getText( 'moveto' );

			$output->addHTML( '<h2><span class="mw-headline" id="Move_team_templates">' . $this->msg( 'createteams-move-heading' )->text() . '</span></h2>' );
			$moveform = '<form name="moveform" method="post" action="#Move_team_templates">
	<table>
		<tr>
			<td class="input-label"><label for="move">' . $this->msg( 'createteams-move-label' )->text() . '</label></td>
			<td class="input-container"><input type="text" name="move" id="move" value="' . $reqMove . '"></td>
			<td class="input-helper">' . $this->msg( 'createteams-move-helper' )->text() . '</td>
		</tr>
		<tr>
			<td class="input-label"><label for="moveto">' . $this->msg( 'createteams-move-moveto-label' )->text() . '</label></td>
			<td class="input-container"><input type="text" name="moveto" id="moveto" value="' . $reqMoveto . '"></td>
			<td class="input-helper">' . $this->msg( 'createteams-move-moveto-helper' )->text() . '</td>
		</tr>
		<tr>
			<td> </td>
			<td colspan="2">
				<input type="submit" name="movebutton" value="' . $this->msg( 'createteams-move-button' )->text() . '">
				<input type="submit" name="movepreviewbutton" value="' . $this->msg( 'createteams-move-preview-button' )->text() . '">
			</td>
		</tr>
	</table>
	</form>';

			$output->addHTML( $moveform );
			if ( $request->getBool( 'movebutton' ) || $request->getBool( 'movepreviewbutton' ) ) {
				if ( $reqMove == '' || $reqMoveto == '' ) {
					$e = $this->msg( 'createteams-move-error-source-or-destination-empty' )->text();
				} else {
					$preview = '{| class="createteams-preview"' . "\n";
					foreach ( array_keys( $this->templates ) as $prefix ) {
						$oldTitle = Title::newFromText( "Template:$prefix/" . strtolower( $reqMove ) );
						$newTitle = Title::newFromText( "Template:$prefix/" . strtolower( $reqMoveto ) );
						if ( $oldTitle == null || $newTitle == null ) {
							if ( $oldTitle == null ) {
								$e .= '*' . $this->msg( 'createteams-move-error-bad-title' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->text() . "\n";
							}
							if ( $newTitle == null ) {
								$e .= '*' . $this->msg( 'createteams-move-error-bad-title' )->params( "Template:$prefix/" . strtolower( $reqMoveto ) )->text() . "\n";
							}
						} else {
							if ( $request->getBool( 'movepreviewbutton' ) ) {
								$preview .= '|-' . "\n" . "![[Template:$prefix/" . strtolower( $reqMove ) . ']]' . "\n" . '|&rarr;' . "\n" . "|[[Template:$prefix/" . strtolower( $reqMoveto ) . ']]' . "\n";
							} else {
								$errors = $oldTitle->getUserPermissionsErrors( 'move', $user );
								if ( !$oldTitle->exists() || !$newTitle->exists() ) {
									$errors = array_merge( $errors, $oldTitle->getUserPermissionsErrors( 'create', $user ) );
								}
								if ( count( $errors ) ) {
									$e .= '*' . $this->msg( 'createteams-move-error-permission' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->text() . "\n";
								} else {
									if ( !$oldTitle->exists() ) {
										$e .= '*' . $this->msg( 'createteams-move-error-source-does-not-exists' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->text() . "\n";
									} elseif ( $newTitle->exists() ) {
										$e .= '*' . $this->msg( 'createteams-move-error-target-already-exists' )->params( "Template:$prefix/" . strtolower( $reqMoveto ) )->text() . "\n";
									} else {
										$movePage = new MovePage( $oldTitle, $newTitle );
										$valid = $movePage->isValidMove();
										if ( !$valid->isOK() ) {
											$e .= '*' . $this->msg( 'createteams-move-error-move' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->text() . "\n";
										} else {
											$permStatus = $movePage->checkPermissions( $this->getUser(), $this->msg( 'createteams-move-summary' )->text() );
											if ( !$permStatus->isOK() ) {
												$e .= '*' . $this->msg( 'createteams-move-error-move' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->text() . "\n";
											} else {
												$status = $movePage->move( $user, $this->msg( 'createteams-move-summary' )->text(), false );
												if ( $status->isOK() ) {
													$log .= '*' . $this->msg( 'createteams-move-log-create-success' )->params( "Template:$prefix/" . strtolower( $reqMove ), "Template:$prefix/" . strtolower( $reqMoveto ) )->text() . "\n";
												} else {
													$e .= '*' . $this->msg( 'createteams-move-error-move' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->text() . $status->getWikiText() . "\n";
												}
											}
										}
									}
								}
							}
						}
					}
					$preview .= '|}';
				}
				if ( $e == '' ) {
					$report = $this->msg( 'createteams-move-report-success' )->params( array( htmlspecialchars( $reqMove ), htmlspecialchars( $reqMoveto ) ) )->text();
				} else {
					$report = $e;
				}
				$report .= '<div class="log">' . "\n" . $log . '</div>';
				if ( $request->getBool( 'movepreviewbutton' ) ) {
					$output->addHTML( '<h3>' . $this->msg( 'createteams-preview-heading' )->text() . '</h3>' );
					$output->addWikiText( $preview );
				} else {
					$output->addHTML( '<h3>' . $this->msg( 'createteams-report-heading' )->text() . '</h3>' );
					$output->addWikiText( $report );
				}
			}
		}

		// Views

		$reqView = $request->getText( 'view' );

		$output->addHTML( '<h2><span class="mw-headline" id="View_team_templates">' . $this->msg( 'createteams-view-heading' )->text() . '</span></h2>' );
		$viewform = '<form name="viewform" method="post" action="#View_team_templates">
<table>
	<tr>
		<td class="input-label"><label for="view">' . $this->msg( 'createteams-view-label' )->text() . '</label></td>
		<td class="input-container"><input type="text" name="view" id="view" value="' . $reqView . '"></td>
		<td class="input-helper">' . $this->msg( 'createteams-view-helper' )->text() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="submit" name="viewbutton" value="' . $this->msg( 'createteams-view-button' )->text() . '">
		</td>
	</tr>
</table>
</form>';

		$output->addHTML( $viewform );
		if ( $request->getBool( 'viewbutton' ) ) {
			if ( $reqView == '' ) {
				$e = $this->msg( 'createteams-view-error-source-empty' )->text();
			} else {
				$preview = '{| class="createteams-preview"' . "\n";
				foreach ( array_keys( $this->templates ) as $prefix ) {
					$title = Title::newFromText( "Template:$prefix/" . strtolower( $reqView ) );
					if ( $title == null ) {
						$e = $this->msg( 'createteams-view-error-bad-title' )->params( "Template:$prefix/" . strtolower( $reqView ) )->text();
					} else {
						$page = WikiPage::factory( $title );
						if ( $page !== null && $page->exists() ) {
							$preview .= '|-' . "\n" . '![[Template:' . $prefix . '/' . strtolower( $reqView ) . ']]' . "\n" . '|{{Template:' . $prefix . '/' . strtolower( $reqView ) . '}}' . "\n";
						} else {
							$preview .= '|-' . "\n" . '![[Template:' . $prefix . '/' . strtolower( $reqView ) . ']]' . "\n" . '|' . $this->msg( 'createteams-view-error-does-not-exist' )->text() . "\n";
						}
					}
				}
				$preview .= '|}';
			}
			if ( $e == '' ) {
				$report = $this->msg( 'createteams-view-report-success' )->params( array( htmlspecialchars( $reqView ) ) )->text();
			} else {
				$report = $e;
			}
			$output->addHTML( '<h3>' . $this->msg( 'createteams-preview-heading' )->text() . '</h3>' );
			$output->addWikiText( $report );
			$output->addWikiText( $preview );
		}

		// Deletions

		if ( $user->isAllowed( 'delete' ) ) {
			// stuff only admins are allowed to see
			$reqDeletepreviewteam = $request->getText( 'deletepreviewteam' );
			$reqDeleteteam = $request->getText( 'deleteteam' );
			$output->addHTML( '<h2><span class="mw-headline" id="Delete_team_templates">' . $this->msg( 'createteams-delete-teams-heading' )->text() . '</span></h2>' );
			$deleteForm = '<form name="delete-form" method="post" action="#Delete_team_templates">
<table>
	<tr>
		<td class="input-label"><label for="deletepreviewteam">' . $this->msg( 'createteams-delete-teams-deletepreviewteam-label' )->text() . '</label></td>
		<td class="input-container">
			<input type="text" name="deletepreviewteam" id="deletepreviewteam" value="' . $reqDeletepreviewteam . '">
		</td>
		<td class="input-helper">' . $this->msg( 'createteams-delete-teams-deletepreviewteam-helper' )->text() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td>
			<input type="submit" name="deletepreviewbutton" value="' . $this->msg( 'createteams-delete-teams-preview-button' )->text() . '">
		</td>
		<td class="input-helper">' . $this->msg( 'createteams-delete-teams-deletebutton-helper' )->text() . '</td>
	</tr>
</table>
</form>';
			$output->addHTML( $deleteForm );

			if ( $request->getBool( 'deletebutton' ) ) {
				if ( $reqDeleteteam == '' ) {
					$e = $this->msg( 'createteams-delete-teams-error-team-name-empty' )->text();
				} else {
					foreach ( array_keys( $this->templates ) as $prefix ) {
						$deltemplate[ $prefix ] = "Template:$prefix/" . strtolower( $reqDeleteteam );
					}
					foreach ( $deltemplate as $value ) {
						$title = Title::newFromText( $value );
						if ( $title == null ) {
							$e = $this->msg( 'createteams-delete-error-bad-title' )->params( $value )->text();
						} else {
							$page = WikiPage::factory( $title );
							$id = $page->getId();
							$errors = $title->getUserPermissionsErrors( 'delete', $user );
							if ( count( $errors ) ) {
								$e .= '*' . $this->msg( 'createteams-delete-error-permission' )->params( $value )->text() . "\n";
							} elseif ( !$title->exists() ) {
								$e .= '*' . $this->msg( 'createteams-delete-error-does-not-exist' )->params( $value )->text() . "\n";
							} else {
								if ( $page->doDeleteArticle( $this->msg( 'createteams-delete-summary-deletion' )->text(), false, $id, '', $user ) ) {
									$log .= '*' . $this->msg( 'createteams-delete-log-deletion-success' )->params( $value )->text() . "\n";
								} else {
									$e .= '*' . $this->msg( 'createteams-delete-error-deletion' )->params( $value )->text() . "\n";
								}
							}
						}
					}
				}
				if ( $e == '' ) {
					$report = $report = $this->msg( 'createteams-delete-teams-report-success' )->params( htmlspecialchars( $reqDeleteteam ) )->text();
				} else {
					$report = $e;
				}
				$report .= '<div class="log">' . "\n" . $log . '</div>';

				$output->addWikiText( '===' . $this->msg( 'createteams-report-heading' )->text() . '===' );
				$output->addWikiText( $report );
			} elseif ( $request->getBool( 'deletepreviewbutton' ) ) {
				if ( $reqDeletepreviewteam == '' ) {
					$preview = $this->msg( 'createteams-delete-teams-error-team-name-empty' )->text();
				} else {
					foreach ( array_keys( $this->templates ) as $prefix ) {
						$deltemplate[ $prefix ] = "Template:$prefix/" . strtolower( $reqDeletepreviewteam );
					}
					foreach ( $deltemplate as $value ) {
						$title = Title::newFromText( $value );
						if ( $title == null ) {
							$e = $this->msg( 'createteams-delete-error-bad-title' )->params( "Template:$prefix/" . strtolower( $reqDeletepreviewteam ) )->text();
						} else {
							$page = WikiPage::factory( $title );
							$id = $page->getId();
							$errors = $title->getUserPermissionsErrors( 'delete', $user );
							if ( count( $errors ) ) {
								$preview .= '*' . $this->msg( 'createteams-delete-error-permission' )->params( $value )->text() . "\n";
							} elseif ( !$title->exists() ) {
								$preview .= '*' . $this->msg( 'createteams-delete-error-does-not-exist' )->params( $value )->text() . "\n";
							} else {
								$preview .= '*' . $this->msg( 'createteams-delete-teams-preview-deletion' )->params( $value )->text() . "\n";
							}
						}
					}
				}
				$output->addHTML( '<h3>' . $this->msg( 'createteams-preview-heading' )->text() . '</h3>' );
				$output->addWikiText( $preview );

				if ( $reqDeletepreviewteam != '' ) {
					$deleteConfirmForm = '<form name="delete-confirm-form" method="post" action="#Delete_team_templates">
	<input type="text" name="deleteteam" value="' . $reqDeletepreviewteam . '" readonly>
	<input type="submit" name="deletebutton" value="' . $this->msg( 'createteams-delete-teams-delete-button' )->text() . '">
	<p class="warning">' . $this->msg( 'createteams-delete-teams-warning-deletion' )->params( $value )->text() . '</p>
</form>';
					$output->addHTML( '<h3>' . $this->msg( 'createteams-delete-teams-confirm-deletion-heading' )->text() . '</h3>' );
					$output->addHTML( $deleteConfirmForm );
				}
			}
		}
	}

}
