/**
 * Mural 17 — menu do celular, busca, seletor de cidade e bolinhas.
 */
( function () {
	var guardado = {
		ler: function ( chave, padrao ) {
			try {
				var v = window.localStorage.getItem( chave );
				return v ? JSON.parse( v ) : padrao;
			} catch ( e ) {
				return padrao;
			}
		},
		gravar: function ( chave, valor ) {
			try {
				window.localStorage.setItem( chave, JSON.stringify( valor ) );
			} catch ( e ) {}
		}
	};

	// Menu de seções no celular.
	var botaoMenu = document.querySelector( '.menu-abrir' );
	var nav = document.getElementById( 'nav-principal' );
	if ( botaoMenu && nav ) {
		var alternarMenu = function ( abrir ) {
			nav.classList.toggle( 'is-aberto', abrir );
			botaoMenu.setAttribute( 'aria-expanded', abrir ? 'true' : 'false' );
			botaoMenu.setAttribute( 'aria-label', abrir ? 'Fechar menu' : 'Abrir menu' );
		};
		botaoMenu.addEventListener( 'click', function () {
			alternarMenu( ! nav.classList.contains( 'is-aberto' ) );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && nav.classList.contains( 'is-aberto' ) ) {
				alternarMenu( false );
				botaoMenu.focus();
			}
		} );
	}

	// Painel de busca.
	var botaoBusca = document.querySelector( '[data-abre-busca]' );
	var painel = document.getElementById( 'busca-painel' );
	if ( botaoBusca && painel ) {
		botaoBusca.addEventListener( 'click', function () {
			var abrir = painel.hidden;
			painel.hidden = ! abrir;
			botaoBusca.setAttribute( 'aria-expanded', abrir ? 'true' : 'false' );
			if ( abrir ) {
				var campo = painel.querySelector( 'input[type="search"]' );
				if ( campo ) {
					campo.focus();
				}
			}
		} );
	}

	// Seletor "Escolha sua cidade".
	var dialogo = document.getElementById( 'cidades' );
	var minha = guardado.ler( 'm17_cidade', null );

	function mostrarMinha() {
		if ( ! minha ) {
			return;
		}
		document.querySelectorAll( '[data-minha-cidade]' ).forEach( function ( el ) {
			el.textContent = minha.nome;
		} );
		var atalho = document.querySelector( '[data-atalho-cidade]' );
		if ( atalho ) {
			atalho.href = minha.url;
			atalho.querySelector( 'b' ).textContent = minha.nome;
			atalho.hidden = false;
		}
	}
	mostrarMinha();

	function sem( texto ) {
		return texto.normalize( 'NFD' ).replace( /[̀-ͯ']/g, '' ).toLowerCase();
	}

	if ( dialogo && 'function' === typeof dialogo.showModal ) {
		var campo = document.getElementById( 'cidades-busca' );
		var grupos = dialogo.querySelectorAll( '.cidades__grupo' );
		var nada = dialogo.querySelector( '.cidades__nada' );

		document.querySelectorAll( '[data-abre-cidades]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				dialogo.showModal();
				campo.value = '';
				filtrar( '' );
				campo.focus();
			} );
		} );
		dialogo.querySelector( '[data-fecha-cidades]' ).addEventListener( 'click', function () {
			dialogo.close();
		} );
		dialogo.addEventListener( 'click', function ( e ) {
			if ( e.target === dialogo ) {
				dialogo.close();
			}
		} );

		var filtrar = function ( termo ) {
			var t = sem( termo.trim() );
			var achou = 0;
			grupos.forEach( function ( g ) {
				var regiao = sem( g.querySelector( '.cidades__regiao' ).textContent );
				var visiveis = 0;
				g.querySelectorAll( 'li' ).forEach( function ( li ) {
					var ok = ! t || -1 !== sem( li.textContent ).indexOf( t ) || -1 !== regiao.indexOf( t );
					li.hidden = ! ok;
					if ( ok ) {
						visiveis++;
					}
				} );
				g.hidden = 0 === visiveis;
				achou += visiveis;
			} );
			nada.hidden = achou > 0;
		};
		campo.addEventListener( 'input', function () {
			filtrar( campo.value );
		} );
		campo.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) {
				var primeiro = dialogo.querySelector( '.cidades__grupo:not([hidden]) li:not([hidden]) a' );
				if ( primeiro ) {
					e.preventDefault();
					primeiro.click();
				}
			}
		} );

		dialogo.querySelectorAll( '[data-cidade]' ).forEach( function ( a ) {
			a.addEventListener( 'click', function () {
				minha = { nome: a.getAttribute( 'data-cidade' ).replace( /^Região de /, '' ), url: a.href };
				guardado.gravar( 'm17_cidade', minha );
			} );
		} );
	}

	// Bolinhas: o anel some quando não há nada novo desde a última visita.
	var vistos = guardado.ler( 'm17_vistos', {} );
	var agora = Math.floor( Date.now() / 1000 );
	var aqui = window.location.href.split( /[?#]/ )[ 0 ];
	if ( vistos[ aqui ] !== undefined || document.querySelector( '.roda' ) ) {
		vistos[ aqui ] = agora;
	}
	document.querySelectorAll( '.roda' ).forEach( function ( r ) {
		var ultima = parseInt( r.getAttribute( 'data-ultima' ), 10 ) || 0;
		var url = r.href.split( /[?#]/ )[ 0 ];
		if ( ! ultima || ( vistos[ url ] && vistos[ url ] >= ultima ) ) {
			r.classList.add( 'roda--vista' );
		}
		r.addEventListener( 'click', function () {
			vistos[ url ] = Math.floor( Date.now() / 1000 );
			guardado.gravar( 'm17_vistos', vistos );
		} );
	} );
	guardado.gravar( 'm17_vistos', vistos );
}() );
