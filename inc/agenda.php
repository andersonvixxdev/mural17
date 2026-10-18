<?php
/**
 * Agenda do 17: eventos do portal (redação, leitores e órgãos públicos),
 * eventos achados nas notícias importadas (para a redação aprovar),
 * ingressos à venda no Sympla e o e-mail "Agenda do fim de semana".
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Categorias da agenda (slug => nome, como gravado no campo do evento).
 *
 * @return array<string, string>
 */
function mural17_categorias_evento() {
	return array(
		'shows'     => 'Shows',
		'festas'    => 'Festas e rodeios',
		'teatro'    => 'Teatro e cinema',
		'infantil'  => 'Infantil',
		'feiras'    => 'Feiras',
		'esporte'   => 'Esporte',
		'religioso' => 'Religioso',
	);
}

/**
 * Entrada gratuita?
 *
 * @param string $valor Campo "Entrada".
 * @return bool
 */
function mural17_evento_gratis( $valor ) {
	return (bool) preg_match( '/\b(gratu|gratis|livre|franca|sem custo)/', mural17_normaliza( $valor ) );
}

/**
 * Intervalo de datas de um filtro da agenda.
 *
 * @param string $quando hoje | fds | semana | mes | AAAA-MM-DD.
 * @return string[] array( início, fim ) em AAAA-MM-DD.
 */
function mural17_intervalo_agenda( $quando ) {
	$hoje = new DateTimeImmutable( 'today', wp_timezone() );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $quando ) ) {
		return array( $quando, $quando );
	}
	switch ( $quando ) {
		case 'hoje':
			return array( $hoje->format( 'Y-m-d' ), $hoje->format( 'Y-m-d' ) );
		case 'fds':
			$dow = (int) $hoje->format( 'N' ); // 1 = segunda … 7 = domingo.
			$sab = $dow >= 6 ? $hoje : $hoje->modify( '+' . ( 6 - $dow ) . ' days' );
			$dom = 7 === $dow ? $hoje : $sab->modify( '+1 day' );
			return array( $sab->format( 'Y-m-d' ), $dom->format( 'Y-m-d' ) );
		case 'mes':
			return array( $hoje->format( 'Y-m-d' ), $hoje->modify( '+30 days' )->format( 'Y-m-d' ) );
		default:
			return array( $hoje->format( 'Y-m-d' ), $hoje->modify( '+6 days' )->format( 'Y-m-d' ) );
	}
}

/**
 * Eventos (do portal e do Sympla) que acontecem entre duas datas, em ordem.
 * Evento de vários dias aparece em cada dia do intervalo em que acontece.
 *
 * @param string       $ini    AAAA-MM-DD.
 * @param string       $fim    AAAA-MM-DD.
 * @param WP_Term|null $escopo Cidade ou região.
 * @param string       $cat    Slug da categoria.
 * @param bool         $gratis Só gratuitos.
 * @return array[] Itens do mesmo formato (ver mural17_item_evento).
 */
function mural17_eventos_entre( $ini, $fim, $escopo = null, $cat = '', $gratis = false ) {
	$args = array(
		'post_type'      => 'evento',
		'post_status'    => 'publish',
		'posts_per_page' => 300,
		'no_found_rows'  => true,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'AND',
			array( 'key' => '_mural17_evento_data', 'value' => $fim, 'compare' => '<=', 'type' => 'DATE' ),
			array(
				'relation' => 'OR',
				array( 'key' => '_mural17_evento_data', 'value' => $ini, 'compare' => '>=', 'type' => 'DATE' ),
				array( 'key' => '_mural17_evento_fim', 'value' => $ini, 'compare' => '>=', 'type' => 'DATE' ),
			),
		),
	);
	$nomes = mural17_categorias_evento();
	if ( $cat && isset( $nomes[ $cat ] ) ) {
		$args['meta_query'][] = array( 'key' => '_mural17_evento_categoria', 'value' => $nomes[ $cat ] );
	}
	if ( $escopo ) {
		$args['tax_query'] = mural17_tax_cidade( $escopo ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$itens = array();
	foreach ( ( new WP_Query( $args ) )->posts as $p ) {
		$itens[] = mural17_item_evento( $p );
	}
	if ( ! $cat || isset( $nomes[ $cat ] ) ) {
		foreach ( mural17_eventos_sympla( $escopo ) as $s ) {
			if ( $s['data'] <= $fim && max( $s['data'], $s['fim'] ) >= $ini && ( ! $cat || $s['cat'] === $nomes[ $cat ] ) ) {
				$itens[] = $s;
			}
		}
	}
	if ( $gratis ) {
		$itens = array_filter(
			$itens,
			function ( $i ) {
				return mural17_evento_gratis( $i['valor'] );
			}
		);
	}
	usort(
		$itens,
		function ( $a, $b ) {
			return array( $a['data'], $a['hora'] ? $a['hora'] : '99' ) <=> array( $b['data'], $b['hora'] ? $b['hora'] : '99' );
		}
	);
	return array_values( $itens );
}

/**
 * Evento do portal no formato da agenda.
 *
 * @param WP_Post $p Evento.
 * @return array
 */
function mural17_item_evento( $p ) {
	$m      = function ( $k ) use ( $p ) {
		return (string) get_post_meta( $p->ID, '_mural17_' . $k, true );
	};
	$origem = $m( 'evento_origem' );
	$orgao  = (int) get_post_meta( $p->ID, '_mural17_orgao_id', true );
	$o      = $orgao ? get_term( $orgao, 'orgao' ) : null;
	return array(
		'tipo'   => 'post',
		'id'     => $p->ID,
		'titulo' => html_entity_decode( get_the_title( $p ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		'data'   => $m( 'evento_data' ),
		'fim'    => $m( 'evento_fim' ) ? $m( 'evento_fim' ) : $m( 'evento_data' ),
		'hora'   => $m( 'evento_hora' ),
		'local'  => $m( 'evento_local' ),
		'cidade' => mural17_cidade( $p->ID ),
		'valor'  => $m( 'evento_valor' ),
		'cat'    => $m( 'evento_categoria' ),
		'link'   => get_permalink( $p ),
		'img'    => get_the_post_thumbnail_url( $p, 'medium_large' ),
		'origem' => $o && ! is_wp_error( $o ) ? 'oficial' : $origem,
		'quem'   => $o && ! is_wp_error( $o ) ? $o->name : $m( 'evento_quem' ),
	);
}

/**
 * Selo de origem de um evento.
 *
 * @param array $i Item.
 * @return string HTML.
 */
function mural17_selo_evento( $i ) {
	switch ( $i['origem'] ) {
		case 'oficial':
			return '<span class="ag-selo ag-selo--oficial">' . esc_html( ( $i['quem'] ? $i['quem'] : 'Prefeitura' ) . ' · oficial' ) . '</span>';
		case 'sympla':
			return '<span class="ag-selo ag-selo--sympla">Ingressos via Sympla</span>';
		case 'bilheteria':
			return '<span class="ag-selo ag-selo--sympla">' . esc_html( 'Ingressos via ' . preg_replace( '/^www\./', '', $i['quem'] ) ) . '</span>';
		case 'leitor':
			return '<span class="ag-selo">Enviado por leitor</span>';
		case 'noticia':
			return '<span class="ag-selo">Da notícia</span>';
	}
	return '';
}

/* ---- Sympla ------------------------------------------------------------- */

/**
 * Cidades lidas no Sympla (as que têm casa de show e produtor).
 *
 * @return string[] Slugs.
 */
function mural17_cidades_sympla() {
	return array( 'rio-preto', 'catanduva', 'votuporanga', 'barretos', 'fernandopolis', 'jales', 'olimpia', 'mirassol', 'bebedouro', 'santa-fe-do-sul', 'novo-horizonte', 'jose-bonifacio' );
}

/**
 * Lê a página pública de eventos do Sympla de cada cidade. A API oficial do
 * Sympla só lista os eventos do próprio produtor; a página da cidade traz os
 * eventos em dados estruturados (schema.org/Event), os mesmos que o Google lê.
 *
 * @return int|WP_Error Quantos eventos ficaram guardados.
 */
function mural17_sympla_atualiza() {
	$itens = array();
	$erros = array();
	foreach ( mural17_cidades_sympla() as $slug ) {
		$c = get_term_by( 'slug', $slug, 'cidade' );
		if ( ! $c ) {
			continue;
		}
		$url = apply_filters( 'mural17_sympla_url', 'https://www.sympla.com.br/eventos/' . sanitize_title( remove_accents( $c->name ) ) . '-sp', $c );
		$r   = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => 'Mozilla/5.0 (compatible; Mural17/1.0; +https://mural17.com.br)',
			)
		);
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
			$erros[] = $c->name . ': ' . ( is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $r ) );
			continue;
		}
		foreach ( mural17_eventos_ldjson( wp_remote_retrieve_body( $r ) ) as $e ) {
			$it = mural17_le_evento_sympla( $e, $c );
			if ( $it ) {
				$itens[ $it['id'] ] = $it;
			}
		}
	}
	$itens = apply_filters( 'mural17_sympla_extras', $itens );
	update_option(
		'mural17_sympla',
		array(
			'em'    => time(),
			'itens' => array_values( $itens ),
		),
		false
	);
	if ( $erros ) {
		update_option( 'mural17_sympla_erro', array( implode( ' · ', array_slice( $erros, 0, 4 ) ), time() ), false );
	} else {
		delete_option( 'mural17_sympla_erro' );
	}
	return count( $itens );
}

