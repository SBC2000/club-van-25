<?php

class ClubVan25InstallHelper {
	private static $tableName = 'club_van_25';
	private static $versionName = 'club_van_25_db_version';
	private static $version = '1.0';
	private static $yearName = 'club_van_25_year';
	private static $year;
	
	public static function install() {
		self::$year = date("Y");
		
		global $wpdb;

		$table_name = $wpdb->prefix . self::$tableName; 
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  id int(11) NOT NULL AUTO_INCREMENT,
		  year int(11) NOT NULL,
		  firstname varchar(30) DEFAULT '' NOT NULL,
		  middlename varchar(20) DEFAULT '' NOT NULL,
		  lastname varchar(30) DEFAULT '' NOT NULL,
		  email varchar(50) DEFAULT '' NOT NULL,
		  hasfile int(1) DEFAULT 0 NOT NULL,
		  token varchar(32) DEFAULT '' NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;
		CREATE TRIGGER before_insert_$table_name
		  BEFORE INSERT ON $table_name 
		  FOR EACH ROW
		  SET new.token = MD5(UUID());";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		add_option( self::$versionName, self::$version );
		add_option( self::$yearName, self::$year );
	}
	
	public static function uninstall() {
		delete_option( self::$versionName );
		delete_option( self::$yearName );
		
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tableName; 
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}
}