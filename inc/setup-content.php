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
