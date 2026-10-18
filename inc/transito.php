<?php
/**
 * Trânsito ao vivo: ocorrências da TomTom (Traffic Incident Details v5) nas
 * rodovias e cidades do 17, lidas a cada 15 minutos.
 *
 * A chave grátis (2.500 consultas por dia) fica na página Serviços do painel
 * ou na constante MURAL17_TOMTOM_CHAVE do wp-config.php.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chave da TomTom configurada.
 *
 * @return string
 */
function mural17_tomtom_chave() {
	if ( defined( 'MURAL17_TOMTOM_CHAVE' ) && MURAL17_TOMTOM_CHAVE ) {
		return (string) MURAL17_TOMTOM_CHAVE;
	}
	return trim( (string) get_option( 'mural17_tomtom_chave' ) );
}

/**
 * Tipos de ocorrência (iconCategory da TomTom) que entram no portal, com o
 * nome em português e o peso na ordenação. Gelo e vento não acontecem aqui.
 *
 * @return array<int, array{0:string,1:int}>
 */
function mural17_tipos_transito() {
	return array(
		8  => array( 'Via interditada', 100 ),
		1  => array( 'Acidente', 90 ),
		11 => array( 'Alagamento', 85 ),
		7  => array( 'Faixa interditada', 70 ),
		6  => array( 'Congestionamento', 50 ),
		3  => array( 'Condição perigosa', 55 ),
		2  => array( 'Neblina', 45 ),
		14 => array( 'Veículo quebrado', 40 ),
		9  => array( 'Obras', 30 ),
	);
}

/**
 * Retângulos de consulta que cobrem as 117 cidades. A TomTom aceita no
 * máximo 10.000 km² por consulta; cada quadro aqui tem uns 8.300 km², e
 * quadro sem nenhuma cidade dentro não é consultado.
 *
 * @return array<int, float[]> Lista de [minLon, minLat, maxLon, maxLat].
 */
function mural17_quadros_transito() {
	$coords = array_values( mural17_coordenadas() );
	$lats   = array_column( $coords, 0 );
	$lngs   = array_column( $coords, 1 );
	$min_la = min( $lats ) - 0.1;
	$max_la = max( $lats ) + 0.1;
	$min_lo = min( $lngs ) - 0.1;
	$max_lo = max( $lngs ) + 0.1;
	$passo  = 0.85;
	$quadros = array();
	for ( $la = $min_la; $la < $max_la; $la += $passo ) {
		for ( $lo = $min_lo; $lo < $max_lo; $lo += $passo ) {
			$q = array( $lo, $la, min( $lo + $passo, $max_lo ), min( $la + $passo, $max_la ) );
			foreach ( $coords as $c ) {
				if ( $c[0] >= $q[1] - 0.1 && $c[0] <= $q[3] + 0.1 && $c[1] >= $q[0] - 0.1 && $c[1] <= $q[2] + 0.1 ) {
					$quadros[] = array_map(
						function ( $v ) {
							return round( $v, 4 );
						},
						$q
					);
					break;
				}
			}
		}
	}
	return $quadros;
}

/**
 * Endereço da consulta de um quadro.
 *
 * @param float[] $quadro [minLon, minLat, maxLon, maxLat].
 * @param string  $chave  Chave.
 * @return string
 */
function mural17_url_transito( $quadro, $chave ) {
	$campos = '{incidents{type,geometry{type,coordinates},properties{id,iconCategory,magnitudeOfDelay,events{description,code},startTime,from,to,length,delay,roadNumbers,lastReportTime}}}';
	$url    = add_query_arg(
		array(
			'key'                => rawurlencode( $chave ),
			'bbox'               => implode( ',', $quadro ),
			'fields'             => rawurlencode( $campos ),
			'language'           => 'pt-PT',
			'timeValidityFilter' => 'present',
		),
		'https://api.tomtom.com/traffic/services/5/incidentDetails'
	);
	return apply_filters( 'mural17_tomtom_url', $url, $quadro );
}

/**
 * Lê as ocorrências de todos os quadros e guarda as que caem no 17.
 *
 * @return bool
 */
