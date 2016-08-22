$(document).ready(function() {
	var historicalteam = '<tr>' +
		'<td class="input-label"><label for="historicaltime">' + mw.messages.get( 'createteams-create-teams-historicaltime-label' ) + '</label></td>' +
		'<td class="input-container"><input type="text" name="historicaltime[]" value=""></td>' +
		'<td class="input-helper">' + mw.messages.get( 'createteams-create-teams-historicaltime-helper' ) + '</td>' +
	'</tr>' +
	'<tr>' +
		'<td class="input-label"><label for="historicalteam">' + mw.messages.get( 'createteams-create-teams-historicalteam-label' ) + '</label></td>' +
		'<td class="input-container"><input type="text" name="historicalteam[]" value=""></td>' +
		'<td class="input-helper">' + mw.messages.get( 'createteams-create-teams-historicalteam-helper' ) + '</td>' +
	'</tr>';
	$('#createhistoricaladd').click(function() {
		$('#historicaloverwriteline').before(historicalteam);
	});
});