


( function( $ ) {

	// ajax
	var request = $.ajax( {
		url: wpApiSettings.url,
		method: 'GET',
		beforeSend: function ( xhr ) {
			xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
		},
		data:{
			//'title' : 'Hello Moon'
		}
	} );
	
	request.done(function( response ) {
	  console.log( response );
	});
	 
	request.fail(function( jqXHR, textStatus ) {
	  console.log( "Request failed: " + textStatus );
	});


	// fetch
	fetch(wpApiSettings.url, {
		method: 'GET',
		credentials: 'same-origin',
		headers: new Headers({
		'Content-Type': 'application/json;charset=UTF-8',
		'X-WP-Nonce' : wpApiSettings.nonce,
	}),
	}).then(response => {
		if ( response.status !== 200 ) {
			throw new Error( 'Problem! Status Code: ' + response.status );
		}
		response.json().then( posts => {
			console.log( posts ); // выведем в консоль
		});
	});



}( jQuery ) );