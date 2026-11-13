<?php
/**
 * SEO do portal: títulos, descrição, canônico, Open Graph, dados estruturados
 * (schema.org) e sitemap do Google Notícias.
 *
 * O site não tem plugin de SEO, e o WordPress sozinho não entrega descrição,
 * Open Graph nem dados estruturados. Sem isso a busca por "mural 17" mostrava
 * murais na Dinamarca: o Google não tinha como ligar o nome à marca. Se um
 * plugin de SEO for instalado (Yoast, Rank Math, AIOSEO, SEOPress), esta parte
 * se desliga para não duplicar as tags.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MURAL17_SEO_VERSAO = 1;

/**
 * Um plugin de SEO cuida das tags?
 *
 * @return bool
 */
function mural17_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Corta um texto para descrição (até ~155 caracteres, sem quebrar palavra).
 *
 * @param string $t Texto.
 * @param int    $max Limite.
 * @return string
 */
function mural17_seo_corta( $t, $max = 155 ) {
	$t = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $t ) ), ENT_QUOTES, 'UTF-8' ) ) );
	if ( mb_strlen( $t ) <= $max ) {
		return $t;
	}
	$t = mb_substr( $t, 0, $max );
	return rtrim( mb_substr( $t, 0, (int) mb_strrpos( $t, ' ' ) ), ' ,.;:-–' ) . '…';
}

/* ---- Títulos ------------------------------------------------------------ */

add_filter(
	'document_title_separator',
	function () {
		return '|';
	}
);

/**
 * Títulos que dizem do que a página trata (a busca é "notícias de Mirassol",
 * não "Mirassol").
 *
 * @param array $p Partes do título.
 * @return array
 */
function mural17_seo_titulo( $p ) {
	if ( mural17_seo_plugin() ) {
		return $p;
	}
	if ( is_front_page() ) {
		return array( 'title' => 'Mural 17', 'tagline' => 'Notícias de Rio Preto e das 117 cidades do DDD 17' );
	}
	if ( is_tax( 'cidade' ) ) {
		$t = get_queried_object();
		if ( mural17_e_regiao( $t ) ) {
			$p['title'] = $t->name . ': notícias de hoje das cidades da região';
		} else {
			$p['title'] = $t->name . ': notícias de hoje, serviços e vagas';
		}
	} elseif ( is_category() ) {
		$p['title'] = single_cat_title( '', false ) . ' em Rio Preto e região';
	}
	return $p;
}
add_filter( 'document_title_parts', 'mural17_seo_titulo', 5 );

/* ---- Descrição, canônico e Open Graph ----------------------------------- */

/**
 * Descrição da página atual.
 *
 * @return string
 */
function mural17_seo_descricao() {
	if ( is_front_page() ) {
		return 'Notícias de hoje de São José do Rio Preto e das 117 cidades do DDD 17: polícia, trânsito, política, empregos, farmácia de plantão, falta de água e luz, agenda e alertas.';
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		$base = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		if ( 'page' === $post->post_type && false !== strpos( $post->post_content, '[mural17_institucional' ) ) {
			$base = do_shortcode( $post->post_content );
		}
		return mural17_seo_corta( $base );
	}
	if ( is_tax( 'cidade' ) ) {
		$t = get_queried_object();
		if ( mural17_e_regiao( $t ) ) {
			return 'Notícias de hoje da ' . $t->name . ' (SP): o que acontece em cada cidade, polícia, trânsito, empregos, serviços e agenda, no Mural 17.';
		}
		return 'Notícias de hoje de ' . $t->name . ' (SP): polícia, trânsito, política, vagas de emprego, farmácia de plantão, falta de água e luz e agenda da cidade, no Mural 17.';
	}
	if ( is_category() ) {
		$d = category_description();
		return $d ? mural17_seo_corta( $d ) : 'Notícias de ' . mb_strtolower( single_cat_title( '', false ) ) . ' de Rio Preto e das cidades do DDD 17, atualizadas o dia todo no Mural 17.';
	}
	if ( is_post_type_archive( 'vaga' ) ) {
		return 'Vagas de emprego em Rio Preto e região: anúncios das empresas, listas dos PATs e do Balcão de Empregos, atualizadas todo dia.';
	}
	if ( is_post_type_archive( 'evento' ) ) {
		return 'Agenda de Rio Preto e região: shows, festas, feiras e eventos da semana e do fim de semana nas cidades do DDD 17.';
	}
	return get_bloginfo( 'description' );
}

/**
 * Imagem para compartilhar (a da matéria, ou a arte da marca).
 *
 * @return array{0:string,1:int,2:int}
 */
