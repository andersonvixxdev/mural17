<?php
/**
 * Ao ativar o tema: cria cidades, categorias e atualiza os links permanentes.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cria um termo se ele ainda não existir.
 *
 * @param string $nome     Nome.
 * @param string $slug     Slug.
 * @param string $taxonomy Taxonomia.
 */
function mural17_garante_termo( $nome, $slug, $taxonomy ) {
	if ( ! term_exists( $slug, $taxonomy ) ) {
		wp_insert_term( $nome, $taxonomy, array( 'slug' => $slug ) );
	}
}

/**
 * Cria as regiões e as 117 cidades do DDD 17, cada cidade dentro da sua região.
 * Termos que já existem (pelo slug) só ganham nome e região corretos.
 */
function mural17_cria_cidades() {
	foreach ( mural17_regioes() as $slug_regiao => $regiao ) {
		$r = term_exists( $slug_regiao, 'cidade' );
		if ( ! $r ) {
			$r = wp_insert_term( $regiao['nome'], 'cidade', array( 'slug' => $slug_regiao ) );
		}
		if ( is_wp_error( $r ) ) {
			continue;
		}
		$id_regiao = (int) $r['term_id'];

		foreach ( $regiao['cidades'] as $slug => $nome ) {
			$c = term_exists( $slug, 'cidade' );
			if ( $c ) {
				wp_update_term(
					(int) $c['term_id'],
					'cidade',
					array(
						'name'   => $nome,
						'parent' => $id_regiao,
					)
				);
			} else {
				wp_insert_term(
					$nome,
					'cidade',
					array(
						'slug'   => $slug,
						'parent' => $id_regiao,
					)
				);
			}
		}
	}
}

/**
 * Conteúdo inicial do portal.
 */
function mural17_ativar() {
	mural17_register_types();
	mural17_cria_cidades();
	update_option( 'mural17_cidades_versao', MURAL17_CIDADES_VERSAO );

	foreach ( array( 'policia' => 'Polícia', 'transito' => 'Trânsito' ) as $slug => $nome ) {
		mural17_garante_termo( $nome, $slug, 'category' );
	}

	$anuncios = array(
		'veiculos' => 'Veículos',
		'imoveis'  => 'Imóveis',
		'moveis'   => 'Móveis e casa',
		'servicos' => 'Serviços',
		'diversos' => 'Diversos',
	);
	foreach ( $anuncios as $slug => $nome ) {
		mural17_garante_termo( $nome, $slug, 'categoria-anuncio' );
	}

	// Portal de Notícias PRO: mostra a taxonomia Cidade no cadastro de cada
	// feed, para a matéria importada já entrar na cidade certa. add_option
	// não sobrescreve se o dono do site já tiver escolhido outra coisa.
	add_option( 'pfai_feed_tax_ativo', 1 );

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'mural17_ativar' );

/**
 * Sites que já tinham o tema ativo recebem as regiões e cidades novas no
 * primeiro acesso ao painel depois da atualização.
 */
function mural17_atualiza_cidades() {
	if ( (int) get_option( 'mural17_cidades_versao' ) >= MURAL17_CIDADES_VERSAO ) {
		return;
	}
	mural17_cria_cidades();
	update_option( 'mural17_cidades_versao', MURAL17_CIDADES_VERSAO );
}
add_action( 'admin_init', 'mural17_atualiza_cidades' );

/**
 * Portal de Notícias PRO: liga o campo "Cidade" no cadastro de feed também
 * em sites onde o plugin chegou depois do tema (a ativação do tema já tinha
 * passado). Só liga se ninguém escolheu outra coisa.
 */
function mural17_liga_cidade_no_feed() {
	if ( '' === get_option( 'pfai_feed_tax_ativo', '' ) ) {
		update_option( 'pfai_feed_tax_ativo', 1 );
	}
}
add_action( 'admin_init', 'mural17_liga_cidade_no_feed' );