function mural17_transito_atualiza() {
	$chave = mural17_tomtom_chave();
	if ( '' === $chave ) {
		return false;
	}
	$tipos  = mural17_tipos_transito();
	$coords = mural17_coordenadas();
	$cidades = array();
	foreach ( mural17_lista_cidades() as $grupo ) {
		foreach ( $grupo['cidades'] as $c ) {
			if ( isset( $coords[ $c->slug ] ) ) {
				$cidades[ $c->term_id ] = $coords[ $c->slug ];
			}
		}
	}

	$itens = array();
	$erros = 0;
	foreach ( mural17_quadros_transito() as $quadro ) {
		$r = wp_remote_get( mural17_url_transito( $quadro, $chave ), array( 'timeout' => 12 ) );
		$codigo = is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r );
		if ( 200 !== $codigo ) {
			++$erros;
			$msg = is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . $codigo;
			if ( 403 === $codigo ) {
				$msg .= ' (chave inválida ou limite diário da TomTom atingido)';
			}
			update_option( 'mural17_transito_erro', array( $msg, time() ), false );
			if ( in_array( $codigo, array( 401, 403 ), true ) ) {
				break; // Chave ruim: as outras consultas dariam o mesmo erro.
			}
			continue;
		}
		$dados = json_decode( wp_remote_retrieve_body( $r ), true );
		foreach ( isset( $dados['incidents'] ) && is_array( $dados['incidents'] ) ? $dados['incidents'] : array() as $inc ) {
			$item = mural17_le_ocorrencia( $inc, $tipos, $cidades );
			if ( $item ) {
				$itens[ $item['id'] ] = $item;
			}
		}
	}

	if ( $erros && ! $itens ) {
		return false;
	}
	usort(
		$itens,
		function ( $a, $b ) {
			return array( $b['peso'], $b['atraso'] ) <=> array( $a['peso'], $a['atraso'] );
		}
	);
	update_option(
		'mural17_transito',
		array(
			'itens' => array_slice( array_values( $itens ), 0, 80 ),
			'em'    => time(),
		),
		false
	);
	if ( ! $erros ) {
		delete_option( 'mural17_transito_erro' );
	}
	return true;
}

/**
 * Converte uma ocorrência da TomTom no formato do portal, ou null se ela
 * não interessa (tipo fora da lista, lentidão pequena, longe do 17).
 *
 * @param array $inc     Ocorrência.
 * @param array $tipos   mural17_tipos_transito().
 * @param array $cidades term_id => [lat, lng].
 * @return array|null
 */
function mural17_le_ocorrencia( $inc, $tipos, $cidades ) {
	$p    = isset( $inc['properties'] ) ? $inc['properties'] : array();
	$tipo = isset( $p['iconCategory'] ) ? (int) $p['iconCategory'] : 0;
	$mag  = isset( $p['magnitudeOfDelay'] ) ? (int) $p['magnitudeOfDelay'] : 0;
	if ( ! isset( $tipos[ $tipo ] ) || ( 6 === $tipo && $mag < 2 ) ) {
		return null;
	}

	// Ponto da ocorrência: o próprio ponto, ou o meio da linha.
	$g     = isset( $inc['geometry']['coordinates'] ) ? $inc['geometry']['coordinates'] : array();
	$ponto = null;
	if ( isset( $g[0] ) && is_numeric( $g[0] ) ) {
		$ponto = $g;
	} elseif ( isset( $g[0][0] ) ) {
		$ponto = $g[ (int) floor( count( $g ) / 2 ) ];
	}
	if ( ! $ponto ) {
		return null;
	}
	$aqui = array( (float) $ponto[1], (float) $ponto[0] );

	$perto = 0;
	$km    = 999;
	foreach ( $cidades as $id => $c ) {
		$d = mural17_km( $aqui, $c );
		if ( $d < $km ) {
			$km    = $d;
			$perto = $id;
		}
	}
	if ( $km > 25 ) {
		return null; // Fora do 17 (MS, MG, região de Ribeirão...).
	}

	$via  = isset( $p['roadNumbers'][0] ) ? (string) $p['roadNumbers'][0] : '';
	$de   = isset( $p['from'] ) ? (string) $p['from'] : '';
	$para = isset( $p['to'] ) ? (string) $p['to'] : '';
	$desc = isset( $p['events'][0]['description'] ) ? (string) $p['events'][0]['description'] : '';

	return array(
		'id'     => isset( $p['id'] ) ? (string) $p['id'] : md5( wp_json_encode( $inc ) ),
		'tipo'   => $tipos[ $tipo ][0],
		'peso'   => $tipos[ $tipo ][1] + ( 3 === $mag ? 10 : 0 ),
		'grave'  => in_array( $tipo, array( 1, 8, 11 ), true ) || 3 === $mag,
		'via'    => $via,
		'trecho' => $de && $para && $de !== $para ? $de . ' → ' . $para : ( $de ? $de : $para ),
		'desc'   => $desc,
		'atraso' => isset( $p['delay'] ) ? (int) $p['delay'] : 0,
		'km'     => isset( $p['length'] ) ? round( (float) $p['length'] / 1000, 1 ) : 0,
		'cidade' => $perto,
		'dist'   => round( $km, 1 ),
		'desde'  => isset( $p['startTime'] ) ? (string) $p['startTime'] : '',
	);
}

