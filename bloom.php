<?php
/*
Plugin Name: Bloom for Publishers
Plugin URI: https://wordpress.org/plugins/news-nearby-search/
Description: Geotag your posts with Bloom to create a local search and mapping visuals for your readers.
Version: 1.1
Author: Bloom
Author URI: http://www.bloom.li
License: GPL2
Text Domain: bloom

Bloom for Publishers is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Bloom for Publishers is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Bloom for Publishers. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

add_action( 'add_meta_boxes', 'blm_location_custom_meta' );
add_action( 'save_post', 'blm_location_save' );
add_action( 'wp_head', 'blm_head' );
add_action( 'admin_enqueue_scripts', 'blm_admin_scripts' );
add_action( 'admin_notices', 'blm_admin_header' );
add_action( 'blm_admin_meta_boxes', array( 'blm_admin_meta_boxes' ) );

/*
* blm_admin_header
* Add a message to the header of the admin
*/
function blm_admin_header(){

	global $pagenow;

	// Handle if not on Post page
	if( 'post.php' != $pagenow && 'post-new.php' != $pagenow ){
		return true;
	}

	// Add Google API notice
	$blm_google_api_key = esc_attr( get_option( 'blm_google_api_key' ) );
	if( ! $blm_google_api_key ) {
		echo '<div class="error notice"><p>Enter your Google API Key on the Settings page in order to use the geotagging feature.</p></div>';
	}

}// blm_admin_header

/*
* blm_admin_meta_boxes
* Add a meta box to the top of a post page
*/
function blm_admin_meta_boxes( $post ) {

        global $pagenow;

        // Handle if not on Post page
        if( 'post.php' != $pagenow && 'post-new.php' != $pagenow ){
                return true;
        }

	// Add Bloom API notice
	$blm_bloom_api_key = esc_attr( get_option( 'blm_bloom_api_key' ) );
	if( ! $blm_bloom_api_key ) {
		add_meta_box( "bloom", "Bloom", null, 'test', "side", "high" );
	}

}// blm_admin_meta_boxes

/*
* blm_location_custom_meta
* Adds a location input to post editor
*/
function blm_location_custom_meta() {

	add_meta_box( 'blm_location_meta', __( 'Article Location', 'location-textdomain' ), 'blm_location_callback', 'post' );

}// blm_location_custom_meta

/*
* blm_location_callback
* Displays the location input in editor
*/
function blm_location_callback( $post ) {

	// Secure the function
	wp_nonce_field( basename( __FILE__ ), 'blm_location_nonce' );    

	// Fetch currently-saved post location data
	$blm_google_api_key = esc_attr( get_option( 'blm_google_api_key' ) );
	$blm_formatted_address = get_post_meta( $post->ID, 'blm_formatted_address', true );
	$blm_address_components = get_post_meta( $post->ID, 'blm_address_components', true );
	$blm_latitude = get_post_meta( $post->ID, 'blm_latitude', true );
	$blm_longitude = get_post_meta( $post->ID, 'blm_longitude', true );

	if( $blm_google_api_key ) {

	?>

	<div id="blm-meta-intro">The location selected here should define the location discussed in this post, if applicable.  By saving the location, the post is <a href="http://www.bloom.li/advocacy/metadata" title="Bloom" target="_blank">geotagged</a> by inserting the address and coordinates into the webpage metadata.</div>

	<div id="blm-location-selection-tool">
		<div id="blm-location-search">
			<div class="blm-search-title">Location Search</div>
			<div class="blm-search-body">
				<input type="text" id="blm-location-input" />
				<button type="button" id="blm-location-request" onClick="blmGeocode();">Search</button>
				<div id="blm-location-results"></div>
			</div>
		</div>
	
		<div id="blm-location-choice" data-display="<?php echo ( $blm_formatted_address ) ? 1 : 0; ?>">
			<div class="blm-search-title">Location Selected<button type="button" id="blm-location-clear" onClick="blmClearLocation();">Clear</button><button type="button" id="blm-location-details" onClick="blmLocationDetailsChange('show');">Show Details</button></div>

			<div class="blm-search-body">
				<div id="blm-location-choice-value"><?php echo $blm_formatted_address; ?></div>
				<div id="blm-location-choice-components">
					<ul>
						<?php
						$blm_address_components_array = json_decode( base64_decode( $blm_address_components ) );

						foreach( $blm_address_components_array as $blm_address_component_key => $blm_address_component_value ) {

							if( ! $blm_address_component_value ){
								$blm_address_component_value = '<em>N/A</em>';
							}

							echo '<li><strong>'.ucfirst( str_replace( '_', ' ', $blm_address_component_key ) ).':</strong> '.$blm_address_component_value.'</li>';

						}
						?>
					</ul>
				</div>
				<input type="hidden" name="blm-formatted-address" id="blm-formatted-address" value="<?php echo $blm_formatted_address; ?>" />
				<input type="hidden" name="blm-address-components" id="blm-address-components" value="<?php echo $blm_address_components; ?>" />
				<input type="hidden" name="blm-latitude" id="blm-latitude" value="<?php echo $blm_latitude; ?>" />
				<input type="hidden" name="blm-longitude" id="blm-longitude" value="<?php echo $blm_longitude; ?>" />
			</div>
		</div>
	</div>

<?php

	} else {

		echo '<p>Enter your Google API Key on the Settings page in order to use the geotagging feature.</p>';

	}

}// blm_location_callback

