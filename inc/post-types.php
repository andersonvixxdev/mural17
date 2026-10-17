<?php
/**
 * Tipos de conteúdo: eventos, vagas e classificados; taxonomias de cidade e categoria de anúncio.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monta os rótulos de um tipo de conteúdo.
 *
 * @param string $singular Nome no singular.
 * @param string $plural   Nome no plural.
 * @param bool   $feminino Se o substantivo é feminino.
 * @return array
 */
function mural17_cpt_labels( $singular, $plural, $feminino = false ) {
	$novo = $feminino ? 'Nova' : 'Novo';
	$todo = $feminino ? 'Todas as' : 'Todos os';
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'add_new'            => "Adicionar {$singular}",
		'add_new_item'       => "Adicionar {$singular}",
		'new_item'           => "{$novo} {$singular}",
		'edit_item'          => "Editar {$singular}",
		'view_item'          => "Ver {$singular}",
		'all_items'          => "{$todo} {$plural}",
		'search_items'       => "Buscar {$plural}",
		'not_found'          => 'Nada encontrado.',
		'not_found_in_trash' => 'Nada na lixeira.',
		'menu_name'          => $plural,
	);
}

/**
 * Registra tipos de conteúdo e taxonomias.
 */
function mural17_register_types() {
	$comum = array(
		'public'       => true,
		'has_archive'  => true,
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
	);

	// Taxonomias antes dos tipos de conteúdo: assim /classificados/categoria/... não colide com as regras dos anúncios.
	register_taxonomy(
		'cidade',
		array( 'post', 'evento', 'vaga', 'classificado' ),
		array(
			'labels'            => array(
				'name'          => 'Cidades',
				'singular_name' => 'Cidade',
				'search_items'  => 'Buscar cidades',
				'all_items'     => 'Todas as cidades',
				'edit_item'     => 'Editar cidade',
				'add_new_item'  => 'Adicionar cidade',
				'menu_name'     => 'Cidades',
			),
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'cidade' ),
		)
	);

	register_taxonomy(
		'categoria-anuncio',
		array( 'classificado' ),
		array(
			'labels'            => array(
				'name'          => 'Categorias de anúncio',
				'singular_name' => 'Categoria de anúncio',
				'all_items'     => 'Todas as categorias',
				'add_new_item'  => 'Adicionar categoria',
				'menu_name'     => 'Categorias',
			),
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'classificados/categoria' ),
		)
	);

	register_post_type(
		'evento',
		array_merge(
			$comum,
			array(
				'labels'    => mural17_cpt_labels( 'Evento', 'Eventos' ),
				'rewrite'   => array( 'slug' => 'agenda' ),
				'menu_icon' => 'dashicons-calendar-alt',
			)
		)
	);

	register_post_type(
		'vaga',
		array_merge(
			$comum,
			array(
				'labels'    => mural17_cpt_labels( 'Vaga', 'Vagas', true ),
				'rewrite'   => array( 'slug' => 'empregos' ),
				'menu_icon' => 'dashicons-businessperson',
			)
		)
	);

	register_post_type(
		'classificado',
		array_merge(
			$comum,
			array(
				'labels'    => mural17_cpt_labels( 'Classificado', 'Classificados' ),
				'rewrite'   => array( 'slug' => 'classificados' ),
				'menu_icon' => 'dashicons-megaphone',
			)
		)
	);
}
add_action( 'init', 'mural17_register_types' );
