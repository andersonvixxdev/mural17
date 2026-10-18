<?php
/**
 * Garantias para as publicações nas redes (módulo Redes Sociais do Portal PRO).
 *
 * O plugin troca {hashtags} pelas hashtags fixas e as da editoria, mas só se
 * o modelo tiver {hashtags} e o campo estiver preenchido. O modelo padrão do
 * Facebook nem tem {hashtags}, e modelo salvo em branco vira legenda vazia no
 * Instagram. Aqui corrigimos isso na leitura da opção, sem mexer no plugin.
 *
 * @package Mural17
 */

/**
 * Hashtags fixas do portal, usadas quando o campo do plugin está vazio.
 *
 * @return string
 */
function mural17_hashtags_padrao() {
	return apply_filters( 'mural17_hashtags_padrao', '#mural17 #ddd17 #noroestepaulista #interiorsp' );
}

/**
 * Completa a configuração do plugin: hashtags, e {hashtags} nos dois modelos.
 *
 * @param mixed $s Opção wpig_settings.
 * @return mixed
 */
function mural17_completa_redes( $s ) {
	if ( ! is_array( $s ) ) {
		return $s;
	}
	if ( '' === trim( (string) ( $s['hashtags'] ?? '' ) ) ) {
		$s['hashtags'] = mural17_hashtags_padrao();
	}
	$modelos = array(
		'caption_template' => "{titulo}\n\n{gravata}\n\n📲 Matéria completa no link da bio · mural17.com.br\n🔔 Siga @mural17br e acompanhe a sua cidade",
		'fb_template'      => "{titulo}\n\n{resumo}\n\n👉 Leia a matéria completa:\n{link}",
	);
	foreach ( $modelos as $campo => $padrao ) {
		$m = trim( (string) ( $s[ $campo ] ?? '' ) );
		if ( '' === $m ) {
			$m = $padrao;
		}
		if ( false === strpos( $m, '{hashtags}' ) ) {
			$m .= "\n\n{hashtags}";
		}
		$s[ $campo ] = $m;
	}
	return $s;
}
add_filter( 'option_wpig_settings', 'mural17_completa_redes' );

/**
 * Prévia do que o plugin publicaria agora para uma matéria.
 *
 * @param WP_Post $post Matéria.
 * @return array|null array( modo, instagram, facebook ) ou null sem o plugin.
 */
function mural17_previa_redes( $post ) {
	if ( ! function_exists( 'wpig_get_settings' ) || ! function_exists( 'wpig_build_caption' ) ) {
		return null;
	}
	$s = wpig_get_settings();
	return array(
		'modo'      => (string) ( $s['publish_mode'] ?? 'feed' ),
		'hashtags'  => function_exists( 'wpig_hashtags_do_post' ) ? wpig_hashtags_do_post( $post, $s ) : '',
		'instagram' => wpig_build_caption( $post, $s ),
		'facebook'  => function_exists( 'wpig_build_fb_message' ) ? wpig_build_fb_message( $post, $s ) : '',
		'ig_saiu'   => (string) get_post_meta( $post->ID, '_wpig_posted', true ),
		'fb_saiu'   => (string) get_post_meta( $post->ID, '_wpig_fb_posted', true ),
	);
}