function mural17_seo_imagem() {
	if ( is_singular() && has_post_thumbnail() ) {
		$i = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $i ) {
			return array( $i[0], (int) $i[1], (int) $i[2] );
		}
	}
	return array( MURAL17_URI . '/assets/marca/mural17-compartilhamento.png', 1200, 630 );
}

/**
 * Endereço canônico da página atual (o WordPress só põe nas matérias).
 *
 * @return string
 */
function mural17_seo_url() {
	if ( is_singular() ) {
		return (string) wp_get_canonical_url();
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	if ( is_tax() || is_category() || is_tag() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
	}
	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}
	return $paged > 1 ? trailingslashit( $url ) . 'page/' . $paged . '/' : $url;
}

/**
 * Tags no <head>.
 */
function mural17_seo_head() {
	if ( mural17_seo_plugin() || is_404() || is_search() ) {
		return;
	}
	$desc = mural17_seo_descricao();
	$url  = mural17_seo_url();
	$img  = mural17_seo_imagem();
	$tit  = wp_get_document_title();
	$tags = array();
	if ( $desc ) {
		$tags[] = '<meta name="description" content="' . esc_attr( $desc ) . '">';
	}
	if ( $url && ! is_singular() ) {
		$tags[] = '<link rel="canonical" href="' . esc_url( $url ) . '">';
	}
	$og = array(
		'og:locale'    => 'pt_BR',
		'og:site_name' => 'Mural 17',
		'og:type'      => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'     => is_singular() ? html_entity_decode( get_the_title( get_queried_object_id() ), ENT_QUOTES, 'UTF-8' ) : $tit,
		'og:description' => $desc,
		'og:url'       => $url,
		'og:image'     => $img[0],
		'og:image:width'  => $img[1],
		'og:image:height' => $img[2],
	);
	foreach ( $og as $k => $v ) {
		if ( '' !== (string) $v ) {
			$tags[] = '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">';
		}
	}
	if ( is_singular( 'post' ) ) {
		$p      = get_queried_object();
		$tags[] = '<meta property="article:published_time" content="' . esc_attr( get_post_time( 'c', true, $p ) ) . '">';
		$tags[] = '<meta property="article:modified_time" content="' . esc_attr( get_post_modified_time( 'c', true, $p ) ) . '">';
		$cats   = get_the_category( $p->ID );
		if ( $cats ) {
			$tags[] = '<meta property="article:section" content="' . esc_attr( $cats[0]->name ) . '">';
		}
	}
	$tags[] = '<meta name="twitter:card" content="summary_large_image">';
	echo "\n" . implode( "\n", $tags ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	mural17_seo_jsonld();
}
add_action( 'wp_head', 'mural17_seo_head', 2 );

/* ---- Dados estruturados (schema.org) ------------------------------------ */

/**
 * Perfis oficiais da marca (ligam o nome "Mural 17" ao site).
 *
 * @return string[]
 */
function mural17_seo_perfis() {
	return array_values( array_filter( array( (string) mural17_opt( 'mural17_instagram' ), (string) mural17_opt( 'mural17_facebook' ) ) ) );
}

/**
 * Blocos JSON-LD: organização e site em todas as páginas; matéria (NewsArticle)
 * e trilha (BreadcrumbList) nas matérias; trilha nas cidades.
 */
function mural17_seo_jsonld() {
	$home  = home_url( '/' );
	$org   = $home . '#organizacao';
	$graph = array(
		array(
			'@type'        => 'NewsMediaOrganization',
			'@id'          => $org,
			'name'         => 'Mural 17',
			'alternateName' => array( 'Mural17', 'Mural 17 DDD 17', '@mural17br' ),
			'url'          => $home,
			'logo'         => array(
				'@type'  => 'ImageObject',
				'url'    => MURAL17_URI . '/assets/marca/mural17-icone-512.png',
				'width'  => 512,
				'height' => 512,
			),
			'sameAs'       => mural17_seo_perfis(),
			'email'        => function_exists( 'mural17_email_redacao' ) ? mural17_email_redacao() : '',
			'areaServed'   => 'Noroeste paulista (DDD 17), São Paulo, Brasil',
		),
		array(
			'@type'           => 'WebSite',
			'@id'             => $home . '#site',
			'name'            => 'Mural 17',
			'alternateName'   => 'Mural17',
			'url'             => $home,
			'inLanguage'      => 'pt-BR',
			'publisher'       => array( '@id' => $org ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => $home . '?s={search_term_string}',
				'query-input' => 'required name=search_term_string',
			),
		),
	);
	if ( function_exists( 'mural17_link_institucional' ) ) {
		$graph[0]['publishingPrinciples'] = mural17_link_institucional( 'politica-editorial' );
		$graph[0]['correctionsPolicy']    = mural17_link_institucional( 'politica-editorial' );
	}

	$trilha = array( array( 'Início', $home ) );
	if ( is_singular( 'post' ) ) {
		$p      = get_queried_object();
		$cidade = function_exists( 'mural17_cidade' ) ? mural17_cidade( $p->ID ) : null;
		$img    = mural17_seo_imagem();
		$art    = array(
			'@type'            => 'NewsArticle',
			'@id'              => get_permalink( $p ) . '#materia',
			'headline'         => mb_substr( html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ), 0, 110 ),
			'description'      => mural17_seo_descricao(),
			'image'            => array( $img[0] ),
			'datePublished'    => get_post_time( 'c', true, $p ),
			'dateModified'     => get_post_modified_time( 'c', true, $p ),
			'author'           => array( '@type' => 'Organization', 'name' => 'Redação Mural 17', 'url' => function_exists( 'mural17_link_institucional' ) ? mural17_link_institucional( 'expediente' ) : $home ),
			'publisher'        => array( '@id' => $org ),
			'mainEntityOfPage' => get_permalink( $p ),
			'inLanguage'       => 'pt-BR',
			'isAccessibleForFree' => true,
		);
		$cats = get_the_category( $p->ID );
		if ( $cats ) {
			$art['articleSection'] = $cats[0]->name;
		}
		if ( $cidade ) {
			$art['contentLocation'] = array( '@type' => 'Place', 'name' => $cidade->name . ', SP' );
			$trilha[]               = array( $cidade->name, get_term_link( $cidade ) );
		}
		$fonte = (string) get_post_meta( $p->ID, '_pfai_source_url', true );
		if ( $fonte && false === strpos( $fonte, 'news.google.com' ) ) {
			$art['isBasedOn'] = esc_url_raw( $fonte );
		}
		$graph[]  = $art;
		$trilha[] = array( html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ), get_permalink( $p ) );
	} elseif ( is_tax( 'cidade' ) ) {
		$t = get_queried_object();
		if ( $t->parent ) {
			$r = get_term( $t->parent, 'cidade' );
			if ( $r && ! is_wp_error( $r ) ) {
				$trilha[] = array( $r->name, get_term_link( $r ) );
			}
		}
		$trilha[] = array( $t->name, get_term_link( $t ) );
	}
	if ( count( $trilha ) > 1 ) {
		$itens = array();
		foreach ( $trilha as $i => $t ) {
			$itens[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $t[0], 'item' => $t[1] );
		}
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $itens );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* ---- Sitemap do Google Notícias ----------------------------------------- */

