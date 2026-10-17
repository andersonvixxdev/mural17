<?php
/**
 * Funções auxiliares usadas pelos templates.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valor de um campo extra do tema.
 *
 * @param string   $key     Chave sem prefixo (ex.: evento_data).
 * @param int|null $post_id ID do post.
 * @return string
 */
function mural17_meta( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return (string) get_post_meta( $post_id, '_mural17_' . $key, true );
}

/**
 * Primeira cidade do post.
 *
 * @param int|null $post_id ID do post.
 * @return WP_Term|null
 */
function mural17_cidade( $post_id = null ) {
	$terms = get_the_terms( $post_id ? $post_id : get_the_ID(), 'cidade' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}
	return $terms[0];
}

/**
 * Chapéu da matéria: cidade, ou a primeira categoria se não houver cidade.
 *
 * @param string $class Classe CSS extra.
 */
function mural17_chapeu( $class = '' ) {
	$term = mural17_cidade();
	if ( ! $term && 'post' === get_post_type() ) {
		// Polícia e Trânsito já aparecem no selo; "Sem categoria" não informa nada.
		$ignorar = array( 'policia', 'transito', 'uncategorized', 'sem-categoria' );
		foreach ( get_the_category() as $cat ) {
			if ( ! in_array( $cat->slug, $ignorar, true ) ) {
				$term = $cat;
				break;
			}
		}
	}
	if ( ! $term ) {
		return;
	}
	printf(
		'<a class="chapeu %1$s" href="%2$s">%3$s</a>',
		esc_attr( $class ),
		esc_url( get_term_link( $term ) ),
		esc_html( $term->name )
	);
}

/**
 * Selo de Polícia/Trânsito quando o post está nessas categorias.
 */
function mural17_selo() {
	if ( has_category( 'transito' ) ) {
		echo '<span class="selo selo--transito">Trânsito</span>';
	} elseif ( has_category( 'policia' ) ) {
		echo '<span class="selo selo--policia">Polícia</span>';
	}
}

/**
 * "Há 2 horas" para posts recentes, data completa para os antigos.
 *
 * @return string
 */
function mural17_quando() {
	$ts = get_post_time( 'U', true );
	if ( time() - $ts < DAY_IN_SECONDS ) {
		/* translators: %s: intervalo de tempo. */
		return sprintf( __( 'Há %s', 'mural17' ), human_time_diff( $ts, time() ) );
	}
	return get_the_date();
}

/**
 * Imagem destacada ou um bloco neutro do mesmo tamanho.
 *
 * @param string $size  Tamanho registrado.
 * @param string $class Classe do contêiner.
 */
function mural17_imagem( $size = 'mural17-card', $class = 'midia' ) {
	echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">';
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( $size, array( 'loading' => 'lazy', 'alt' => '' ) );
	} else {
		echo '<span class="midia__vazia"></span>';
	}
	echo '</a>';
}

/**
 * Fonte original de notícias agregadas. Lê o campo do tema e os campos
 * gravados pelos plugins Portal de Notícias PRO, WP RSS Aggregator e Feedzy.
 *
 * @param int|null $post_id ID do post.
 * @return array{nome:string,url:string}|null
 */
function mural17_fonte( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$url     = mural17_meta( 'fonte_url', $post_id );
	$nome    = mural17_meta( 'fonte_nome', $post_id );

	if ( ! $url ) {
		$url = (string) get_post_meta( $post_id, '_pfai_source_url', true );
		if ( $url && ! $nome && class_exists( 'PFAI_Credito_Fonte' ) ) {
			$nome = PFAI_Credito_Fonte::nome_da_fonte( $post_id );
		}
	}
	if ( ! $url ) {
		$url = (string) get_post_meta( $post_id, 'wprss_item_permalink', true );
	}
	if ( ! $url ) {
		$url = (string) get_post_meta( $post_id, 'feedzy_item_url', true );
	}
	if ( ! $url ) {
		return null;
	}
	if ( ! $nome ) {
		$feed_id = get_post_meta( $post_id, 'wprss_feed_id', true );
		$nome    = $feed_id ? get_the_title( (int) $feed_id ) : wp_parse_url( $url, PHP_URL_HOST );
	}
	return array(
		'nome' => (string) $nome,
		'url'  => $url,
	);
}

/**
 * Link de WhatsApp a partir de um número.
 *
 * @param string $numero Número com ou sem formatação.
 * @param string $texto  Mensagem inicial.
 * @return string
 */
function mural17_whatsapp_link( $numero, $texto = '' ) {
	$numero = preg_replace( '/\D+/', '', (string) $numero );
	if ( ! $numero ) {
		return '';
	}
	if ( strlen( $numero ) <= 11 ) {
		$numero = '55' . $numero;
	}
	$url = 'https://wa.me/' . $numero;
	if ( $texto ) {
		$url .= '?text=' . rawurlencode( $texto );
	}
	return $url;
}

/**
 * Dia e mês abreviado de um evento.
 *
 * @param int|null $post_id ID do evento.
 * @return array{dia:string,mes:string}
 */
