(function($) {
	$.fn.oauthpopup = function( options ) {
		var left = ( ( screen.width / 2 ) - ( 800 / 2 ) );
		var top = ( ( screen.height / 2 ) - ( 800 / 2 ) );

		var settings						=	$.extend( {
													url: null,
													name: 'oAuthLogin',
													specs: 'location=0,status=0,width=800,height=600,left=' + left + ',top=' + top,
													init: function(){ return true; },
													changed: function(){},
													callback: function(){ window.location.reload(); }
												}, options );

		$( this ).bind( 'click tap', function() {
			if ( settings.init( settings ) ) {
				var oAuthWindow				=	window.open( settings.url, settings.name, settings.specs );

				window.oAuthSuccess			=	false;
				window.oAuthError			=	null;
				window.oAuthClosed= false; 

				var windowClosed			=	window.setInterval( function(){
													if ( oAuthWindow.closed || window.oAuthClosed) {
														window.clearInterval( windowClosed );
														window.oAuthClosed= false;
														settings.callback( window.oAuthSuccess, window.oAuthError, oAuthWindow );
													}
												}, 1000 );
			}
		});
	};
})(jQuery);

(function($) {
	$.fn.oauthredirect = function( options ) {

		var settings						=	$.extend( {
													url: null,
													name: 'oAuthLogin',
													init: function(){ return true; },
													changed: function(){},
													callback: function(){ }
												}, options );

		$( this ).bind( 'click tap', function() {
			if ( settings.init( settings ) ) {
				window.location = settings.url;
			}
		});
	};
})(jQuery);