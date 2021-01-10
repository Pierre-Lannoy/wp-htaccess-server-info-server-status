jQuery( document ).ready(
	function($) {
		function refreshValues() {
			if ( running ) {
				$.ajax(
					{
						type : "POST",
						url : ajaxurl,
						data : { action:"hsiss_get_status", nonce: livestatus.nonce },
						success: function( response ) {
							if ( response ) {
								items = JSON.parse( response );
								if ( undefined !== items.txt ) {
									if ( items.txt.length > 0 ) {
										items.txt.forEach(
											function (item) {
												$( "#" + item[0] ).text( item[1] );
											}
										);
									}
								}
								if ( undefined !== items.kpi ) {
									if ( items.kpi.length > 0 ) {
										items.kpi.forEach(
											function (item) {
												$( "#" + item[0] ).html( item[1] );
											}
										);
									}
								}

							}
						},
						error: function( response ) {
							console.log( response );
						},
						complete:function( response ) {
							setTimeout( refreshValues, livestatus.frequency );
						}
					}
				);
			} else {
				setTimeout( refreshValues, livestatus.frequency );
			}
		}

		running = true;
		refreshValues();

	}
);
