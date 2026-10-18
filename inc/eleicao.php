<?php
/**
 * Especial de apuração: resultados oficiais do TSE (Portal de Resultados,
 * arquivos públicos "dados simplificados") para presidente no Brasil, no
 * estado e nas 117 cidades do 17, com mapa, placar e minuto a minuto.
 *
 * O site guarda a última leitura e a publica num arquivo estático
 * (uploads/mural17/apuracao.json). O navegador lê esse arquivo; quando ele
 * fica velho, pede uma atualização, e só um pedido por minuto chega ao TSE.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/eleicao-ibge.php';

/**
 * Configuração do especial.
 *
 * @return array
 */
function mural17_eleicao_config() {
	return wp_parse_args(
		(array) get_option( 'mural17_eleicao' ),
		array(
			'modo'          => 'auto', // auto | ligado | desligado.
			'inicio'        => '2026-10-25T07:00',
			'fechamento'    => '2026-10-25T17:00',
			'fim'           => '2026-10-26T12:00',
			'titulo'        => '2º turno',
			'data'          => 'domingo, 25 de outubro',
			'cargo'         => 'presidente',
			'base'          => 'https://resultados.tse.jus.br/oficial',
			'pasta'         => 'ele2026',
			'eleicao'       => '', // Código do 2º turno (federal). Vazio = descobre sozinho.
			'eleicao_teste' => '', // Código do 1º turno, para o teste.
			'publicar'      => 1,  // Marcos principais viram matéria (e vão para as redes).
			'fotos'         => array( '13' => '', '22' => '' ), // Número do candidato => endereço da foto.
		)
	);
}

/**
 * Data da configuração no fuso do site.
 *
 * @param string $chave Chave.
 * @return int Timestamp.
 */
function mural17_eleicao_hora( $chave ) {
	$d = mural17_data( str_replace( 'T', ' ', mural17_eleicao_config()[ $chave ] ) );
	return $d ? $d->getTimestamp() : 0;
}

/**
 * O especial está no ar?
 *
 * @return bool
 */
function mural17_eleicao_ativa() {
	$c = mural17_eleicao_config();
	if ( 'ligado' === $c['modo'] ) {
		return true;
	}
	if ( 'desligado' === $c['modo'] ) {
		return false;
	}
	$agora = time();
	return $agora >= mural17_eleicao_hora( 'inicio' ) && $agora <= mural17_eleicao_hora( 'fim' );
}

/**
 * Prévia com os dados do teste (1º turno), só para quem administra.
 *
 * @return bool
 */
