<?php

// Create custom plugin settings menu
add_action( 'admin_menu', 'blm_settings_menu' );

/*
* blm_settings_menu
* Add Bloom to the settings menu
*/
function blm_settings_menu() {

	// Create new top-level menu
	add_options_page( 'Bloom', 'Bloom', 'administrator', __FILE__, 'blm_settings_page' , plugins_url( '/images/icon.png', __FILE__ ) );

	// Call register settings function
	add_action( 'admin_init', 'blm_register_settings' );

	// Register CSS if on settings page
	if( isset( $_GET['page'] ) && ( 'bloom/settings.php' === $_GET['page'] || 'news-nearby-search/settings.php' === $_GET['page'] ) ) {

		// Register scripts
		add_action( 'admin_enqueue_scripts', 'blm_settings_scripts' );

	}

}// blm_settings_menu

/*
* blm_register_settings
* Register settings page with Wordpress
*/
function blm_register_settings() {

	register_setting( 'blm_options_group', 'blm_bloom_api_key' );
	register_setting( 'blm_options_group', 'blm_bloom_publisher_key' );
	register_setting( 'blm_options_group', 'blm_google_api_key' );
	register_setting( 'blm_options_group', 'blm_nns_enabled' );

}// blm_register_settings

/*
* blm_settings_page
* Code for the Bloom settings page
*/
function blm_settings_page() {

	// Validate application key
	$app_key = get_option( 'blm_bloom_api_key' );

	if( $app_key ) {

		// Process Bloom API call
		$api_response = blm_bloom_api_process( 'application', array (
			'key' => $app_key,
			'app_action' => 'validate_key'
		) );

		$api_response = json_decode( $api_response );

		if( $api_response->success ) {
			$app_key_valid = $api_response;
		}

	}

	// Validate publisher key
	$pub_key = get_option( 'blm_bloom_publisher_key' );
	if( $pub_key ) {

		// Process Bloom API call
		$api_response = blm_bloom_api_process( 'user', array (
			'key' => $pub_key,
			'app_action' => 'validate_key'
		) );

		$api_response = json_decode( $api_response );

		if( $api_response->success ) {
			$pub_key_valid = $api_response;
		}

	}

	// Validate Google API key
	$google_key = get_option( 'blm_google_api_key' );

	?>

	<div class="wrap">

		<h2>Bloom</h2>
		<p>The following settings only apply to publishers who are registered on <a href="http://www.bloom.li" title="Bloom" target="_blank">Bloom</a>.<br />The keys allow you to easily integrate geotagging, local search, mapping into your website.</p>

		<form method="post" action="options.php">

			<?php

			// Identify which settings this page will handle
			settings_fields( 'blm_options_group' );
			do_settings_sections( 'blm_options_group' );
			add_thickbox();

			?>

			<div class="blm-settings-section">

				<table class="form-table">

					<tr>
						<th scope="row">
							<strong>Bloom API Key</strong>
							<a href="#TB_inline?width=600&height=250&inlineId=blm-tb-bloomapikey" class="thickbox"></a>
						</th>
						<td data-field="bloom-api-key">
							<input type="text" name="blm_bloom_api_key" value="<?php echo esc_attr( get_option( 'blm_bloom_api_key' ) ); ?>" />

							<? if( isset( $app_key_valid ) ) { ?>

							<div data-code="<? echo $app_key_valid->code; ?>" class="blm-field-note">
								<div class="blm-field-message">
									<span><? echo $app_key_valid->message; ?></span>
									<a href="http://www.bloom.li" title="Bloom Plugins" target="_blank" class="blm-field-link">Get your API key</a>
								</div>
							</div>

							<? } ?>

						</td>
					</tr>

					<tr>
						<th scope="row">
							<strong>Bloom Publisher Key</strong>
							<a href="#TB_inline?width=600&height=250&inlineId=blm-tb-bloompublisherkey" class="thickbox"></a>
						</th>
						<td data-field="bloom-publisher-key">
							<input type="text" name="blm_bloom_publisher_key" value="<?php echo esc_attr( get_option( 'blm_bloom_publisher_key' ) ); ?>" />
							<? if( isset( $pub_key_valid ) ) { ?>

							<div data-code="<? echo $pub_key_valid->code; ?>" class="blm-field-note">
								<div class="blm-field-message">
									<span><? echo $pub_key_valid->message; ?></span>
									<a href="http://www.bloom.li" title="Bloom Plugins" target="_blank" class="blm-field-link">Get your publisher key</a>
								</div>
							</div>

							<? } ?>

						</td>
					</tr>

					<tr>
						<th scope="row">
							<strong>Google API Key</strong>
							<a href="#TB_inline?width=600&height=300&inlineId=blm-tb-googleapikey" class="thickbox"></a>
						</th>   
						<td data-field="google-api-key">
							<input type="text" name="blm_google_api_key" value="<?php echo esc_attr( get_option( 'blm_google_api_key' ) ); ?>" />
							<div data-code="<? echo ( $google_key ) ? 1 : 0 ?>" class="blm-field-note">
								<div class="blm-field-message">
									<a href="http://www.google.com" title="Google API" target="_blank">Get your Google API key</a>
								</div>
							</div>
						</td>

					</tr>

					<tr>
						<th scope="row">
							<strong>News Nearby Search</strong>
							<a href="http://www.bloom.li/discovery/plugins/embed/nearbysearch" title="News Nearby Search" target="_blank" class="blm-external-link"></a>
						</th>
						<td>
							<select name="blm_nns_enabled">
								<option <?php echo ( 'true' === esc_attr( get_option( 'blm_nns_enabled' ) ) ) ? 'selected="selected"' : ''; ?> value="true">Enabled</option>
								<option <?php echo ( 'false' === esc_attr( get_option( 'blm_nns_enabled' ) ) ) ? 'selected="selected"' : ''; ?> value="false">Disabled</option>
							</select>
						</td>
					</tr>

				</table>

				<?php submit_button(); ?>

				<div id="blm-tb-bloomapikey" style="display: none;">
					<h3>Bloom API Key</h3>
					<p>This is your private key that gives you access to Bloom's geotagging and search tools.  For security purposes, please keep this to yourself.</p>
					<ol>
						<li>Login to your Bloom account</li>
						<li>Go to your Account Profile page</li>
						<li>Copy the API Key</li>
					</ol>
				</div>

				<div id="blm-tb-bloompublisherkey" style="display: none;">
					<h3>Bloom Publisher Key</h3>
					<p>This is your key for your publisher account that allows you to send requests to Bloom for geotagging and search.</p>
					<ol>
						<li>Login to your Bloom account</li>
						<li>Go to your Account Profile page</li>
						<li>Copy the Publisher Key</li>
					</ol>
				</div>

				<div id="blm-tb-googleapikey" style="display: none;">
					<h3>Google API Key</h3>
					<p>This is required in order to run the geocoding feature.</p>
					<ol>
						<li>Visit <a href="https://console.developers.google.com/apis" title="Google Developer API" target="_blank">https://console.developers.google.com/apis</a></li>
						<li>Select a Project, or Create a Project (top right corner in blue header)</li>
						<li>Go back to Google API’s page: <a href="https://console.developers.google.com/apis" title="Google Developer API" target="_blank">https://console.developers.google.com/apis</a></li>
						<li>In the Google APIs tab, search for "Google Maps JavaScript API"</li>
						<li>Enable "Google Maps JavaScript API"</li>
						<li>Add Credentials: follow these steps and it will give you your API key.</li>
						<li>Copy/paste the API key into the Bloom plugin settings.</li>
					</ol>
				</div>

			</div>

		</form>

	</div>

<?php

}// blm_settings_page

/*
* blm_settings_scripts
* Add CSS scripts
*/
function blm_settings_scripts( $hook ) {

        wp_enqueue_style( 'blm_settings_css_main', plugin_dir_url( __FILE__ ).'css/settings.css', null, '1.0' );

}// blm_settings_scripts

?>
