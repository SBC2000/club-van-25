<?php

include "Member.php";

class ClubVan25Handler {
	private static $chunkSize = 3;
	private static $tableName = 'club_van_25';
	private static $yearOptionName = 'club_van_25_year';

	public static function getUploadUrl() {
		return path_join(wp_upload_dir()['baseurl'], 'clubvan25');
	}

	public static function getUploadDir() {
		return path_join(wp_upload_dir()['basedir'], 'clubvan25');
	}

	public static function setTempUploadDir( $dirs ) {
		$dir = '/clubvan25/temp';

		$dirs['subdir'] = $dir;
		$dirs['path'] = $dirs['basedir'] . $dir;
		$dirs['url'] = $dirs['baseurl'] . $dir;

		return $dirs;
	}

	public static function getOverview() {
		ob_start();
		cv25_show_overview(ClubVan25Handler::getChunks());
		$returned = ob_get_contents();
		ob_end_clean();
		return $returned;
	}

	public static function getHandleUpload($token) {
		if ($token != 'invalid') {
			$member = ClubVan25Handler::getMemberByToken($token);
		}

		ob_start();
		cv25_show_upload($member, self::getUploadUrl());
		$returned = ob_get_contents();
		ob_end_clean();
		return $returned;
	}

	public static function getChunks() {
		$members = self::loadMembers(get_option(self::$yearOptionName));

		$uploadUrl = self::getUploadUrl();
		$memberArrays = array_map(function($m) use ($uploadUrl) {
			$fileName = $m->hasFile() ? $m->getFileName() : 'unknown.jpeg';
			$filePath = path_join($uploadUrl, $fileName);

			return array('path' => $filePath, 'name' => $m->getName());
		}, $members);

		return array_chunk($memberArrays, self::$chunkSize);
	}

	public static function loadMembers($year) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tableName;

		$where = $year ? "AND year='$year'" : "";

		$query = "SELECT * FROM $table_name WHERE deleted=FALSE $where ORDER BY lastname";

		$result = $wpdb->get_results($query, ARRAY_A);

		return array_map(function($m) { return new Member($m); }, $result);
	}

	public static function getMemberById($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tableName;

		$result = $wpdb->get_row("SELECT * FROM $table_name WHERE id='$id'", ARRAY_A);

		return $result ? new Member($result) : null;
	}

	public static function getMemberByToken($token) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tableName;

		$result = $wpdb->get_row("SELECT * FROM $table_name WHERE token='$token'", ARRAY_A);

		return $result ? new Member($result) : null;
	}

	public static function updateMember($member) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tableName;

		return $wpdb->update($table_name, $member->toArray(), array('id' => $member->getId()));
	}

	public static function createMember($data) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tableName;

		$wpdb->insert($table_name, $data);

		return self::getMemberById($wpdb->insert_id);
	}

	public static function deleteMembers($ids) {
		foreach ($ids as $id) {
			$member = self::getMemberById($id);
			$member->setDeleted(true);
			self::updateMember($member);
		}
	}

	public static function updateYear($year) {
		if (2010 <= $year && $year <= 2025) {
			update_option(self::$yearOptionName, $year);
			$response = array(
				"status" => "success",
				"year" => get_option(self::$yearOptionName),
			);
		} else {
			$response = array(
				"status" => "error",
				"year" => get_option(self::$yearOptionName),
			);
		}
		return json_encode($response);
	}
}

function cv25_show_overview($chunks) {
	wp_enqueue_style('clubvan25', plugins_url('../css/clubvan25.css', __FILE__));
	foreach ($chunks as $chunk) : ?>
	<div class='clubvan25row'>
		<?php foreach ($chunk as $member) : ?>
		<div class='clubvan25item'>
			<img src='<?php print($member['path']); ?>'/>
			<?php print($member['name']); ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>
<?php }

function cv25_show_upload($member, $uploadUrl) {
	if ($member) :
		wp_enqueue_style('clubvan25upload', plugins_url('../css/croppic.css', __FILE__));
		wp_enqueue_script('croppic', plugins_url('../js/croppic.js', __FILE__));
		wp_enqueue_script('custom-croppic', plugins_url('../js/custom-croppic.js', __FILE__), array('croppic'));
		wp_localize_script('custom-croppic', 'php', array(
			'url' => admin_url( 'admin-ajax.php' ),
			'uploadAction' => 'cv25_upload',
			'cropAction' => 'cv25_crop',
			'id' => $member->getId(),
			'image' => path_join($uploadUrl, $member->hasFile() ? $member->getFileName() : 'unknown.jpeg'),
		));
	?>
	<div class='outerDiv'>
		<p>Hallo <?php print($member->getName()); ?>! Leuk dat je je hebt aangemeld voor de Club van 25!</p>
		<p>We zijn heel blij met je bijdrage aan het SBC2000 zomertoernooi en daarom willen we je graag samen met de andere leden van de Club van 25 een speciaal plekje geven op de website. Hiervoor hebben we alleen een selfie van je nodig. Deze kun je hiernaast uploaden.</p>
	</div>
	<div class='outerDiv'>
		<div class='innerDiv'>
			<div id='cropContainerEyecandy'></div>
		</div>
	</div>
	<?php else : ?>
		<p>Helaas, we hebben geen lid van de club van 25 kunnen vinden met deze code.</p>
	<?php endif;
} ?>
