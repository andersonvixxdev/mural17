<?php
/**
 * Preço dos combustíveis: lê a planilha semanal da ANP (Levantamento de
 * Preços de Combustíveis, aba MUNICIPIOS) e guarda as cidades do 17 que a
 * ANP pesquisa. As outras cidades usam a mais próxima como referência.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Produtos mostrados (nome na planilha => chave).
 *
 * @return array<string, array{chave:string,nome:string}>
 */
function mural17_produtos_anp() {
	return array(
		'GASOLINA COMUM'   => array( 'chave' => 'gasolina', 'nome' => 'Gasolina comum' ),
		'ETANOL HIDRATADO' => array( 'chave' => 'etanol', 'nome' => 'Etanol' ),
		'OLEO DIESEL S10'  => array( 'chave' => 'diesel', 'nome' => 'Diesel S10' ),
		'GLP'              => array( 'chave' => 'gas', 'nome' => 'Gás de cozinha (13 kg)' ),
	);
}

/**
 * Página da ANP com os links das planilhas.
 *
 * @return string
 */
function mural17_anp_pagina() {
	return apply_filters( 'mural17_anp_pagina', 'https://www.gov.br/anp/pt-br/assuntos/precos-e-defesa-da-concorrencia/precos/levantamento-de-precos-de-combustiveis-ultimas-semanas-pesquisadas' );
}

/**
 * Procura a planilha mais nova e importa se for diferente da guardada.
 *
 * @param bool $forcar Importa mesmo que seja a mesma planilha.
 * @return true|WP_Error
 */
function mural17_anp_atualiza( $forcar = false ) {
	$r = wp_remote_get( mural17_anp_pagina(), array( 'timeout' => 20 ) );
	if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
		return mural17_anp_erro( 'Não foi possível abrir a página da ANP.' );
	}
	if ( ! preg_match_all( '#https://[^"\']+/resumo_semanal_lpc_[^"\']+\.xlsx#i', wp_remote_retrieve_body( $r ), $m ) ) {
		return mural17_anp_erro( 'A página da ANP não tem o link da planilha semanal.' );
	}
	$links = array_unique( $m[0] );
	// O nome traz as datas da semana: o maior é o mais novo.
	rsort( $links );
	$url   = $links[0];
	$atual = get_option( 'mural17_combustivel' );
	if ( ! $forcar && is_array( $atual ) && isset( $atual['url'] ) && $atual['url'] === $url ) {
		return true;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	$arquivo = wp_tempnam( 'anp.xlsx' );
	$baixou  = wp_remote_get(
		$url,
		array(
			'timeout'  => 60,
			'stream'   => true,
			'filename' => $arquivo,
		)
	);
	if ( is_wp_error( $baixou ) || 200 !== wp_remote_retrieve_response_code( $baixou ) || filesize( $arquivo ) < 1000 ) {
		wp_delete_file( $arquivo );
		return mural17_anp_erro( 'Não foi possível baixar a planilha da ANP.' );
	}
	$dados = mural17_anp_le_planilha( $arquivo );
	wp_delete_file( $arquivo );
	if ( is_wp_error( $dados ) ) {
		return mural17_anp_erro( $dados->get_error_message() );
	}

	// A semana anterior serve para mostrar se subiu ou desceu.
	$anterior = array();
	if ( is_array( $atual ) && ! empty( $atual['cidades'] ) && ( $atual['semana'][1] ?? '' ) < $dados['semana'][1] ) {
		foreach ( $atual['cidades'] as $id => $produtos ) {
			foreach ( $produtos as $k => $p ) {
				$anterior[ $id ][ $k ] = $p['media'];
			}
		}
	} elseif ( is_array( $atual ) && isset( $atual['anterior'] ) ) {
		$anterior = $atual['anterior'];
	}
	$dados['anterior'] = $anterior;
	$dados['url']      = $url;
	$dados['em']       = time();
	update_option( 'mural17_combustivel', $dados, false );
	delete_option( 'mural17_combustivel_erro' );
	return true;
}
add_action( 'mural17_le_anp', 'mural17_anp_atualiza' );

/**
 * Guarda e devolve o erro da última leitura.
 *
 * @param string $msg Mensagem.
 * @return WP_Error
 */
function mural17_anp_erro( $msg ) {
	update_option( 'mural17_combustivel_erro', array( $msg, time() ), false );
	return new WP_Error( 'anp', $msg );
}

