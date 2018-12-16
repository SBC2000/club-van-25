<?php

include "ClubVan25Handler.php";

class ClubVan25AdminHandler {
	private static $tableName = 'club_van_25';

	public static function addPluginMenu() {
		add_options_page( 'Club van 25 options', 'Club van 25', 'manage_options', 'club-van-25', 'ClubVan25AdminHandler::addPluginPage' );
	}

	public static function addPluginPage() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		cv25_show_admin_page();
	}

	public static function showMemberTable($year) {
		cv25_show_member_table($year);
	}

	public static function copyMembers($year, $ids) {
		foreach ($ids as $id) {
			$member = ClubVan25Handler::getMemberById($id);

			$data = $member->toArray();
			$data['year'] = $year;
			unset($data['id']);
			unset($data['token']);

			$newMember = ClubVan25Handler::createMember($data);

			if ($member->hasFile()) {
				$oldFile = path_join(ClubVan25Handler::getUploadDir(), $member->getFileName());
				$newFile = path_join(ClubVan25Handler::getUploadDir(), $newMember->getFileName());
				copy($oldFile, $newFile);
			}
		}
	}

	public static function getYears() {
		global $wpdb;
		$tableName = $wpdb->prefix . self::$tableName;

		$query = "SELECT DISTINCT `year` FROM $tableName";

		$result = $wpdb->get_results($query, ARRAY_A);
		$years = array_map(function($row) { return $row['year']; }, $result);

		$currentYear = get_option('club_van_25_year');

		if (!in_array($currentYear, $years)) {
			$years[] = $currentYear;
		}

		sort($years);

		return json_encode(array(
			current => $currentYear,
			years => $years,
		));
	}
}

function cv25_show_admin_page() {
	$allMembers = array_map(function($member) { return $member->toArray(); }, ClubVan25Handler::loadMembers(null));

	wp_enqueue_style('clubvan25', plugins_url('../css/clubvan25.css', __FILE__));
	wp_enqueue_script('cv25-admin', plugins_url('../js/clubvan25admin.js', __FILE__));
	wp_localize_script('cv25-admin', 'php', array(
		'currentAction' => 'cv25_update_year',
		'yearsAction' => 'cv25_get_years',
		'newYearAction' => 'cv25_copy_members',
		'createMemberAction' => 'cv25_create_member',
		'currentYear' => 'current-year-id',
		'currentSpinner' => 'current-spinner-id',
		'currentButton' => 'current-save-id',
		'memberAction' => 'cv25_get_member_table',
		'memberYear' => 'member-year-id',
		'memberSpinner' => 'member-spinner-id',
		'memberButton' => 'member-load-id',
		'memberTable' => 'member-table-id',
		'memberForm' => 'add-member-form-id',
		'newMemberSpinner' => 'new-member-spinner-id',
		'newMemberButton' => 'new-member-button-id',
		'deleteMembers' => 'cv25_delete_members',
	));
?>
<script>
window.onload = function() {
	loadYears();
	loadMemberTable();
};

function toggleGrow(growId, wrapperId, caretId) {
	var growDiv = document.getElementById(growId);
    if (growDiv.clientHeight) {
      growDiv.style.height = 0;
	  document.getElementById(caretId).className = 'caret caret-down';
    } else {
      var wrapper = document.getElementById(wrapperId);
      growDiv.style.height = wrapper.clientHeight + "px";
	  document.getElementById(caretId).className = 'caret caret-up';
    }
}
</script>
<h1>Club van 25</h1>
<div class="admin-wrapper">
	<div id="year-select">
		<span>
			<div>Let op! Door de huidige jaargang aan te passen verander je het overzicht dat op de website wordt getoond.</div>
			<label for="current-year">Huidige jaargang:</label><select id="current-year-id" name="current-year"></select>
			<button id="current-save-id" onClick="updateYear()">Opslaan</button>
			<div id="current-spinner-id" class="ajax-loader"></div>
		</span>
		<div>
			<h2 class="clickable" onClick="toggleGrow('grow-id', 'measuring-wrapper-id', 'caret-id')">Start een nieuwe jaargang<span id="caret-id" class="caret caret-down"></span></h2>
			<div class="grow" id="grow-id">
				<div id="measuring-wrapper-id">
					<div>
						Selecteer de namen van de huidige jaargang die je wil kopiëren naar de nieuwe jaargang door ze aan te vinken.
					</div>
					<label for="new-year">Nieuwe jaargang:<input id="new-year-id" name="new-year"></label>
					<button onClick="addNewYear()">Maken</button>
				</div>
			</div>
		</div>
		<div>
			<h2 class="clickable" onClick="toggleGrow('grow-id2', 'measuring-wrapper-id2', 'caret-id2')">Voeg een lid toe<span id="caret-id2" class="caret caret-down"></span></h2>
			<div class="grow" id="grow-id2">
				<div id="measuring-wrapper-id2">
					<form onsubmit="addMember(this); return false">
						<label for="firstName"><span>Voornaam</span><input name="firstName" type="text"></label>
						<label for="middleName"><span>Tussenvoegsels</span><input name="middleName" type="text"></label>
						<label for="lastName"><span>Achternaam</span><input name="lastName" type="text"></label>
						<label for="email"><span>Email</span><input name="email" type="email"></label>
						<input id="new-member-button-id" type="submit" class="pull-right" value="Toevoegen">
						<div id="new-member-spinner-id" class="ajax-loader"></div>
					</form>
				</div>
			</div>
		</div>
		<h2>Leden</h2>
		<span>
			<label for="member-year">Jaar</label>
			<select id="member-year-id" name="member-year"></select>
			<button id="member-load-id" onClick="loadMemberTable()">Laden</button>
			<div id="member-spinner-id" class="ajax-loader"></div>
			<div><button id="delete-members-id" onClick="deleteMembers()">Geselecteerde leden verwijderen</button></div>
		</span>
	</div>
</div>
<div class="admin-wrapper">
	<div id="member-table-id"></div>
</div>
<?php } ?>

<?php
function cv25_show_member_table($year) {
	$members = ClubVan25Handler::loadMembers($year);
	$baseUrl = path_join(get_site_url(), 'club-van-25-selfie-upload/?cv25-token=');
	$checkmarkUrl = plugins_url('images/check.png', dirname(__FILE__));
	$first = true;
?>
<table id="cv25-member-table">
	<tr>
		<th><input id="member-select-all-checkbox" type="checkbox" onClick="selectAll(this.checked)"></th><th class='clubvan25'>Naam</th><th class='clubvan25'>Afb</th><th class='clubvan25'>Upload</th><th></th>
	</tr>
	<?php foreach($members as $member) : ?>
	<tr>
		<td><input class="member-checkbox" type="checkbox" value="<?php print($member->getId()); ?>" onClick="selectMember(this.checked)"></td>
		<td><?php print($member->getName()); ?></td>
		<td>
			<?php if($member->hasFile()) : ?>
			<img src="<?php print($checkmarkUrl); ?>" />
			<?php else : ?>
			&nbsp;
			<?php endif; ?>
		</td>
		<td><a href="<?php print($baseUrl . $member->getToken()); ?>">link</a></td>
	</tr>
	<?php endforeach; ?>
</table>
<?php } ?>