/*
* blm_location_save
* Save the location input value inputted
*/
function blm_location_save( $post_id ) {

	// Checks for input and sanitizes/saves if needed

	if( isset( $_POST['blm-formatted-address'] ) ) {
		update_post_meta( $post_id, 'blm_formatted_address', sanitize_text_field( urldecode( $_POST['blm-formatted-address'] ) ) );
	}

	if( isset( $_POST['blm-address-components'] ) ) {
		update_post_meta( $post_id, 'blm_address_components', sanitize_text_field( $_POST['blm-address-components'] ) );
	}

	if( isset( $_POST['blm-formatted-address'] ) ) {
		update_post_meta( $post_id, 'blm_latitude', sanitize_text_field( $_POST['blm-latitude'] ) );
	}

	if( isset( $_POST['blm-formatted-address'] ) ) {
		update_post_meta( $post_id, 'blm_longitude', sanitize_text_field( $_POST['blm-longitude'] ) );
	}

}// blm_location_save

/*
* blm_head
* Add post's metadata to head section
*/
function blm_head() {

	// Retrieves the stored value from the database
	$blm_formatted_address = get_post_meta( get_the_ID(), 'blm_formatted_address', true );
	$blm_longitude = get_post_meta( get_the_ID(), 'blm_longitude', true );
	$blm_latitude = get_post_meta( get_the_ID(), 'blm_latitude', true );

	// Only show tags if inside post
	if( is_single() && $blm_formatted_address && $blm_latitude && $blm_longitude ){

		// Checks and displays the retrieved value
		echo '<meta property="geo:formatted_address" content="'.htmlentities( $blm_formatted_address, ENT_QUOTES ).'" />'."\n";
		echo '<meta property="geo:latitude" content="'.$blm_latitude.'" />'."\n";
		echo '<meta property="geo:longitude" content="'.$blm_longitude.'" />'."\n";

	}

}// blm_head

/*
* blm_admin_scripts
* Add CSS and JS to the admin post page
*/
function blm_admin_scripts( $hook ) {

	if( 'post.php' != $hook && 'post-new.php' != $hook ) {
		return true;
	}

	$blm_google_api_key = esc_attr( get_option( 'blm_google_api_key' ) );

	if( $blm_google_api_key ) {
		wp_enqueue_script( 'blm_meta_js_geo', 'https://maps.googleapis.com/maps/api/js?key='.$blm_google_api_key );
		wp_enqueue_script( 'blm_meta_js_init', plugin_dir_url( __FILE__ ).'js/meta.js', null, '1.0' );
	}

	wp_enqueue_style( 'blm_meta_css_main', plugin_dir_url( __FILE__ ).'css/meta.css', null, '1.0' ); 

}// blm_admin_scripts

// Include additional functions
include( plugin_dir_path( __FILE__ ) . 'settings.php' );
include( plugin_dir_path( __FILE__ ) . 'api.php' );
include( plugin_dir_path( __FILE__ ) . 'nns.php' );

?>
