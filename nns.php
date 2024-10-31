<?php

add_action( 'wp_footer', 'blm_nns_footer', 100 );
add_action( 'wp_enqueue_scripts', 'blm_nns_scripts' );

/*
* blm_nns_footer
* Add tab to the footer of the website
*/
function blm_nns_footer() {

	//Ignore if publisher key is not provided or if NNS is not enabled
	if( ! get_option( 'blm_bloom_publisher_key' ) || 'false' === get_option( 'blm_nns_enabled' )  ) {
		return true;
	}

	// Get input
	$blm_publisher_key = esc_attr( get_option( 'blm_bloom_publisher_key' ) );

	// Check requirements
	if( ! $blm_publisher_key ) {
		return true;
	}

	// Add plugin
	echo '<bloom data-plugin="news-nearby-search" data-publisher="'.$blm_publisher_key.'"></bloom>';

}// blm_nns_footer

/*
* blm_nns_scripts
* Add CSS and JS to the plugin
*/
function blm_nns_scripts() {

	//Ignore if NNS is not enabled
	if( 'false' === get_option( 'blm_nns_enabled' ) ) {
		return true;
	}

	//Handle if geo file requested
	$blm_google_api_key = esc_attr( get_option( 'blm_google_api_key' ) );

	if( $blm_google_api_key ) {
		wp_enqueue_script( 'blm_nns_js_geo', 'https://maps.googleapis.com/maps/api/js?key='.$blm_google_api_key );
	}

	wp_enqueue_script( 'blm_nns_js_main', 'http://api.bloom.li/static/nearby/search/js/nns.js', null, '1.0' );

}// blm_nns_scripts

?>
