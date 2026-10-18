<?php
/**
 * Dados do "mural vivo": a home e as páginas de região e cidade.
 *
 * Tudo recebe um escopo: null para o DDD 17 inteiro, ou um termo da
 * taxonomia cidade (região ou cidade). Região inclui as suas cidades.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filtro de taxonomia para um escopo.
 *
 * @param WP_Term|null $escopo Região, cidade ou null.
 * @return array
 */
function mural17_tax_escopo( $escopo ) {
	if ( ! $escopo ) {
		return array();
	}
	return array(
		array(
			'taxonomy'         => 'cidade',
			'field'            => 'term_id',
			'terms'            => $escopo->term_id,
			'include_children' => true,
		),
	);
}

/**
 * Consulta com o escopo aplicado.
 *
 * @param WP_Term|null $escopo Escopo.
 * @param array        $args   Argumentos do WP_Query.
 * @return WP_Query
 */
function mural17_consulta( $escopo, $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_type'           => 'post',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$tax = mural17_tax_escopo( $escopo );
	if ( $tax ) {
		$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	return new WP_Query( $args );
}

/**
 * Nome curto para títulos: "Rio Preto" em vez de "Região de Rio Preto".
 *
 * @param WP_Term|null $escopo Escopo.
 * @return string
 */
function mural17_nome_escopo( $escopo ) {
	if ( ! $escopo ) {
		return '17';
	}
	return preg_replace( '/^Região de /', '', $escopo->name );
}

/**
 * O escopo é uma região (termo de primeiro nível)?
 *
 * @param WP_Term|null $escopo Escopo.
 * @return bool
 */
function mural17_e_regiao( $escopo ) {
	return $escopo && 0 === (int) $escopo->parent;
}

/**
 * Filtro de meta para eventos entre duas datas (Y-m-d).
 *
 * @param string $de  Data inicial.
 * @param string $ate Data final.
 * @return array
 */
function mural17_meta_eventos( $de, $ate ) {
	return array(
		array(
			'key'     => '_mural17_evento_data',
			'value'   => array( $de, $ate ),
			'compare' => 'BETWEEN',
			'type'    => 'DATE',
		),
	);
}

/**
 * Filtro de meta para vagas com inscrição aberta (sem prazo ou prazo futuro).
 *
 * @return array
 */
function mural17_meta_vagas_abertas() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_mural17_vaga_validade',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_mural17_vaga_validade',
			'value'   => '',
			'compare' => '=',
		),
		array(
			'key'     => '_mural17_vaga_validade',
			'value'   => current_time( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		),
	);
}

/**
 * Números do topo ("Hoje no 17"). Guardados por 10 minutos.
 *
 * @param WP_Term|null $escopo Escopo.
 * @return array{noticias:int,vagas:int,eventos:int,anuncios:int,atualizado:int}
 */
