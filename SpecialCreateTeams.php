<?php

class SpecialCreateTeams extends SpecialPage
{
	private $templates;

	function __construct()
	{
		parent::__construct( 'CreateTeams', 'edit' );
	}

	function getGroupName() {
		return 'liquipedia';
	}

	function getTemplates()
	{
		$json = json_decode( 
			wfMessage( 'createteams-templates.json' )->inContentLanguage()->plain(), 
			true
		);
		$this->templates = $json['templates'];
	}

	function makeTeamTemplate( $template, $vars ) {
		$str = $template['wikitext'];
		foreach ( $vars as $tag => $replacement ) {
			$str = str_replace( '{$' . $tag. '$}', $replacement, $str );
		}
		$str .= '<noinclude>[[Category:' . $template['category'] . ']]</noinclude>';
		return $str;
	}

	function makeHistoricalTeamTemplate( $prefix, $reqHistoricaltemplate, $reqHistoricalteam, $reqHistoricaltime, $reqHistoricalteamlength, $reqHistoricaltimelength ) {
		$str = '{{#vardefine:' . $reqHistoricaltemplate . 'time|{{#time:U|{{{1|{{#var:date|{{#var:edate|{{#var:sdate|{{CURRENTYEAR}}-{{CURRENTMONTH}}-{{CURRENTDAY2}}}}}}}}}}}}}}}<!-- this variable name needs to be unique --><!--' . "\n";
		$str .= '-->{{#ifexpr:{{#time:U|' . $reqHistoricaltime[0] . '}} < {{#var:' . $reqHistoricaltemplate . 'time}}|{{' . $prefix . '/' . strtolower( $reqHistoricalteam[0] ) . '}}}}<!--' . "\n";
			if( $reqHistoricalteamlength > 2) {
			for($i = 1; $i < $reqHistoricalteamlength - 1; $i++) {
				if( $reqHistoricaltime[$i - 1] != '' || $reqHistoricalteam[$i] != '' ) {
					$str .= '-->{{#ifexpr:{{#time:U|' . $reqHistoricaltime[$i - 1] . '}} >= {{#var:' . $reqHistoricaltemplate . 'time}} AND {{#time:U|' . $reqHistoricaltime[$i] . '}} < {{#var:' . $reqHistoricaltemplate . 'time}}|{{' . $prefix . '/' . strtolower( $reqHistoricalteam[$i] ) . '}}}}<!--' . "\n";
				}
			}
		}
		
		$str .= '-->{{#ifexpr:{{#time:U|' . $reqHistoricaltime[$reqHistoricaltimelength - 1] . '}} >= {{#var:' . $reqHistoricaltemplate . 'time}}|{{' . $prefix . '/' . strtolower( $reqHistoricalteam[$reqHistoricalteamlength - 1] ) . '}}}}<!--' . "\n";
		$str .= '--><noinclude>[[Category:Historical ' . $prefix . ' team template]]</noinclude>';
		return $str;
	}

	function execute( $par ) {
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}
		global $wgUser;
		$output = $this->getOutput();
		$this->setHeaders();
		$output->addModules( 'ext.createteams.SpecialPage' );
		$report = ''; $e = ''; $log = ''; $preview = '';
		$this->getTemplates();

