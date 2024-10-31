// Initialize Google geocoder
var blmGeocoder = null;
if( typeof( google ) != 'undefined' ) {
	blmGeocoder = new google.maps.Geocoder();
}

/**
* onload
* Actions to perform on window load
*/
window.onload = function() {

	// Get location input field
	var blmLocationInput = document.getElementById( 'blm-location-input' );

	if( ! blmLocationInput ){
		return false;
	}

	// Add listener for location input keypress
	blmLocationInput.addEventListener( 'keypress', function( e ) {

		if( 13 === e.keyCode ) {
			blmGeocode();
			e.preventDefault();
			return false;
		}

	} );

	// Add listener for location input keyup
	blmLocationInput.addEventListener( 'keyup', function( e ) {

		if( 13 === e.keyCode ) {
			blmGeocode();
			e.preventDefault();
			return false;
		}

	} );

}// onload

/**
* blmGeocode
* Geocode an input
*/
function blmGeocode() {

	// Get value
	var blmLocationInput = document.getElementById( 'blm-location-input' ).value;

	if( ! blmLocationInput ){
		return false;
	}

	// Get address json results
	blmGeocoder.geocode( { 'address': blmLocationInput }, function( blmGeocoderResults, blmGeocoderStatus ) {

		var blmResultsDisplay = '';

		if ( blmGeocoderStatus === google.maps.GeocoderStatus.OK ) {

			// Calculate results length
			var blmResultsLimit = 5;

			// Iterate through results
			if( blmGeocoderResults.length > 0 ){

				var blmResultsCount = 0;
				var blmIndex = 0;

				// Confirm that the next json result is defined and blmResultsCount under limit
				while( blmGeocoderResults[ blmIndex ] !== undefined && blmResultsCount < blmResultsLimit ) {

					// Confirm that the result is a street address via looking at street address and route components
					if( blmLocationCheck( blmGeocoderResults[ blmIndex ], 'address' ) || blmLocationCheck( blmGeocoderResults[ blmIndex ], 'intersection' ) ) {

						blmGeocoderResults[ blmIndex ].geometry = {
							location: {
								lat: blmGeocoderResults[ blmIndex ].geometry.location.lat(),
								lng: blmGeocoderResults[ blmIndex ].geometry.location.lng()
							}
						}

						blmResultValue = btoa( JSON.stringify( blmGeocoderResults[ blmIndex ] ) );

						// Add result item to list
						blmResultsDisplay += '<li><input type="hidden" value="' + blmResultValue + '" id="blm-result-select-' + blmIndex + '" /><a href="javascript:;" onClick="blmReplaceInput( ' + blmIndex + ' );">' + blmGeocoderResults[ blmIndex ].formatted_address + '</a></li>';

						// Increment count
						blmResultsCount++;

					}

					// Increment iteration
					blmIndex++;

				}

			}

		}

		// Show results
		document.getElementById( 'blm-location-results' ).setAttribute( 'data-display', 1 );

		// Check if results are found
		if( blmResultsDisplay ) {
			blmResultsDisplay = '<ul>' + blmResultsDisplay + '</ul>';
		} else {
			blmResultsDisplay = '<p>No locations found</p>';
		}

		// Throw results into HTML
		document.getElementById( 'blm-location-results' ).innerHTML = blmResultsDisplay;

	} );

}// blmGeocode