function mural17_evento_dia( $post_id = null ) {
	$data = mural17_meta( 'evento_data', $post_id );
	$ts   = $data ? strtotime( $data ) : false;
	if ( ! $ts ) {
		return array(
			'dia' => '--',
			'mes' => '',
		);
	}
	$meses = array( 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez' );
	return array(
		'dia' => gmdate( 'd', $ts ),
		'mes' => $meses[ (int) gmdate( 'n', $ts ) - 1 ],
	);
}

/**
 * Data por extenso de um evento (ex.: sábado, 24 de outubro · 20h).
 *
 * @param int|null $post_id ID do evento.
 * @return string
 */
function mural17_evento_quando( $post_id = null ) {
	$data = mural17_meta( 'evento_data', $post_id );
	$hora = mural17_meta( 'evento_hora', $post_id );
	if ( ! $data ) {
		return '';
	}
	$texto = date_i18n( 'l, j \d\e F', strtotime( $data ) );
	if ( $hora ) {
		$texto .= ' · ' . str_replace( ':00', 'h', $hora );
	}
	return $texto;
}

/**
 * Conta visualizações das matérias para o bloco "Mais lidas".
 * Ignora quem pode editar posts e robôs comuns.
 */
function mural17_conta_visita() {
	if ( ! is_singular( 'post' ) || current_user_can( 'edit_posts' ) ) {
		return;
	}
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp/i', $ua ) ) {
		return;
	}
	$id = get_queried_object_id();
	update_post_meta( $id, '_mural17_views', (int) get_post_meta( $id, '_mural17_views', true ) + 1 );
}
add_action( 'template_redirect', 'mural17_conta_visita' );

/**
 * Consulta das mais lidas dos últimos dias.
 *
 * @param int $qtd  Quantidade.
 * @param int $dias Janela em dias.
 * @return WP_Query
 */
function mural17_mais_lidas( $qtd = 5, $dias = 7 ) {
	return new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => $qtd,
			'meta_key'            => '_mural17_views', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'date_query'          => array( array( 'after' => $dias . ' days ago' ) ),
		)
	);
}

/**
 * Mostra uma área de anúncio. Vazia, só aparece um aviso para administradores.
 *
 * @param string $area  ID da área de widget.
 * @param string $class Classe CSS (tamanho).
 */
function mural17_anuncio( $area, $class = 'anuncio--728' ) {
	if ( is_active_sidebar( $area ) ) {
		echo '<div class="anuncio ' . esc_attr( $class ) . '">';
		dynamic_sidebar( $area );
		echo '</div>';
	} elseif ( current_user_can( 'edit_theme_options' ) ) {
		$label = 'anuncio--300' === $class ? '300 × 250' : '728 × 90';
		echo '<div class="anuncio anuncio--vazio ' . esc_attr( $class ) . '">Publicidade · ' . esc_html( $label ) . ' <small>(adicione em Aparência › Widgets)</small></div>';
	}
}

/**
 * Cabeçalho de seção com título e link "ver todas".
 *
 * @param string $titulo Título.
 * @param string $link   URL.
 * @param string $acao   Texto do link.
 * @param string $tag    Tag do título.
 */
function mural17_titulo_secao( $titulo, $link = '', $acao = 'Ver todas', $tag = 'h2' ) {
	echo '<div class="secao__topo">';
	printf( '<%1$s class="secao__titulo">%2$s</%1$s>', tag_escape( $tag ), esc_html( $titulo ) );
	if ( $link ) {
		printf( '<a class="secao__link" href="%1$s">%2$s</a>', esc_url( $link ), esc_html( $acao ) );
	}
	echo '</div>';
}

/**
 * Link do arquivo de uma categoria pelo slug.
 *
 * @param string $slug Slug.
 * @return string
 */
function mural17_cat_link( $slug ) {
	$cat = get_category_by_slug( $slug );
	return $cat ? get_category_link( $cat ) : '';
}

/**
 * Menu de reserva quando nenhum menu foi definido: cidades + seções.
 */
function mural17_menu_padrao() {
	echo '<ul class="nav__lista">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Início</a></li>';
	$cidades = get_terms(
		array(
			'taxonomy'   => 'cidade',
			'hide_empty' => false,
			'parent'     => 0,
		)
	);
	if ( ! is_wp_error( $cidades ) ) {
		foreach ( $cidades as $c ) {
			echo '<li><a href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a></li>';
		}
	}
	foreach ( array( 'policia' => 'Polícia', 'transito' => 'Trânsito' ) as $slug => $nome ) {
		$link = mural17_cat_link( $slug );
		if ( $link ) {
			echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $nome ) . '</a></li>';
		}
	}
	echo '<li><a href="' . esc_url( get_post_type_archive_link( 'vaga' ) ) . '">Empregos</a></li>';
	echo '<li><a href="' . esc_url( get_post_type_archive_link( 'classificado' ) ) . '">Classificados</a></li>';
	echo '<li><a href="' . esc_url( get_post_type_archive_link( 'evento' ) ) . '">Agenda</a></li>';
	echo '</ul>';
}
