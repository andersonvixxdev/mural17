<?php
/**
 * Campos extras de eventos, vagas, classificados e fonte das notícias.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definição dos campos por tipo de conteúdo.
 *
 * @return array
 */
function mural17_fields() {
	return array(
		'evento'       => array(
			'titulo' => 'Dados do evento',
			'campos' => array(
				'evento_data'  => array( 'label' => 'Data', 'type' => 'date' ),
				'evento_hora'  => array( 'label' => 'Horário', 'type' => 'time' ),
				'evento_local' => array( 'label' => 'Local (ex.: Praça da Matriz)', 'type' => 'text' ),
				'evento_valor' => array( 'label' => 'Entrada (ex.: Gratuita, R$ 20)', 'type' => 'text' ),
				'evento_link'  => array( 'label' => 'Link de ingressos ou informações', 'type' => 'url' ),
			),
		),
		'vaga'         => array(
			'titulo' => 'Dados da vaga',
			'campos' => array(
				'vaga_empresa'  => array( 'label' => 'Empresa ou órgão', 'type' => 'text' ),
				'vaga_tipo'     => array(
					'label'   => 'Tipo',
					'type'    => 'select',
					'options' => array( 'CLT', 'PJ', 'Temporário', 'Estágio', 'Jovem aprendiz', 'Freelancer', 'Concurso' ),
				),
				'vaga_salario'  => array( 'label' => 'Salário (opcional)', 'type' => 'text' ),
				'vaga_contato'  => array( 'label' => 'Como se candidatar (e-mail, WhatsApp ou link)', 'type' => 'text' ),
				'vaga_validade' => array( 'label' => 'Inscrições até', 'type' => 'date' ),
			),
		),
		'classificado' => array(
			'titulo' => 'Dados do anúncio',
			'campos' => array(
				'anuncio_preco'    => array( 'label' => 'Preço (ex.: R$ 1.500 ou A combinar)', 'type' => 'text' ),
				'anuncio_whatsapp' => array( 'label' => 'WhatsApp do anunciante (só números, com DDD)', 'type' => 'text' ),
				'anuncio_contato'  => array( 'label' => 'Outro contato (opcional)', 'type' => 'text' ),
			),
		),
		'post'         => array(
			'titulo' => 'Fonte da notícia (para notícias de outros sites)',
			'campos' => array(
				'fonte_nome' => array( 'label' => 'Nome da fonte (ex.: g1 Rio Preto)', 'type' => 'text' ),
				'fonte_url'  => array( 'label' => 'Link da matéria original', 'type' => 'url' ),
			),
		),
	);
}

/**
 * Registra os metadados (também disponíveis na API REST).
 */
function mural17_register_meta() {
	foreach ( mural17_fields() as $post_type => $grupo ) {
		foreach ( $grupo['campos'] as $key => $campo ) {
			register_post_meta(
				$post_type,
				'_mural17_' . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'url' === $campo['type'] ? 'esc_url_raw' : 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'mural17_register_meta' );

/**
 * Adiciona as caixas de campos no editor.
 */
function mural17_add_meta_boxes() {
	foreach ( mural17_fields() as $post_type => $grupo ) {
		add_meta_box(
			'mural17_' . $post_type,
			$grupo['titulo'],
			'mural17_render_meta_box',
			$post_type,
			'post' === $post_type ? 'side' : 'normal',
			'high',
			array( 'campos' => $grupo['campos'] )
		);
	}
}
add_action( 'add_meta_boxes', 'mural17_add_meta_boxes' );

/**
 * Desenha a caixa de campos.
 *
 * @param WP_Post $post Post atual.
 * @param array   $box  Dados da caixa.
 */
function mural17_render_meta_box( $post, $box ) {
	wp_nonce_field( 'mural17_salvar_campos', 'mural17_nonce' );
	echo '<div class="mural17-campos" style="display:grid;gap:12px">';
	foreach ( $box['args']['campos'] as $key => $campo ) {
		$id    = 'mural17_' . $key;
		$valor = get_post_meta( $post->ID, '_mural17_' . $key, true );
		echo '<p style="margin:0"><label for="' . esc_attr( $id ) . '" style="display:block;font-weight:600;margin-bottom:4px">' . esc_html( $campo['label'] ) . '</label>';
		if ( 'select' === $campo['type'] ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" style="width:100%">';
			echo '<option value="">—</option>';
			foreach ( $campo['options'] as $opcao ) {
				echo '<option value="' . esc_attr( $opcao ) . '"' . selected( $valor, $opcao, false ) . '>' . esc_html( $opcao ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input type="' . esc_attr( $campo['type'] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $valor ) . '" style="width:100%">';
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Salva os campos.
 *
 * @param int $post_id ID do post.
 */
function mural17_save_meta( $post_id ) {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_salvar_campos' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = mural17_fields();
	$type   = get_post_type( $post_id );
	if ( ! isset( $fields[ $type ] ) ) {
		return;
	}

	foreach ( $fields[ $type ]['campos'] as $key => $campo ) {
		$name = 'mural17_' . $key;
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizado abaixo.
		$valor = 'url' === $campo['type'] ? esc_url_raw( $raw ) : sanitize_text_field( $raw );

		if ( '' === $valor ) {
			delete_post_meta( $post_id, '_mural17_' . $key );
		} else {
			update_post_meta( $post_id, '_mural17_' . $key, $valor );
		}
	}
}
add_action( 'save_post', 'mural17_save_meta' );