/**
* blmReplaceInput
* On click of a location result, put it in the input
*/
function blmReplaceInput( blmResultSelectId ) {

	// Select location
	var blmResult = JSON.parse( atob( document.getElementById( 'blm-result-select-' + blmResultSelectId ).value ) );

	// Construct address components
	var blmAddressComponentsDisplay = '';
	var blmAddressComponents = {
		street_number: '',
		route: '',
		locality: '',
		administrative_area_level_3: '',
		administrative_area_level_2: '',
		administrative_area_level_1: '',
		administrative_area_level_1_abbr: '',
		postal_code: '',
		country: '',
		country_abbr: ''
	};

	// Loop through address components of selected address
	for( var i = 0; i < blmResult.address_components.length; i++ ) {

		// Get address component type
		var blmAddressComponentsType = blmResult.address_components[ i ].types;
		if( ! blmAddressComponentsType ) {
			continue;
		}

		// Form HTML
		var blmAddressComponentsTypeDisplay = blmAddressComponentsType[0].charAt(0).toUpperCase() +  blmAddressComponentsType[0].replace( /_/g, ' ' ).substr(1);
		blmAddressComponents[ blmAddressComponentsType[0] ] = blmResult.address_components[ i ].long_name;
		blmAddressComponentsDisplay += '<li><strong>' + blmAddressComponentsTypeDisplay + ':</strong> ' + blmAddressComponents[ blmAddressComponentsType[0] ] + '</li>';

		// Handle HTML for abbreviations
		if( blmAddressComponentsType[0] == 'administrative_area_level_1' || blmAddressComponentsType[0] == 'country' ) {
			blmAddressComponents[ blmAddressComponentsType[0] + '_abbr' ] = blmResult.address_components[ i ].short_name;
			blmAddressComponentsDisplay += '<li><strong>' + blmAddressComponentsTypeDisplay + ' abbr:</strong> ' + blmAddressComponents[ blmAddressComponentsType[0] + '_abbr' ] + '</li>';
		}

	}

	blmAddressComponents = btoa( JSON.stringify( blmAddressComponents ) );
	var blmLocation = blmResult.geometry.location;
	var blmLatitude = blmLocation[ Object.keys( blmLocation )[0] ];
	var blmLongitude = blmLocation[ Object.keys( blmLocation )[1] ];

	// Change location details to be saved and selected location title
	document.getElementById( 'blm-location-choice-value' ).innerHTML = blmResult.formatted_address;
	document.getElementById( 'blm-formatted-address' ).value = encodeURI( blmResult.formatted_address );
	document.getElementById( 'blm-address-components' ).value = blmAddressComponents;
	document.getElementById( 'blm-latitude' ).value = blmLatitude;
	document.getElementById( 'blm-longitude' ).value = blmLongitude;
	document.getElementById( 'blm-location-results' ).setAttribute( 'data-display', 0 );
	document.getElementById( 'blm-location-choice' ).setAttribute( 'data-display', 1 );
	document.getElementById( 'blm-location-choice-components' ).innerHTML = '<ul>' + blmAddressComponentsDisplay + '</ul>';

}// blmReplaceInput

/*
* blmClearLocation
* Clear currently selected location
*/
function blmClearLocation() {

	document.getElementById( 'blm-location-choice-value' ).innerHTML = '';
	document.getElementById( 'blm-formatted-address' ).value = '';
	document.getElementById( 'blm-address-components' ).value =  '';
	document.getElementById( 'blm-latitude' ).value = '';
	document.getElementById( 'blm-longitude' ).value = '';
	document.getElementById( 'blm-location-choice' ).setAttribute( 'data-display', 0 );

}// blmClearLocation

/*
* blmLocationDetailsChange
* Show or hide the location details
*/
function blmLocationDetailsChange( blmDetailsRequest ) {

	if( blmDetailsRequest === 'show' ){
		var blmDetailsDisplay = 'block';
		var blmDetailsText = 'Hide details';
	} else {
		var blmDetailsDisplay = 'none';
		var blmDetailsText = 'Show details';
	}

	document.getElementById( 'blm-location-choice-components' ).style.display = blmDetailsDisplay;
	document.getElementById( 'blm-location-details' ).setAttribute( 'onClick', "blmLocationDetailsChange('" + ( ( blmDetailsRequest === 'show' ) ? 'hide' : 'show' ) + "')" );
	document.getElementById( 'blm-location-details' ).innerHTML = blmDetailsText;

}// blmLocationDetailsChange

/*
* blmLocationCheck
* 
*/
function blmLocationCheck( blmLocation, blmLocationType ) {

	switch( blmLocationType ) {

		case 'address':

			if('street_number' === blmLocation.address_components[0].types[0] 
				&& '' != blmLocation.address_components[0].long_name 
				&& 'route' === blmLocation.address_components[1].types[0] 
				&& '' != blmLocation.address_components[1].long_name ) {

				return true;

			}

			break;

		case 'intersection':

			if('route' === blmLocation.address_components[0].types[0]                          
				&& '' != blmLocation.address_components[0].long_name 
				&& 'intersection' === blmLocation.types[0] ) {

				return true;

			}

			break;

	}

	return false;

}//blmLocationCheck

