<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<form method="post" action="options.php" id="litespeed_form_options" class="litespeed-relative">

<?php

	require LSCWP_DIR . "admin/tpl/inc/check_if_network_disable_all.php" ;

	settings_fields( LiteSpeed_Cache_Config::OPTION_NAME ) ;

	require LSCWP_DIR . "admin/tpl/setting/settings_crawler.php" ;

	echo "<div class='litespeed-top20'></div>" ;

	if ( $this->get_disable_all() ) {
		submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-success', 'litespeed-submit', true, array('disabled' => true)) ;
	}
	else {
		submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-success', 'litespeed-submit') ;
	}

?>

</form>