		$output->addHTML( '<div id="toc" class="toc"><div id="toctitle"><h2>Contents</h2></div>
			<ul>
			<li class="toclevel-1 tocsection-1"><a href="#Create_team_templates"><span class="tocnumber">1</span> <span class="toctext">' . wfMessage( 'createteams-create-teams-heading' )->inContentLanguage()->text() . '</span></a></li>
			<li class="toclevel-1 tocsection-2"><a href="#Create_historical_team_templates"><span class="tocnumber">2</span> <span class="toctext">' . wfMessage( 'createteams-create-historicalteam-heading' )->inContentLanguage()->text() . '</span></a></li>
			<li class="toclevel-1 tocsection-3"><a href="#Create_redirects"><span class="tocnumber">3</span> <span class="toctext">' . wfMessage( 'createteams-create-redirects-heading' )->inContentLanguage()->text() . '</span></a></li>
			<li class="toclevel-1 tocsection-4"><a href="#Move_team_templates"><span class="tocnumber">4</span> <span class="toctext">' . wfMessage( 'createteams-move-heading' )->inContentLanguage()->text() . '</span></a></li>' );
			if ( $wgUser->isAllowed( 'delete' ) ) {
				$output->addHTML( '<li class="toclevel-1 tocsection-5"><a href="#Delete_team_templates"><span class="tocnumber">5</span> <span class="toctext">' . wfMessage( 'createteams-delete-teams-heading' )->inContentLanguage()->text() . '</span></a></li>' );
			}
		$output->addHTML( '</ul>
		</div>' );

		# Get request data from, e.g.

		# Do stuff
		# ...
		//$wgOut->setPageTitle( "create team templates" );
		$output->addWikiText( '==' . wfMessage( 'createteams-create-teams-heading' )->inContentLanguage()->text() . '==' );
		$output->addWikiText( wfMessage( 'createteams-create-teams-desc' )->inContentLanguage()->text() );

		global $wgUploadNavigationUrl;
		if($wgUploadNavigationUrl) {
			$uploadMessage = wfMessage( 'createteams-create-teams-image-helper-remote' )->params( $wgUploadNavigationUrl )->inContentLanguage()->parse();
		} else {
			$uploadMessage = wfMessage( 'createteams-create-teams-image-helper' )->inContentLanguage()->parse();
		}
		$request = $this->getRequest();

		$reqTeam      = $request->getText( 'team' );
		$reqTeamslug  = $request->getText( 'teamslug' );
		$reqPagetitle = $request->getText( 'pagetitle' );
		$reqImage     = $request->getText( 'image' );
		$reqTeamshort = $request->getText( 'teamshort' );
		$reqOverwrite = $request->getBool( 'overwrite' );

		$output->addHTML( '<form name="createform" id="createform" method="post">
<table>
	<tr>
		<td class="input-label"><label for="team">' . wfMessage( 'createteams-create-teams-team-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="team" id="team" value="' . $reqTeam . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-team-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="team">' . wfMessage( 'createteams-create-teams-team-slug-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="teamslug" id="teamslug" value="' . $reqTeamslug . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-team-slug-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="pagetitle">' . wfMessage( 'createteams-create-teams-pagetitle-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="pagetitle" id="pagetitle" value="' . $reqPagetitle . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-pagetitle-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="teamshort">' . wfMessage( 'createteams-create-teams-teamshort-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="teamshort" id="teamshort" value="' . $reqTeamshort . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-teamshort-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="image">' . wfMessage( 'createteams-create-teams-image-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="image" id="image" value="' . $reqImage . '"></td>
		<td class="input-helper">' . $uploadMessage . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="overwrite">' . wfMessage( 'createteams-create-teams-overwrite-label' )->inContentLanguage()->parse() . '</label></td>
		<td><input type="checkbox" name="overwrite" id="overwrite"' . ( $reqOverwrite ? ' checked=""' : '' ) . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-overwrite-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="submit" name="createbutton" value="' . wfMessage( 'createteams-create-teams-create-button' )->inContentLanguage()->text() . '"> 
			<input type="submit" name="createpreviewbutton" value="' . wfMessage( 'createteams-create-teams-preview-button' )->inContentLanguage()->text() . '">
		</td>
	</tr>
</table>
</form>');

		if ( $request->getBool( 'createbutton' ) || $request->getBool( 'createpreviewbutton' ) ) {
			if ( $reqImage == '' ) {
				$reqImage = $reqTeam . 'logo std.png';
			}
			$wikiimage = 'File:' . $reqImage;
			$imagetitle = Title::newFromText( $wikiimage );
			$imagewikipage = new WikiFilePage( $imagetitle );
			$imagefile = $imagewikipage->getFile();
			$test = $imagefile->exists();

			if ( $reqTeam == '' ) {
				$e = wfMessage( 'createteams-create-teams-error-team-name-empty' )->inContentLanguage()->text();
			} else if ( preg_match( '/[a-z]*:\/\//', $reqTeam ) == 1 ) {
				$e = wfMessage( 'createteams-create-teams-error-team-name-url' )->inContentLanguage()->text();
			} else if ( $imagefile->exists() == false ) {
				$e = wfMessage( 'createteams-create-teams-error-image-not-found' )->inContentLanguage()->text();
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
					$vars['namewithlink'] = '[[' . $reqPagetitle . '|' . $reqTeam . ']]';
					$vars['link'] = $reqPagetitle;
				} else {
					$vars['namewithlink'] = '[[' . $reqTeam . ']]';
					$vars['link'] = $reqTeam;
				}
				if ( $vars['image'] == '' ) {
					$vars['image'] = $reqTeam . 'logo_std.png';
				}

				foreach ( $this->templates as $prefix => $template ) {
					$contents["Template:$prefix/$lcname"] = self::makeTeamTemplate( $template, $vars );
				}

				$preview = '{| class="createteams-preview"' . "\n";
				foreach ( $contents as $key => $value ) {
					$title   = Title::newFromText( $key );
					$page    = WikiPage::factory( $title );
					$content = \ContentHandler::makeContent( $value, $page->getTitle(), CONTENT_MODEL_WIKITEXT );
					if ( $request->getBool( 'createpreviewbutton' ) ) {
						$preview .= '|-' . "\n" . '![[' . $key . ']]' . "\n" . '|' . $value . "\n";
					} else {
						$errors = $title->getUserPermissionsErrors( 'edit', $wgUser );
						if ( !$title->exists() ) {
							$errors = array_merge( $errors, $title->getUserPermissionsErrors( 'create', $wgUser ) );
						}
						if ( count( $errors ) ) {
							$e .= '*' . wfMessage( 'createteams-create-error-permission' )->params( $key )->inContentLanguage()->text() . "\n";
						} else {
							if ( $title->exists() ) {
								if ( $reqOverwrite ) {
									$status = $page->doeditcontent( $content, wfMessage( 'createteams-create-summary-edit' )->inContentLanguage()->text(), EDIT_UPDATE, false, $wgUser, null );
									if ( $status->isOK() ) {
										$log .= '*' . wfMessage( 'createteams-create-log-edit-success' )->params( $key )->inContentLanguage()->text() . "\n";
									} else {
										$e .= '*' . wfMessage( 'createteams-create-error-edit' )->params( $key )->inContentLanguage()->text() . $status->getWikiText() . "\n";
									}
								} else {
									$e .= '*' . wfMessage( 'createteams-create-error-edit-already-exists' )->params( $key )->inContentLanguage()->text() . "\n";
								}
							} else {
								$status = $page->doeditcontent( $content, wfMessage( 'createteams-create-summary-creation' )->inContentLanguage()->text(), EDIT_NEW, false, $wgUser, null );
								if ( $status->isOK() ) {
									$log .= '*' . wfMessage( 'createteams-create-log-create-success' )->params( $key )->inContentLanguage()->text() . "\n";
								} else {
									$e .= '*' . wfMessage( 'createteams-create-error-create' )->params( $key )->inContentLanguage()->text() . $status->getWikiText() . "\n";
								}
							}
						}
					}
				}
				$preview .= '|}';
			}
			if ( $e == '' ) {
				$report = wfMessage( 'createteams-create-teams-report-success' )
					->params( htmlspecialchars($reqTeam) )->inContentLanguage()->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'createpreviewbutton' ) ) {
				$output->addWikiText( '===' . wfMessage( 'createteams-preview-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $preview );
				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			} else {
				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			}
		}

		$reqHistoricaltemplate		= $request->getText( 'historicaltemplate' );
		$reqHistoricalteam		= $request->getArray( 'historicalteam' );
		$reqHistoricaltime		= $request->getArray( 'historicaltime' );
		if( is_array( $reqHistoricalteam ) ) {
			foreach( $reqHistoricalteam as $index => $value ) {
				if( $value == '') {
					unset( $reqHistoricalteam[$index] );
				}
			}
			$reqHistoricalteam = array_values( $reqHistoricalteam );
		}
		if( is_array( $reqHistoricaltime ) ) {
			foreach( $reqHistoricaltime as $index => $value ) {
				if( $value == '') {
					unset( $reqHistoricaltime[$index] );
				}
			}
			$reqHistoricaltime = array_values( $reqHistoricaltime );
		}
		$reqHistoricalteamlength	= count( $reqHistoricalteam );
		$reqHistoricaltimelength	= count( $reqHistoricaltime );
		$reqHistoricaloverwrite		= $request->getBool( 'historicaloverwrite' );

		$output->addWikiText( '==' . wfMessage( 'createteams-create-historicalteam-heading' )->inContentLanguage()->text() . '==' );

		$historicalteamform = '<form name="createhistoricalform" id="createhistoricalform" method="post">
<table>
	<tr>
		<td> </td>
		<td colspan="2" class="input-helper">' . wfMessage( 'createteams-create-teams-historicaltemplate-info' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicaltemplate">' . wfMessage( 'createteams-create-teams-historicaltemplate-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="historicaltemplate" id="historicaltemplate" value="' . $reqHistoricaltemplate . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicaltemplate-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicalteam">' . wfMessage( 'createteams-create-teams-historicalteam-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="historicalteam[]" value="' . $reqHistoricalteam[0] . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicalteam-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicaltime">' . wfMessage( 'createteams-create-teams-historicaltime-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="historicaltime[]" value="' . $reqHistoricaltime[0] . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicaltime-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="historicalteam">' . wfMessage( 'createteams-create-teams-historicalteam-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="historicalteam[]" value="' . $reqHistoricalteam[1] . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicalteam-helper' )->inContentLanguage()->parse() . '</td>
	</tr>';
		if( $reqHistoricalteamlength > 2) {
			for($i = 2; $i < $reqHistoricalteamlength; $i++) {
				if( $reqHistoricaltime[$i - 1] != '' || $reqHistoricalteam[$i] != '' ) {
					$historicalteamform .= '<tr>
						<td class="input-label"><label for="historicaltime">' . wfMessage( 'createteams-create-teams-historicaltime-label' )->inContentLanguage()->parse() . '</label></td>
						<td class="input-container"><input type="text" name="historicaltime[]" value="' . $reqHistoricaltime[$i - 1] . '"></td>
						<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicaltime-helper' )->inContentLanguage()->parse() . '</td>
					</tr>
					<tr>
						<td class="input-label"><label for="historicalteam">' . wfMessage( 'createteams-create-teams-historicalteam-label' )->inContentLanguage()->parse() . '</label></td>
						<td class="input-container"><input type="text" name="historicalteam[]" value="' . strtolower( $reqHistoricalteam[$i] ) . '"></td>
						<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicalteam-helper' )->inContentLanguage()->parse() . '</td>
					</tr>';
				}
			}
		}
		$historicalteamform .= '<tr id="historicaloverwriteline">
		<td class="input-label"><label for="historicaloverwrite">' . wfMessage( 'createteams-create-teams-historicaloverwrite-label' )->inContentLanguage()->parse() . '</label></td>
		<td><input type="checkbox" name="historicaloverwrite" id="historicaloverwrite"' . ( $reqHistoricaloverwrite ? ' checked=""' : '' ) . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-teams-historicaloverwrite-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="button" name="createhistoricaladd" id="createhistoricaladd" value="' . wfMessage( 'createteams-create-teams-historicaladd-button' )->inContentLanguage()->text() . '"> 
			<input type="submit" name="createhistoricalbutton" value="' . wfMessage( 'createteams-create-teams-historicalcreate-button' )->inContentLanguage()->text() . '"> 
			<input type="submit" name="createhistoricalpreviewbutton" value="' . wfMessage( 'createteams-create-teams-historicalpreview-button' )->inContentLanguage()->text() . '">
		</td>
	</tr>
</table>
</form>';

		$output->addHTML( $historicalteamform );
		if ( $request->getBool( 'createhistoricalbutton' ) || $request->getBool( 'createhistoricalpreviewbutton' ) ) {
			if( $reqHistoricalteamlength == ( $reqHistoricaltimelength + 1 ) ) {
				if ( $reqHistoricaltemplate == '' ) {
					$e = wfMessage( 'createteams-create-teams-error-team-name-empty' )->inContentLanguage()->text();
				} else if ( preg_match( '/[a-z]*:\/\//', $reqHistoricaltemplate ) == 1 ) {
					$e = wfMessage( 'createteams-create-teams-error-team-name-url' )->inContentLanguage()->text();
				} else {
					$lcname = strtolower( $reqHistoricaltemplate );

					foreach ( $this->templates as $prefix => $template ) {
						$contents["Template:$prefix/$lcname"] = self::makeHistoricalTeamTemplate( $prefix, $lcname, $reqHistoricalteam, $reqHistoricaltime, $reqHistoricalteamlength, $reqHistoricaltimelength );
					}

					$preview = '{| class="createteams-preview"' . "\n";
					foreach ( $contents as $key => $value ) {
						$title   = Title::newFromText( $key );
						$page    = WikiPage::factory( $title );
						$content = \ContentHandler::makeContent( $value, $page->getTitle(), CONTENT_MODEL_WIKITEXT );
						if ( $request->getBool( 'createhistoricalpreviewbutton' ) ) {
							$preview .= '|-' . "\n" . '![[' . $key . ']]' . "\n" . '|' . $value . "\n";
						} else {
							$errors = $title->getUserPermissionsErrors( 'edit', $wgUser );
							if ( !$title->exists() ) {
								$errors = array_merge( $errors, $title->getUserPermissionsErrors( 'create', $wgUser ) );
							}
							if ( count( $errors ) ) {
								$e .= '*' . wfMessage( 'createteams-create-error-permission' )->params( $key )->inContentLanguage()->text() . "\n";
							} else {
								if ( $title->exists() ) {
									if ( $reqHistoricaloverwrite ) {
										$status = $page->doeditcontent( $content, wfMessage( 'createteams-create-summary-edit' )->inContentLanguage()->text(), EDIT_UPDATE, false, $wgUser, null );
										if ( $status->isOK() ) {
											$log .= '*' . wfMessage( 'createteams-create-log-edit-success' )->params( $key )->inContentLanguage()->text() . "\n";
										} else {
											$e .= '*' . wfMessage( 'createteams-create-error-edit' )->params( $key )->inContentLanguage()->text() . $status->getWikiText() . "\n";
										}
									} else {
										$e .= '*' . wfMessage( 'createteams-create-error-edit-already-exists' )->params( $key )->inContentLanguage()->text() . "\n";
									}
								} else {
									$status = $page->doeditcontent( $content, wfMessage( 'createteams-create-summary-creation' )->inContentLanguage()->text(), EDIT_NEW, false, $wgUser, null );
									if ( $status->isOK() ) {
										$log .= '*' . wfMessage( 'createteams-create-log-create-success' )->params( $key )->inContentLanguage()->text() . "\n";
									} else {
										$e .= '*' . wfMessage( 'createteams-create-error-create' )->params( $key )->inContentLanguage()->text() . $status->getWikiText() . "\n";
									}
								}
							}
						}
					}
				}
				$preview .= '|}';
			} else {
				$e .= '*' . wfMessage( 'createteams-create-error-historical-number-error' )->inContentLanguage()->text() . "\n";
			}
			if ( $e == '' ) {
				$report = wfMessage( 'createteams-create-teams-report-success' )
					->params( htmlspecialchars($reqHistoricaltemplate) )->inContentLanguage()->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'createhistoricalpreviewbutton' ) ) {
				$output->addWikiText( '===' . wfMessage( 'createteams-preview-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $preview );
				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			} else {
				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			}
		}

		// Redirects

		$reqRedirect 		= $request->getText( 'redirect' );
		$reqRedirectteam 	= $request->getText( 'redirectteam' );
		$reqRedirectoverwrite 	= $request->getText( 'redirectoverwrite' );

		$output->addWikiText( '==' . wfMessage( 'createteams-create-redirects-heading' )->inContentLanguage()->text() . '==' );
		$redirectform = '<form name="redirectform" method="post">
<table>
	<tr>
		<td class="input-label"><label for="redirect">' . wfMessage( 'createteams-create-redirects-redirect-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="redirect" id="redirect" value="' . $reqRedirect . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-redirects-redirect-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="redirectteam">' . wfMessage( 'createteams-create-redirects-redirectteam-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="redirectteam" id="redirectteam" value="' . $reqRedirectteam . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-redirects-redirectteam-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="redirectoverwrite">' . wfMessage( 'createteams-create-redirects-redirectoverwrite-label' )->inContentLanguage()->parse() . '</label></td>
		<td><input type="checkbox" name="redirectoverwrite" id="redirectoverwrite"' . ( $reqRedirectoverwrite ? ' checked=""' : '' ) . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-create-redirects-redirectoverwrite-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="submit" name="redirectbutton" value="' . wfMessage( 'createteams-create-redirects-create-button' )->inContentLanguage()->text() . '"> 
			<input type="submit" name="redirectpreviewbutton" value="' . wfMessage( 'createteams-create-redirects-preview-button' )->inContentLanguage()->text() . '">
		</td>
	</tr>
</table>
</form>';

		$output->addHTML( $redirectform );
		if ( $request->getBool( 'redirectbutton' ) || $request->getBool( 'redirectpreviewbutton' ) ) {
			if ( $reqRedirect == '' || $reqRedirectteam == '' ) {
				$e = wfMessage( 'createteams-create-redirects-error-source-or-destination-empty' )->inContentLanguage()->text();
			} else {
				foreach ( array_keys( $this->templates ) as $prefix ) {
					$contents[ "Template:$prefix/" . strtolower( $reqRedirect ) ] = "#REDIRECT [[Template:$prefix/" . strtolower( $reqRedirectteam ) . "]]";
				}
				$preview = '{| class="createteams-preview"' . "\n";
				foreach ( $contents as $key => $value ) {
					$title = Title::newFromText( $key );
					$page  = WikiPage::factory( $title );
					$content = \ContentHandler::makeContent( $value, $page->getTitle(), CONTENT_MODEL_WIKITEXT );
					if ( $request->getBool( 'redirectpreviewbutton' ) ) {
						$preview .= '|-' . "\n" . '![[' . $key . ']]' . "\n" . '|<nowiki>' . $value . '</nowiki>' . "\n";
					} else {
						$errors = $title->getUserPermissionsErrors( 'edit', $wgUser );
						if ( !$title->exists() ) {
							$errors = array_merge( $errors, $title->getUserPermissionsErrors( 'create', $wgUser ) );
						}
						if ( count( $errors ) ) {
							$e .= '*' . wfMessage( 'createteams-create-error-permission' )->params( $key )->inContentLanguage()->text() . "\n";
						} else {
							if ( $title->exists() ) {
								if ( $reqRedirectoverwrite ) {
									$status = $page->doeditcontent( $content, wfMessage( 'createteams-create-summary-edit' )->inContentLanguage()->text(), EDIT_UPDATE, false, $wgUser, null );
									if ( $status->isOK() ) {
										$log .= '*' . wfMessage( 'createteams-create-log-edit-success' )->params( $key )->inContentLanguage()->text() . "\n";
									} else {
										$e .= '*' . wfMessage( 'createteams-create-error-edit' )->params( $key )->inContentLanguage()->text() . $status->getWikiText() . "\n";
									}
								} else {
									$e .= '*' . wfMessage( 'createteams-create-error-edit-already-exists' )->params( $key )->inContentLanguage()->text() . "\n";
								}
							} else {
								$status = $page->doeditcontent( $content, wfMessage( 'createteams-create-summary-creation' )->inContentLanguage()->text(), EDIT_NEW, false, $wgUser, null );
								if ( $status->isOK() ) {
									$log .= '*' . wfMessage( 'createteams-create-log-create-success' )->params( $key )->inContentLanguage()->text() . "\n";
								} else {
									$e .= '*' . wfMessage( 'createteams-create-error-create' )->params( $key )->inContentLanguage()->text() . $status->getWikiText() . "\n";
								}
							}
						}
					}
				}
				$preview .= '|}';
			}
			if ( $e == '' ) {
				$report = wfMessage( 'createteams-create-redirects-report-success' )
					->params( array( htmlspecialchars( $reqRedirect ), htmlspecialchars( $reqRedirectteam ) ) )
					->inContentLanguage()->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'redirectpreviewbutton' ) ) {
				$output->addWikiText( '===' . wfMessage( 'createteams-preview-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $preview );
			} else {
				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			}
		}

		// Moves

		$reqMove 	= $request->getText( 'move' );
		$reqMoveto	= $request->getText( 'moveto' );

		$output->addWikiText( '==' . wfMessage( 'createteams-move-heading' )->inContentLanguage()->text() . '==' );
		$moveform = '<form name="moveform" method="post">
<table>
	<tr>
		<td class="input-label"><label for="move">' . wfMessage( 'createteams-move-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="move" id="move" value="' . $reqMove . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-move-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td class="input-label"><label for="moveto">' . wfMessage( 'createteams-move-moveto-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container"><input type="text" name="moveto" id="moveto" value="' . $reqMoveto . '"></td>
		<td class="input-helper">' . wfMessage( 'createteams-move-moveto-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td colspan="2">
			<input type="submit" name="movebutton" value="' . wfMessage( 'createteams-move-button' )->inContentLanguage()->text() . '"> 
			<input type="submit" name="movepreviewbutton" value="' . wfMessage( 'createteams-move-preview-button' )->inContentLanguage()->text() . '">
		</td>
	</tr>
</table>
</form>';

		$output->addHTML( $moveform );
		if ( $request->getBool( 'movebutton' ) || $request->getBool( 'movepreviewbutton' ) ) {
			if ( $reqMove == '' || $reqMoveto == '' ) {
				$e = wfMessage( 'createteams-move-error-source-or-destination-empty' )->inContentLanguage()->text();
			} else {
				$preview = '{| class="createteams-preview"' . "\n";
				foreach ( array_keys( $this->templates ) as $prefix ) {
					$oldTitle = Title::newFromText( "Template:$prefix/" . strtolower( $reqMove ) );
					$newTitle = Title::newFromText( "Template:$prefix/" . strtolower( $reqMoveto ) );
					if ( $request->getBool( 'movepreviewbutton' ) ) {
						$preview .= '|-' . "\n" . "![[Template:$prefix/" . strtolower( $reqMove ) . ']]' . "\n" . '|&rarr;' . "\n" . "|[[Template:$prefix/" . strtolower( $reqMoveto ) . ']]' . "\n";
					} else {
						$errors = $oldTitle->getUserPermissionsErrors( 'move', $wgUser );
						if ( !$oldTitle->exists() || !$newTitle->exists() ) {
							$errors = array_merge( $errors, $oldTitle->getUserPermissionsErrors( 'create', $wgUser ) );
						}
						if ( count( $errors ) ) {
							$e .= '*' . wfMessage( 'createteams-move-error-permission' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->inContentLanguage()->text() . "\n";
						} else {
							if ( !$oldTitle->exists() ) {
								$e .= '*' . wfMessage( 'createteams-move-error-source-does-not-exists' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->inContentLanguage()->text() . "\n";
							} elseif ( $newTitle->exists() ) {
								$e .= '*' . wfMessage( 'createteams-move-error-target-already-exists' )->params( "Template:$prefix/" . strtolower( $reqMoveto ) )->inContentLanguage()->text() . "\n";
							} else {
								$movePage = new MovePage( $oldTitle, $newTitle );
								$status = $movePage->move( $wgUser, wfMessage( 'createteams-move-summary' )->inContentLanguage()->text(), false );
								if ( $status->isOK() ) {
									$log .= '*' . wfMessage( 'createteams-move-log-create-success' )->params( "Template:$prefix/" . strtolower( $reqMove ), "Template:$prefix/" . strtolower( $reqMoveto ) )->inContentLanguage()->text() . "\n";
								} else {
									$e .= '*' . wfMessage( 'createteams-move-error-move' )->params( "Template:$prefix/" . strtolower( $reqMove ) )->inContentLanguage()->text() . $status->getWikiText() . "\n";
								}
							}
						}
					}
				}
				$preview .= '|}';
			}
			if ( $e == '' ) {
				$report = wfMessage( 'createteams-move-report-success' )
					->params( array( htmlspecialchars( $reqMove ), htmlspecialchars( $reqMoveto ) ) )
					->inContentLanguage()->text();
			} else {
				$report = $e;
			}
			$report .= '<div class="log">' . "\n" . $log . '</div>';
			if ( $request->getBool( 'movepreviewbutton' ) ) {
				$output->addWikiText( '===' . wfMessage( 'createteams-preview-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $preview );
			} else {
				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			}
		}

		// Deletions

		if ( $wgUser->isAllowed( 'delete' ) ) {
			// stuff only admins are allowed to see
			$reqDeletepreviewteam = $request->getText( 'deletepreviewteam' );
			$reqDeleteteam = $request->getText( 'deleteteam' );
			$output->addWikiText( '==' . wfMessage( 'createteams-delete-teams-heading' )->inContentLanguage()->text() . '==' );
			$deleteForm = '<form name="delete-form" method="post">
<table>
	<tr>
		<td class="input-label"><label for="deletepreviewteam">' . wfMessage( 'createteams-delete-teams-deletepreviewteam-label' )->inContentLanguage()->parse() . '</label></td>
		<td class="input-container">
			<input type="text" name="deletepreviewteam" id="deletepreviewteam" value="' . $reqDeletepreviewteam . '">
		</td>
		<td class="input-helper">' . wfMessage( 'createteams-delete-teams-deletepreviewteam-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
	<tr>
		<td> </td>
		<td>
			<input type="submit" name="deletepreviewbutton" value="' . wfMessage( 'createteams-delete-teams-preview-button' )->inContentLanguage()->text() . '">
		</td>
		<td class="input-helper">' . wfMessage( 'createteams-delete-teams-deletebutton-helper' )->inContentLanguage()->parse() . '</td>
	</tr>
</table>
</form>';
			$output->addHTML( $deleteForm );

			if ( $request->getBool( 'deletebutton' ) ) {
				if ( $reqDeleteteam == '' ) {
					$e = wfMessage( 'createteams-delete-teams-error-team-name-empty' )->inContentLanguage()->text();
				} else {
					foreach ( array_keys( $this->templates ) as $prefix ) {
						$deltemplate[ $prefix ] = "Template:$prefix/" . strtolower( $reqDeleteteam );
					}
					foreach ( $deltemplate as $value ) {
						$title = Title::newFromText( $value );
						$page  = WikiPage::factory( $title );
						$id    = $page->getId();
						$errors = $title->getUserPermissionsErrors( 'delete', $wgUser );
						if (count($errors)) {
							$e .= '*' . wfMessage( 'createteams-delete-error-permission' )->params( $value )->inContentLanguage()->text() . "\n";
						} else if ( !$title->exists() ) {
							$e .= '*' . wfMessage( 'createteams-delete-error-does-not-exist' )->params( $value )->inContentLanguage()->text() . "\n";
						} else {
							if ( $page->doDeleteArticle( wfMessage( 'createteams-delete-summary-deletion' )->inContentLanguage()->text(), false, $id, '', $wgUser ) ) {
								$log .= '*' . wfMessage( 'createteams-delete-log-deletion-success' )->params( $value )->inContentLanguage()->text() . "\n";
							} else {
								$e .= '*' . wfMessage( 'createteams-delete-error-deletion' )->params( $value )->inContentLanguage()->text() . "\n";
							}
						}
					}
				}
				if ( $e == '' ) {
					$report = $report = wfMessage( 'createteams-delete-teams-report-success' )
						->params( htmlspecialchars( $reqDeleteteam ) )->inContentLanguage()->text();
				} else {
					$report = $e;
				}
				$report .= '<div class="log">' . "\n" . $log . '</div>';

				$output->addWikiText( '===' . wfMessage( 'createteams-report-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $report );
			} else if ( $request->getBool( 'deletepreviewbutton' ) ) {
				if ( $reqDeletepreviewteam == '' ) {
					$preview = wfMessage( 'createteams-delete-teams-error-team-name-empty' )->inContentLanguage()->text();
				} else {
					foreach ( array_keys( $this->templates ) as $prefix ) {
						$deltemplate[ $prefix ] = "Template:$prefix/" . strtolower( $reqDeletepreviewteam );
					}
					foreach ( $deltemplate as $value ) {
						$title = Title::newFromText( $value );
						$page  = WikiPage::factory( $title );
						$id    = $page->getId();
						$errors = $title->getUserPermissionsErrors( 'delete', $wgUser );
						if (count($errors)) {
							$preview .= '*' . wfMessage( 'createteams-delete-error-permission' )->params( $value )->inContentLanguage()->text() . "\n";
						} else if ( !$title->exists() ) {
							$preview .= '*' . wfMessage( 'createteams-delete-error-does-not-exist' )->params( $value )->inContentLanguage()->text() . "\n";
						} else {
							$preview .= '*' . wfMessage( 'createteams-delete-teams-preview-deletion' )->params( $value )->inContentLanguage()->text() . "\n";
						}
					}
				}
				$output->addWikiText( '===' . wfMessage( 'createteams-preview-heading' )->inContentLanguage()->text() . '===' );
				$output->addWikiText( $preview );

				if ( $reqDeletepreviewteam != '' ) {
					$deleteConfirmForm = '<form name="delete-confirm-form" method="post">
	<input type="text" name="deleteteam" value="' . $reqDeletepreviewteam . '" readonly>
	<input type="submit" name="deletebutton" value="' . wfMessage( 'createteams-delete-teams-delete-button' )->inContentLanguage()->text() . '">
	<p class="warning">' . wfMessage( 'createteams-delete-teams-warning-deletion' )->params( $value )->inContentLanguage()->text() . '</p>
</form>';
					$output->addWikiText( '===' . wfMessage( 'createteams-delete-teams-confirm-deletion-heading' )->inContentLanguage()->text() . '===' );
					$output->addHTML( $deleteConfirmForm );
				}
			}
		}
	}
}