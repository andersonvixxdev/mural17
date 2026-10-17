<?php
/**
 * Opções do tema em Aparência › Personalizar › Mural 17.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valores padrão das opções.
 *
 * @return array
 */
function mural17_defaults() {
	return array(
		'mural17_cor'        => '#B3261E',
		'mural17_slogan'     => 'Tudo que acontece no 17',
		'mural17_whatsapp'   => '',
		'mural17_email'      => '',
		'mural17_instagram'  => 'https://instagram.com/mural17',
		'mural17_facebook'   => 'https://facebook.com/mural17',
		'mural17_ultimahora' => true,
	);
}

/**
 * Lê uma opção do tema com padrão.
 *
 * @param string $key Chave.
 * @return mixed
 */
function mural17_opt( $key ) {
	$defaults = mural17_defaults();
	return get_theme_mod( $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/**
 * Registra o painel no Personalizador.
 *
 * @param WP_Customize_Manager $wp_customize Gerenciador.
 */
function mural17_customize_register( $wp_customize ) {
	$d = mural17_defaults();

	$wp_customize->add_section(
		'mural17',
		array(
			'title'    => 'Mural 17',
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'mural17_cor',
		array(
			'default'           => $d['mural17_cor'],
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'mural17_cor',
			array(
				'label'   => 'Cor de destaque',
				'section' => 'mural17',
			)
		)
	);

	$textos = array(
		'mural17_slogan'    => array( 'Slogan (abaixo do logo)', 'sanitize_text_field', 'text' ),
		'mural17_whatsapp'  => array( 'WhatsApp da redação (só números, com 55 e DDD)', 'sanitize_text_field', 'text' ),
		'mural17_email'     => array( 'E-mail de contato', 'sanitize_email', 'email' ),
		'mural17_instagram' => array( 'Link do Instagram', 'esc_url_raw', 'url' ),
		'mural17_facebook'  => array( 'Link do Facebook', 'esc_url_raw', 'url' ),
	);
	foreach ( $textos as $key => $cfg ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $d[ $key ],
				'sanitize_callback' => $cfg[1],
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $cfg[0],
				'section' => 'mural17',
				'type'    => $cfg[2],
			)
		);
	}

	$wp_customize->add_setting(
		'mural17_ultimahora',
		array(
			'default'           => $d['mural17_ultimahora'],
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);
	$wp_customize->add_control(
		'mural17_ultimahora',
		array(
			'label'   => 'Mostrar faixa "Última hora"',
			'section' => 'mural17',
			'type'    => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'mural17_customize_register' );

/**
 * CSS da cor de destaque escolhida.
 *
 * @return string
 */
function mural17_accent_css() {
	$cor = sanitize_hex_color( mural17_opt( 'mural17_cor' ) );
	if ( ! $cor ) {
		$cor = '#B3261E';
	}
	return ':root{--m17-accent:' . $cor . ';}';
}
