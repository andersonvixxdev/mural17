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

	// Seletor "Escolha sua cidade", localização e bloco "Agora em ...".
	var dados = window.mural17 || { cidades: [], rest: '' };
	var dialogo = document.getElementById( 'cidades' );
	var minha = guardado.ler( 'm17_cidade', null );

	function porId( id ) {
		for ( var i = 0; i < dados.cidades.length; i++ ) {
			if ( dados.cidades[ i ].id === id ) {
				return dados.cidades[ i ];
			}
		}
		return null;
	}

	function quando( iso ) {
		var d = new Date( iso );
		var hoje = new Date();
		var dois = function ( n ) {
			return ( n < 10 ? '0' : '' ) + n;
		};
		if ( d.toDateString() === hoje.toDateString() ) {
			return dois( d.getHours() ) + ':' + dois( d.getMinutes() );
		}
		hoje.setDate( hoje.getDate() - 1 );
		if ( d.toDateString() === hoje.toDateString() ) {
			return 'ontem';
		}
		return dois( d.getDate() ) + '/' + dois( d.getMonth() + 1 );
	}

	function texto( html ) {
		var el = document.createElement( 'textarea' );
		el.innerHTML = html;
		return el.value;
	}

	function mostrarMinha() {
		var convite = document.querySelector( '[data-convite-cidade]' );
		if ( ! minha ) {
			if ( convite ) {
				convite.hidden = false;
			}
			return;
		}
		if ( convite ) {
			convite.hidden = true;
		}
		document.querySelectorAll( '[data-minha-cidade]' ).forEach( function ( el ) {
			el.textContent = minha.nome;
		} );

		var bloco = document.querySelector( '[data-sua-cidade]' );
		if ( ! bloco || ! minha.id || ! dados.rest ) {
			return;
		}
		var lista = bloco.querySelector( '[data-sua-cidade-lista]' );
		var vazio = bloco.querySelector( '[data-sua-cidade-vazio]' );
		var link = bloco.querySelector( '[data-sua-cidade-link]' );
		bloco.querySelector( '[data-sua-cidade-nome]' ).textContent = minha.nome;
		link.href = minha.url;
		bloco.hidden = false;
		lista.textContent = '';
		vazio.hidden = true;

		var url = dados.rest + ( -1 === dados.rest.indexOf( '?' ) ? '?' : '&' ) +
			'cidade=' + minha.id + '&per_page=4&_fields=title,link,date';
		window.fetch( url ).then( function ( r ) {
			return r.ok ? r.json() : [];
		} ).then( function ( posts ) {
			if ( ! posts.length ) {
				var c = porId( minha.id );
				vazio.textContent = 'Ainda não há notícias de ' + minha.nome + '. ';
				if ( c ) {
					var a = document.createElement( 'a' );
					a.href = c.ru;
					a.textContent = 'Ver a região de ' + c.r + ' →';
					vazio.appendChild( a );
				}
				vazio.hidden = false;
				return;
			}
			posts.forEach( function ( p ) {
				var li = document.createElement( 'li' );
				var t = document.createElement( 'time' );
				t.dateTime = p.date;
				t.textContent = quando( p.date );
				var a = document.createElement( 'a' );
				a.href = p.link;
				a.textContent = texto( p.title.rendered );
				li.appendChild( t );
				li.appendChild( a );
				lista.appendChild( li );
			} );
		} ).catch( function () {
			bloco.hidden = true;
		} );
	}

	function escolher( cidade ) {
		minha = { id: cidade.id, nome: cidade.n, url: cidade.u };
		guardado.gravar( 'm17_cidade', minha );
	}

	function avisar( msg ) {
		document.querySelectorAll( '[data-aviso-local]' ).forEach( function ( el ) {
			el.textContent = msg;
		} );
	}

	function abrirLista() {
		if ( dialogo && 'function' === typeof dialogo.showModal && ! dialogo.open ) {
			dialogo.showModal();
		}
	}

	// Cidade mais próxima, calculada aqui no aparelho.
	function maisPerto( la, lo ) {
		var rad = Math.PI / 180;
		var melhor = null;
		var dist = Infinity;
		dados.cidades.forEach( function ( c ) {
			var dla = ( c.la - la ) * rad;
			var dlo = ( c.lo - lo ) * rad;
			var h = Math.sin( dla / 2 ) * Math.sin( dla / 2 ) +
				Math.cos( la * rad ) * Math.cos( c.la * rad ) * Math.sin( dlo / 2 ) * Math.sin( dlo / 2 );
			var km = 12742 * Math.asin( Math.sqrt( h ) );
			if ( km < dist ) {
				dist = km;
				melhor = c;
			}
		} );
		return { cidade: melhor, km: dist };
	}

	document.querySelectorAll( '[data-localizar]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			if ( ! navigator.geolocation ) {
				avisar( 'Este navegador não informa a localização. Escolha a cidade na lista.' );
				abrirLista();
				return;
			}
			b.disabled = true;
			avisar( 'Procurando sua cidade…' );
			navigator.geolocation.getCurrentPosition( function ( pos ) {
				b.disabled = false;
				var achado = maisPerto( pos.coords.latitude, pos.coords.longitude );
				if ( ! achado.cidade || achado.km > 40 ) {
					avisar( 'Você parece estar fora da região do 17. Escolha a cidade na lista.' );
					abrirLista();
					return;
				}
				escolher( achado.cidade );
				avisar( 'Pronto: ' + achado.cidade.n + '.' );
				if ( dialogo && dialogo.open ) {
					dialogo.close();
				}
				mostrarMinha();
			}, function () {
				b.disabled = false;
				avisar( 'Não foi possível usar a localização. Escolha a cidade na lista.' );
				abrirLista();
			}, { timeout: 10000, maximumAge: 3600000 } );
		} );
	} );

	mostrarMinha();

	function sem( txt ) {
		return txt.normalize( 'NFD' ).replace( /[\u0300-\u036f']/g, '' ).toLowerCase();
	}

	if ( dialogo && 'function' === typeof dialogo.showModal ) {
		var campo = document.getElementById( 'cidades-busca' );
		var grupos = dialogo.querySelectorAll( '.cidades__grupo' );
		var nada = dialogo.querySelector( '.cidades__nada' );

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

		document.querySelectorAll( '[data-abre-cidades]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				abrirLista();
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

		dialogo.querySelectorAll( '[data-id]' ).forEach( function ( a ) {
			a.addEventListener( 'click', function () {
				var c = porId( parseInt( a.getAttribute( 'data-id' ), 10 ) );
				if ( c ) {
					escolher( c );
				}
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