function mural17_eleicao_teste() {
	return isset( $_GET['teste'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Endereços dos arquivos públicos do TSE.
 *
 * @param string $tipo    config | municipios | resultado.
 * @param string $eleicao Código da eleição.
 * @param string $abr     br | sp.
 * @param string $mun     Código TSE do município (vazio = a abrangência inteira).
 * @return string
 */
function mural17_tse_url( $tipo, $eleicao = '', $abr = 'br', $mun = '' ) {
	$c   = mural17_eleicao_config();
	$e6  = str_pad( (string) $eleicao, 6, '0', STR_PAD_LEFT );
	$raiz = untrailingslashit( $c['base'] );
	switch ( $tipo ) {
		case 'config':
			$url = $raiz . '/comum/config/ele-c.json';
			break;
		case 'municipios':
			$url = "{$raiz}/{$c['pasta']}/{$eleicao}/config/mun-e{$e6}-cm.json";
			break;
		default:
			$url = "{$raiz}/{$c['pasta']}/{$eleicao}/dados-simplificados/{$abr}/{$abr}{$mun}-c0001-e{$e6}-r.json";
	}
	return apply_filters( 'mural17_tse_url', $url, $tipo, $eleicao, $abr, $mun );
}

/**
 * Baixa um JSON do TSE.
 *
 * @param string $url Endereço.
 * @return array|WP_Error
 */
function mural17_tse_json( $url ) {
	$r = wp_remote_get( $url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$codigo = (int) wp_remote_retrieve_response_code( $r );
	if ( 200 !== $codigo ) {
		return new WP_Error( 'tse_http', 'HTTP ' . $codigo . ' em ' . $url );
	}
	$j = json_decode( wp_remote_retrieve_body( $r ), true );
	return is_array( $j ) ? $j : new WP_Error( 'tse_json', 'Resposta sem JSON em ' . $url );
}

/**
 * Eleições publicadas no Portal de Resultados (para achar os códigos).
 *
 * @return array|WP_Error Lista de array( cd, nm, turno, data, pasta ).
 */
function mural17_tse_eleicoes() {
	$j = mural17_tse_json( mural17_tse_url( 'config' ) );
	if ( is_wp_error( $j ) ) {
		return $j;
	}
	$lista = array();
	foreach ( isset( $j['pl'] ) && is_array( $j['pl'] ) ? $j['pl'] : array() as $pleito ) {
		foreach ( isset( $pleito['e'] ) && is_array( $pleito['e'] ) ? $pleito['e'] : array() as $e ) {
			$lista[] = array(
				'cd'    => (string) ( $e['cd'] ?? '' ),
				'nm'    => (string) ( $e['nm'] ?? '' ),
				'turno' => (string) ( $e['t'] ?? '' ),
				'data'  => (string) ( $pleito['dt'] ?? '' ),
				'pasta' => (string) ( $j['c'] ?? '' ),
			);
		}
	}
	return $lista;
}

/**
 * Código da eleição federal (presidente) de um turno, descoberto no TSE e
 * guardado. A configuração manual, se houver, manda.
 *
 * @param bool $teste true = 1º turno.
 * @return string
 */
function mural17_eleicao_codigo( $teste = false ) {
	$c     = mural17_eleicao_config();
	$chave = $teste ? 'eleicao_teste' : 'eleicao';
	if ( '' !== $c[ $chave ] ) {
		return $c[ $chave ];
	}
	$achados = (array) get_option( 'mural17_eleicao_codigos' );
	if ( ! empty( $achados[ $chave ] ) ) {
		return $achados[ $chave ];
	}
	$lista = mural17_tse_eleicoes();
	if ( is_wp_error( $lista ) ) {
		return '';
	}
	foreach ( $lista as $e ) {
		if ( false === stripos( remove_accents( $e['nm'] ), 'federal' ) ) {
			continue;
		}
		if ( '1' === $e['turno'] ) {
			$achados['eleicao_teste'] = $e['cd'];
		} elseif ( '2' === $e['turno'] ) {
			$achados['eleicao'] = $e['cd'];
		}
	}
	update_option( 'mural17_eleicao_codigos', $achados, false );
	return isset( $achados[ $chave ] ) ? $achados[ $chave ] : '';
}

/**
 * Código TSE de cada cidade do 17 numa eleição (slug => código).
 *
 * @param string $eleicao Código da eleição.
 * @return array<string, string>|WP_Error
 */
function mural17_tse_municipios( $eleicao ) {
	$guardado = get_option( 'mural17_tse_mun_' . $eleicao );
	if ( is_array( $guardado ) && count( $guardado ) >= 100 ) {
		return $guardado;
	}
	$j = mural17_tse_json( mural17_tse_url( 'municipios', $eleicao ) );
	if ( is_wp_error( $j ) ) {
		return $j;
	}
	$por_ibge = array_flip( array_map( 'strval', mural17_ibge_cidades() ) );
	$por_nome = array();
	foreach ( mural17_lista_cidades() as $g ) {
		foreach ( $g['cidades'] as $c ) {
			$por_nome[ mural17_normaliza( $c->name ) ] = $c->slug;
		}
	}
	$mapa = array();
	foreach ( isset( $j['abr'] ) && is_array( $j['abr'] ) ? $j['abr'] : array() as $uf ) {
		if ( 'SP' !== strtoupper( (string) ( $uf['cd'] ?? '' ) ) ) {
			continue;
		}
		foreach ( isset( $uf['mu'] ) && is_array( $uf['mu'] ) ? $uf['mu'] : array() as $m ) {
			$ibge = (string) ( $m['cdi'] ?? '' );
			$nome = mural17_normaliza( (string) ( $m['nm'] ?? '' ) );
			$slug = isset( $por_ibge[ $ibge ] ) ? $por_ibge[ $ibge ] : ( isset( $por_nome[ $nome ] ) ? $por_nome[ $nome ] : '' );
			if ( $slug ) {
				$mapa[ $slug ] = (string) $m['cd'];
			}
		}
	}
	if ( count( $mapa ) >= 100 ) {
		update_option( 'mural17_tse_mun_' . $eleicao, $mapa, false );
	}
	return $mapa;
}

/**
 * Número do TSE ("1.234,56" ou "1234,56") para float.
 *
 * @param mixed $v Valor.
 * @return float
 */
function mural17_tse_num( $v ) {
	$v = (string) $v;
	if ( false !== strpos( $v, ',' ) ) {
		$v = str_replace( array( '.', ',' ), array( '', '.' ), $v );
	}
	return (float) $v;
}

/**
 * Nome de urna em caixa alta para nome próprio ("FLÁVIO BOLSONARO" =>
 * "Flávio Bolsonaro").
 *
 * @param string $nome Nome.
 * @return string
 */
function mural17_nome_urna( $nome ) {
	$nome = mb_convert_case( mb_strtolower( trim( (string) $nome ), 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' );
	return preg_replace_callback(
		'/\b(Da|De|Do|Das|Dos|E)\b/u',
		function ( $m ) {
			return mb_strtolower( $m[1], 'UTF-8' );
		},
		$nome
	);
}

/**
 * Resultado simplificado do TSE no formato do portal.
 *
 * @param array $j JSON do TSE.
 * @return array
 */
function mural17_tse_resultado( $j ) {
	$cands = array();
	foreach ( isset( $j['cand'] ) && is_array( $j['cand'] ) ? $j['cand'] : array() as $c ) {
		$cands[] = array(
			'num'    => (string) ( $c['n'] ?? '' ),
			'nome'   => mural17_nome_urna( $c['nm'] ?? '' ),
			'votos'  => (int) mural17_tse_num( $c['vap'] ?? 0 ),
			'pct'    => mural17_tse_num( $c['pvap'] ?? 0 ),
			'eleito' => 's' === strtolower( (string) ( $c['e'] ?? '' ) ) || false !== stripos( (string) ( $c['st'] ?? '' ), 'eleito' ) && false === stripos( (string) ( $c['st'] ?? '' ), 'não' ),
		);
	}
	// Ordem fixa pelo número: a cor de cada candidato não muda durante a noite.
	usort(
		$cands,
		function ( $a, $b ) {
			return (int) $a['num'] <=> (int) $b['num'];
		}
	);
	return array(
		'pst'     => mural17_tse_num( $j['pst'] ?? 0 ),
		's'       => (int) mural17_tse_num( $j['s'] ?? 0 ),
		'st'      => (int) mural17_tse_num( $j['st'] ?? 0 ),
		'hora'    => trim( (string) ( $j['dg'] ?? '' ) . ' ' . (string) ( $j['hg'] ?? '' ) ),
		'cand'    => $cands,
		'vv'      => (int) mural17_tse_num( $j['vv'] ?? 0 ),
		'brancos' => mural17_tse_num( $j['pvb'] ?? 0 ),
		'nulos'   => mural17_tse_num( $j['ptvn'] ?? ( $j['pvn'] ?? 0 ) ),
		'abst'    => mural17_tse_num( $j['pa'] ?? 0 ),
	);
}

/**
 * Soma das 117 cidades.
 *
 * @param array $cidades slug => resultado.
 * @return array|null
 */
function mural17_soma_ddd17( $cidades ) {
	if ( ! $cidades ) {
		return null;
	}
	$votos = array();
	$nomes = array();
	$s     = 0;
	$st    = 0;
	$pst   = 0;
	foreach ( $cidades as $r ) {
		foreach ( $r['cand'] as $c ) {
			$votos[ $c['num'] ] = ( $votos[ $c['num'] ] ?? 0 ) + $c['votos'];
			$nomes[ $c['num'] ] = $c['nome'];
		}
		$s   += $r['s'];
		$st  += $r['st'];
		$pst += $r['pst'];
	}
	$total = array_sum( $votos );
	$cands = array();
	foreach ( $votos as $num => $v ) {
		$cands[] = array(
			'num'    => (string) $num,
			'nome'   => $nomes[ $num ],
			'votos'  => $v,
			'pct'    => $total ? round( 100 * $v / $total, 2 ) : 0,
			'eleito' => false,
		);
	}
	usort(
		$cands,
		function ( $a, $b ) {
			return (int) $a['num'] <=> (int) $b['num'];
		}
	);
	return array(
		'pst'     => $s ? round( 100 * $st / $s, 2 ) : round( $pst / count( $cidades ), 2 ),
		's'       => $s,
		'st'      => $st,
		'hora'    => '',
		'cand'    => $cands,
		'vv'      => $total,
		'brancos' => 0,
		'nulos'   => 0,
		'abst'    => 0,
	);
}

/**
 * Lê o TSE (Brasil, SP e as 117 cidades) e publica o resultado.
 *
 * @param bool $teste true = 1º turno, guardado à parte.
 * @return array|WP_Error Resumo.
 */
function mural17_apuracao_atualiza( $teste = false ) {
	$eleicao = mural17_eleicao_codigo( $teste );
	if ( '' === $eleicao ) {
		$e = new WP_Error( 'tse_codigo', 'Não achei o código da eleição no TSE. Preencha à mão em Eleição.' );
		mural17_apuracao_erro( $e, $teste );
		return $e;
	}
	$br = mural17_tse_json( mural17_tse_url( 'resultado', $eleicao, 'br' ) );
	if ( is_wp_error( $br ) ) {
		mural17_apuracao_erro( $br, $teste );
		return $br;
	}
	$sp  = mural17_tse_json( mural17_tse_url( 'resultado', $eleicao, 'sp' ) );
	$mun = mural17_tse_municipios( $eleicao );

	$cidades = array();
	$falhas  = 0;
	if ( is_array( $mun ) && $mun ) {
		$pedidos = array();
		foreach ( $mun as $slug => $cd ) {
			$pedidos[ $slug ] = array(
				'url'  => mural17_tse_url( 'resultado', $eleicao, 'sp', $cd ),
				'type' => 'GET',
			);
		}
		foreach ( array_chunk( $pedidos, 20, true ) as $lote ) {
			$respostas = mural17_requisicoes_juntas( $lote );
			foreach ( $respostas as $slug => $corpo ) {
				$j = is_string( $corpo ) ? json_decode( $corpo, true ) : null;
				if ( is_array( $j ) && isset( $j['cand'] ) ) {
					$cidades[ $slug ] = mural17_tse_resultado( $j );
				} else {
					++$falhas;
				}
			}
		}
	}

	$opcao    = $teste ? 'mural17_apuracao_teste' : 'mural17_apuracao';
	$anterior = get_option( $opcao );
	$dados    = array(
		'em'      => time(),
		'teste'   => $teste,
		'eleicao' => $eleicao,
		'br'      => mural17_tse_resultado( $br ),
		'sp'      => is_array( $sp ) && ! is_wp_error( $sp ) ? mural17_tse_resultado( $sp ) : null,
		'cid'     => $cidades,
		'ddd'     => mural17_soma_ddd17( $cidades ),
	);
	$dados['marcos'] = mural17_apuracao_marcos( is_array( $anterior ) ? $anterior : array(), $dados, $teste );
	$dados['posts']  = mural17_eleicao_posts();
	update_option( $opcao, $dados, false );
	delete_option( $teste ? 'mural17_apuracao_teste_erro' : 'mural17_apuracao_erro' );
	mural17_apuracao_publica( $dados, $teste );

	return array(
		'cidades' => count( $cidades ),
		'falhas'  => $falhas + ( is_wp_error( $mun ) ? 117 : 0 ),
		'br'      => $dados['br'],
		'mun_erro' => is_wp_error( $mun ) ? $mun->get_error_message() : '',
	);
}

/**
 * Vários GET ao mesmo tempo (as 117 cidades levariam um minuto uma a uma).
 *
 * @param array $pedidos chave => array( url, type ).
 * @return array chave => corpo (ou null).
 */
function mural17_requisicoes_juntas( $pedidos ) {
	$saida = array();
	// O teste local troca as respostas por pre_http_request; aí vai um a um.
	if ( has_filter( 'pre_http_request' ) || ! class_exists( '\WpOrg\Requests\Requests' ) ) {
		foreach ( $pedidos as $k => $p ) {
			$r           = wp_remote_get( $p['url'], array( 'timeout' => 8 ) );
			$saida[ $k ] = ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ? wp_remote_retrieve_body( $r ) : null;
		}
		return $saida;
	}
	$respostas = \WpOrg\Requests\Requests::request_multiple(
		$pedidos,
		array(
			'timeout' => 8,
			'verify'  => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
		)
	);
	foreach ( $respostas as $k => $r ) {
		$saida[ $k ] = ( $r instanceof \WpOrg\Requests\Response && 200 === (int) $r->status_code ) ? $r->body : null;
	}
	return $saida;
}

/**
 * Guarda o erro da última leitura.
 *
 * @param WP_Error $e     Erro.
 * @param bool     $teste Teste.
 */
function mural17_apuracao_erro( $e, $teste ) {
	update_option( $teste ? 'mural17_apuracao_teste_erro' : 'mural17_apuracao_erro', array( $e->get_error_message(), time() ), false );
}

/**
 * Marcos automáticos do minuto a minuto: fases da apuração no Brasil, virada
 * e cidades do 17 que fecham a contagem.
 *
 * @param array $antes Leitura anterior.
 * @param array $agora Leitura nova.
 * @param bool  $teste Teste.
 * @return array[] Lista de array( t, texto ), mais novo primeiro (até 40).
 */
function mural17_apuracao_marcos( $antes, $agora, $teste ) {
	$marcos = isset( $antes['marcos'] ) && is_array( $antes['marcos'] ) ? $antes['marcos'] : array();
	$novos  = array();
	$fmt    = function ( $n ) {
		return number_format( (float) $n, 1, ',', '.' );
	};
	$lider  = function ( $r ) {
		$c = $r && $r['cand'] ? $r['cand'] : array();
		usort(
			$c,
			function ( $a, $b ) {
				return $b['votos'] <=> $a['votos'];
			}
		);
		return $c ? $c[0] : null;
	};

	$b0 = isset( $antes['br'] ) ? (float) $antes['br']['pst'] : 0;
	$b1 = (float) $agora['br']['pst'];
	$l1 = $lider( $agora['br'] );
	foreach ( array( 1, 25, 50, 75, 90, 100 ) as $faixa ) {
		if ( $b0 < $faixa && $b1 >= $faixa && $l1 ) {
			$novos[] = array(
				1 === $faixa
					? 'Começa a apuração no Brasil. Primeiras urnas: ' . $l1['nome'] . ' tem ' . $fmt( $l1['pct'] ) . '%.'
					: ( 100 === $faixa
						? 'Brasil termina a apuração: ' . $l1['nome'] . ' tem ' . $fmt( $l1['pct'] ) . '% dos votos válidos.'
						: 'Brasil passa de ' . $faixa . '% das urnas apuradas: ' . $l1['nome'] . ' lidera com ' . $fmt( $l1['pct'] ) . '%.' ),
				in_array( $faixa, array( 50, 90, 100 ), true ),
				'',
			);
		}
	}
	foreach ( $agora['br']['cand'] as $c ) {
		$ja = false;
		foreach ( isset( $antes['br']['cand'] ) ? $antes['br']['cand'] : array() as $a ) {
			$ja = $ja || ( $a['num'] === $c['num'] && $a['eleito'] );
		}
		if ( $c['eleito'] && ! $ja ) {
			$novos[] = array( 'O TSE declara ' . $c['nome'] . ' eleito presidente.', true, '' );
		}
	}
	$l0 = isset( $antes['br'] ) ? $lider( $antes['br'] ) : null;
	if ( $l0 && $l1 && $l0['num'] !== $l1['num'] && $b1 >= 5 ) {
		$novos[] = array( 'Virada: ' . $l1['nome'] . ' passa à frente no Brasil, com ' . $fmt( $l1['pct'] ) . '%.', true, '' );
	}
	foreach ( $agora['cid'] as $slug => $r ) {
		$p0 = isset( $antes['cid'][ $slug ] ) ? (float) $antes['cid'][ $slug ]['pst'] : 0;
		$l  = $lider( $r );
		if ( $p0 < 100 && (float) $r['pst'] >= 100 && $l ) {
			$c       = get_term_by( 'slug', $slug, 'cidade' );
			$novos[] = array( ( $c ? $c->name : $slug ) . ' conclui a apuração: ' . $l['nome'] . ' tem ' . $fmt( $l['pct'] ) . '%.', in_array( $slug, mural17_polos_eleicao(), true ), $slug );
		}
	}
	foreach ( array_reverse( $novos ) as $n ) {
		array_unshift(
			$marcos,
			array(
				't'     => time(),
				'texto' => $n[0],
			)
		);
	}
	if ( ! $teste ) {
		foreach ( $novos as $n ) {
			if ( $n[1] ) {
				mural17_publica_marco( $n[0], $n[2], $agora );
			}
		}
	}
	return array_slice( $marcos, 0, 40 );
}

/**
 * Cidades-polo: quando uma delas fecha a apuração, vira matéria (e post nas
 * redes). As outras 110 ficam só no minuto a minuto.
 *
 * @return string[]
 */
function mural17_polos_eleicao() {
	return array( 'rio-preto', 'catanduva', 'votuporanga', 'fernandopolis', 'jales', 'barretos', 'santa-fe-do-sul' );
}

/**
 * Imagem de capa das matérias automáticas, enviada à Biblioteca de Mídia
 * na primeira vez.
 *
 * @return int ID do anexo, ou 0.
 */
function mural17_capa_eleicao() {
	$id = (int) get_option( 'mural17_capa_eleicao' );
	if ( $id && get_post( $id ) ) {
		return $id;
	}
	$origem = get_template_directory() . '/assets/eleicao/mural17-eleicao-1200x675.png';
	$up     = wp_upload_bits( 'mural17-eleicao-2026.png', null, file_get_contents( $origem ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'Eleições 2026: apuração ao vivo',
			'post_status'    => 'inherit',
		),
		$up['file']
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', 'Arte do Mural 17: apuração do 2º turno ao vivo' );
	update_option( 'mural17_capa_eleicao', $id, false );
	return $id;
}

/**
 * Publica um marco principal como matéria curta (o plugin de redes posta).
 *
 * @param string $texto Marco.
 * @param string $slug  Cidade do marco, ou vazio (Brasil).
 * @param array  $dados Leitura de agora.
 */
function mural17_publica_marco( $texto, $slug, $dados ) {
	if ( ! mural17_eleicao_config()['publicar'] ) {
		return;
	}
	$feitos = (array) get_option( 'mural17_marcos_publicados' );
	$chave  = md5( $texto );
	if ( isset( $feitos[ $chave ] ) ) {
		return;
	}
	$feitos[ $chave ] = time();
	update_option( 'mural17_marcos_publicados', $feitos, false );

	$linha = function ( $r, $onde ) {
		if ( ! $r || empty( $r['cand'] ) ) {
			return '';
		}
		$p = array();
		foreach ( $r['cand'] as $c ) {
			$p[] = '<strong>' . esc_html( $c['nome'] ) . '</strong> ' . number_format( (float) $c['pct'], 2, ',', '.' ) . '% (' . number_format( (float) $c['votos'], 0, ',', '.' ) . ' votos)';
		}
		return '<p>' . esc_html( $onde ) . ', com ' . number_format( (float) $r['pst'], 2, ',', '.' ) . '% das seções totalizadas: ' . implode( ' × ', $p ) . '.</p>';
	};
	$corpo  = '<p>' . esc_html( $texto ) . '</p>';
	$corpo .= $slug && isset( $dados['cid'][ $slug ] ) ? $linha( $dados['cid'][ $slug ], 'Resultado na cidade' ) : '';
	$corpo .= $linha( $dados['br'], 'No Brasil' );
	$corpo .= $linha( $dados['ddd'], 'Nas 117 cidades do DDD 17' );
	$corpo .= '<p>Percentuais sobre os votos válidos. Fonte: Tribunal Superior Eleitoral (TSE).</p>';
	$corpo .= '<p><a href="' . esc_url( home_url( '/eleicoes-2026/' ) ) . '">Acompanhe a apuração ao vivo, com o mapa e o resultado de cada cidade do 17</a>.</p>';

	$cats = array( mural17_categoria_eleicao() );
	$pol  = get_term_by( 'slug', 'politica', 'category' );
	if ( $pol ) {
		$cats[] = (int) $pol->term_id;
	}
	$id = wp_insert_post(
		array(
			'post_title'    => mural17_eleicao_config()['titulo'] . ': ' . rtrim( $texto, '.' ),
			'post_content'  => $corpo,
			'post_excerpt'  => $texto,
			'post_status'   => 'publish',
			'post_category' => $cats,
			'post_author'   => (int) get_option( 'mural17_autor_eleicao', 1 ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return;
	}
	$capa = mural17_capa_eleicao();
	if ( $capa ) {
		set_post_thumbnail( $id, $capa );
	}
	$c = $slug ? get_term_by( 'slug', $slug, 'cidade' ) : null;
	if ( $c ) {
		wp_set_object_terms( $id, array( (int) $c->term_id ), 'cidade' );
	}
}

/**
 * Categoria do especial.
 *
 * @return int
 */
function mural17_categoria_eleicao() {
	$t = get_term_by( 'slug', 'eleicoes-2026', 'category' );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$n = wp_insert_term( 'Eleições 2026', 'category', array( 'slug' => 'eleicoes-2026' ) );
	return is_wp_error( $n ) ? 0 : (int) $n['term_id'];
}

/**
 * Matérias do minuto a minuto: Eleições 2026 desde a abertura do especial.
 *
 * @return array[]
 */
function mural17_eleicao_posts() {
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 15,
			'cat'                 => mural17_categoria_eleicao(),
			'date_query'          => array( array( 'after' => gmdate( 'Y-m-d H:i:s', mural17_eleicao_hora( 'inicio' ) - DAY_IN_SECONDS ), 'column' => 'post_date_gmt' ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$lista = array();
	foreach ( $q->posts as $p ) {
		$lista[] = array(
			't'      => (int) get_post_time( 'U', true, $p ),
			'titulo' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
			'resumo' => wp_trim_words( get_the_excerpt( $p ), 22, '…' ),
			'link'   => get_permalink( $p ),
		);
	}
	return $lista;
}

/**
 * Arquivo estático com a leitura, que o navegador consulta.
 *
 * @param bool $teste Teste.
 * @return array( caminho, url ).
 */
function mural17_apuracao_arquivo( $teste = false ) {
	$up   = wp_upload_dir( null, false );
	$nome = $teste ? 'apuracao-teste.json' : 'apuracao.json';
	return array( $up['basedir'] . '/mural17/' . $nome, set_url_scheme( $up['baseurl'] . '/mural17/' . $nome ) );
}

/**
 * Grava o arquivo estático.
 *
 * @param array $dados Leitura.
 * @param bool  $teste Teste.
 */
function mural17_apuracao_publica( $dados, $teste ) {
	list( $caminho ) = mural17_apuracao_arquivo( $teste );
	wp_mkdir_p( dirname( $caminho ) );
	file_put_contents( $caminho, wp_json_encode( mural17_apuracao_publico( $dados ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Só o que a página precisa, com o nome das cidades.
 *
 * @param array $dados Leitura.
 * @return array
 */
function mural17_apuracao_publico( $dados ) {
	$nomes = array();
	foreach ( mural17_lista_cidades() as $g ) {
		foreach ( $g['cidades'] as $c ) {
			$nomes[ $c->slug ] = $c->name;
		}
	}
	$cid = array();
	foreach ( (array) $dados['cid'] as $slug => $r ) {
		$cid[ $slug ] = array_merge( $r, array( 'nome' => isset( $nomes[ $slug ] ) ? $nomes[ $slug ] : $slug ) );
	}
	return array(
		'em'     => $dados['em'],
		'br'     => $dados['br'],
		'sp'     => $dados['sp'],
		'ddd'    => $dados['ddd'],
		'cid'    => $cid,
		'marcos' => isset( $dados['marcos'] ) ? $dados['marcos'] : array(),
		'posts'  => isset( $dados['posts'] ) ? $dados['posts'] : array(),
		'fecha'  => mural17_eleicao_hora( 'fechamento' ),
	);
}

/**
 * Leitura guardada (a do teste, na prévia de quem administra).
 *
 * @return array|null
 */
function mural17_apuracao() {
	$d = get_option( mural17_eleicao_teste() ? 'mural17_apuracao_teste' : 'mural17_apuracao' );
	return is_array( $d ) ? mural17_apuracao_publico( $d ) : null;
}

/**
 * Atualização pedida pelo navegador: no máximo uma por minuto, com trava.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'mural17/v1',
			'/apuracao',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$d = get_option( 'mural17_apuracao' );
					$velho = ! is_array( $d ) || time() - (int) $d['em'] >= 55;
					if ( $velho && mural17_eleicao_ativa() && time() >= mural17_eleicao_hora( 'fechamento' ) - 300 && ! get_transient( 'mural17_apuracao_trava' ) ) {
						set_transient( 'mural17_apuracao_trava', 1, 50 );
						mural17_apuracao_atualiza();
						$d = get_option( 'mural17_apuracao' );
					}
					$r = rest_ensure_response( is_array( $d ) ? mural17_apuracao_publico( $d ) : array() );
					$r->header( 'Cache-Control', 'no-store' );
					return $r;
				},
			)
		);
	}
);

/**
 * Plano B do navegador: leitura a cada 2 minutos pelo agendador enquanto o
 * especial está no ar.
 *
 * @param array $s Intervalos.
 * @return array
 */
function mural17_intervalo_eleicao( $s ) {
	$s['mural17_2min'] = array(
		'interval' => 2 * MINUTE_IN_SECONDS,
		'display'  => 'A cada 2 minutos (Mural 17)',
	);
	return $s;
}
add_filter( 'cron_schedules', 'mural17_intervalo_eleicao' );

add_action(
	'init',
	function () {
		$no_ar = mural17_eleicao_ativa() && time() >= mural17_eleicao_hora( 'fechamento' ) - 300;
		$ja    = wp_next_scheduled( 'mural17_le_apuracao' );
		if ( $no_ar && ! $ja ) {
			wp_schedule_event( time() + 10, 'mural17_2min', 'mural17_le_apuracao' );
		} elseif ( ! $no_ar && $ja ) {
			wp_clear_scheduled_hook( 'mural17_le_apuracao' );
		}
	}
);
add_action(
	'mural17_le_apuracao',
	function () {
		if ( ! get_transient( 'mural17_apuracao_trava' ) ) {
			set_transient( 'mural17_apuracao_trava', 1, 50 );
			mural17_apuracao_atualiza();
		}
	}
);

/**
 * Matéria importada sobre a eleição, no dia, entra no minuto a minuto.
 *
 * @param int    $meta_id Meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 * @param mixed  $valor   Valor.
 */
function mural17_eleicao_na_importacao( $meta_id, $post_id, $chave, $valor ) {
	if ( '_pfai_feed_id' !== $chave || ! (int) $valor ) {
		return;
	}
	$agora = time();
	if ( $agora < mural17_eleicao_hora( 'inicio' ) - DAY_IN_SECONDS || $agora > mural17_eleicao_hora( 'fim' ) ) {
		return;
	}
	$p = get_post( $post_id );
	if ( $p && preg_match( '/\b(segundo turno|2o turno|apurac|urna|eleic|eleit|votac|tse|presidente eleito)/', mural17_normaliza( $p->post_title . ' ' . $p->post_excerpt ) ) ) {
		wp_set_post_categories( $post_id, array( mural17_categoria_eleicao() ), true );
	}
}
add_action( 'added_post_meta', 'mural17_eleicao_na_importacao', 22, 4 );

/**
 * Endereço /eleicoes-2026/.
 */
function mural17_rota_eleicao() {
	add_rewrite_rule( '^eleicoes-2026/?$', 'index.php?m17_eleicao=1', 'top' );
}
add_action( 'init', 'mural17_rota_eleicao' );
add_filter(
	'query_vars',
	function ( $v ) {
		$v[] = 'm17_eleicao';
		return $v;
	}
);
add_action(
	'admin_init',
	function () {
		if ( 1 > (int) get_option( 'mural17_eleicao_rotas' ) ) {
			flush_rewrite_rules( false );
			update_option( 'mural17_eleicao_rotas', 1 );
		}
	}
);
add_filter(
	'pre_handle_404',
	function ( $pre, $q ) {
		return get_query_var( 'm17_eleicao' ) ? true : $pre;
	},
	10,
	2
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->get( 'm17_eleicao' ) ) {
			$q->is_home     = false;
			$q->is_404      = false;
			$q->is_singular = false;
			$q->set( 'posts_per_page', 1 );
		}
	}
);
add_filter(
	'template_include',
	function ( $t ) {
		if ( get_query_var( 'm17_eleicao' ) ) {
			status_header( 200 );
			return get_template_directory() . '/eleicao.php';
		}
		return $t;
	},
	30
);
add_filter(
	'document_title_parts',
	function ( $p ) {
		if ( get_query_var( 'm17_eleicao' ) ) {
			$p['title'] = 'Apuração ao vivo · Eleições 2026';
		}
		return $p;
	}
);

/**
 * Faixa "AO VIVO" no topo de todo o site enquanto o especial está no ar.
 */
function mural17_faixa_eleicao() {
	if ( ! mural17_eleicao_ativa() && ! mural17_eleicao_teste() ) {
		return;
	}
	$c     = mural17_eleicao_config();
	$d     = mural17_apuracao();
	$aberta = time() < mural17_eleicao_hora( 'fechamento' ) && ! mural17_eleicao_teste();
	$link  = home_url( '/eleicoes-2026/' ) . ( mural17_eleicao_teste() ? '?teste=1' : '' );
	?>
	<a class="faixa-eleicao" href="<?php echo esc_url( $link ); ?>" data-ap-faixa>
		<span class="wrap faixa-eleicao__in">
			<b class="faixa-eleicao__vivo">AO VIVO</b>
			<span class="faixa-eleicao__txt" data-ap-faixa-txt>
				<?php
				if ( $aberta ) {
					echo esc_html( 'Hoje tem ' . $c['titulo'] . ': urnas abertas até ' . wp_date( 'G\hi', mural17_eleicao_hora( 'fechamento' ) ) . '. Acompanhe a apuração aqui.' );
				} elseif ( $d && $d['br'] ) {
					echo esc_html( 'Apuração do ' . $c['titulo'] . ' para ' . $c['cargo'] . ' · ' . number_format( (float) $d['br']['pst'], 1, ',', '.' ) . '% das urnas no Brasil' );
				} else {
					echo esc_html( 'Apuração do ' . $c['titulo'] . ' para ' . $c['cargo'] . ' começa às ' . wp_date( 'G\h', mural17_eleicao_hora( 'fechamento' ) ) . '.' );
				}
				?>
			</span>
			<span class="faixa-eleicao__mini" data-ap-mini <?php echo $aberta || ! $d ? 'hidden' : ''; ?>></span>
			<span class="faixa-eleicao__ir">Acompanhe →</span>
		</span>
	</a>
	<?php
}

/**
 * Dados para o JavaScript do especial.
 */
function mural17_script_eleicao() {
	if ( ! mural17_eleicao_ativa() && ! mural17_eleicao_teste() && ! get_query_var( 'm17_eleicao' ) ) {
		return;
	}
	$teste = mural17_eleicao_teste();
	list( , $url ) = mural17_apuracao_arquivo( $teste );
	wp_enqueue_script( 'mural17-eleicao', get_template_directory_uri() . '/assets/js/eleicao.js', array(), MURAL17_VERSION, true );
	wp_localize_script(
		'mural17-eleicao',
		'M17AP',
		array(
			'arquivo' => $url,
			'rest'    => $teste ? '' : esc_url_raw( rest_url( 'mural17/v1/apuracao' ) ),
			'ativo'   => mural17_eleicao_ativa() || $teste,
			'fecha'   => mural17_eleicao_hora( 'fechamento' ),
			'teste'   => $teste,
			'fotos'   => array_filter( (array) mural17_eleicao_config()['fotos'] ),
			'dados'   => get_query_var( 'm17_eleicao' ) ? mural17_apuracao() : null,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'mural17_script_eleicao' );
