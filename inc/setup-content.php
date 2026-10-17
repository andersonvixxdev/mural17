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
 * Conteúdo inicial do portal.
 */
function mural17_ativar() {
	mural17_register_types();

	$cidades = array(
		'neves-paulista' => 'Neves Paulista',
		'mirassol'       => 'Mirassol',
		'jaci'           => 'Jaci',
		'rio-preto'      => 'Rio Preto',
	);
	foreach ( $cidades as $slug => $nome ) {
		mural17_garante_termo( $nome, $slug, 'cidade' );
	}

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

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'mural17_ativar' );
