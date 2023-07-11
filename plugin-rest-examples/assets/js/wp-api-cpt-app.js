


( function( $ ) {

	// ajax
	var request = $.ajax({
		url: wpApiSettings.url,
		method: 'GET',
		crossDomain: true,
		beforeSend: function ( xhr ) {
			xhr.setRequestHeader( 'Authorization', 'Basic ' + btoa( wpApiSettings.user_name + ':' +  wpApiSettings.user_pass ) );
		},
	});	

	request.done(function( data, txtStatus, xhr ) {
		console.log( data );
		console.log( xhr.status );
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
		'Authorization': ('Basic ' + btoa( wpApiSettings.user_name + ':' +  wpApiSettings.user_pass)),
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