/**
 * No cadastro de feed, só a Cidade: órgão oficial não vem de feed.
 *
 * @param array $taxonomias Taxonomias extras do feed.
 * @return array
 */
function mural17_taxonomias_do_feed( $taxonomias ) {
	return array_intersect_key( (array) $taxonomias, array( 'cidade' => true ) );
}
add_filter( 'pfai_feed_taxonomias_extras', 'mural17_taxonomias_do_feed' );

/**
 * Cidade de um feed do Portal PRO: a marcada no cadastro do feed ou, se não
 * tiver (feeds do "Importar lista" não têm), a que aparece no nome
 * ("Prefeitura de Mirassol") ou no endereço (mirassol.sp.gov.br).
 *
 * @param int $feed_id Feed.
 * @return int ID da cidade, ou 0.
 */
function mural17_cidade_do_feed( $feed_id ) {
	static $cache = array();
	$feed_id = (int) $feed_id;
	if ( isset( $cache[ $feed_id ] ) ) {
		return $cache[ $feed_id ];
	}
	$cache[ $feed_id ] = 0;
	if ( class_exists( 'PFAI_Feed_Taxonomias' ) ) {
		$marcadas = PFAI_Feed_Taxonomias::do_feed( $feed_id );
		if ( ! empty( $marcadas['cidade'] ) ) {
			$cache[ $feed_id ] = (int) $marcadas['cidade'];
			return $cache[ $feed_id ];
		}
	}
	global $wpdb;
	$tabela = $wpdb->prefix . 'pfai_feeds';
	$feed   = $wpdb->get_row( $wpdb->prepare( "SELECT name, url FROM {$tabela} WHERE id = %d", $feed_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	if ( ! $feed ) {
		return 0;
	}
	$nome = ' ' . mural17_normaliza( $feed->name ) . ' ';
	$host = (string) wp_parse_url( (string) $feed->url, PHP_URL_HOST );
	$host = preg_replace( '/^www\./', '', strtolower( $host ) );
	$sub  = str_replace( '-', '', strtok( $host, '.' ) );

	$mapa = mural17_mapa_nomes_cidades();
	// Nomes mais longos primeiro: "Santa Fé do Sul" antes de "Sul".
	uksort(
		$mapa,
		function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);
	foreach ( $mapa as $n => $id ) {
		$compacto = str_replace( ' ', '', $n );
		if ( false !== strpos( $nome, ' ' . $n . ' ' ) || ( $host && ( $sub === $compacto || 0 === strpos( $host, $compacto . '.' ) ) ) ) {
			$cache[ $feed_id ] = (int) $id;
			return $cache[ $feed_id ];
		}
	}
	// Endereços que não usam o nome inteiro.
	$apelidos = array(
		'riopreto' => 'rio-preto',
	);
	if ( isset( $apelidos[ $sub ] ) ) {
		$t = get_term_by( 'slug', $apelidos[ $sub ], 'cidade' );
		$cache[ $feed_id ] = $t ? (int) $t->term_id : 0;
	}
	return $cache[ $feed_id ];
}

/**
 * Matéria importada sem cidade ganha a cidade do feed.
 *
 * @param int    $meta_id ID do meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 * @param mixed  $valor   Valor.
 */
function mural17_cidade_na_importacao( $meta_id, $post_id, $chave, $valor ) {
	if ( '_pfai_feed_id' !== $chave || ! (int) $valor ) {
		return;
	}
	$atuais = wp_get_object_terms( $post_id, 'cidade', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $atuais ) && $atuais ) {
		return;
	}
	$cidade = mural17_cidade_do_feed( (int) $valor );
	if ( $cidade ) {
		wp_set_object_terms( $post_id, array( $cidade ), 'cidade', false );
	}
}
add_action( 'added_post_meta', 'mural17_cidade_na_importacao', 20, 4 );
add_action( 'updated_post_meta', 'mural17_cidade_na_importacao', 20, 4 );