/**
 * Ocorrências de agora para uma cidade (e arredores), uma região ou o 17.
 * Leitura com mais de 1 hora não vale: a TomTom pode ter parado de responder.
 *
 * @param WP_Term|null $escopo Cidade, região ou nada.
 * @param int          $qtd    Quantas.
 * @return array[]
 */
function mural17_transito_agora( $escopo = null, $qtd = 3 ) {
	$dados = get_option( 'mural17_transito' );
	if ( ! is_array( $dados ) || empty( $dados['itens'] ) || time() - (int) $dados['em'] > HOUR_IN_SECONDS ) {
		return array();
	}
	$itens = $dados['itens'];
	if ( $escopo ) {
		$coords = mural17_coordenadas();
		$itens  = array_filter(
			$itens,
			function ( $i ) use ( $escopo, $coords ) {
				$c = get_term( (int) $i['cidade'], 'cidade' );
				if ( ! $c || is_wp_error( $c ) ) {
					return false;
				}
				if ( mural17_e_regiao( $escopo ) ) {
					return (int) $c->parent === (int) $escopo->term_id;
				}
				if ( (int) $c->term_id === (int) $escopo->term_id ) {
					return true;
				}
				// Rodovia logo ao lado conta para a cidade vizinha também.
				return isset( $coords[ $escopo->slug ], $coords[ $c->slug ] ) && mural17_km( $coords[ $escopo->slug ], $coords[ $c->slug ] ) <= 20;
			}
		);
	}
	return array_slice( array_values( $itens ), 0, $qtd );
}

/**
 * Linha de detalhe de uma ocorrência: via, trecho, atraso.
 *
 * @param array $i Ocorrência.
 * @return string
 */
function mural17_linha_transito( $i ) {
	$c      = get_term( (int) $i['cidade'], 'cidade' );
	$lugar  = $c && ! is_wp_error( $c ) ? ( $i['dist'] <= 3 ? 'em ' : 'perto de ' ) . mural17_nome_curto( $c ) : '';
	$partes = array_filter( array( $i['via'], $i['trecho'] ? $i['trecho'] : $lugar ) );
	if ( $i['atraso'] >= 120 ) {
		$partes[] = '+' . round( $i['atraso'] / 60 ) . ' min';
	} elseif ( $i['km'] >= 1 ) {
		$partes[] = number_format_i18n( $i['km'], 1 ) . ' km';
	}
	return implode( ' · ', $partes );
}

/**
 * Leitura a cada 15 minutos, só com chave configurada.
 *
 * @param array $s Intervalos.
 * @return array
 */
function mural17_intervalo_transito( $s ) {
	$s['mural17_15min'] = array(
		'interval' => 15 * MINUTE_IN_SECONDS,
		'display'  => 'A cada 15 minutos (Mural 17)',
	);
	return $s;
}
add_filter( 'cron_schedules', 'mural17_intervalo_transito' );

/**
 * Agenda (ou tira) a leitura conforme a chave existe.
 */
function mural17_agenda_transito() {
	$tem = '' !== mural17_tomtom_chave();
	$ja  = wp_next_scheduled( 'mural17_le_transito' );
	if ( $tem && ! $ja ) {
		wp_schedule_event( time() + 30, 'mural17_15min', 'mural17_le_transito' );
	} elseif ( ! $tem && $ja ) {
		wp_clear_scheduled_hook( 'mural17_le_transito' );
	}
}
add_action( 'init', 'mural17_agenda_transito' );
add_action( 'mural17_le_transito', 'mural17_transito_atualiza' );
add_action(
	'switch_theme',
	function () {
		wp_clear_scheduled_hook( 'mural17_le_transito' );
	}
);

add_action(
	'admin_post_mural17_admin_transito',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_transito' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		if ( isset( $_POST['chave'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$nova = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['chave'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			if ( '' !== $nova || ! empty( $_POST['apagar'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				update_option( 'mural17_tomtom_chave', $nova, false );
				delete_option( 'mural17_transito_erro' );
			}
		}
		mural17_agenda_transito();
		if ( '' === mural17_tomtom_chave() ) {
			delete_option( 'mural17_transito' );
			mural17_volta_admin_servicos( 'transito-sem', 'transito' );
		}
		mural17_volta_admin_servicos( mural17_transito_atualiza() ? 'transito' : 'transito-erro', 'transito' );
	}
);
