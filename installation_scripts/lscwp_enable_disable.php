<?php

include "wp-config.php" ;
include_once "wp-admin/includes/plugin.php" ;

const PLUGIN_NAME = "litespeed-cache/litespeed-cache.php" ;

$action = $argv[1] ;
$WP_DIR = $argv[2] ;

/*
* If plugin is in cache list, echo its name and current status.
*/
function cachedetect($plugin,$value){

$cache_list = array(
"LiteSpeed Cache",
"Gator Cache",
"SG CachePress",
"W3 Total Cache",
"WP Fastest Cache",
"WP Super Cache",
"ZenCache"
);

$name = $plugin['Name'];

if(in_array($name,$cache_list)){
	if (is_plugin_active($value)){
		echo "$name - Enabled\n";
	}
	else{
		echo "$name - Disabled\n";
	}
}
}

if ( $action == "status"  ) {
	$plugins = get_plugins();
	array_walk($plugins,"cachedetect");
			}

elseif ( $action == "enable" ) {
	if ( ! activate_plugin(PLUGIN_NAME, '', false, false) == null ) {
		printf("\nLSCWP not enabled for %s \n\n", $WP_DIR) ;
		return false;
	}
	return true;
}

elseif ( $action == "disable" ) {

	$dbh = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD) ;

	if ( ! $dbh ) {
		die( $WP_DIR . " - Could not connect to database: " . mysql_error() . "\nIf possible, LSCWP will still be removed\n\n") ;
	}

	if ( mysqli_select_db($dbh, DB_NAME) == FALSE ) {
		die($WP_DIR . " - Could not select database '" . DB_NAME . "': " . mysql_error() . "\nIf possible, LSCWP will still be removed\n\n") ;
	}

	$sql = "SELECT option_value
		FROM " . $table_prefix . "options
		WHERE option_name = 'active_plugins'
		" ;

	$active = mysqli_query($dbh, $sql) ;
	if ( $active == false ) {
		die($WP_DIR . " - Query failed: " . mysql_error() . "\nIf possible, LSCWP will still be removed\n\n") ;
	}

	$row = mysqli_fetch_assoc($active) ;

	$plugins = unserialize($row["option_value"]) ;

	foreach ( $plugins as $pkey => $pval ) {
		if ( $pval == PLUGIN_NAME ) {
			unset($plugins[$pkey]) ;
		}
	}
	$sql = "UPDATE " . $table_prefix . "options
	SET option_value = '" . serialize($plugins) . "'
	WHERE option_name = 'active_plugins'
	" ;
	$disable = mysqli_query($dbh, $sql) ;
	if ( $disable == false ) {
		printf($WP_DIR . " - Unable to disable LSCWP with query error: " . mysql_error() . "\nIf possible, LSWCP will still be removed\n\n") ;
	}
}
