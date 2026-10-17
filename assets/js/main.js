/**
 * Mural 17 — menu do celular.
 */
( function () {
	var botao = document.querySelector( '.menu-abrir' );
	var nav = document.getElementById( 'nav-principal' );
	if ( ! botao || ! nav ) {
		return;
	}

	function alternar( abrir ) {
		nav.classList.toggle( 'is-aberto', abrir );
		botao.setAttribute( 'aria-expanded', abrir ? 'true' : 'false' );
		botao.setAttribute( 'aria-label', abrir ? 'Fechar menu' : 'Abrir menu' );
	}

	botao.addEventListener( 'click', function () {
		alternar( ! nav.classList.contains( 'is-aberto' ) );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && nav.classList.contains( 'is-aberto' ) ) {
			alternar( false );
			botao.focus();
		}
	} );
}() );
