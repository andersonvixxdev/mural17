<?php
/**
 * Mural 17 — funções do tema.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MURAL17_VERSION', '2.0.0' );
define( 'MURAL17_CIDADES_VERSAO', 1 );
define( 'MURAL17_DIR', get_template_directory() );
define( 'MURAL17_URI', get_template_directory_uri() );

require MURAL17_DIR . '/inc/cidades.php';
require MURAL17_DIR . '/inc/post-types.php';
require MURAL17_DIR . '/inc/meta-boxes.php';
require MURAL17_DIR . '/inc/customizer.php';
require MURAL17_DIR . '/inc/helpers.php';
require MURAL17_DIR . '/inc/mural.php';
require MURAL17_DIR . '/inc/setup-content.php';

/**
 * Configuração básica do tema.
 */
function mural17_setup() {
	load_theme_textdomain( 'mural17', MURAL17_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'mural17-destaque', 1200, 675, true );
	add_image_size( 'mural17-card', 640, 360, true );
	add_image_size( 'mural17-mini', 320, 240, true );

	register_nav_menus(
		array(
			'principal' => __( 'Menu principal (seções)', 'mural17' ),
			'topo'      => __( 'Barra do topo', 'mural17' ),
			'rodape'    => __( 'Rodapé', 'mural17' ),
		)
	);
}
add_action( 'after_setup_theme', 'mural17_setup' );

/**
 * Áreas de widget (anúncios e barra lateral).
 */
function mural17_widgets_init() {
	$areas = array(
		'barra-lateral' => __( 'Barra lateral', 'mural17' ),
		'anuncio-topo'  => __( 'Anúncio do topo (728×90)', 'mural17' ),
		'anuncio-meio'  => __( 'Anúncio da home (728×90)', 'mural17' ),
		'anuncio-lado'  => __( 'Anúncio lateral (300×250)', 'mural17' ),
		'anuncio-post'  => __( 'Anúncio dentro da matéria', 'mural17' ),
	);

	foreach ( $areas as $id => $name ) {
		register_sidebar(
			array(
				'name'          => $name,
				'id'            => $id,
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
}
add_action( 'widgets_init', 'mural17_widgets_init' );

/**
 * CSS, fontes e JS.
 */
function mural17_assets() {
	wp_enqueue_style(
		'mural17-fonts',
		'https://fonts.googleapis.com/css2?family=Unbounded:wght@700;900&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700;8..60,800&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'mural17-main', MURAL17_URI . '/assets/css/main.css', array( 'mural17-fonts' ), MURAL17_VERSION );
	wp_add_inline_style( 'mural17-main', mural17_accent_css() );

	wp_enqueue_script( 'mural17-main', MURAL17_URI . '/assets/js/main.js', array(), MURAL17_VERSION, true );
	wp_add_inline_script(
		'mural17-main',
		'window.mural17 = ' . wp_json_encode(
			array(
				'rest'    => esc_url_raw( rest_url( 'wp/v2/posts' ) ),
				'cidades' => mural17_cidades_js(),
			)
		) . ';',
		'before'
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'mural17_assets' );

/**
 * Pré-conexão com o Google Fonts.
 *
 * @param array  $urls          URLs.
 * @param string $relation_type Tipo.
 * @return array
 */
function mural17_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array(
			'href' => 'https://fonts.gstatic.com',
			'crossorigin',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'mural17_resource_hints', 10, 2 );

/**
 * Favicon da marca enquanto não houver "Ícone do site" no Personalizar.
 */
function mural17_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	$base = MURAL17_URI . '/assets/marca/';
	echo '<link rel="icon" href="' . esc_url( $base . 'mural17-favicon.svg' ) . '" type="image/svg+xml">' . "\n";
	echo '<link rel="icon" href="' . esc_url( $base . 'mural17-icone-512.png' ) . '" sizes="512x512">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $base . 'mural17-icone-180.png' ) . '">' . "\n";
	echo '<meta name="theme-color" content="#0E1014">' . "\n";
}
add_action( 'wp_head', 'mural17_favicon' );

/**
 * Resumo curto nas listagens.
 *
 * @return int
 */
function mural17_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'mural17_excerpt_length' );

/**
 * Remove o "[...]" do resumo.
 *
 * @return string
 */
function mural17_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'mural17_excerpt_more' );

/**
 * Eventos: arquivo ordenado pela data do evento, só os próximos.
 * Vagas e classificados: 12 por página.
 *
 * @param WP_Query $query Consulta.
 */
function mural17_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'evento' ) ) {
		$query->set( 'meta_key', '_mural17_evento_data' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
		$query->set(
			'meta_query',
			array(
				array(
					'key'     => '_mural17_evento_data',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			)
		);
	}

	if ( $query->is_post_type_archive( array( 'vaga', 'classificado' ) ) ) {
		$query->set( 'posts_per_page', 12 );
	}

	if ( $query->is_search() ) {
		$query->set( 'post_type', array( 'post', 'evento', 'vaga', 'classificado' ) );
	}
}
add_action( 'pre_get_posts', 'mural17_pre_get_posts' );