function mural17_numeros( $escopo ) {
	$chave = 'mural17_numeros_' . ( $escopo ? $escopo->term_id : 'todos' );
	$cache = get_transient( $chave );
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$hoje  = current_time( 'Y-m-d' );
	$conta = function ( $args ) use ( $escopo ) {
		$args['posts_per_page'] = 1;
		$args['fields']         = 'ids';
		$args['no_found_rows']  = false;
		return (int) mural17_consulta( $escopo, $args )->found_posts;
	};

	$ultima = mural17_consulta(
		$escopo,
		array(
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	$numeros = array(
		'noticias'   => $conta( array( 'date_query' => array( array( 'after' => $hoje . ' 00:00:00', 'inclusive' => true ) ) ) ),
		'vagas'      => $conta(
			array(
				'post_type'  => 'vaga',
				'meta_query' => mural17_meta_vagas_abertas(), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		),
		'eventos'    => $conta(
			array(
				'post_type'  => 'evento',
				'meta_query' => mural17_meta_eventos( $hoje, gmdate( 'Y-m-d', strtotime( $hoje . ' +6 days' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		),
		'anuncios'   => $conta(
			array(
				'post_type'  => 'classificado',
				'date_query' => array( array( 'after' => '30 days ago' ) ),
			)
		),
		'atualizado' => $ultima->posts ? (int) get_post_time( 'U', false, $ultima->posts[0] ) : 0,
	);

	set_transient( $chave, $numeros, 10 * MINUTE_IN_SECONDS );
	return $numeros;
}

/**
 * Limpa os números guardados quando algo é publicado ou alterado.
 */
function mural17_limpa_numeros() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_mural17\\_numeros\\_%' OR option_name LIKE '\\_transient\\_timeout\\_mural17\\_numeros\\_%' OR option_name LIKE '\\_transient\\_mural17\\_rodas%' OR option_name LIKE '\\_transient\\_timeout\\_mural17\\_rodas%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
add_action( 'save_post', 'mural17_limpa_numeros' );
add_action( 'deleted_post', 'mural17_limpa_numeros' );

/**
 * Manchete do escopo: post fixado no topo; sem fixado, o mais recente com foto.
 *
 * @param WP_Term|null $escopo Escopo.
 * @return WP_Post|null
 */
function mural17_manchete( $escopo ) {
	$fixados = get_option( 'sticky_posts' );
	if ( $fixados ) {
		$q = mural17_consulta(
			$escopo,
			array(
				'posts_per_page' => 1,
				'post__in'       => $fixados,
				'date_query'     => array( array( 'after' => '3 days ago' ) ),
			)
		);
		if ( $q->posts ) {
			return $q->posts[0];
		}
	}
	// Manchete automática é da redação: notas oficiais ficam de fora (fixadas à mão, entram).
	$tax = array_merge(
		array( 'relation' => 'AND' ),
		mural17_tax_escopo( $escopo ),
		array(
			array(
				'taxonomy' => 'orgao',
				'operator' => 'NOT EXISTS',
			),
		)
	);
	foreach ( array( array( 'meta_key' => '_thumbnail_id' ), array() ) as $extra ) {
		$q = new WP_Query(
			array_merge(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => 1,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'tax_query'           => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
				),
				$extra
			)
		);
		if ( $q->posts ) {
			return $q->posts[0];
		}
	}
	return null;
}

/**
 * Hora do Plantão: "09:42" hoje, "ontem" ou "12/10".
 *
 * @param WP_Post|int|null $post Post.
 * @return string
 */
function mural17_hora_plantao( $post = null ) {
	$ts   = (int) get_post_time( 'U', false, $post );
	$hoje = current_time( 'Y-m-d' );
	$dia  = gmdate( 'Y-m-d', $ts );
	if ( $dia === $hoje ) {
		return gmdate( 'H:i', $ts );
	}
	if ( gmdate( 'Y-m-d', strtotime( $hoje . ' -1 day' ) ) === $dia ) {
		return 'ontem';
	}
	return gmdate( 'd/m', $ts );
}

/**
 * Próximo evento do escopo (hoje em diante).
 *
 * @param WP_Term|null $escopo Escopo.
 * @return WP_Post|null
 */
function mural17_proximo_evento( $escopo ) {
	$hoje = current_time( 'Y-m-d' );
	$q    = mural17_consulta(
		$escopo,
		array(
			'post_type'      => 'evento',
			'posts_per_page' => 1,
			'meta_key'       => '_mural17_evento_data', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => mural17_meta_eventos( $hoje, gmdate( 'Y-m-d', strtotime( $hoje . ' +60 days' ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q->posts ? $q->posts[0] : null;
}

/**
 * Vaga mais recente com inscrição aberta.
 *
 * @param WP_Term|null $escopo Escopo.
 * @return WP_Post|null
 */
function mural17_vaga_do_dia( $escopo ) {
	$q = mural17_consulta(
		$escopo,
		array(
			'post_type'      => 'vaga',
			'posts_per_page' => 1,
			'meta_query'     => mural17_meta_vagas_abertas(), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q->posts ? $q->posts[0] : null;
}

/**
 * Eventos dos próximos 7 dias, agrupados por data (Y-m-d).
 *
 * @param WP_Term|null $escopo Escopo.
 * @return array<string, WP_Post[]>
 */
function mural17_semana( $escopo ) {
	$hoje = current_time( 'Y-m-d' );
	$dias = array();
	for ( $i = 0; $i < 7; $i++ ) {
		$dias[ gmdate( 'Y-m-d', strtotime( $hoje . " +{$i} days" ) ) ] = array();
	}
	$q = mural17_consulta(
		$escopo,
		array(
			'post_type'      => 'evento',
			'posts_per_page' => 40,
			'meta_key'       => '_mural17_evento_data', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => mural17_meta_eventos( $hoje, array_key_last( $dias ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $q->posts as $ev ) {
		$d = (string) get_post_meta( $ev->ID, '_mural17_evento_data', true );
		if ( isset( $dias[ $d ] ) ) {
			$dias[ $d ][] = $ev;
		}
	}
	return $dias;
}

/**
 * Sigla de duas letras para as bolinhas (Rio Preto → RP, Catanduva → CA).
 *
 * @param string $nome Nome.
 * @return string
 */
function mural17_sigla( $nome ) {
	$nome     = remove_accents( preg_replace( '/^Região de /', '', $nome ) );
	$palavras = array_values(
		array_filter(
			preg_split( '/[\s\'-]+/', $nome ),
			function ( $p ) {
				return ! in_array( strtolower( $p ), array( 'de', 'do', 'da', 'dos', 'das', 'd' ), true );
			}
		)
	);
	if ( count( $palavras ) >= 2 ) {
		return strtoupper( $palavras[0][0] . $palavras[1][0] );
	}
	return strtoupper( substr( $palavras[0], 0, 2 ) );
}

/**
 * Bolinhas do topo: regiões (na home), cidades (na região) ou cidades
 * vizinhas (na cidade), mais os assuntos. Cada uma traz o horário da última
 * publicação, que o navegador compara com a última visita para pintar o anel.
 *
 * @param WP_Term|null $escopo Escopo.
 * @return array<int, array{rotulo:string,sigla:string,url:string,ultima:int,tipo:string}>
 */
function mural17_rodas( $escopo ) {
	$chave = 'mural17_rodas_' . ( $escopo ? $escopo->term_id : 'todos' );
	$cache = get_transient( $chave );
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$ultima = function ( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'post_type'           => 'post',
				'posts_per_page'      => 1,
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		$q    = new WP_Query( $args );
		return $q->posts ? (int) get_post_time( 'U', true, $q->posts[0] ) : 0;
	};

	$rodas = array();
	foreach ( array( 'policia' => array( 'Polícia', '190' ), 'transito' => array( 'Trânsito', 'km' ) ) as $slug => $info ) {
		$cat = get_category_by_slug( $slug );
		if ( $cat ) {
			$args = array( 'cat' => $cat->term_id );
			if ( $escopo ) {
				$args['tax_query'] = mural17_tax_escopo( $escopo ); // phpcs:ignore WordPress.DB.SlowDBQuery
			}
			$rodas[] = array(
				'rotulo' => $info[0],
				'sigla'  => $info[1],
				'url'    => get_category_link( $cat ),
				'ultima' => $ultima( $args ),
				'tipo'   => $slug,
			);
		}
	}

	if ( ! $escopo ) {
		$lugares = get_terms(
			array(
				'taxonomy'   => 'cidade',
				'parent'     => 0,
				'hide_empty' => false,
			)
		);
	} elseif ( mural17_e_regiao( $escopo ) ) {
		$lugares = get_terms(
			array(
				'taxonomy'   => 'cidade',
				'parent'     => $escopo->term_id,
				'hide_empty' => true,
			)
		);
	} else {
		$lugares = get_terms(
			array(
				'taxonomy'   => 'cidade',
				'parent'     => $escopo->parent,
				'hide_empty' => true,
				'exclude'    => array( $escopo->term_id ),
			)
		);
	}

	foreach ( is_wp_error( $lugares ) ? array() : $lugares as $l ) {
		$rodas[] = array(
			'rotulo' => preg_replace( '/^Região de /', '', $l->name ),
			'sigla'  => mural17_sigla( $l->name ),
			'url'    => get_term_link( $l ),
			'ultima' => $ultima( array( 'tax_query' => mural17_tax_escopo( $l ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'tipo'   => 'lugar',
		);
	}

	// Lugares com novidade primeiro; Polícia e Trânsito ficam na frente.
	usort(
		$rodas,
		function ( $a, $b ) {
			$fixa_a = 'lugar' !== $a['tipo'];
			$fixa_b = 'lugar' !== $b['tipo'];
			if ( $fixa_a !== $fixa_b ) {
				return $fixa_a ? -1 : 1;
			}
			return $b['ultima'] <=> $a['ultima'];
		}
	);

	$rodas[] = array(
		'rotulo' => 'Empregos',
		'sigla'  => 'R$',
		'url'    => get_post_type_archive_link( 'vaga' ),
		'ultima' => $ultima( array( 'post_type' => 'vaga' ) ),
		'tipo'   => 'vaga',
	);
	$rodas[] = array(
		'rotulo' => 'Agenda',
		'sigla'  => current_time( 'd' ),
		'url'    => get_post_type_archive_link( 'evento' ),
		'ultima' => $ultima( array( 'post_type' => 'evento' ) ),
		'tipo'   => 'evento',
	);

	set_transient( $chave, $rodas, 10 * MINUTE_IN_SECONDS );
	return $rodas;
}

/**
 * Lugares para o bloco "Por região" / "Por cidade", com as 3 últimas de cada.
 *
 * @param WP_Term|null $escopo   Escopo.
 * @param int[]        $exibidos IDs que já apareceram no mural.
 * @return array<int, array{termo:WP_Term,posts:WP_Post[]}>
 */
function mural17_por_lugar( $escopo, $exibidos ) {
	if ( $escopo && ! mural17_e_regiao( $escopo ) ) {
		return array();
	}
	$lugares = get_terms(
		array(
			'taxonomy'   => 'cidade',
			'parent'     => $escopo ? $escopo->term_id : 0,
			'hide_empty' => false,
		)
	);
	$saida   = array();
	foreach ( is_wp_error( $lugares ) ? array() : $lugares as $l ) {
		$q = mural17_consulta(
			$l,
			array(
				'posts_per_page' => 3,
				'post__not_in'   => $exibidos,
			)
		);
		if ( $q->posts ) {
			$saida[] = array(
				'termo' => $l,
				'posts' => $q->posts,
				'ultima' => (int) get_post_time( 'U', true, $q->posts[0] ),
			);
		}
	}
	usort(
		$saida,
		function ( $a, $b ) {
			return $b['ultima'] <=> $a['ultima'];
		}
	);
	return array_slice( $saida, 0, 8 );
}

/**
 * Lista de regiões e cidades para o seletor "Escolha sua cidade".
 *
 * @return array<int, array{regiao:WP_Term,cidades:WP_Term[]}>
 */
function mural17_lista_cidades() {
	$regioes = get_terms(
		array(
			'taxonomy'   => 'cidade',
			'parent'     => 0,
			'hide_empty' => false,
		)
	);
	$saida   = array();
	foreach ( is_wp_error( $regioes ) ? array() : $regioes as $r ) {
		$cidades = get_terms(
			array(
				'taxonomy'   => 'cidade',
				'parent'     => $r->term_id,
				'hide_empty' => false,
			)
		);
		$saida[] = array(
			'regiao'  => $r,
			'cidades' => is_wp_error( $cidades ) ? array() : $cidades,
		);
	}
	return $saida;
}

/**
 * Cidades para o navegador: nome, link, região e coordenadas. Usado pelo
 * "Usar minha localização" (a conta da cidade mais próxima é feita no próprio
 * aparelho; a posição da pessoa não é enviada ao site) e pelo bloco "Sua
 * cidade" da home.
 *
 * @return array<int, array>
 */
function mural17_cidades_js() {
	$cache = get_transient( 'mural17_cidades_js' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$coords = mural17_coordenadas();
	$saida  = array();
	foreach ( mural17_lista_cidades() as $grupo ) {
		$r = $grupo['regiao'];
		foreach ( $grupo['cidades'] as $c ) {
			if ( ! isset( $coords[ $c->slug ] ) ) {
				continue;
			}
			$saida[] = array(
				'id'  => $c->term_id,
				'n'   => $c->name,
				'u'   => get_term_link( $c ),
				'r'   => preg_replace( '/^Região de /', '', $r->name ),
				'ru'  => get_term_link( $r ),
				'la'  => $coords[ $c->slug ][0],
				'lo'  => $coords[ $c->slug ][1],
			);
		}
	}
	set_transient( 'mural17_cidades_js', $saida, DAY_IN_SECONDS );
	return $saida;
}

/**
 * Refaz a lista do navegador quando uma cidade muda.
 */
function mural17_limpa_cidades_js() {
	delete_transient( 'mural17_cidades_js' );
}
add_action( 'created_cidade', 'mural17_limpa_cidades_js' );
add_action( 'edited_cidade', 'mural17_limpa_cidades_js' );
add_action( 'delete_cidade', 'mural17_limpa_cidades_js' );
