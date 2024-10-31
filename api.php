<?php

add_action( 'save_post', 'blm_bloom_post_save', 10, 3 );
add_filter( 'post_updated_messages', 'blm_bloom_post_save_notice' );

/*
* blm_bloom_post_save
* Save the post to Bloom
*/
function blm_bloom_post_save( $post_id , $post, $update) {

	// Handle if not initial save
	if( get_post_meta( $post_id, 'blm_bloom_post_submitted', true ) === 'true' ) {
		return true;
	}

	// Update justsaved field
	update_post_meta( $post_id, 'blm_bloom_post_justsaved', 'true' );

	// Get Bloom keys
	$blm_bloom_api_key = get_option( 'blm_bloom_api_key' );
	$blm_bloom_publisher_key = get_option( 'blm_bloom_publisher_key' );

	// Check key requirements
	if( ! $blm_bloom_api_key || ! $blm_bloom_publisher_key ) {

		// Update header message
		$response_message = array(
			'code' => 0,
			'message' => 'This post could not be submitted to Bloom because your keys are not provided.'
		);
		update_post_meta( $post_id, 'blm_bloom_post_response', json_encode( $response_message ) );

		return true;

	}

	// Get location data
	$blm_location_address = get_post_meta( $post_id, 'blm_formatted_address', true );
	$blm_location_latitude = get_post_meta( $post_id, 'blm_latitude', true );
	$blm_location_longitude = get_post_meta( $post_id, 'blm_longitude', true );

	// Check location requirements
	if( ! $blm_location_address || ! $blm_location_latitude || ! $blm_location_longitude ) {

		// Update header message
		$response_message = array(
			'code' => 0,
			'message' => 'This post could not be submitted to Bloom because a location was not selected.'
		);
		update_post_meta( $post_id, 'blm_bloom_post_response', json_encode( $response_message ) );

		return true;

	}

	// Gather post data
	$query = array(
		'app_key' => $blm_bloom_api_key,
		'app_user' => $blm_bloom_publisher_key,
		'app_action' => 'post_add',
		'title' => base64_encode( $post->post_title ),
		'content' => base64_encode( wp_trim_excerpt( $post->post_content ) ),
		'category' => 'news',
		'location_address' => $blm_location_address,
		'location_latitude' => $blm_location_latitude,
		'location_longitude' => $blm_location_longitude,
		'url' => rawurlencode( get_post_permalink( $post_id ) ),
		'image_url' => rawurlencode( get_the_post_thumbnail_url( $post_id ) ),
		'user_agent' => $_SERVER['HTTP_USER_AGENT']
	);

	// Process Bloom API call
	$blm_bloom_api_response = blm_bloom_api_process( 'post', $query );

	// Update header message with API response
	update_post_meta( $post_id, 'blm_bloom_post_response', $blm_bloom_api_response );

}// blm_bloom_post_save

/*
* blm_admin_header
* Add a message to the header of the admin
*/
function blm_bloom_post_save_notice( $messages ) {

	global $pagenow, $post, $post_ID;

	// Handle if post was just saved to Bloom
	if( 'post-new.php' != $pagenow && 'true' === get_post_meta( $post_ID, 'blm_bloom_post_justsaved', true) ) {

		add_action( 'admin_notices', 'blm_bloom_saved_message' );

		// Update justsaved field
		update_post_meta( $post_ID, 'blm_bloom_post_justsaved', 'false' );

	}

	return $messages;

}// blm_bloom_post_save_notice

/*
* blm_bloom_saved_message
* Display a message when post is saved, if applicable
*/
function blm_bloom_saved_message() {

	global $post, $post_ID;

	// Decode the API response
	$response = json_decode( get_post_meta( $post_ID, 'blm_bloom_post_response', true ) );
	$response->code = (int) $response->code;

	// Handle type of response
	if( 1 === $response->code ) {

		// Handle successful response

		$response_notice = 'updated notice-success';

		update_post_meta( $post_ID, 'blm_bloom_post_submitted', 'true' );

	} else {

		// Handle failed response

		$response_notice = 'notice-error';

		if( 12 === $response->code ) {

			update_post_meta( $post_ID, 'blm_bloom_post_submitted', 'true' );

		} else {

			update_post_meta($post_ID, 'blm_bloom_post_submitted', 'false' );

		}

	}

	echo '<div class="notice '.$response_notice.' is-dismissible" data-code="'.$response->code.'"><p>Bloom: '.$response->message.'</p></div>';

}// blm_bloom_saved_message

/*
* blm_bloom_api_process
* Call the initial API request
*/
function blm_bloom_api_process( $method_url, $query ){

	// Format data input
	$query_string = null;
	foreach( $query as $key => $value ) { 
		$query_string .= $key . '=' . json_encode( $value ) . '&';
	}

	$query_string = rtrim( $query_string, '&' );

	// Process API call
	$curl_handle = curl_init();
	curl_setopt( $curl_handle, CURLOPT_URL, 'http://api.bloom.li/api/'.$method_url );
	curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl_handle, CURLOPT_POST, count( $query ) );
	curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $query_string );
	$buffer = curl_exec( $curl_handle );
	curl_close( $curl_handle );
	$result = json_decode( $buffer );

	if( isset( $result->success ) && $result->success ) {
		return $result->data;
	} else {
		return json_encode( array(
			'code' => 0,
			'message' => 'API could not produce result'
		) );
	}

}// blm_bloom_api_process