add_action(
	'init',
	function () {
		add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?mural17_news_sitemap=1', 'top' );
		if ( (int) get_option( 'mural17_seo_versao' ) < MURAL17_SEO_VERSAO ) {
			flush_rewrite_rules( false );
			update_option( 'mural17_seo_versao', MURAL17_SEO_VERSAO );
		}
	}
);
add_filter(
	'query_vars',
	function ( $v ) {
		$v[] = 'mural17_news_sitemap';
		return $v;
	}
);

/**
 * /news-sitemap.xml: matérias das últimas 48 horas, no formato do Google
 * Notícias (é o que faz a matéria aparecer em "Principais notícias").
 */
add_action(
	'template_redirect',
	function () {
		if ( ! get_query_var( 'mural17_news_sitemap' ) ) {
			return;
		}
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'date_query'     => array( array( 'after' => '48 hours ago' ) ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
		foreach ( $posts as $p ) {
			echo '<url><loc>' . esc_url( get_permalink( $p ) ) . '</loc><news:news><news:publication><news:name>Mural 17</news:name><news:language>pt</news:language></news:publication>';
			echo '<news:publication_date>' . esc_html( get_post_time( 'c', true, $p ) ) . '</news:publication_date>';
			echo '<news:title>' . esc_html( html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ) ) . '</news:title></news:news></url>' . "\n";
		}
		echo '</urlset>';
		exit;
	}
);

// O WordPress poria uma barra no fim (/news-sitemap.xml/) e redirecionaria.
add_filter(
	'redirect_canonical',
	function ( $para ) {
		return get_query_var( 'mural17_news_sitemap' ) ? false : $para;
	}
);

add_filter(
	'robots_txt',
	function ( $txt, $publico ) {
		if ( $publico ) {
			$txt .= "Sitemap: " . home_url( '/news-sitemap.xml' ) . "\n";
		}
		return $txt;
	},
	10,
	2
);
