/**
 * Especial de apuração: placar, mapa, cidade por cidade, minuto a minuto,
 * faixa do topo e destaques. Lê o arquivo estático a cada minuto; quando
 * ele está velho, pede ao site uma leitura nova do TSE.
 */
( function () {
	'use strict';

	var cfg = window.M17AP || {};
	var dados = cfg.dados || null;
	var aba = 'br';
	var ordem = 'votos';
	var COR = [ '#0E7C86', '#C2410C' ];

	function q( sel, raiz ) {
		return ( raiz || document ).querySelector( sel );
	}
	function qq( sel, raiz ) {
		return Array.prototype.slice.call( ( raiz || document ).querySelectorAll( sel ) );
	}
	function num( n, casas ) {
		return Number( n || 0 ).toLocaleString( 'pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas } );
	}
	function hora( t ) {
		var d = new Date( t * 1000 );
		return d.getHours() + 'h' + ( '0' + d.getMinutes() ).slice( -2 );
	}
	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s == null ? '' : String( s );
		return d.innerHTML;
	}
	function lider( r ) {
		var p = par( r );
		if ( p.length < 2 ) {
			return -1;
		}
		var i = p[ 1 ].votos > p[ 0 ].votos ? 1 : 0;
		return p[ i ].votos > 0 ? i : -1;
	}
	// Os dois candidatos do placar: os mais votados no Brasil, em ordem de
	// número (no 2º turno são os únicos; no teste com o 1º turno, há vários).
	function par( r ) {
		if ( ! r || ! r.cand ) {
			return [];
		}
		var base = dados && dados.br && dados.br.cand ? dados.br.cand.slice() : r.cand.slice();
		base.sort( function ( a, b ) {
			return b.votos - a.votos;
		} );
		var nums = base.slice( 0, 2 ).map( function ( c ) {
			return c.num;
		} ).sort( function ( a, b ) {
			return Number( a ) - Number( b );
		} );
		return nums.map( function ( n ) {
			for ( var i = 0; i < r.cand.length; i++ ) {
				if ( r.cand[ i ].num === n ) {
					return r.cand[ i ];
				}
			}
			return { num: n, nome: '', votos: 0, pct: 0 };
		} );
	}
	function foto( c ) {
		return c && cfg.fotos && cfg.fotos[ c.num ] ? cfg.fotos[ c.num ] : '';
	}

	function aberta() {
		return ! cfg.teste && cfg.fecha && Date.now() / 1000 < cfg.fecha;
	}
	function resultado( escopo ) {
		if ( ! dados ) {
			return null;
		}
		if ( escopo.indexOf( 'cid:' ) === 0 ) {
			return dados.cid ? dados.cid[ escopo.slice( 4 ) ] || null : null;
		}
		return dados[ escopo ] || null;
	}

	/* ---------- Placar grande ---------- */
	function placar() {
		var caixa = q( '[data-ap-placar]' );
		if ( ! caixa ) {
			return;
		}
		var r = resultado( aba );
		var lid = lider( r );
		var pr = par( r );
		qq( '[data-ap-cand]' ).forEach( function ( el ) {
			var i = Number( el.getAttribute( 'data-ap-cand' ) );
			var c = pr[ i ] && pr[ i ].nome ? pr[ i ] : null;
			q( '[data-ap-nome]', el ).textContent = c ? c.nome : ( aberta() ? 'Apuração começa às ' + hora( cfg.fecha ) : 'Aguardando o TSE' );
			var ini = q( '[data-ap-inicial]', el );
			if ( foto( c ) ) {
				ini.innerHTML = '<img src="' + esc( foto( c ) ) + '" alt="">';
			} else {
				ini.textContent = c ? c.nome.charAt( 0 ) : '·';
			}
			q( '[data-ap-pct]', el ).textContent = c ? num( c.pct, 1 ) + '%' : '—';
			q( '[data-ap-votos]', el ).textContent = c ? num( c.votos, 0 ) + ' votos' : '';
			q( '[data-ap-selo]', el ).textContent = c && c.eleito ? 'Eleito, segundo o TSE' : ( c && i === lid && r.pst > 0 ? 'Lidera' : '' );
			el.classList.toggle( 'is-lider', !! c && i === lid && r.pst > 0 );
		} );
		var p0 = pr[ 0 ] ? pr[ 0 ].pct : 0;
		var p1 = pr[ 1 ] ? pr[ 1 ].pct : 0;
		var tot = p0 + p1 || 1;
		q( '[data-ap-barra="0"]' ).style.width = ( 100 * p0 / tot ) + '%';
		q( '[data-ap-barra="1"]' ).style.width = ( 100 * p1 / tot ) + '%';
		q( '[data-ap-pst]' ).innerHTML = '<b>' + num( r ? r.pst : 0, 2 ) + '%</b> das seções totalizadas';
		var extra = [];
		if ( r && r.brancos ) {
			extra.push( 'Brancos ' + num( r.brancos, 1 ) + '%' );
		}
		if ( r && r.nulos ) {
			extra.push( 'Nulos ' + num( r.nulos, 1 ) + '%' );
		}
		if ( r && r.abst ) {
			extra.push( 'Abstenção ' + num( r.abst, 1 ) + '%' );
		}
		q( '[data-ap-extra]' ).textContent = extra.join( ' · ' );
		var h = q( '[data-ap-hora]' );
		if ( h && dados && dados.br && dados.br.hora ) {
			h.textContent = 'Resultados oficiais do TSE, atualizados a cada minuto. Última totalização: ' + dados.br.hora.replace( /:\d\d$/, '' ).replace( ':', 'h' ) + '.';
		}
	}

	/* ---------- Mapa ---------- */
	function mapa() {
		var area = q( '[data-ap-mapa]' );
		if ( ! area ) {
			return;
		}
		qq( 'path[data-cidade]', area ).forEach( function ( p ) {
			var r = dados && dados.cid ? dados.cid[ p.getAttribute( 'data-cidade' ) ] : null;
			var i = lider( r );
			if ( ! r || ! r.pst || i < 0 ) {
				p.style.fill = 'url(#ap-hach)';
				p.style.fillOpacity = '1';
				return;
			}
			var pm = par( r );
			var m = Math.abs( pm[ 0 ].pct - pm[ 1 ].pct );
			p.style.fill = COR[ i ];
			p.style.fillOpacity = m < 5 ? '0.38' : ( m < 15 ? '0.68' : '1' );
		} );
		var nomes = dados ? par( dados.br ) : [];
		qq( '[data-ap-leg]' ).forEach( function ( el ) {
			var c = nomes[ Number( el.getAttribute( 'data-ap-leg' ) ) ];
			if ( c ) {
				el.textContent = c.nome;
			}
		} );
	}

	function dica( p, ev ) {
		var tip = q( '[data-ap-tip]' );
		var area = q( '[data-ap-mapa]' );
		var slug = p.getAttribute( 'data-cidade' );
		var r = dados && dados.cid ? dados.cid[ slug ] : null;
		var nome = r ? r.nome : ( q( 'title', p ) ? q( 'title', p ).textContent : slug );
		var html = '<b>' + esc( nome ) + '</b>';
		if ( r && r.cand && r.pst > 0 ) {
			par( r ).forEach( function ( c, i ) {
				html += '<span class="ap-tip__l"><span><i style="background:' + COR[ i ] + '"></i>' + esc( c.nome ) + '</span><b>' + num( c.pct, 1 ) + '%</b></span>';
			} );
			html += '<small>' + num( r.pst, 1 ) + '% das seções · ' + num( r.vv, 0 ) + ' votos válidos</small>';
		} else {
			html += '<small>Apuração ainda não começou.</small>';
		}
		tip.innerHTML = html;
		tip.hidden = false;
		var a = area.getBoundingClientRect();
		var b = p.getBoundingClientRect();
		var x = ( ev && ev.clientX ? ev.clientX : b.left + b.width / 2 ) - a.left;
		var y = ( ev && ev.clientY ? ev.clientY : b.top + b.height / 2 ) - a.top;
		tip.style.left = Math.max( 4, Math.min( x + 14, a.width - tip.offsetWidth - 4 ) ) + 'px';
		tip.style.top = Math.max( 4, y - tip.offsetHeight - 10 ) + 'px';
		qq( 'path.is-sel', area ).forEach( function ( o ) {
			o.classList.remove( 'is-sel' );
		} );
		p.classList.add( 'is-sel' );
	}

	/* ---------- Cidade por cidade ---------- */
	function tabela() {
		var corpo = q( '[data-ap-tabela]' );
		if ( ! corpo || ! dados || ! dados.cid ) {
			return;
		}
		var nomes = par( dados.br );
		qq( '[data-ap-col]' ).forEach( function ( th ) {
			var c = nomes[ Number( th.getAttribute( 'data-ap-col' ) ) ];
			th.textContent = c ? c.nome : '—';
		} );
		var lista = Object.keys( dados.cid ).map( function ( s ) {
			var r = dados.cid[ s ];
			r.slug = s;
			var pt = par( r );
			r.margem = pt.length > 1 ? Math.abs( pt[ 0 ].pct - pt[ 1 ].pct ) : 0;
			return r;
		} );
		if ( ! lista.length ) {
			return;
		}
		lista.sort( function ( a, b ) {
			if ( ordem === 'nome' ) {
				return a.nome.localeCompare( b.nome, 'pt-BR' );
			}
			if ( ordem === 'margem' ) {
				return b.margem - a.margem;
			}
			if ( ordem === 'pst' ) {
				return a.pst - b.pst;
			}
			return ( b.vv || 0 ) - ( a.vv || 0 );
		} );
		corpo.innerHTML = lista.map( function ( r ) {
			var pc = par( r );
			var c0 = pc[ 0 ] || { pct: 0 };
			var c1 = pc[ 1 ] || { pct: 0 };
			var t = r.pst ? ( c0.pct + c1.pct || 1 ) : Infinity;
			var lid = r.pst ? lider( r ) : -1;
			return '<tr><th scope="row">' + esc( r.nome ) + '</th>' +
				'<td' + ( lid === 0 ? ' class="is-lider"' : '' ) + '>' + ( r.pst ? num( c0.pct, 1 ) + '%' : '—' ) + '</td>' +
				'<td' + ( lid === 1 ? ' class="is-lider"' : '' ) + '>' + ( r.pst ? num( c1.pct, 1 ) + '%' : '—' ) + '</td>' +
				'<td><span class="ap-mini"><i style="width:' + ( 100 * c0.pct / t ) + '%;background:' + COR[ 0 ] + '"></i><i style="width:' + ( 100 * c1.pct / t ) + '%;background:' + COR[ 1 ] + '"></i></span></td>' +
				'<td>' + num( r.pst, 1 ) + '%</td></tr>';
		} ).join( '' );
	}

	/* ---------- Minuto a minuto ---------- */
	function feed() {
		var ul = q( '[data-ap-feed]' );
		if ( ! ul || ! dados ) {
			return;
		}
		var itens = [];
		( dados.marcos || [] ).forEach( function ( m ) {
			itens.push( { t: m.t, html: '<h3><span class="ap-tag">Apuração</span>' + esc( m.texto ) + '</h3>', auto: true } );
		} );
		( dados.posts || [] ).forEach( function ( p ) {
			itens.push( { t: p.t, html: '<h3><a href="' + esc( p.link ) + '">' + esc( p.titulo ) + '</a></h3>' + ( p.resumo ? '<p>' + esc( p.resumo ) + '</p>' : '' ) } );
		} );
		if ( ! itens.length ) {
			return;
		}
		itens.sort( function ( a, b ) {
			return b.t - a.t;
		} );
		ul.innerHTML = itens.slice( 0, 30 ).map( function ( i ) {
			return '<li' + ( i.auto ? ' class="is-auto"' : '' ) + '><time>' + hora( i.t ) + '</time><div>' + i.html + '</div></li>';
		} ).join( '' );
	}

	/* ---------- Faixa do topo e destaques ---------- */
	function barrinha( r ) {
		var p = par( r );
		if ( p.length < 2 ) {
			return '';
		}
		var t = p[ 0 ].pct + p[ 1 ].pct || 1;
		var lado = function ( c ) {
			return ( foto( c ) ? '<img class="ap-rosto" src="' + esc( foto( c ) ) + '" alt="">' : '' ) + esc( c.nome.split( ' ' )[ 0 ] ) + ' ' + num( c.pct, 1 ) + '%';
		};
		return '<span>' + lado( p[ 0 ] ) + '</span>' +
			'<span class="ap-mini ap-mini--faixa"><i style="width:' + ( 100 * p[ 0 ].pct / t ) + '%;background:' + COR[ 0 ] + '"></i><i style="width:' + ( 100 * p[ 1 ].pct / t ) + '%;background:' + COR[ 1 ] + '"></i></span>' +
			'<span>' + lado( p[ 1 ] ) + '</span>';
	}

	function faixa() {
		var f = q( '[data-ap-faixa]' );
		if ( ! f || ! dados || ! dados.br || aberta() ) {
			return;
		}
		var t = q( '[data-ap-faixa-txt]', f );
		var mini = q( '[data-ap-mini]', f );
		t.textContent = 'Apuração para presidente · ' + num( dados.br.pst, 1 ) + '% das urnas no Brasil';
		mini.innerHTML = barrinha( dados.br );
		mini.hidden = ! dados.br.pst;
	}

	function destaques() {
		qq( '[data-ap-destaque]' ).forEach( function ( el ) {
			var slug = el.getAttribute( 'data-ap-destaque' );
			var r = slug && dados && dados.cid ? dados.cid[ slug ] : null;
			var onde = r ? r.nome : 'no Brasil';
			if ( ! r ) {
				r = dados ? dados.br : null;
			}
			var alvo = q( '[data-ap-destaque-in]', el );
			if ( aberta() ) {
				alvo.innerHTML = '<p class="ap-dest__txt">Urnas abertas até ' + hora( cfg.fecha ) + '. A apuração começa logo depois, ao vivo aqui.</p>';
				return;
			}
			if ( ! r || ! r.pst ) {
				alvo.innerHTML = '<p class="ap-dest__txt">Aguardando as primeiras urnas.</p>';
				return;
			}
			alvo.innerHTML = '<p class="ap-dest__onde">' + esc( onde.charAt( 0 ).toUpperCase() + onde.slice( 1 ) ) + ' · ' + num( r.pst, 1 ) + '% apuradas</p><p class="ap-dest__barra">' + barrinha( r ) + '</p>';
		} );
	}

	/* ---------- Contagem regressiva ---------- */
	function relogio() {
		var box = q( '[data-ap-contagem]' );
		if ( ! box ) {
			return;
		}
		var falta = Math.round( cfg.fecha - Date.now() / 1000 );
		box.hidden = ! aberta();
		if ( falta <= 0 ) {
			return;
		}
		var h = Math.floor( falta / 3600 );
		var m = Math.floor( ( falta % 3600 ) / 60 );
		var s = falta % 60;
		q( '[data-ap-relogio]', box ).textContent = ( '0' + h ).slice( -2 ) + ':' + ( '0' + m ).slice( -2 ) + ':' + ( '0' + s ).slice( -2 );
	}

	function tudo() {
		placar();
		mapa();
		tabela();
		feed();
		faixa();
		destaques();
	}

	/* ---------- Leitura ---------- */
	function carregar() {
		if ( document.hidden || ! cfg.arquivo ) {
			return;
		}
		fetch( cfg.arquivo + '?v=' + Math.floor( Date.now() / 30000 ), { cache: 'no-store' } )
			.then( function ( r ) {
				return r.ok ? r.json() : null;
			} )
			.catch( function () {
				return null;
			} )
			.then( function ( d ) {
				if ( d && d.em ) {
					dados = d;
					tudo();
				}
				var velho = ! d || ! d.em || Date.now() / 1000 - d.em > 90;
				if ( velho && cfg.rest && cfg.ativo && Date.now() / 1000 >= cfg.fecha - 300 ) {
					return fetch( cfg.rest, { cache: 'no-store' } ).then( function ( r ) {
						return r.ok ? r.json() : null;
					} ).then( function ( n ) {
						if ( n && n.em ) {
							dados = n;
							tudo();
						}
					} ).catch( function () {} );
				}
			} );
	}

	/* ---------- Eventos ---------- */
	qq( '[data-ap-aba]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			aba = b.getAttribute( 'data-ap-aba' );
			qq( '[data-ap-aba]' ).forEach( function ( o ) {
				o.classList.toggle( 'is-on', o === b );
			} );
			var sel = q( '[data-ap-cidade-sel]' );
			if ( sel ) {
				sel.value = '';
				sel.parentNode.classList.remove( 'is-on' );
			}
			placar();
		} );
	} );
	var sel = q( '[data-ap-cidade-sel]' );
	if ( sel ) {
		sel.addEventListener( 'change', function () {
			if ( ! sel.value ) {
				return;
			}
			aba = 'cid:' + sel.value;
			qq( '[data-ap-aba]' ).forEach( function ( o ) {
				o.classList.remove( 'is-on' );
			} );
			sel.parentNode.classList.add( 'is-on' );
			placar();
		} );
		// Começa na cidade que a pessoa já escolheu no portal.
		try {
			var minha = JSON.parse( window.localStorage.getItem( 'm17_cidade' ) || 'null' );
			var slug = minha && minha.url ? ( minha.url.match( /cidade\/([^/]+)/ ) || [] )[ 1 ] : '';
			if ( slug && q( 'option[value="' + slug + '"]', sel ) ) {
				sel.value = slug;
			}
		} catch ( e ) {}
	}
	qq( '[data-ap-ordem]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			ordem = b.getAttribute( 'data-ap-ordem' );
			qq( '[data-ap-ordem]' ).forEach( function ( o ) {
				o.classList.toggle( 'is-on', o === b );
			} );
			tabela();
		} );
	} );
	qq( '[data-ap-mapa] path[data-cidade]' ).forEach( function ( p ) {
		p.addEventListener( 'mousemove', function ( ev ) {
			dica( p, ev );
		} );
		p.addEventListener( 'click', function ( ev ) {
			dica( p, ev );
		} );
		p.addEventListener( 'focus', function () {
			dica( p );
		} );
	} );
	var area = q( '[data-ap-mapa]' );
	if ( area ) {
		area.addEventListener( 'mouseleave', function () {
			q( '[data-ap-tip]' ).hidden = true;
		} );
	}

	tudo();
	relogio();
	setInterval( relogio, 1000 );
	carregar();
	setInterval( carregar, 60000 );
	document.addEventListener( 'visibilitychange', function () {
		if ( ! document.hidden ) {
			carregar();
		}
	} );
}() );