/**
 * Lê a aba MUNICIPIOS da planilha (.xlsx) e devolve as cidades do 17.
 *
 * @param string $arquivo Caminho do .xlsx.
 * @return array|WP_Error
 */
function mural17_anp_le_planilha( $arquivo ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'anp', 'O servidor não tem a extensão zip do PHP, usada para ler a planilha.' );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $arquivo ) ) {
		return new WP_Error( 'anp', 'A planilha da ANP veio corrompida.' );
	}
	$xml = function ( $nome ) use ( $zip ) {
		$s = $zip->getFromName( $nome );
		if ( false === $s ) {
			return null;
		}
		$prev = libxml_use_internal_errors( true );
		$x    = simplexml_load_string( $s, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE );
		libxml_use_internal_errors( $prev );
		return false === $x ? null : $x;
	};

	// Qual arquivo é a aba MUNICIPIOS.
	$wb   = $xml( 'xl/workbook.xml' );
	$rels = $xml( 'xl/_rels/workbook.xml.rels' );
	$alvo = 'xl/worksheets/sheet2.xml';
	if ( $wb && $rels ) {
		$rid = '';
		foreach ( $wb->sheets->sheet as $sh ) {
			if ( 'MUNICIPIOS' === strtoupper( remove_accents( (string) $sh['name'] ) ) ) {
				$rid = (string) $sh->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' )->id;
			}
		}
		foreach ( $rels->Relationship as $rel ) {
			if ( $rid && (string) $rel['Id'] === $rid ) {
				$alvo = 'xl/' . ltrim( preg_replace( '#^/?xl/#', '', (string) $rel['Target'] ), '/' );
			}
		}
	}

	$textos = array();
	$ss     = $xml( 'xl/sharedStrings.xml' );
	if ( $ss ) {
		foreach ( $ss->si as $si ) {
			$t = '';
			if ( isset( $si->t ) ) {
				$t = (string) $si->t;
			}
			foreach ( $si->r as $run ) {
				$t .= (string) $run->t;
			}
			$textos[] = $t;
		}
	}
	$planilha = $xml( $alvo );
	$zip->close();
	if ( ! $planilha ) {
		return new WP_Error( 'anp', 'A planilha da ANP não tem a aba de municípios.' );
	}

	$mapa     = mural17_mapa_nomes_cidades();
	$produtos = mural17_produtos_anp();
	$cols     = array();
	$cidades  = array();
	$semana   = array( '', '' );
	$col      = function ( $ref ) {
		return preg_replace( '/\d+/', '', (string) $ref );
	};
	foreach ( $planilha->sheetData->row as $row ) {
		$linha = array();
		foreach ( $row->c as $c ) {
			$v = isset( $c->v ) ? (string) $c->v : ( isset( $c->is->t ) ? (string) $c->is->t : '' );
			if ( 's' === (string) $c['t'] && '' !== $v ) {
				$v = isset( $textos[ (int) $v ] ) ? $textos[ (int) $v ] : '';
			}
			$linha[ $col( $c['r'] ) ] = trim( $v );
		}
		// Linha de títulos: descobre a coluna de cada dado.
		if ( ! $cols ) {
			foreach ( $linha as $letra => $titulo ) {
				$t = strtoupper( remove_accents( $titulo ) );
				$k = array(
					'DATA INICIAL'                => 'ini',
					'DATA FINAL'                  => 'fim',
					'ESTADO'                      => 'uf',
					'MUNICIPIO'                   => 'mun',
					'PRODUTO'                     => 'prod',
					'NUMERO DE POSTOS PESQUISADOS' => 'postos',
					'PRECO MEDIO REVENDA'         => 'media',
					'PRECO MINIMO REVENDA'        => 'min',
					'PRECO MAXIMO REVENDA'        => 'max',
				);
				if ( isset( $k[ $t ] ) ) {
					$cols[ $k[ $t ] ] = $letra;
				}
			}
			if ( count( $cols ) < 9 ) {
				$cols = array();
			}
			continue;
		}
		$g = function ( $k ) use ( $linha, $cols ) {
			return isset( $linha[ $cols[ $k ] ] ) ? $linha[ $cols[ $k ] ] : '';
		};
		if ( 'SAO PAULO' !== strtoupper( remove_accents( $g( 'uf' ) ) ) ) {
			continue;
		}
		$prod = strtoupper( remove_accents( $g( 'prod' ) ) );
		$id   = $mapa[ mural17_normaliza( $g( 'mun' ) ) ] ?? 0;
		if ( ! $id || ! isset( $produtos[ $prod ] ) ) {
			continue;
		}
		$cidades[ $id ][ $produtos[ $prod ]['chave'] ] = array(
			'media'  => round( (float) $g( 'media' ), 2 ),
			'min'    => round( (float) $g( 'min' ), 2 ),
			'max'    => round( (float) $g( 'max' ), 2 ),
			'postos' => (int) $g( 'postos' ),
		);
		if ( ! $semana[0] ) {
			$semana = array( mural17_data_excel( $g( 'ini' ) ), mural17_data_excel( $g( 'fim' ) ) );
		}
	}
	if ( ! $cidades ) {
		return new WP_Error( 'anp', 'Nenhuma cidade do 17 na planilha desta semana.' );
	}
	return array(
		'semana'  => $semana,
		'cidades' => $cidades,
	);
}

