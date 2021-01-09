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
								if ( undefined !== items.kpi ) {
									if ( init ) {
										init = false;
									}
									if ( items.kpi.length > 0 ) {
										items.kpi.forEach(
											function (item) {
												$( "#" + item[0] ).text( item[1] );
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

		let running = true;
		let init    = true;
		refreshValues();

	}
);
