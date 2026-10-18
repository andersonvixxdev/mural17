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
				'evento_fim'   => array( 'label' => 'Até (se durar mais de um dia)', 'type' => 'date' ),
				'evento_hora'  => array( 'label' => 'Horário', 'type' => 'time' ),
				'evento_categoria' => array(
					'label'   => 'Categoria',
					'type'    => 'select',
					'options' => array( 'Shows', 'Festas e rodeios', 'Teatro e cinema', 'Infantil', 'Feiras', 'Esporte', 'Religioso', 'Outros' ),
				),
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
		'plantao'      => array(
			'titulo' => 'Farmácia e horário do plantão',
			'campos' => array(
				'plantao_endereco' => array( 'label' => 'Endereço (rua, número e bairro)', 'type' => 'text' ),
				'plantao_telefone' => array( 'label' => 'Telefone', 'type' => 'text' ),
				'plantao_whatsapp' => array( 'label' => 'WhatsApp (opcional, com DDD)', 'type' => 'text' ),
				'plantao_inicio'   => array( 'label' => 'Começa', 'type' => 'datetime-local' ),
				'plantao_fim'      => array( 'label' => 'Termina', 'type' => 'datetime-local' ),
				'plantao_fonte'    => array( 'label' => 'Fonte da escala (ex.: Prefeitura de Mirassol)', 'type' => 'text' ),
			),
		),
		'interrupcao'  => array(
			'titulo' => 'Dados da falta de água ou luz',
			'campos' => array(
				'int_tipo'        => array(
					'label'   => 'O que falta',
					'type'    => 'select',
					'options' => array(
						'agua'    => 'Água',
						'energia' => 'Energia',
					),
				),
				'int_bairros'     => array( 'label' => 'Bairros ou ruas atingidos', 'type' => 'textarea' ),
				'int_motivo'      => array( 'label' => 'Motivo (ex.: rompimento de adutora, troca de transformador)', 'type' => 'text' ),
				'int_inicio'      => array( 'label' => 'Começa ou começou', 'type' => 'datetime-local' ),
				'int_previsao'    => array( 'label' => 'Previsão de volta (vazio = sem previsão)', 'type' => 'datetime-local' ),
				'int_obs'         => array( 'label' => 'Observação (ex.: caminhão-pipa na escola X até 20h)', 'type' => 'text' ),
				'int_fonte'       => array( 'label' => 'Quem informou (serviço de água ou concessionária)', 'type' => 'text' ),
				'int_normalizado' => array( 'label' => 'Normalizado em (preencha quando voltar)', 'type' => 'datetime-local' ),
			),
		),
		'guia'         => array(
			'titulo' => 'Dados do item do guia',
			'campos' => array(
				'guia_telefone' => array( 'label' => 'Telefone', 'type' => 'text' ),
				'guia_endereco' => array( 'label' => 'Endereço ou local', 'type' => 'text' ),
				'guia_horario'  => array( 'label' => 'Horário (feiras: dia e horário; coleta: lixo comum)', 'type' => 'text' ),
				'guia_obs'      => array( 'label' => 'Observação (coleta: reciclável)', 'type' => 'text' ),
				'guia_link'     => array( 'label' => 'Link (horários de ônibus, lista de UBS...)', 'type' => 'url' ),
				'guia_ordem'    => array( 'label' => 'Ordem na seção (menor aparece antes)', 'type' => 'number' ),
			),
		),
		'falecimento'  => array(
			'titulo' => 'Velório e sepultamento',
			'campos' => array(
				'fal_idade'          => array( 'label' => 'Idade', 'type' => 'number' ),
				'fal_velorio_inicio' => array( 'label' => 'Velório: começa', 'type' => 'datetime-local' ),
				'fal_velorio_fim'    => array( 'label' => 'Velório: até', 'type' => 'datetime-local' ),
				'fal_velorio_local'  => array( 'label' => 'Local do velório', 'type' => 'text' ),
				'fal_sepult_tipo'    => array(
					'label'   => 'Despedida',
					'type'    => 'select',
					'options' => array( 'Sepultamento', 'Cremação' ),
				),
				'fal_sepult_quando'  => array( 'label' => 'Sepultamento ou cremação: quando', 'type' => 'datetime-local' ),
				'fal_sepult_local'   => array( 'label' => 'Cemitério ou crematório', 'type' => 'text' ),
				'fal_missa_quando'   => array( 'label' => 'Missa ou culto de 7º dia: quando', 'type' => 'datetime-local' ),
				'fal_missa_local'    => array( 'label' => 'Missa ou culto de 7º dia: onde', 'type' => 'text' ),
				'fal_funeraria'      => array( 'label' => 'Informado por (funerária)', 'type' => 'text' ),
				'fal_autorizado'     => array( 'label' => 'A família autorizou a publicação', 'type' => 'checkbox' ),
			),
		),
		'concurso'     => array(
			'titulo' => 'Dados do concurso',
			'campos' => array(
				'conc_orgao'          => array( 'label' => 'Órgão (ex.: Prefeitura de Mirassol)', 'type' => 'text' ),
				'conc_tipo'           => array(
					'label'   => 'Tipo',
					'type'    => 'select',
					'options' => array(
						'concurso' => 'Concurso',
						'processo' => 'Processo seletivo',
						'estagio'  => 'Estágio',
					),
				),
				'conc_vagas'          => array( 'label' => 'Vagas (ex.: 42 + cadastro reserva)', 'type' => 'text' ),
				'conc_escolaridade'   => array(
					'label'   => 'Escolaridade',
					'type'    => 'multi',
					'options' => mural17_escolaridades(),
				),
				'conc_salario'        => array( 'label' => 'Maior salário ou bolsa (só números, ex.: 4870)', 'type' => 'number' ),
				'conc_taxa'           => array( 'label' => 'Taxa de inscrição (ex.: R$ 45 a R$ 80, Sem taxa)', 'type' => 'text' ),
				'conc_inscricoes_ate' => array( 'label' => 'Inscrições até', 'type' => 'date' ),
				'conc_prova'          => array( 'label' => 'Data da prova (opcional)', 'type' => 'date' ),
				'conc_edital'         => array( 'label' => 'Link do edital', 'type' => 'url' ),
			),
		),
		'fala'         => array(
			'titulo' => 'Reclamação e resposta',
			'campos' => array(
				'fala_tipo'        => array(
					'label'   => 'Tipo',
					'type'    => 'select',
					'options' => mural17_tipos_fala(),
				),
				'fala_onde'        => array( 'label' => 'Onde (rua, bairro, ponto de referência)', 'type' => 'text' ),
				'fala_situacao'    => array(
					'label'   => 'Situação',
					'type'    => 'select',
					'options' => array(
						'aberta'     => 'Aberta',
						'respondida' => 'Respondida',
						'resolvida'  => 'Resolvida',
					),
				),
				'fala_avisado_em'  => array( 'label' => 'Prefeitura avisada em', 'type' => 'date' ),
				'fala_resposta'    => array( 'label' => 'Resposta do órgão', 'type' => 'textarea' ),
				'fala_resposta_de' => array( 'label' => 'Quem respondeu (ex.: Prefeitura de Mirassol)', 'type' => 'text' ),
				'fala_resposta_em' => array( 'label' => 'Respondido em', 'type' => 'datetime-local' ),
				'fala_nome'        => array( 'label' => 'Nome de quem enviou (não aparece no site)', 'type' => 'text' ),
				'fala_contato'     => array( 'label' => 'WhatsApp de quem enviou (não aparece no site)', 'type' => 'text' ),
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
		// Os tipos dos serviços não vão para a API (a Fala 17 guarda contatos).
		if ( ! in_array( $post_type, array( 'post', 'evento', 'vaga', 'classificado' ), true ) ) {
			continue;
		}
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
		if ( 'checkbox' === $campo['type'] ) {
			echo '<p style="margin:0"><label><input type="checkbox" name="' . esc_attr( $id ) . '" value="1"' . checked( $valor, '1', false ) . '> ' . esc_html( $campo['label'] ) . '</label></p>';
			continue;
		}
		echo '<p style="margin:0"><label for="' . esc_attr( $id ) . '" style="display:block;font-weight:600;margin-bottom:4px">' . esc_html( $campo['label'] ) . '</label>';
		if ( 'select' === $campo['type'] ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" style="width:100%">';
			echo '<option value="">—</option>';
			foreach ( $campo['options'] as $k => $opcao ) {
				$v = is_int( $k ) ? $opcao : $k;
				echo '<option value="' . esc_attr( $v ) . '"' . selected( $valor, $v, false ) . '>' . esc_html( $opcao ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'multi' === $campo['type'] ) {
			$marcados = array_filter( explode( ',', (string) $valor ) );
			foreach ( $campo['options'] as $k => $opcao ) {
				echo '<label style="margin-right:12px"><input type="checkbox" name="' . esc_attr( $id ) . '[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $marcados, true ), true, false ) . '> ' . esc_html( $opcao ) . '</label>';
			}
		} elseif ( 'textarea' === $campo['type'] ) {
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" rows="3" style="width:100%">' . esc_textarea( $valor ) . '</textarea>';
		} else {
			if ( 'datetime-local' === $campo['type'] ) {
				$valor = str_replace( ' ', 'T', (string) $valor );
			}
			echo '<input type="' . esc_attr( $campo['type'] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $valor ) . '" style="width:100%"' . ( 'number' === $campo['type'] ? ' step="any" min="0"' : '' ) . '>';
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Valor limpo de um campo vindo de formulário.
 *
 * @param array $campo Definição do campo.
 * @param mixed $raw   Valor enviado (já sem barras).
 * @return string
 */
function mural17_limpa_campo( $campo, $raw ) {
	switch ( $campo['type'] ) {
		case 'url':
			return esc_url_raw( (string) $raw );
		case 'textarea':
			return sanitize_textarea_field( (string) $raw );
		case 'checkbox':
			return $raw ? '1' : '';
		case 'number':
			$n = str_replace( ',', '.', preg_replace( '/[^\d,.]/', '', (string) $raw ) );
			// "4.870" digitado como milhar vira 4870.
			if ( preg_match( '/^\d{1,3}(\.\d{3})+$/', $n ) ) {
				$n = str_replace( '.', '', $n );
			}
			return is_numeric( $n ) ? (string) ( 0 + $n ) : '';
		case 'datetime-local':
			$d = mural17_data( sanitize_text_field( (string) $raw ) );
			return $d ? $d->format( 'Y-m-d H:i' ) : '';
		case 'date':
			$d = mural17_data( sanitize_text_field( (string) $raw ) );
			return $d ? $d->format( 'Y-m-d' ) : '';
		case 'multi':
			$ok = array_intersect( array_map( 'sanitize_key', (array) $raw ), array_keys( $campo['options'] ) );
			return implode( ',', $ok );
		case 'select':
			$v      = sanitize_text_field( (string) $raw );
			$validos = array();
			foreach ( $campo['options'] as $k => $opcao ) {
				$validos[] = is_int( $k ) ? $opcao : $k;
			}
			return in_array( $v, $validos, true ) ? $v : '';
		default:
			return sanitize_text_field( (string) $raw );
	}
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
		if ( ! isset( $_POST[ $name ] ) && ! in_array( $campo['type'], array( 'checkbox', 'multi' ), true ) ) {
			continue;
		}
		$raw   = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizado abaixo.
		$valor = mural17_limpa_campo( $campo, $raw );

		if ( '' === $valor ) {
			delete_post_meta( $post_id, '_mural17_' . $key );
		} else {
			update_post_meta( $post_id, '_mural17_' . $key, $valor );
		}
	}
}
add_action( 'save_post', 'mural17_save_meta' );
