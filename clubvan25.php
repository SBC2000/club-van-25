<?php 
    /*
    Plugin Name: Club van 25
    Plugin URI: http://zomertoernooi.sbctoernooien.nl
    Description: Beheer deelnemers club van 25
    Author: Vincent van der Weele
    Version: 1.0
    Author URI: http://zomertoernooi.sbctoernooien.nl
    */	
	
	include "classes/ClubVan25AdminHandler.php";
	include "classes/ClubVan25InstallHelper.php";
	include "classes/ClubVan25CroppicHandler.php";
	
	/* Runs when plugin is activated */
	register_activation_hook(__FILE__,'ClubVan25InstallHelper::install'); 
	
	/* Runs on plugin deactivation*/
	register_deactivation_hook( __FILE__, 'ClubVan25InstallHelper::uninstall' );
	
	/* Register admin menu */
	add_action( 'admin_menu', 'ClubVan25AdminHandler::addPluginMenu' );
	
	/* Register shortcode */
	add_shortcode('clubvan25', 'ClubVan25Handler::getOverview');
	
	/* Register upload shortcode */
	add_shortcode('clubvan25upload', 'cv25_upload');
	
	/* Bind post calls */
	add_action( 'wp_ajax_cv25_upload', 'cv25_onupload' );
	add_action( 'wp_ajax_nopriv_cv25_upload', 'cv25_onupload' );	
	add_action( 'wp_ajax_cv25_crop', 'cv25_oncrop' );
	add_action( 'wp_ajax_nopriv_cv25_crop', 'cv25_oncrop' );
	add_action( 'wp_ajax_cv25_update_year', 'cv25_onupdateyear' );	
	add_action( 'wp_ajax_cv25_get_member_table', 'cv25_ongetmembertable' );
	add_action( 'wp_ajax_cv25_create_member', 'cv25_oncreatemember' );
	add_action( 'wp_ajax_cv25_copy_members', 'cv25_oncopymembers' );
	add_action( 'wp_ajax_cv25_delete_members', 'cv25_ondeletemembers' );
	add_action( 'wp_ajax_cv25_get_years', 'cv25_ongetyears' );
	
	/* Register query parameter to be used in url  */
	add_filter( 'query_vars', 'cv25_add_query_var' );
		
	function cv25_upload() {
		return ClubVan25Handler::getHandleUpload(get_query_var('cv25-token', 'invalid'));
	}
	
	function cv25_onupload() {
		// a bit weird to die here, but we need to print the result 
		// and prevent all other output
		die(ClubVan25CroppicHandler::uploadImage($_FILES["img"]));
	}
	
	function cv25_oncrop() {
		// a bit weird to die here, but we need to print the result 
		// and prevent all other output
		die(ClubVan25CroppicHandler::cropImage($_POST, $_POST['id']));
	}
	
	function cv25_onupdateyear() {
		die(ClubVan25Handler::updateYear($_POST['data']['year']));
	}
	
	function cv25_ongetmembertable() {
		ClubVan25AdminHandler::showMemberTable($_POST['data']['year']);
		die();
	}
	
	function cv25_oncreatemember() {
		ClubVan25Handler::createMember($_POST['data']);
		die();
	}
	
	function cv25_oncopymembers() {
		ClubVan25AdminHandler::copyMembers($_POST['data']['year'], $_POST['data']['ids']);
		die();
	}
	
	function cv25_ondeletemembers() {
		ClubVan25Handler::deleteMembers($_POST['data']);
		die();
	}
	
	function cv25_ongetyears() {
		die(ClubVan25AdminHandler::getYears());
	}

	function cv25_settings() {
		ClubVan25AdminHandler::add_plugin_page();
	}
	
	function cv25_add_query_var( $vars ){
	  $vars[] = "cv25-token";
	  return $vars;
	}
?>