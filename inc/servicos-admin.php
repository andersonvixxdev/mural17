<?php
/**
 * Painel do WordPress para os serviços: menu Serviços com a situação das
 * leituras automáticas (INMET e ANP), importação da escala de plantão,
 * alerta da redação e estoque de sangue; colunas nas listas.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Página Serviços.
 */
function mural17_pagina_servicos_admin() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$inmet    = get_option( 'mural17_inmet' );
	$inmet_e  = get_option( 'mural17_inmet_erro' );
	$comb     = mural17_combustivel();
	$comb_e   = get_option( 'mural17_combustivel_erro' );
	$manual   = wp_parse_args(
		(array) get_option( 'mural17_alerta_manual' ),
		array(
			'ativo'      => 0,
			'evento'     => '',
			'nivel'      => 'laranja',
			'texto'      => '',
			'instrucoes' => '',
			'inicio'     => '',
			'fim'        => '',
			'fonte'      => '',
			'cidades'    => array(),
		)
	);
	$sangue   = wp_parse_args(
		(array) get_option( 'mural17_sangue' ),
		array(
			'tipos' => array(),
			'local' => 'Hemocentro de São José do Rio Preto',
			'link'  => '',
			'em'    => '',
		)
	);
	$msg      = isset( $_GET['feito'] ) ? sanitize_key( wp_unslash( $_GET['feito'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$msgs     = array(
		'escala'    => 'Escala salva.',
		'escala-0'  => 'Nenhuma linha da escala foi entendida.',
		'alerta'    => 'Alerta salvo.',
		'sangue'    => 'Estoque de sangue salvo.',
		'anp'       => 'Preços da ANP atualizados.',
		'anp-erro'  => 'Não foi possível ler a ANP agora. Veja o erro abaixo.',
		'inmet'     => 'Avisos do INMET lidos.',
		'inmet-erro' => 'Não foi possível ler o INMET agora. Veja o erro abaixo.',
		'gn'        => 'Feeds do Google News cadastrados.',
		'gn-erro'   => 'Não foi possível cadastrar os feeds do Google News. Veja o erro abaixo.',
	);
	$gn       = get_option( 'mural17_google_news' );
	$form     = function ( $acao ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( $acao ) . '">';
		wp_nonce_field( $acao, 'mural17_nonce' );
	};
	?>
	<div class="wrap mural17-servicos-admin">
		<h1>Serviços</h1>
		<?php if ( isset( $msgs[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo false !== strpos( $msg, 'erro' ) || '-0' === substr( $msg, -2 ) ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $msgs[ $msg ] ); ?></p></div>
		<?php endif; ?>
		<p>As páginas públicas ficam em <a href="<?php echo esc_url( home_url( '/servicos/' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/servicos/' ) ); ?></a>. Serviços de água, concessionárias e funerárias se cadastram em <a href="<?php echo esc_url( mural17_link_oficial( 'cadastro' ) ); ?>">Cadastro de órgão</a> e publicam pelo Painel do órgão.</p>

		<div class="m17-cards">
			<div class="m17-card">
				<h2>Avisos do INMET</h2>
				<p>Lidos sozinhos a cada 30 minutos. Os que pegam cidades do 17 aparecem na faixa do topo; os de perigo e grande perigo vão por e-mail para os inscritos.</p>
				<p><b>Última leitura:</b> <?php echo is_array( $inmet ) ? esc_html( wp_date( 'd/m H:i', $inmet['em'] ) . ' · ' . count( $inmet['avisos'] ) . ' aviso(s) no 17' ) : 'ainda não leu'; ?></p>
				<?php if ( $inmet_e ) : ?>
					<p class="m17-erro"><b>Erro:</b> <?php echo esc_html( $inmet_e ); ?></p>
				<?php endif; ?>
				<?php $form( 'mural17_admin_inmet' ); ?>
				<?php submit_button( 'Ler agora', 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<div class="m17-card">
				<h2>Preço dos combustíveis (ANP)</h2>
				<p>A planilha semanal sai às sextas. O site confere duas vezes por dia e importa quando muda.</p>
				<p><b>Semana guardada:</b> <?php echo $comb ? esc_html( wp_date( 'd/m', mural17_data( $comb['semana'][0] )->getTimestamp() ) . ' a ' . wp_date( 'd/m/Y', mural17_data( $comb['semana'][1] )->getTimestamp() ) . ' · ' . count( $comb['cidades'] ) . ' cidades do 17' ) : 'nenhuma ainda'; ?></p>
				<?php if ( is_array( $comb_e ) ) : ?>
					<p class="m17-erro"><b>Erro em <?php echo esc_html( wp_date( 'd/m H:i', $comb_e[1] ) ); ?>:</b> <?php echo esc_html( $comb_e[0] ); ?></p>
				<?php endif; ?>
				<?php $form( 'mural17_admin_anp' ); ?>
				<?php submit_button( 'Atualizar agora', 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<div class="m17-card" id="google-news">
				<h2>Google News das 117 cidades</h2>
				<p>Cadastra no Portal de Notícias PRO um feed de busca do Google News para cada cidade do 17, já com a cidade no nome. Os que já existem ficam como estão.</p>
				<?php if ( is_array( $gn ) ) : ?>
					<p><b>Última vez:</b> <?php echo esc_html( $gn['criados'] . ' cadastrado(s), ' . $gn['existiam'] . ' já existia(m)' ); ?></p>
				<?php elseif ( $gn ) : ?>
					<p class="m17-erro"><b>Erro:</b> <?php echo esc_html( $gn ); ?></p>
				<?php endif; ?>
				<?php $form( 'mural17_admin_google_news' ); ?>
					<p>
						<label>Frequência <select name="frequency">
							<option value="every_6h">A cada 6 horas</option>
							<option value="every_12h" selected>A cada 12 horas (2x por dia)</option>
							<option value="daily">Uma vez por dia</option>
						</select></label>
					</p>
					<p>
						<label><input type="checkbox" name="ai_rewrite" value="1" checked> Reescrever com IA</label><br>
						<label><input type="checkbox" name="ai_image" value="1" checked> Gerar imagem quando faltar</label>
					</p>
					<?php submit_button( 'Cadastrar os feeds', 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<?php endif; ?>
		</div>

		<h2 id="escala">Importar escala de farmácia de plantão</h2>
		<p>Para cidades em que a prefeitura não manda a escala pelo painel. Cada linha vira um plantão de 24 horas.</p>
		<?php $form( 'mural17_admin_escala' ); ?>
			<table class="form-table">
				<tr><th><label for="adm-escala-cidade">Cidade</label></th><td><select id="adm-escala-cidade" name="cidade" required><?php mural17_opcoes_cidades(); ?></select></td></tr>
			</table>
			<div class="m17-escala"><?php mural17_campos_escala(); ?></div>
			<?php submit_button( 'Salvar escala' ); ?>
		</form>

		<h2 id="alerta">Alerta da redação</h2>
		<p>Para avisos que não vêm do INMET: Defesa Civil, falta de energia em várias cidades, interdição de rodovia. Aparece na faixa do topo enquanto estiver ligado e dentro do horário.</p>
		<?php $form( 'mural17_admin_alerta' ); ?>
			<table class="form-table">
				<tr><th>Ligado</th><td><label><input type="checkbox" name="ativo" value="1" <?php checked( $manual['ativo'] ); ?>> Mostrar no site</label></td></tr>
				<tr><th><label for="al-evento">Título curto</label></th><td><input id="al-evento" name="evento" class="regular-text" value="<?php echo esc_attr( $manual['evento'] ); ?>" placeholder="Ex.: Risco de alagamento"></td></tr>
				<tr><th><label for="al-nivel">Nível</label></th><td><select id="al-nivel" name="nivel">
					<?php foreach ( mural17_niveis_alerta() as $k => $n ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $manual['nivel'], $k ); ?>><?php echo esc_html( $n['rotulo'] . ' (' . $k . ')' ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
				<tr><th><label for="al-texto">O que está acontecendo</label></th><td><textarea id="al-texto" name="texto" rows="3" class="large-text"><?php echo esc_textarea( $manual['texto'] ); ?></textarea></td></tr>
				<tr><th><label for="al-instr">O que fazer (uma dica por linha)</label></th><td><textarea id="al-instr" name="instrucoes" rows="4" class="large-text"><?php echo esc_textarea( $manual['instrucoes'] ); ?></textarea></td></tr>
				<tr><th><label for="al-ini">Começa</label></th><td><input id="al-ini" type="datetime-local" name="inicio" value="<?php echo esc_attr( str_replace( ' ', 'T', $manual['inicio'] ) ); ?>"> até <input type="datetime-local" name="fim" value="<?php echo esc_attr( str_replace( ' ', 'T', $manual['fim'] ) ); ?>" aria-label="Termina"></td></tr>
				<tr><th><label for="al-fonte">Fonte</label></th><td><input id="al-fonte" name="fonte" class="regular-text" value="<?php echo esc_attr( $manual['fonte'] ); ?>" placeholder="Ex.: Defesa Civil de Rio Preto"></td></tr>
				<tr><th><label for="al-cidades">Cidades</label></th><td>
					<select id="al-cidades" name="cidades[]" multiple size="8" style="min-width:320px">
						<?php foreach ( mural17_lista_cidades() as $grupo ) : ?>
							<optgroup label="<?php echo esc_attr( $grupo['regiao']->name ); ?>">
								<?php foreach ( $grupo['cidades'] as $c ) : ?>
									<option value="<?php echo (int) $c->term_id; ?>" <?php selected( in_array( (int) $c->term_id, array_map( 'intval', (array) $manual['cidades'] ), true ) ); ?>><?php echo esc_html( $c->name ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
					<p class="description">Nenhuma marcada = todo o 17. Segure Ctrl (ou Cmd) para marcar várias.</p>
				</td></tr>
				<tr><th>E-mail</th><td><label><input type="checkbox" name="enviar" value="1"> Mandar por e-mail para os inscritos agora</label></td></tr>
			</table>
			<?php submit_button( 'Salvar alerta' ); ?>
		</form>

		<h2 id="sangue">Estoque de sangue</h2>
		<?php $form( 'mural17_admin_sangue' ); ?>
			<table class="form-table">
				<tr><th><label for="sg-local">Hemocentro</label></th><td><input id="sg-local" name="local" class="regular-text" value="<?php echo esc_attr( $sangue['local'] ); ?>"></td></tr>
				<tr><th><label for="sg-link">Link “como doar”</label></th><td><input id="sg-link" type="url" name="link" class="regular-text" value="<?php echo esc_attr( $sangue['link'] ); ?>"></td></tr>
				<?php foreach ( mural17_tipos_sangue() as $t ) : ?>
					<tr><th><?php echo esc_html( $t ); ?></th><td>
						<?php foreach ( array_merge( array( '' => 'Sem dado' ), mural17_estados_sangue() ) as $k => $nome ) : ?>
							<label style="margin-right:14px"><input type="radio" name="tipos[<?php echo esc_attr( $t ); ?>]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $sangue['tipos'][ $t ] ?? '', $k ); ?>> <?php echo esc_html( $nome ); ?></label>
						<?php endforeach; ?>
					</td></tr>
				<?php endforeach; ?>
			</table>
			<?php if ( $sangue['em'] ) : ?>
				<p>Última atualização: <?php echo esc_html( mysql2date( 'd/m/Y H:i', $sangue['em'] ) ); ?></p>
			<?php endif; ?>
			<?php submit_button( 'Salvar estoque' ); ?>
		</form>
	</div>
	<style>
		.mural17-servicos-admin .m17-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin: 16px 0 8px; }
		.mural17-servicos-admin .m17-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 4px 18px 18px; }
		.mural17-servicos-admin .m17-erro { color: #b32d2e; }
		.mural17-servicos-admin .m17-escala { max-width: 900px; display: grid; gap: 12px; }
		.mural17-servicos-admin .m17-escala .campo { display: flex; flex-direction: column; gap: 4px; margin: 0; }
		.mural17-servicos-admin .m17-escala textarea { width: 100%; font-family: monospace; }
		.mural17-servicos-admin .m17-escala .formulario__dupla { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		.mural17-servicos-admin .campo__dica { color: #646970; font-size: 12px; }
	</style>
	<?php
}

/**
 * Confere permissão e nonce de um formulário da página Serviços.
 *
 * @param string $acao Ação.
 */
function mural17_confere_admin_servicos( $acao ) {
	if ( ! current_user_can( 'edit_others_posts' ) || ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), $acao ) ) {
		wp_die( 'Sem permissão.' );
	}
}

/**
 * Volta para a página Serviços.
 *
 * @param string $feito  Código.
 * @param string $ancora Âncora.
 */
function mural17_volta_admin_servicos( $feito, $ancora = '' ) {
	wp_safe_redirect( admin_url( 'admin.php?page=mural17-servicos&feito=' . $feito ) . ( $ancora ? '#' . $ancora : '' ) );
	exit;
}

add_action(
	'admin_post_mural17_admin_inmet',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_inmet' );
		mural17_volta_admin_servicos( mural17_inmet_atualiza() ? 'inmet' : 'inmet-erro' );
	}
);

add_action(
	'admin_post_mural17_admin_anp',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_anp' );
		mural17_volta_admin_servicos( is_wp_error( mural17_anp_atualiza( true ) ) ? 'anp-erro' : 'anp' );
	}
);

add_action(
	'admin_post_mural17_admin_escala',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_escala' );
		$cidade = mural17_acha_cidade( absint( mural17_post( 'cidade' ) ) );
		if ( ! $cidade ) {
			mural17_volta_admin_servicos( 'escala-0', 'escala' );
		}
		$fonte = mural17_post( 'fonte' );
		$r     = mural17_importa_plantoes( $cidade, mural17_post( 'escala', true ), mural17_post( 'hora' ), $fonte ? $fonte : 'Redação do Mural 17' );
		mural17_volta_admin_servicos( $r['criados'] ? 'escala' : 'escala-0', 'escala' );
	}
);

add_action(
	'admin_post_mural17_admin_alerta',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_alerta' );
		$velho = (array) get_option( 'mural17_alerta_manual' );
		$dt    = function ( $k ) {
			$d = mural17_data( mural17_post( $k ) );
			return $d ? $d->format( 'Y-m-d H:i' ) : '';
		};
		$a     = array(
			'ativo'      => empty( $_POST['ativo'] ) ? 0 : 1,
			'evento'     => mural17_post( 'evento' ),
			'nivel'      => isset( mural17_niveis_alerta()[ mural17_post( 'nivel' ) ] ) ? mural17_post( 'nivel' ) : 'laranja',
			'texto'      => mural17_post( 'texto', true ),
			'instrucoes' => mural17_post( 'instrucoes', true ),
			'inicio'     => $dt( 'inicio' ),
			'fim'        => $dt( 'fim' ),
			'fonte'      => mural17_post( 'fonte' ),
			'cidades'    => isset( $_POST['cidades'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['cidades'] ) ) : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'versao'     => (int) ( $velho['versao'] ?? 0 ) + 1,
		);
		update_option( 'mural17_alerta_manual', $a, false );
		if ( ! empty( $_POST['enviar'] ) && $a['ativo'] ) {
			$alerta = mural17_alerta_manual();
			if ( $alerta ) {
				$texto = trim( $alerta['evento'] . ' ' . mural17_periodo_alerta( $alerta ) . ".\n\n" . implode( "\n", $alerta['riscos'] ) );
				if ( $alerta['instrucoes'] ) {
					$texto .= "\n\nO que fazer:\n- " . implode( "\n- ", $alerta['instrucoes'] );
				}
				mural17_avisa_inscritos( 'alertas', $alerta['cidades'], 'Alerta: ' . $alerta['evento'], $texto . "\n\nFonte: " . $alerta['fonte'], mural17_servicos()['alertas']['url'] );
			}
		}
		mural17_volta_admin_servicos( 'alerta', 'alerta' );
	}
);

add_action(
	'admin_post_mural17_admin_sangue',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_sangue' );
		$tipos = array();
		$pedido = isset( $_POST['tipos'] ) ? (array) wp_unslash( $_POST['tipos'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( mural17_tipos_sangue() as $t ) {
			$v           = isset( $pedido[ $t ] ) ? sanitize_key( $pedido[ $t ] ) : '';
			$tipos[ $t ] = isset( mural17_estados_sangue()[ $v ] ) ? $v : '';
		}
		update_option(
			'mural17_sangue',
			array(
				'tipos' => $tipos,
				'local' => mural17_post( 'local' ),
				'link'  => isset( $_POST['link'] ) ? esc_url_raw( wp_unslash( $_POST['link'] ) ) : '',
				'em'    => current_time( 'mysql' ),
			),
			false
		);
		mural17_volta_admin_servicos( 'sangue', 'sangue' );
	}
);

/* -------------------------------------------------------------------------
 * Colunas nas listas
 * ---------------------------------------------------------------------- */

/**
 * Colunas extras por tipo.
 *
 * @return array<string, array<string, string>>
 */
function mural17_colunas_servicos() {
	return array(
		'plantao'     => array( 'm17_quando' => 'Plantão' ),
		'interrupcao' => array(
			'm17_tipo'     => 'Falta',
			'm17_situacao' => 'Situação',
		),
		'fala'        => array(
			'm17_situacao' => 'Situação',
			'm17_eutambem' => 'Eu também',
		),
		'concurso'    => array( 'm17_prazo' => 'Inscrições' ),
		'falecimento' => array( 'm17_funeraria' => 'Informado por' ),
	);
}

foreach ( array_keys( mural17_colunas_servicos() ) as $mural17_tipo ) {
	add_filter(
		"manage_{$mural17_tipo}_posts_columns",
		function ( $cols ) use ( $mural17_tipo ) {
			$data = isset( $cols['date'] ) ? $cols['date'] : null;
			unset( $cols['date'] );
			$cols = array_merge( $cols, mural17_colunas_servicos()[ $mural17_tipo ] );
			if ( $data ) {
				$cols['date'] = $data;
			}
			return $cols;
		}
	);
	add_action( "manage_{$mural17_tipo}_posts_custom_column", 'mural17_coluna_servico', 10, 2 );
}

/**
 * Conteúdo das colunas.
 *
 * @param string $col     Coluna.
 * @param int    $post_id Post.
 */
function mural17_coluna_servico( $col, $post_id ) {
	switch ( $col ) {
		case 'm17_quando':
			echo esc_html( mural17_quando_servico( mural17_campo( $post_id, 'plantao_inicio' ) ) );
			break;
		case 'm17_tipo':
			echo 'energia' === mural17_campo( $post_id, 'int_tipo' ) ? 'Energia' : 'Água';
			break;
		case 'm17_situacao':
			if ( 'interrupcao' === get_post_type( $post_id ) ) {
				if ( mural17_campo( $post_id, 'int_normalizado' ) ) {
					echo 'Normalizado';
				} elseif ( mural17_campo( $post_id, 'int_inicio' ) > mural17_agora() ) {
					echo 'Programado';
				} else {
					echo '<b>Agora</b>';
				}
			} else {
				$s = mural17_campo( $post_id, 'fala_situacao' );
				echo esc_html( ucfirst( $s ? $s : 'aberta' ) );
			}
			break;
		case 'm17_eutambem':
			echo (int) get_post_meta( $post_id, '_mural17_eutambem', true );
			break;
		case 'm17_prazo':
			echo esc_html( mural17_prazo_concurso( $post_id )[1] );
			break;
		case 'm17_funeraria':
			echo esc_html( mural17_campo( $post_id, 'fal_funeraria' ) );
			break;
	}
}

/**
 * Bolinha com o número de reclamações e falecimentos esperando revisão.
 */
function mural17_bolinha_servicos() {
	global $menu;
	$n = 0;
	foreach ( array( 'fala', 'falecimento' ) as $t ) {
		$c  = wp_count_posts( $t );
		$n += isset( $c->pending ) ? (int) $c->pending : 0;
	}
	if ( ! $n || ! is_array( $menu ) ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		if ( 'mural17-servicos' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}
add_action( 'admin_menu', 'mural17_bolinha_servicos', 99 );