/**
 * Eventos (schema.org/Event) dentro dos blocos application/ld+json de uma
 * página, em qualquer nível (@graph, ItemList, listas).
 *
 * @param string $html Página.
 * @return array[]
 */
function mural17_eventos_ldjson( $html ) {
	$saida = array();
	if ( ! preg_match_all( '#<script[^>]+application/ld\+json[^>]*>(.*?)</script>#is', (string) $html, $m ) ) {
		return $saida;
	}
	$anda = function ( $no ) use ( &$anda, &$saida ) {
		if ( ! is_array( $no ) ) {
			return;
		}
		$tipo = isset( $no['@type'] ) ? (array) $no['@type'] : array();
		foreach ( $tipo as $t ) {
			if ( is_string( $t ) && false !== stripos( $t, 'Event' ) && isset( $no['name'], $no['startDate'] ) ) {
				$saida[] = $no;
				return;
			}
		}
		foreach ( $no as $v ) {
			$anda( $v );
		}
	};
	foreach ( $m[1] as $bloco ) {
		$anda( json_decode( html_entity_decode( trim( $bloco ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true ) );
	}
	return $saida;
}

/**
 * Evento do Sympla no formato da agenda, só se for numa cidade do 17 e
 * ainda não tiver passado.
 *
 * @param array   $e       Evento schema.org.
 * @param WP_Term $buscada Cidade da página lida.
 * @return array|null
 */
function mural17_le_evento_sympla( $e, $buscada ) {
	$tz    = wp_timezone();
	$ini   = strtotime( (string) $e['startDate'] );
	$fim   = isset( $e['endDate'] ) ? strtotime( (string) $e['endDate'] ) : $ini;
	$link  = esc_url_raw( (string) ( $e['url'] ?? '' ) );
	if ( ! $ini || '' === $link || max( $ini, $fim ) < time() - DAY_IN_SECONDS ) {
		return null;
	}
	$loc    = isset( $e['location'] ) && is_array( $e['location'] ) ? $e['location'] : array();
	$loc    = isset( $loc[0] ) ? $loc[0] : $loc;
	$cidade = (string) ( $loc['address']['addressLocality'] ?? '' );
	$c      = $buscada;
	if ( '' !== $cidade ) {
		$mapa = mural17_mapa_nomes_cidades();
		$n    = mural17_normaliza( $cidade );
		$c    = isset( $mapa[ $n ] ) ? get_term( (int) $mapa[ $n ], 'cidade' ) : ( false !== strpos( $n, 'rio preto' ) ? $buscada : null );
	}
	if ( ! $c || is_wp_error( $c ) ) {
		return null; // Fora do 17.
	}
	$oferta = isset( $e['offers'] ) && is_array( $e['offers'] ) ? $e['offers'] : array();
	$oferta = isset( $oferta[0] ) ? $oferta[0] : $oferta;
	$preco  = $oferta['lowPrice'] ?? ( $oferta['price'] ?? '' );
	$valor  = '' === (string) $preco ? '' : ( 0.0 === (float) $preco ? 'Gratuito' : 'a partir de R$ ' . number_format( (float) $preco, 0, ',', '.' ) );
	$nome   = trim( wp_strip_all_tags( html_entity_decode( (string) $e['name'], ENT_QUOTES, 'UTF-8' ) ) );
	$img    = isset( $e['image'] ) ? ( is_array( $e['image'] ) ? reset( $e['image'] ) : $e['image'] ) : '';
	return array(
		'tipo'   => 'sympla',
		'id'     => 's' . substr( md5( $link ), 0, 12 ),
		'titulo' => mb_substr( $nome, 0, 120 ),
		'data'   => wp_date( 'Y-m-d', $ini, $tz ),
		'fim'    => wp_date( 'Y-m-d', $fim, $tz ),
		'hora'   => wp_date( 'H:i', $ini, $tz ),
		'local'  => trim( wp_strip_all_tags( (string) ( $loc['name'] ?? '' ) ) ),
		'cidade' => $c,
		'valor'  => $valor,
		'cat'    => mural17_categoria_do_texto( $nome ),
		'link'   => $link,
		'img'    => is_string( $img ) ? esc_url_raw( $img ) : '',
		'origem' => 'sympla',
		'quem'   => '',
	);
}

/**
 * Eventos do Sympla guardados, num escopo.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @return array[]
 */
function mural17_eventos_sympla( $escopo = null ) {
	$g     = get_option( 'mural17_sympla' );
	$itens = is_array( $g ) && isset( $g['itens'] ) ? $g['itens'] : array();
	if ( ! $escopo ) {
		return $itens;
	}
	$ids = mural17_ids_escopo( $escopo );
	return array_values(
		array_filter(
			$itens,
			function ( $i ) use ( $ids ) {
				return $i['cidade'] && in_array( (int) $i['cidade']->term_id, $ids, true );
			}
		)
	);
}

/* ---- Eventos achados nas notícias --------------------------------------- */

/**
 * Categoria pelo texto.
 *
 * @param string $texto Texto.
 * @return string Nome da categoria (ou "Outros").
 */
function mural17_categoria_do_texto( $texto ) {
	$t = ' ' . mural17_normaliza( $texto ) . ' ';
	$m = array(
		'Festas e rodeios' => '/ (festa|rodeio|peao|quermesse|aniversario d|carnaval|cavalgada|baile|arraia|festival junino)/',
		'Shows'            => '/ (show|cantor|cantora|banda|dupla|concerto|musical|orquestra|sertanej|pagode|samba)/',
		'Teatro e cinema'  => '/ (teatro|cinema|espetaculo|peca |filme|stand up|danca|ballet)/',
		'Infantil'         => '/ (infantil|criancas|kids|dia das criancas)/',
		'Feiras'           => '/ (feira|exposicao|expo |mostra|bazar)/',
		'Esporte'          => '/ (corrida|campeonato|torneio|maratona|jogo |partida|pedal|ciclismo|futebol)/',
		'Religioso'        => '/ (missa|culto|procissao|cantata|romaria|novena)/',
	);
	foreach ( $m as $nome => $re ) {
		if ( preg_match( $re, $t ) ) {
			return $nome;
		}
	}
	return 'Outros';
}

/**
 * Procura um evento futuro num texto: palavra de evento + data nos próximos
 * 120 dias. Devolve data, hora e local, ou null.
 *
 * @param string $titulo Título.
 * @param string $texto  Texto.
 * @return array|null
 */
function mural17_evento_no_texto( $titulo, $texto ) {
	$bruto = wp_strip_all_tags( $titulo . ". \n" . $texto );
	$n     = ' ' . mural17_normaliza( $bruto ) . ' ';
	if ( ! preg_match( '/ (show|festa|festival|rodeio|feira|apresentac|espetaculo|corrida|campeonato|exposicao|quermesse|aniversario d|cinema|teatro|cantata|concerto|desfile|torneio|baile|cavalgada|carnaval|gincana|mostra|encontro de|palestra|oficina|missa) /', $n ) ) {
		return null;
	}
	$tz    = wp_timezone();
	$hoje  = new DateTimeImmutable( 'today', $tz );
	$limite = $hoje->modify( '+120 days' );
	$meses = array( 'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'abril' => 4, 'maio' => 5, 'junho' => 6, 'julho' => 7, 'agosto' => 8, 'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12 );
	$tx    = strtolower( remove_accents( $bruto ) );
	$datas = array();
	if ( preg_match_all( '/\b(\d{1,2})(?:º|o)? de (' . implode( '|', array_keys( $meses ) ) . ')(?: de (\d{4}))?/u', $tx, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m as $x ) {
			$datas[] = array( (int) $x[1][0], $meses[ $x[2][0] ], isset( $x[3] ) ? (int) $x[3][0] : 0, $x[0][1] );
		}
	}
	if ( preg_match_all( '/(?<![\d\/])(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?(?![\d\/])/', $tx, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m as $x ) {
			$datas[] = array( (int) $x[1][0], (int) $x[2][0], isset( $x[3] ) ? (int) $x[3][0] : 0, $x[0][1] );
		}
	}
	$melhor = null;
	foreach ( $datas as $d ) {
		list( $dia, $mes, $ano, $pos ) = $d;
		$ano = $ano ? ( $ano < 100 ? 2000 + $ano : $ano ) : (int) $hoje->format( 'Y' );
		if ( ! checkdate( $mes, $dia, $ano ) ) {
			continue;
		}
		$dt = $hoje->setDate( $ano, $mes, $dia );
		if ( ! $d[2] && $dt < $hoje->modify( '-60 days' ) ) {
			$dt = $dt->modify( '+1 year' );
		}
		if ( $dt >= $hoje && $dt <= $limite && ( ! $melhor || $dt < $melhor[0] ) ) {
			$melhor = array( $dt, $pos );
		}
	}
	if ( ! $melhor ) {
		return null;
	}
	$hora = '';
	$depois = substr( $tx, $melhor[1], 160 );
	if ( preg_match( '/(?:as|a partir das|das)\s+(\d{1,2})(?:h|:)(\d{2})?/', $depois, $h ) || preg_match( '/\b(\d{1,2})h(\d{2})?\b/', $depois, $h ) ) {
		if ( (int) $h[1] <= 23 ) {
			$hora = sprintf( '%02d:%02d', (int) $h[1], isset( $h[2] ) && '' !== $h[2] ? (int) $h[2] : 0 );
		}
	}
	$local = '';
	// Nome de lugar com inicial maiúscula ("na Praça Matriz", não "na praça").
	if ( preg_match( '/\b(?:na|no|em)\s+((?:Praça|Recinto|Teatro|Centro|Parque|Ginásio|Estádio|Clube|Igreja|Paróquia|Salão|Espaço|Concha|Arena|Auditório|Câmara|Biblioteca|Museu|Avenida|Rua|Quadra|Sesc|Estação|Lago|Represa|Pavilhão|Casa de Cultura|Associação|Rodeio)[^,.;:\n(]{0,55})/u', $bruto, $l ) ) {
		$local = trim( $l[1] );
	}
	return array(
		'data'  => $melhor[0]->format( 'Y-m-d' ),
		'hora'  => $hora,
		'local' => $local,
		'cat'   => mural17_categoria_do_texto( $titulo . ' ' . $texto ),
	);
}

/**
 * Matéria importada que anuncia evento vira sugestão (rascunho) na agenda.
 *
 * @param int    $meta_id Meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 * @param mixed  $valor   Valor.
 */
function mural17_sugere_evento( $meta_id, $post_id, $chave, $valor ) {
	if ( '_pfai_feed_id' !== $chave || ! (int) $valor || get_post_meta( $post_id, '_mural17_evento_sugestao', true ) ) {
		return;
	}
	$p = get_post( $post_id );
	if ( ! $p || 'post' !== $p->post_type ) {
		return;
	}
	$e = mural17_evento_no_texto( $p->post_title, $p->post_excerpt . ' ' . $p->post_content );
	if ( ! $e ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'evento',
			'post_status'  => 'draft',
			'post_title'   => mb_substr( wp_strip_all_tags( $p->post_title ), 0, 120 ),
			'post_content' => wp_trim_words( wp_strip_all_tags( $p->post_excerpt ? $p->post_excerpt : $p->post_content ), 60, '…' ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return;
	}
	update_post_meta( $post_id, '_mural17_evento_sugestao', $id );
	update_post_meta( $id, '_mural17_evento_sugerido', $post_id );
	update_post_meta( $id, '_mural17_evento_origem', 'noticia' );
	update_post_meta( $id, '_mural17_evento_data', $e['data'] );
	update_post_meta( $id, '_mural17_evento_hora', $e['hora'] );
	update_post_meta( $id, '_mural17_evento_local', $e['local'] );
	update_post_meta( $id, '_mural17_evento_categoria', $e['cat'] );
	update_post_meta( $id, '_mural17_evento_link', get_permalink( $p ) );
	$cid = wp_get_object_terms( $post_id, 'cidade', array( 'fields' => 'ids' ) );
	if ( $cid && ! is_wp_error( $cid ) ) {
		wp_set_object_terms( $id, array( (int) $cid[0] ), 'cidade' );
	}
	$foto = get_post_thumbnail_id( $p );
	if ( $foto ) {
		set_post_thumbnail( $id, $foto );
	}
}
add_action( 'added_post_meta', 'mural17_sugere_evento', 23, 4 );

/**
 * Tela "Eventos sugeridos" no painel.
 */
function mural17_pagina_eventos_sugeridos() {
	$lista = get_posts(
		array(
			'post_type'      => 'evento',
			'post_status'    => 'draft',
			'posts_per_page' => 100,
			'meta_key'       => '_mural17_evento_sugerido', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	$feito = isset( $_GET['feito'] ) ? sanitize_key( wp_unslash( $_GET['feito'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap">
		<h1>Eventos encontrados nas notícias</h1>
		<?php if ( $feito ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo 'aprovado' === $feito ? 'Evento publicado na agenda.' : 'Sugestão descartada.'; ?></p></div>
		<?php endif; ?>
		<p>Matérias importadas que anunciam um evento com data. A data, a hora e o local foram tirados do texto: confira antes de aprovar. Aprovado, o evento entra na agenda com link para a matéria.</p>
		<table class="widefat striped">
			<thead><tr><th>Evento</th><th>Data e hora</th><th>Cidade · local</th><th>Categoria</th><th>Da matéria</th><th></th></tr></thead>
			<tbody>
				<?php if ( ! $lista ) : ?>
					<tr><td colspan="6">Nenhuma sugestão agora.</td></tr>
				<?php endif; ?>
				<?php foreach ( $lista as $e ) : ?>
					<?php
					$i   = mural17_item_evento( $e );
					$mat = get_post( (int) get_post_meta( $e->ID, '_mural17_evento_sugerido', true ) );
					?>
					<tr>
						<td><b><?php echo esc_html( $i['titulo'] ); ?></b></td>
						<td><?php echo esc_html( wp_date( 'd/m', strtotime( $i['data'] . ' 12:00' ) ) . ( $i['hora'] ? ' · ' . str_replace( ':00', 'h', $i['hora'] ) : ' · sem hora' ) ); ?></td>
						<td><?php echo esc_html( implode( ' · ', array_filter( array( $i['cidade'] ? $i['cidade']->name : 'sem cidade', $i['local'] ) ) ) ); ?></td>
						<td><?php echo esc_html( $i['cat'] ); ?></td>
						<td><?php echo $mat ? '<a href="' . esc_url( get_permalink( $mat ) ) . '" target="_blank">' . esc_html( wp_trim_words( get_the_title( $mat ), 8 ) ) . '</a>' : '—'; ?></td>
						<td style="white-space:nowrap">
							<?php foreach ( array( 'aprovar' => 'Aprovar', 'descartar' => 'Descartar' ) as $acao => $rotulo ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<input type="hidden" name="action" value="mural17_sugestao_evento">
									<input type="hidden" name="o_que" value="<?php echo esc_attr( $acao ); ?>">
									<input type="hidden" name="id" value="<?php echo (int) $e->ID; ?>">
									<?php wp_nonce_field( 'mural17_sugestao_evento_' . $e->ID, 'mural17_nonce' ); ?>
									<button class="button<?php echo 'aprovar' === $acao ? ' button-primary' : ''; ?>"><?php echo esc_html( $rotulo ); ?></button>
								</form>
							<?php endforeach; ?>
							<a class="button" href="<?php echo esc_url( get_edit_post_link( $e->ID ) ); ?>">Editar</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

add_action(
	'admin_menu',
	function () {
		$n = count(
			get_posts(
				array(
					'post_type'      => 'evento',
					'post_status'    => 'draft',
					'posts_per_page' => 50,
					'fields'         => 'ids',
					'meta_key'       => '_mural17_evento_sugerido', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
		add_submenu_page( 'mural17-servicos', 'Eventos sugeridos', 'Eventos sugeridos' . ( $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '' ), 'edit_others_posts', 'mural17-eventos-sugeridos', 'mural17_pagina_eventos_sugeridos' );
	},
	21
);

add_action(
	'admin_post_mural17_sugestao_evento',
	function () {
		$id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! current_user_can( 'edit_others_posts' ) || ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_sugestao_evento_' . $id ) || 'evento' !== get_post_type( $id ) ) {
			wp_die( 'Sem permissão.' );
		}
		$aprovar = 'aprovar' === sanitize_key( wp_unslash( $_POST['o_que'] ?? '' ) );
		if ( $aprovar ) {
			wp_publish_post( $id );
		} else {
			wp_trash_post( $id );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mural17-eventos-sugeridos&feito=' . ( $aprovar ? 'aprovado' : 'descartado' ) ) );
		exit;
	}
);

/* ---- Eventos pelo painel do órgão --------------------------------------- */

/**
 * Aba "Eventos" no painel do órgão.
 *
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_eventos( $orgao ) {
	$meus = get_posts(
		array(
			'post_type'      => 'evento',
			'post_status'    => array( 'publish', 'pending' ),
			'posts_per_page' => 10,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_mural17_orgao_id', 'value' => $orgao->term_id ),
				array( 'key' => '_mural17_evento_data', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
			),
			'meta_key'       => '_mural17_evento_data', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);
	?>
	<div class="oficial-painel__grade">
		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Divulgar evento</h2>
			<?php mural17_aviso_oficial(); ?>
			<input type="hidden" name="action" value="mural17_evento_orgao">
			<?php wp_nonce_field( 'mural17_evento_orgao', 'mural17_nonce' ); ?>
			<p class="campo"><label for="ev-t">Nome do evento</label><input id="ev-t" name="titulo" required maxlength="120" placeholder="Ex.: Festa de aniversário da cidade"></p>
			<div class="formulario__dupla">
				<p class="campo"><label for="ev-d">Data</label><input id="ev-d" type="date" name="data" required min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></p>
				<p class="campo"><label for="ev-h">Horário</label><input id="ev-h" type="time" name="hora"></p>
			</div>
			<div class="formulario__dupla">
				<p class="campo"><label for="ev-f">Até (se durar mais de um dia)</label><input id="ev-f" type="date" name="fim"></p>
				<p class="campo"><label for="ev-c">Categoria</label><select id="ev-c" name="categoria"><?php foreach ( mural17_categorias_evento() + array( 'outros' => 'Outros' ) as $nome ) : ?><option><?php echo esc_html( $nome ); ?></option><?php endforeach; ?></select></p>
			</div>
			<p class="campo"><label for="ev-l">Local</label><input id="ev-l" name="local" required placeholder="Ex.: Praça da Matriz"></p>
			<div class="formulario__dupla">
				<p class="campo"><label for="ev-v">Entrada</label><input id="ev-v" name="valor" placeholder="Gratuita, R$ 20…"></p>
				<p class="campo"><label for="ev-k">Link de ingressos ou informações</label><input id="ev-k" type="url" name="link" placeholder="https://"></p>
			</div>
			<p class="campo"><label for="ev-x">Descrição</label><textarea id="ev-x" name="descricao" rows="5" placeholder="Programação, atrações, o que levar."></textarea></p>
			<p class="formulario__acoes"><button type="submit" class="botao botao--pilula botao--oficial"><?php echo mural17_publicacao_direta( $orgao ) ? 'Publicar na agenda' : 'Enviar para a agenda'; ?></button></p>
		</form>
		<aside class="oficial-minhas">
			<h2>Próximos eventos</h2>
			<?php if ( $meus ) : ?>
				<ul>
					<?php foreach ( $meus as $e ) : ?>
						<li>
							<span class="situacao <?php echo 'publish' === $e->post_status ? 'situacao--ok' : 'situacao--espera'; ?>"><?php echo 'publish' === $e->post_status ? 'Na agenda' : 'Em revisão'; ?></span>
							<b><?php echo esc_html( get_the_title( $e ) ); ?></b>
							<small><?php echo esc_html( mural17_evento_quando( $e->ID ) ); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="vazio">Nenhum evento daqui para frente.</p>
			<?php endif; ?>
		</aside>
	</div>
	<?php
}

/**
 * Recebe o evento do painel do órgão.
 */
function mural17_recebe_evento_orgao() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_evento_orgao' ) ) {
		mural17_volta_aba( 'eventos', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao || ! isset( mural17_abas_painel( $orgao )['eventos'] ) ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}
	$titulo = mural17_post( 'titulo' );
	$data   = mural17_post( 'data' );
	if ( '' === $titulo || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data ) ) {
		mural17_volta_aba( 'eventos', 'erro-evento' );
	}
	$direta = mural17_publicacao_direta( $orgao );
	$id     = wp_insert_post(
		array(
			'post_type'    => 'evento',
			'post_status'  => $direta ? 'publish' : 'pending',
			'post_title'   => $titulo,
			'post_content' => wpautop( mural17_post( 'descricao', true ) ),
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		mural17_volta_aba( 'eventos', 'erro-evento' );
	}
	$fim = mural17_post( 'fim' );
	$cat = mural17_post( 'categoria' );
	update_post_meta( $id, '_mural17_orgao_id', $orgao->term_id );
	update_post_meta( $id, '_mural17_evento_origem', 'oficial' );
	update_post_meta( $id, '_mural17_evento_data', $data );
	update_post_meta( $id, '_mural17_evento_fim', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fim ) && $fim > $data ? $fim : '' );
	update_post_meta( $id, '_mural17_evento_hora', preg_match( '/^\d{2}:\d{2}$/', mural17_post( 'hora' ) ) ? mural17_post( 'hora' ) : '' );
	update_post_meta( $id, '_mural17_evento_local', mural17_post( 'local' ) );
	update_post_meta( $id, '_mural17_evento_valor', mural17_post( 'valor' ) );
	update_post_meta( $id, '_mural17_evento_link', esc_url_raw( mural17_post( 'link' ) ) );
	update_post_meta( $id, '_mural17_evento_categoria', in_array( $cat, array_merge( array_values( mural17_categorias_evento() ), array( 'Outros' ) ), true ) ? $cat : 'Outros' );
	$cidade = mural17_cidade_do_orgao( $orgao );
	if ( $cidade ) {
		wp_set_object_terms( $id, array( (int) $cidade->term_id ), 'cidade' );
	}
	mural17_volta_aba( 'eventos', $direta ? 'evento-ok' : 'evento-revisao' );
}
add_action( 'admin_post_mural17_evento_orgao', 'mural17_recebe_evento_orgao' );

/* ---- Agendamentos e e-mail de quinta ------------------------------------ */

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'mural17_le_sympla' ) ) {
			wp_schedule_event( time() + 300, 'daily', 'mural17_le_sympla' );
		}
		if ( ! wp_next_scheduled( 'mural17_agenda_fds' ) ) {
			$hoje = new DateTimeImmutable( 'today 10:00', wp_timezone() );
			$dow  = (int) $hoje->format( 'N' );
			$qui  = $hoje->modify( '+' . ( ( 4 - $dow + 7 ) % 7 ) . ' days' );
			if ( $qui->getTimestamp() <= time() ) {
				$qui = $qui->modify( '+7 days' );
			}
			wp_schedule_event( $qui->getTimestamp(), 'weekly', 'mural17_agenda_fds' );
		}
	}
);
add_action( 'mural17_le_sympla', 'mural17_sympla_atualiza' );

/**
 * Texto do e-mail "Agenda do fim de semana" de um escopo.
 *
 * @param WP_Term|null $escopo Cidade, região ou o 17.
 * @return string Vazio se não houver evento.
 */
function mural17_texto_agenda_fds( $escopo ) {
	list( $ini, $fim ) = mural17_intervalo_agenda( 'fds' );
	$sexta             = wp_date( 'Y-m-d', strtotime( $ini . ' 12:00 -1 day' ) );
	$itens             = mural17_eventos_entre( $sexta, $fim, $escopo );
	if ( ! $itens ) {
		return '';
	}
	$linhas = array();
	$dia    = '';
	foreach ( array_slice( $itens, 0, 25 ) as $i ) {
		$d = max( $i['data'], $sexta );
		if ( $d !== $dia ) {
			$dia      = $d;
			$linhas[] = "\n" . mb_strtoupper( wp_date( 'l, j/m', strtotime( $d . ' 12:00' ) ) );
		}
		$linhas[] = '• ' . ( $i['hora'] ? str_replace( ':00', 'h', $i['hora'] ) . ' · ' : '' ) . $i['titulo'] . ' · ' . implode( ', ', array_filter( array( $i['local'], $i['cidade'] ? $i['cidade']->name : '' ) ) ) . ( $i['valor'] ? ' · ' . $i['valor'] : '' ) . "\n  " . $i['link'];
	}
	return trim( implode( "\n", $linhas ) );
}

/**
 * Toda quinta, o que fazer no fim de semana, para quem se inscreveu em
 * "agenda" (só vai quando há evento no escopo da pessoa).
 *
 * @param int $desde Offset do lote.
 */
function mural17_envia_agenda_fds( $desde = 0 ) {
	$lote = 80;
	$q    = new WP_Query(
		array(
			'post_type'      => 'inscrito',
			'post_status'    => 'private',
			'posts_per_page' => $lote,
			'offset'         => (int) $desde,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_m17_confirmado', 'compare' => 'EXISTS' ),
				array( 'key' => '_m17_tema', 'value' => 'agenda' ),
			),
		)
	);
	$feitos = array();
	foreach ( $q->posts as $p ) {
		$cid = (int) get_post_meta( $p->ID, '_m17_cidade', true );
		if ( ! isset( $feitos[ $cid ] ) ) {
			$escopo         = $cid ? get_term( $cid, 'cidade' ) : null;
			$escopo         = $escopo && ! is_wp_error( $escopo ) ? $escopo : null;
			$feitos[ $cid ] = array( $escopo, mural17_texto_agenda_fds( $escopo ) );
		}
		list( $escopo, $texto ) = $feitos[ $cid ];
		if ( '' === $texto ) {
			continue;
		}
		$url   = add_query_arg( array( 'quando' => 'fds' ) + ( $escopo ? array( 'cidade' => $escopo->slug ) : array() ), get_post_type_archive_link( 'evento' ) );
		$token = (string) get_post_meta( $p->ID, '_m17_token', true );
		wp_mail(
			(string) get_post_meta( $p->ID, '_m17_email', true ),
			'[Mural 17] O que fazer no fim de semana ' . ( $escopo ? 'em ' . mural17_nome_curto( $escopo ) : 'no 17' ),
			$texto . "\n\nA agenda completa: " . $url . "\n\n--\nVocê recebe porque se inscreveu na agenda do Mural 17.\nParar de receber: " . mural17_link_inscricao( 'cancela', $token )
		);
	}
	if ( count( $q->posts ) === $lote ) {
		wp_schedule_single_event( time() + 60, 'mural17_agenda_fds', array( (int) $desde + $lote ) );
	}
}
add_action( 'mural17_agenda_fds', 'mural17_envia_agenda_fds', 10, 1 );

add_action(
	'admin_post_mural17_admin_sympla',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_sympla' );
		mural17_sympla_atualiza();
		mural17_volta_admin_servicos( get_option( 'mural17_sympla_erro' ) ? 'sympla-erro' : 'sympla', 'sympla' );
	}
);

/* ---- Página da agenda --------------------------------------------------- */

/**
 * Na agenda, ?cidade= é filtro da página, não arquivo de cidade.
 *
 * @param WP_Query $q Consulta.
 */
function mural17_consulta_agenda( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( 'evento' ) ) {
		return;
	}
	if ( '' !== (string) $q->get( 'cidade' ) ) {
		$q->set( 'cidade', '' );
		$q->set( 'tax_query', array() );
		$q->is_tax = false;
	}
	$q->set( 'posts_per_page', 1 );
}
add_action( 'pre_get_posts', 'mural17_consulta_agenda', 5 );

/* ---- Agenda oficial da Prefeitura de Rio Preto -------------------------- */

/**
 * Categoria do portal para a categoria da agenda de Rio Preto.
 *
 * @param string $cat Categoria de lá.
 * @return string
 */
function mural17_categoria_sjrp( $cat ) {
	$c = mural17_normaliza( $cat );
	$m = array(
		'Shows'            => array( 'show', 'musica', 'concerto' ),
		'Festas e rodeios' => array( 'festa', 'festival', 'desfile', 'movimento' ),
		'Teatro e cinema'  => array( 'teatro', 'danca', 'mostra' ),
		'Feiras'           => array( 'feira', 'exposicao', 'gastronomia' ),
		'Esporte'          => array( 'corrida', 'campeonato', 'torneio' ),
	);
	foreach ( $m as $nome => $lista ) {
		if ( in_array( $c, $lista, true ) ) {
			return $nome;
		}
	}
	return 'Outros';
}

/**
 * Lê a agenda oficial de Rio Preto (eventos.riopreto.sp.gov.br): a lista
 * vem no HTML da página e o detalhe de cada evento (local, descrição, link,
 * se cobra ingresso) num JSON que o próprio site busca por POST.
 *
 * @return int|WP_Error Quantos eventos foram criados ou atualizados.
 */
function mural17_sjrp_atualiza() {
	$base = apply_filters( 'mural17_sjrp_base', 'https://eventos.riopreto.sp.gov.br/' );
	$r    = wp_remote_get( $base . 'agenda', array( 'timeout' => 20, 'user-agent' => 'Mozilla/5.0 (compatible; Mural17/1.0)' ) );
	if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		$e = is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $r );
		update_option( 'mural17_sjrp_erro', array( $e, time() ), false );
		return new WP_Error( 'sjrp', $e );
	}
	$cidade = get_term_by( 'slug', 'rio-preto', 'cidade' );
	$feitos = 0;
	$det    = 0;
	foreach ( mural17_cartoes_sjrp( wp_remote_retrieve_body( $r ) ) as $c ) {
		$ini = DateTime::createFromFormat( 'd/m/Y - H:i', $c['ini'], wp_timezone() );
		$fim = DateTime::createFromFormat( 'd/m/Y - H:i', $c['fim'], wp_timezone() );
		if ( ! $ini || ! $fim || $fim->getTimestamp() < time() ) {
			continue;
		}
		// Atração permanente (exposição de meses, caminho de peregrinação) não é evento de agenda.
		if ( $fim->getTimestamp() - $ini->getTimestamp() > 45 * DAY_IN_SECONDS ) {
			continue;
		}
		$ext = 'sjrp-' . $c['id'];
		$ja  = get_posts(
			array(
				'post_type'      => 'evento',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mural17_evento_ext', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $ext, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		if ( $ja && 'trash' === get_post_status( $ja[0] ) ) {
			continue; // A redação descartou: não volta.
		}
		$info = array();
		// Detalhe para evento novo ou para o que ficou sem ele na leitura anterior.
		if ( ( ! $ja || '' === (string) get_post_meta( $ja[0], '_mural17_evento_link', true ) ) && $det < 30 ) {
			++$det;
			$d = wp_remote_post( $base . 'informacoesEvento/' . rawurlencode( $c['id'] ) . '/', array( 'timeout' => 15, 'user-agent' => 'Mozilla/5.0 (compatible; Mural17/1.0)' ) );
			if ( ! is_wp_error( $d ) && 200 === (int) wp_remote_retrieve_response_code( $d ) ) {
				$corpo = wp_remote_retrieve_body( $d );
				$corpo = mb_check_encoding( $corpo, 'UTF-8' ) ? $corpo : mb_convert_encoding( $corpo, 'UTF-8', 'ISO-8859-1' );
				$info  = (array) json_decode( $corpo, true );
			}
		}
		$titulo = html_entity_decode( (string) ( $info['nomeEvento'] ?? $c['titulo'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$desc   = isset( $info['descricao'] ) ? wp_kses_post( wpautop( str_replace( array( "\r<br>", '<br>' ), "\n", (string) $info['descricao'] ) ) ) : esc_html( $c['resumo'] );
		$id     = wp_insert_post(
			array(
				'ID'           => $ja ? $ja[0] : 0,
				'post_type'    => 'evento',
				'post_status'  => $ja ? get_post_status( $ja[0] ) : 'publish',
				'post_title'   => mb_substr( trim( $titulo ), 0, 140 ),
				'post_content' => $ja ? get_post_field( 'post_content', $ja[0] ) : $desc,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			continue;
		}
		++$feitos;
		update_post_meta( $id, '_mural17_evento_ext', $ext );
		update_post_meta( $id, '_mural17_evento_origem', 'oficial' );
		update_post_meta( $id, '_mural17_evento_quem', 'Prefeitura de Rio Preto' );
		update_post_meta( $id, '_mural17_evento_data', $ini->format( 'Y-m-d' ) );
		update_post_meta( $id, '_mural17_evento_fim', $fim->format( 'Y-m-d' ) > $ini->format( 'Y-m-d' ) ? $fim->format( 'Y-m-d' ) : '' );
		update_post_meta( $id, '_mural17_evento_hora', '00:00' === $ini->format( 'H:i' ) ? '' : $ini->format( 'H:i' ) );
		update_post_meta( $id, '_mural17_evento_categoria', mural17_categoria_sjrp( $c['cat'] ) );
		if ( $info ) {
			$local = isset( $info['locais'][0]['endereco'] ) ? (string) $info['locais'][0]['endereco'] : '';
			$local = trim( preg_replace( '/\s*-\s*Endere[cç]o:.*$/iu', '', $local ) );
			update_post_meta( $id, '_mural17_evento_local', mb_substr( $local, 0, 120 ) );
			$valor = '';
			if ( isset( $info['cobraIngresso'] ) && ! $info['cobraIngresso'] ) {
				$valor = 'Gratuita';
			} elseif ( preg_match( '/R\$\s?\d[\d.,]*/u', wp_strip_all_tags( (string) ( $info['descricao'] ?? '' ) ), $p ) ) {
				$valor = 'a partir de ' . $p[0];
			}
			update_post_meta( $id, '_mural17_evento_valor', $valor );
			update_post_meta( $id, '_mural17_evento_link', esc_url_raw( ! empty( $info['urlWebsite'] ) ? $info['urlWebsite'] : $base . 'agenda?evento=' . rawurlencode( $c['id'] ) ) );
		}
		if ( $cidade ) {
			wp_set_object_terms( $id, array( (int) $cidade->term_id ), 'cidade' );
		}
		if ( ! has_post_thumbnail( $id ) ) {
			mural17_foto_sjrp( $id, $base . 'RecuperarImagem?id_evento=' . rawurlencode( $c['id'] ) . '&i=0', $c['id'] );
		}
	}
	update_option( 'mural17_sjrp', array( 'em' => time(), 'feitos' => $feitos ), false );
	delete_option( 'mural17_sjrp_erro' );
	return $feitos;
}

/**
 * Cartões de evento da página da agenda de Rio Preto.
 *
 * @param string $html Página.
 * @return array[] id, ini, fim, cat, titulo, resumo.
 */
function mural17_cartoes_sjrp( $html ) {
	$saida = array();
	$partes = preg_split( '/<div class="single-package/', (string) $html );
	array_shift( $partes );
	foreach ( $partes as $p ) {
		$g = function ( $re ) use ( $p ) {
			// O site codifica duas vezes ("&amp;#8211;"): decodifica até sumir.
			return preg_match( $re, $p, $m ) ? trim( html_entity_decode( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : '';
		};
		$id = $g( '/name="abrirModal"\s+id="(\d+)"/' );
		if ( '' === $id ) {
			continue;
		}
		$saida[ $id ] = array(
			'id'     => $id,
			'ini'    => $g( '/id="dataEvento"[^>]*>([^<]+)</' ),
			'fim'    => $g( '/id="dataFimEvento"[^>]*>([^<]+)</' ),
			'cat'    => $g( '/id="categoriaCard"[^>]*>([^<]+)</' ),
			'titulo' => $g( '/<h4>(.*?)<\/h4>/s' ),
			'resumo' => preg_replace( '/\s*\.\.\.\s*$/', '…', $g( '/<p style="height: auto[^"]*">(.*?)<\/p>/s' ) ),
		);
	}
	return array_values( $saida );
}

/**
 * Baixa a foto do evento de Rio Preto e põe como destaque.
 *
 * @param int    $post_id Evento.
 * @param string $url     Endereço da imagem.
 * @param string $ext_id  Código lá.
 */
function mural17_foto_sjrp( $post_id, $url, $ext_id ) {
	$r = wp_remote_get( $url, array( 'timeout' => 20 ) );
	if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		return;
	}
	$tipo = (string) wp_remote_retrieve_header( $r, 'content-type' );
	$ext  = false !== strpos( $tipo, 'png' ) ? 'png' : ( false !== strpos( $tipo, 'webp' ) ? 'webp' : 'jpg' );
	$up   = wp_upload_bits( 'evento-rio-preto-' . sanitize_file_name( $ext_id ) . '.' . $ext, null, wp_remote_retrieve_body( $r ) );
	if ( ! empty( $up['error'] ) ) {
		return;
	}
	$tipo_wp = wp_check_filetype( $up['file'] );
	if ( empty( $tipo_wp['type'] ) || 0 !== strpos( $tipo_wp['type'], 'image/' ) ) {
		wp_delete_file( $up['file'] );
		return;
	}
	$a = wp_insert_attachment(
		array(
			'post_mime_type' => $tipo_wp['type'],
			'post_title'     => get_the_title( $post_id ),
			'post_status'    => 'inherit',
		),
		$up['file'],
		$post_id
	);
	if ( $a && ! is_wp_error( $a ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $a, wp_generate_attachment_metadata( $a, $up['file'] ) );
		update_post_meta( $a, '_wp_attachment_image_alt', get_the_title( $post_id ) );
		set_post_thumbnail( $post_id, $a );
	}
}

/* ---- Outras bilheterias (Zé do Ingresso e afins) ------------------------ */

/**
 * Páginas de bilheterias lidas pelo mesmo padrão do Sympla (schema.org/Event).
 *
 * @return string[]
 */
function mural17_bilheterias() {
	$lista = array_filter( array_map( 'trim', explode( "\n", (string) get_option( 'mural17_bilheterias', "https://zedoingresso.com.br\nhttps://www.ingressodigital.com" ) ) ) );
	return array_values( array_filter( $lista, 'wp_http_validate_url' ) );
}

add_filter(
	'mural17_sympla_extras',
	function ( $itens ) {
		$rp = get_term_by( 'slug', 'rio-preto', 'cidade' );
		foreach ( mural17_bilheterias() as $url ) {
			$r = wp_remote_get( $url, array( 'timeout' => 15, 'user-agent' => 'Mozilla/5.0 (compatible; Mural17/1.0)' ) );
			if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
				continue;
			}
			foreach ( mural17_eventos_ldjson( wp_remote_retrieve_body( $r ) ) as $e ) {
				// Sem cidade no evento não dá para saber se é do 17: só entra com cidade.
				$loc = isset( $e['location'] ) && is_array( $e['location'] ) ? ( isset( $e['location'][0] ) ? $e['location'][0] : $e['location'] ) : array();
				if ( empty( $loc['address']['addressLocality'] ) ) {
					continue;
				}
				$it = mural17_le_evento_sympla( $e, $rp );
				if ( $it ) {
					$it['origem']        = 'bilheteria';
					$it['quem']          = (string) wp_parse_url( $url, PHP_URL_HOST );
					$itens[ $it['id'] ] = $it;
				}
			}
		}
		return $itens;
	}
);

add_action(
	'mural17_le_sympla',
	function () {
		mural17_sjrp_atualiza();
	},
	20
);

add_action(
	'admin_post_mural17_admin_sjrp',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_sjrp' );
		if ( isset( $_POST['bilheterias'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_option( 'mural17_bilheterias', sanitize_textarea_field( wp_unslash( $_POST['bilheterias'] ) ), false ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		$r = mural17_sjrp_atualiza();
		mural17_sympla_atualiza();
		mural17_volta_admin_servicos( is_wp_error( $r ) ? 'sjrp-erro' : 'sjrp', 'sympla' );
	}
);