/**
 * Data do Excel (número de dias desde 30/12/1899) ou texto dd/mm/aaaa → Y-m-d.
 *
 * @param string $v Valor.
 * @return string
 */
function mural17_data_excel( $v ) {
	if ( is_numeric( $v ) ) {
		return gmdate( 'Y-m-d', (int) round( ( (float) $v - 25569 ) * DAY_IN_SECONDS ) );
	}
	if ( preg_match( '#^(\d{2})/(\d{2})/(\d{4})$#', $v, $m ) ) {
		return "{$m[3]}-{$m[2]}-{$m[1]}";
	}
	return '';
}

/**
 * Dados guardados, ou null.
 *
 * @return array|null
 */
function mural17_combustivel() {
	$d = get_option( 'mural17_combustivel' );
	return is_array( $d ) && ! empty( $d['cidades'] ) ? $d : null;
}

/**
 * Cidade com pesquisa da ANP para uma cidade: ela mesma ou a mais próxima.
 *
 * @param WP_Term $cidade Cidade.
 * @return array{cidade:WP_Term,km:float,precos:array}|null
 */
function mural17_combustivel_referencia( $cidade ) {
	$d = mural17_combustivel();
	if ( ! $d || ! $cidade ) {
		return null;
	}
	if ( isset( $d['cidades'][ $cidade->term_id ] ) ) {
		return array(
			'cidade' => $cidade,
			'km'     => 0.0,
			'precos' => $d['cidades'][ $cidade->term_id ],
		);
	}
	$coords = mural17_coordenadas();
	if ( ! isset( $coords[ $cidade->slug ] ) ) {
		return null;
	}
	$melhor = null;
	foreach ( array_keys( $d['cidades'] ) as $id ) {
		$c = get_term( (int) $id, 'cidade' );
		if ( ! $c || is_wp_error( $c ) || ! isset( $coords[ $c->slug ] ) ) {
			continue;
		}
		$km = mural17_km( $coords[ $cidade->slug ], $coords[ $c->slug ] );
		if ( ! $melhor || $km < $melhor['km'] ) {
			$melhor = array(
				'cidade' => $c,
				'km'     => $km,
				'precos' => $d['cidades'][ $id ],
			);
		}
	}
	return $melhor;
}

/**
 * Variação da semana para uma cidade e produto: [classe, texto].
 *
 * @param int    $id      Cidade.
 * @param string $produto Produto.
 * @return array{0:string,1:string}|null
 */
function mural17_variacao_combustivel( $id, $produto ) {
	$d = mural17_combustivel();
	if ( ! $d || ! isset( $d['anterior'][ $id ][ $produto ], $d['cidades'][ $id ][ $produto ] ) ) {
		return null;
	}
	$dif = round( $d['cidades'][ $id ][ $produto ]['media'] - $d['anterior'][ $id ][ $produto ], 2 );
	if ( abs( $dif ) < 0.005 ) {
		return array( 'igual', '= 0,00' );
	}
	return array( $dif > 0 ? 'subiu' : 'desceu', ( $dif > 0 ? '▲ ' : '▼ ' ) . number_format( abs( $dif ), 2, ',', '.' ) );
}

/**
 * Preço no formato brasileiro.
 *
 * @param float $v Valor.
 * @return string
 */
function mural17_preco( $v ) {
	return 'R$ ' . number_format( (float) $v, 2, ',', '.' );
}
