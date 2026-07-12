/**
 * Unregister the legacy core-embed/wordpress block when embeds are disabled.
 */
( function ( wp ) {
	if ( ! wp || ! wp.dom || ! wp.dom.blocks || typeof wp.dom.blocks.unregisterBlockType !== 'function' ) {
		return;
	}

	try {
		wp.dom.blocks.unregisterBlockType( 'core-embed/wordpress' );
	} catch ( e ) {
		// Block may already be unregistered or unavailable.
	}
}( window.wp ) );
