<?php
/**
 * Formulários do site para os órgãos: cadastro e painel de envio de notas.
 * As páginas são criadas na instalação com os shortcodes
 * [mural17_cadastro_orgao] e [mural17_painel_orgao].
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mensagens mostradas depois de um envio (?m17=codigo).
 *
 * @return array<string, array{0:string,1:string}> codigo => [tipo, texto]
 */
function mural17_mensagens_oficial() {
	return array(
		'cadastro-ok'     => array( 'ok', 'Pedido recebido. A redação vai conferir os dados e responder por e-mail em até 1 dia útil.' ),
		'nota-revisao'    => array( 'ok', 'Nota enviada. A redação foi avisada e revisa em até 2 horas no horário comercial.' ),
		'nota-publicada'  => array( 'ok', 'Nota publicada.' ),
		'nota-rascunho'   => array( 'ok', 'Rascunho salvo. Ele só vai para a redação quando você clicar em “Enviar para revisão”.' ),
		'erro-nonce'      => array( 'erro', 'A página ficou aberta por muito tempo. Recarregue e tente de novo.' ),
		'erro-email'      => array( 'erro', 'Use o e-mail institucional do órgão (terminado em .sp.gov.br, .sp.leg.br, .jus.br, .mp.br, .def.br ou .gov.br).' ),
		'erro-email-uso'  => array( 'erro', 'Já existe uma conta com este e-mail. Entre pelo Painel do órgão ou peça uma nova senha.' ),
		'erro-campos'     => array( 'erro', 'Preencha os campos obrigatórios: tipo, cidade, nome do órgão, responsável e telefone.' ),
		'erro-orgao'      => array( 'erro', 'Este órgão já tem cadastro no Mural 17. Fale com a redação para incluir outra pessoa.' ),
		'erro-termos'     => array( 'erro', 'Para continuar, aceite os termos para órgãos públicos.' ),
		'erro-titulo'     => array( 'erro', 'A nota precisa de título e texto.' ),
		'erro-declaracao' => array( 'erro', 'Confirme que a nota é de interesse público e segue os termos.' ),
		'erro-foto'       => array( 'erro', 'A foto precisa ser JPG, PNG ou WebP de até 8 MB. O resto da nota foi salvo; tente mandar a foto de novo.' ),
		'erro-permissao'  => array( 'erro', 'Esta nota não pode mais ser editada pelo painel.' ),
	);
}

/**
 * Aviso de sucesso ou erro no topo do formulário.
 */
function mural17_aviso_oficial() {
	$codigo = isset( $_GET['m17'] ) ? sanitize_key( wp_unslash( $_GET['m17'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$msgs   = mural17_mensagens_oficial();
	if ( isset( $msgs[ $codigo ] ) ) {
		printf( '<p class="aviso aviso--%1$s" role="status">%2$s</p>', esc_attr( $msgs[ $codigo ][0] ), esc_html( $msgs[ $codigo ][1] ) );
	}
}

/**
 * Volta para a página de origem com um código de mensagem.
 *
 * @param string $qual   "cadastro" ou "painel".
 * @param string $codigo Código.
 * @param array  $extra  Outros parâmetros.
 */
function mural17_volta_oficial( $qual, $codigo, $extra = array() ) {
	wp_safe_redirect( add_query_arg( array_merge( array( 'm17' => $codigo ), $extra ), mural17_link_oficial( $qual ) ) );
	exit;
}

/**
 * Lista de cidades (sem as regiões) para os selects.
 *
 * @param int $marcada ID da cidade marcada.
 */
function mural17_opcoes_cidades( $marcada = 0 ) {
	foreach ( mural17_lista_cidades() as $grupo ) {
		echo '<optgroup label="' . esc_attr( $grupo['regiao']->name ) . '">';
		foreach ( $grupo['cidades'] as $c ) {
			printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $c->term_id, selected( $marcada, $c->term_id, false ), esc_html( $c->name ) );
		}
		echo '</optgroup>';
	}
}

/* -------------------------------------------------------------------------
 * Cadastro do órgão
 * ---------------------------------------------------------------------- */

/**
 * Formulário de cadastro.
 *
 * @return string
 */
function mural17_sc_cadastro() {
	$v = get_transient( 'mural17_cad_' . ( isset( $_GET['f'] ) ? sanitize_key( wp_unslash( $_GET['f'] ) ) : 'x' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$v = is_array( $v ) ? $v : array();
	$g = function ( $k ) use ( $v ) {
		return isset( $v[ $k ] ) ? $v[ $k ] : '';
	};
	ob_start();
	?>
	<div class="oficial-cadastro">
		<section class="oficial-cadastro__lado">
			<span class="selo-oficial selo-oficial--claro"><?php echo mural17_icone_oficial( 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Para órgãos públicos</span>
			<h2>Publique os comunicados do seu órgão no Mural 17</h2>
			<p>Prefeituras, câmaras, fóruns, promotorias, defensorias e polícias das 117 cidades do DDD 17. Sem custo.</p>
			<ol class="oficial-passos">
				<li><b>Peça o cadastro</b> com o e-mail institucional do órgão.</li>
				<li><b>A redação confere</b> pelo telefone oficial e libera em até 1 dia útil.</li>
				<li><b>Envie suas notas.</b> Elas saem com o selo Oficial, na página da sua cidade e na página do órgão.</li>
			</ol>
			<p class="oficial-cadastro__nota">Nos 3 meses antes das eleições, notas de prefeituras e câmaras ficam suspensas, como manda a lei eleitoral. Só avisos urgentes de utilidade pública passam, com aprovação da redação.</p>
		</section>

		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php mural17_aviso_oficial(); ?>
			<input type="hidden" name="action" value="mural17_cadastro">
			<?php wp_nonce_field( 'mural17_cadastro', 'mural17_nonce' ); ?>
			<p class="formulario__mel" aria-hidden="true"><label>Não preencha<input type="text" name="mural17_site_confirma" tabindex="-1" autocomplete="off"></label></p>

			<div class="formulario__dupla">
				<p class="campo">
					<label for="cad-tipo">Tipo de órgão</label>
					<select id="cad-tipo" name="tipo" required>
						<option value="">Escolha</option>
						<?php foreach ( mural17_tipos_orgao() as $k => $t ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $g( 'tipo' ), $k ); ?>><?php echo esc_html( $t['nome'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="campo">
					<label for="cad-cidade">Cidade</label>
					<select id="cad-cidade" name="cidade" required>
						<option value="">Escolha</option>
						<?php mural17_opcoes_cidades( (int) $g( 'cidade' ) ); ?>
					</select>
				</p>
			</div>
			<p class="campo">
				<label for="cad-nome">Nome do órgão, como deve aparecer</label>
				<input id="cad-nome" name="nome" required maxlength="80" value="<?php echo esc_attr( $g( 'nome' ) ); ?>" placeholder="Ex.: Câmara Municipal de Jaci">
			</p>
			<p class="campo">
				<label for="cad-email">E-mail institucional</label>
				<input id="cad-email" name="email" type="email" required value="<?php echo esc_attr( $g( 'email' ) ); ?>" placeholder="imprensa@suacidade.sp.gov.br">
				<span class="campo__dica">Aceitamos <?php echo esc_html( implode( ', ', mural17_dominios_oficiais() ) ); ?>. E-mail pessoal (Gmail, Hotmail) não é aceito.</span>
			</p>
			<div class="formulario__tripla">
				<p class="campo"><label for="cad-resp">Responsável</label><input id="cad-resp" name="responsavel" required maxlength="80" value="<?php echo esc_attr( $g( 'responsavel' ) ); ?>"></p>
				<p class="campo"><label for="cad-cargo">Cargo</label><input id="cad-cargo" name="cargo" maxlength="80" value="<?php echo esc_attr( $g( 'cargo' ) ); ?>" placeholder="Ex.: assessor de imprensa"></p>
				<p class="campo"><label for="cad-tel">Telefone oficial</label><input id="cad-tel" name="telefone" required maxlength="30" value="<?php echo esc_attr( $g( 'telefone' ) ); ?>" placeholder="(17) 0000-0000"></p>
			</div>
			<p class="campo">
				<label for="cad-site">Site oficial</label>
				<input id="cad-site" name="site" type="url" maxlength="200" value="<?php echo esc_attr( $g( 'site' ) ); ?>" placeholder="https://">
			</p>
			<p class="campo campo--check">
				<input id="cad-termos" type="checkbox" name="termos" value="1" required>
				<label for="cad-termos">Li e aceito os termos para órgãos públicos: o órgão responde pelo que publica, a redação pode revisar ou recusar, e propaganda política e promoção pessoal não são permitidas.</label>
			</p>
			<p class="formulario__acoes">
				<button type="submit" class="botao botao--pilula botao--oficial">Pedir cadastro</button>
				<span class="campo__dica">Já tem cadastro? <a href="<?php echo esc_url( mural17_link_oficial( 'painel' ) ); ?>">Entrar no painel</a></span>
			</p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'mural17_cadastro_orgao', 'mural17_sc_cadastro' );

/**
 * Recebe o cadastro.
 */
function mural17_recebe_cadastro() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_cadastro' ) ) {
		mural17_volta_oficial( 'cadastro', 'erro-nonce' );
	}
	// Robôs preenchem o campo escondido.
	if ( ! empty( $_POST['mural17_site_confirma'] ) ) {
		mural17_volta_oficial( 'cadastro', 'cadastro-ok' );
	}

	$d = array(
		'tipo'        => isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '',
		'cidade'      => isset( $_POST['cidade'] ) ? absint( $_POST['cidade'] ) : 0,
		'nome'        => isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '',
		'email'       => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'responsavel' => isset( $_POST['responsavel'] ) ? sanitize_text_field( wp_unslash( $_POST['responsavel'] ) ) : '',
		'cargo'       => isset( $_POST['cargo'] ) ? sanitize_text_field( wp_unslash( $_POST['cargo'] ) ) : '',
		'telefone'    => isset( $_POST['telefone'] ) ? sanitize_text_field( wp_unslash( $_POST['telefone'] ) ) : '',
		'site'        => isset( $_POST['site'] ) ? esc_url_raw( wp_unslash( $_POST['site'] ) ) : '',
	);

	$volta = function ( $codigo ) use ( $d ) {
		$f = wp_generate_password( 12, false );
		set_transient( 'mural17_cad_' . strtolower( $f ), $d, 10 * MINUTE_IN_SECONDS );
		mural17_volta_oficial( 'cadastro', $codigo, array( 'f' => strtolower( $f ) ) );
	};

	$cidade = $d['cidade'] ? get_term( $d['cidade'], 'cidade' ) : null;
	if ( ! isset( mural17_tipos_orgao()[ $d['tipo'] ] ) || ! $cidade || is_wp_error( $cidade ) || ! $cidade->parent || ! $d['nome'] || ! $d['responsavel'] || ! $d['telefone'] ) {
		$volta( 'erro-campos' );
	}
	if ( ! mural17_email_oficial( $d['email'] ) ) {
		$volta( 'erro-email' );
	}
	if ( email_exists( $d['email'] ) ) {
		$volta( 'erro-email-uso' );
	}
	if ( empty( $_POST['termos'] ) ) {
		$volta( 'erro-termos' );
	}
	if ( term_exists( $d['nome'], 'orgao' ) ) {
		$volta( 'erro-orgao' );
	}

	$t = wp_insert_term( $d['nome'], 'orgao' );
	if ( is_wp_error( $t ) ) {
		$volta( 'erro-orgao' );
	}
	$orgao_id = (int) $t['term_id'];
	foreach ( array( 'tipo', 'cidade', 'email', 'responsavel', 'cargo', 'telefone', 'site' ) as $k ) {
		update_term_meta( $orgao_id, 'mural17_' . $k, $d[ $k ] );
	}
	update_term_meta( $orgao_id, 'mural17_status', 'pendente' );
	update_term_meta( $orgao_id, 'mural17_pedido_em', current_time( 'mysql' ) );

	$login = sanitize_user( strtok( $d['email'], '@' ) . '-' . $orgao_id, true );
	$uid   = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $d['email'],
			'user_pass'    => wp_generate_password( 24 ),
			'display_name' => $d['responsavel'],
			'role'         => 'orgao_pendente',
		)
	);
	if ( is_wp_error( $uid ) ) {
		wp_delete_term( $orgao_id, 'orgao' );
		$volta( 'erro-email-uso' );
	}
	update_user_meta( $uid, 'mural17_orgao', $orgao_id );
	update_term_meta( $orgao_id, 'mural17_usuario', $uid );

	wp_mail(
		get_option( 'admin_email' ),
		'[Mural 17] Novo pedido de cadastro: ' . $d['nome'],
		sprintf(
			"%s pediu cadastro como órgão oficial.\n\nCidade: %s\nE-mail: %s\nResponsável: %s (%s)\nTelefone: %s\nSite: %s\n\nConfira pelo telefone oficial antes de liberar:\n%s",
			$d['nome'],
			$cidade->name,
			$d['email'],
			$d['responsavel'],
			$d['cargo'],
			$d['telefone'],
			$d['site'],
			admin_url( 'edit.php?page=mural17-notas&aba=pedidos' )
		)
	);

	mural17_volta_oficial( 'cadastro', 'cadastro-ok' );
}
add_action( 'admin_post_nopriv_mural17_cadastro', 'mural17_recebe_cadastro' );
add_action( 'admin_post_mural17_cadastro', 'mural17_recebe_cadastro' );

/* -------------------------------------------------------------------------
 * Painel do órgão
 * ---------------------------------------------------------------------- */

/**
 * Situação de uma nota para o órgão.
 *
 * @param WP_Post $p Nota.
 * @return array{0:string,1:string} [classe, rótulo]
 */
function mural17_situacao_nota( $p ) {
	switch ( $p->post_status ) {
		case 'publish':
			return array( 'ok', 'Publicada' );
		case 'pending':
			return array( 'espera', 'Em revisão' );
		case 'devolvida':
			return array( 'erro', 'Devolvida' );
		default:
			return array( 'neutro', 'Rascunho' );
	}
}

/**
 * Painel: login, aviso de cadastro em análise ou o formulário de nota.
 *
 * @return string
 */
function mural17_sc_painel() {
	ob_start();

	if ( ! is_user_logged_in() ) {
		?>
		<div class="oficial-entrar">
			<h2>Entrar no painel do órgão</h2>
			<?php mural17_aviso_oficial(); ?>
			<?php
			wp_login_form(
				array(
					'redirect'       => mural17_link_oficial( 'painel' ),
					'label_username' => 'E-mail institucional',
					'label_password' => 'Senha',
					'label_remember' => 'Manter conectado neste aparelho',
					'label_log_in'   => 'Entrar',
				)
			);
			?>
			<p class="campo__dica"><a href="<?php echo esc_url( wp_lostpassword_url( mural17_link_oficial( 'painel' ) ) ); ?>">Esqueci ou ainda não criei a senha</a> · <a href="<?php echo esc_url( mural17_link_oficial( 'cadastro' ) ); ?>">Cadastrar órgão</a></p>
		</div>
		<?php
		return ob_get_clean();
	}

	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao ) {
		$u = wp_get_current_user();
		if ( in_array( 'orgao_pendente', (array) $u->roles, true ) ) {
			echo '<div class="oficial-entrar"><h2>Cadastro em análise</h2><p>A redação está conferindo os dados do seu órgão. Você recebe um e-mail assim que o acesso for liberado.</p></div>';
		} elseif ( current_user_can( 'edit_others_posts' ) ) {
			echo '<div class="oficial-entrar"><h2>Esta página é para os órgãos</h2><p>Para revisar e aprovar as notas, use <a href="' . esc_url( admin_url( 'edit.php?page=mural17-notas' ) ) . '">Posts › Notas oficiais</a>.</p></div>';
		} else {
			echo '<div class="oficial-entrar"><h2>Conta sem órgão</h2><p>Esta conta não está ligada a um órgão ativo. Fale com a redação.</p></div>';
		}
		return ob_get_clean();
	}

	// Edição de uma nota que ainda não foi publicada.
	$nota   = null;
	$editar = isset( $_GET['editar'] ) ? absint( $_GET['editar'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $editar ) {
		$p = get_post( $editar );
		$o = $p ? mural17_orgao_do_post( $p->ID ) : null;
		if ( $p && $o && $o->term_id === $orgao->term_id && in_array( $p->post_status, array( 'draft', 'pending', 'devolvida' ), true ) ) {
			$nota = $p;
		}
	}
	$m        = function ( $k ) use ( $nota ) {
		return $nota ? (string) get_post_meta( $nota->ID, '_mural17_' . $k, true ) : '';
	};
	$tipo     = $m( 'nota_tipo' ) ? $m( 'nota_tipo' ) : 'servico';
	$cidade   = get_term( (int) mural17_orgao_meta( $orgao, 'cidade' ), 'cidade' );
	$eleicao  = mural17_periodo_eleitoral() && mural17_orgao_eleitoral( $orgao );
	$minhas   = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'pending', 'draft', 'devolvida' ),
			'posts_per_page' => 10,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'orgao',
					'field'    => 'term_id',
					'terms'    => $orgao->term_id,
				),
			),
		)
	);
	?>
	<div class="oficial-painel">
		<div class="oficial-painel__quem">
			<?php mural17_brasao( $orgao ); ?>
			<span><b><?php echo esc_html( $orgao->name ); ?></b><small><?php echo esc_html( wp_get_current_user()->user_email ); ?></small></span>
			<a href="<?php echo esc_url( get_term_link( $orgao ) ); ?>">Ver página do órgão</a>
			<a href="<?php echo esc_url( wp_logout_url( mural17_link_oficial( 'painel' ) ) ); ?>">Sair</a>
		</div>

		<div class="oficial-painel__grade">
			<form class="formulario" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2><?php echo $nota ? 'Editar nota' : 'Nova nota oficial'; ?></h2>
				<?php mural17_aviso_oficial(); ?>
				<?php if ( $nota && 'devolvida' === $nota->post_status && $m( 'motivo_devolucao' ) ) : ?>
					<p class="aviso aviso--erro"><b>A redação devolveu esta nota:</b> <?php echo esc_html( $m( 'motivo_devolucao' ) ); ?></p>
				<?php endif; ?>
				<?php if ( $eleicao ) : ?>
					<p class="aviso aviso--espera"><b>Período eleitoral.</b> Pela lei eleitoral, notas de prefeituras e câmaras não são publicadas agora. Envie só avisos urgentes de utilidade pública; a redação decide um a um.</p>
				<?php endif; ?>
				<input type="hidden" name="action" value="mural17_nota">
				<input type="hidden" name="editar" value="<?php echo $nota ? (int) $nota->ID : 0; ?>">
				<?php wp_nonce_field( 'mural17_nota', 'mural17_nonce' ); ?>

				<fieldset class="campo">
					<legend>Tipo de nota</legend>
					<div class="opcoes">
						<?php foreach ( mural17_tipos_nota() as $k => $nome ) : ?>
							<label class="opcao"><input type="radio" name="nota_tipo" value="<?php echo esc_attr( $k ); ?>" <?php checked( $tipo, $k ); ?>> <?php echo esc_html( $nome ); ?></label>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<p class="campo">
					<label for="nota-titulo">Título</label>
					<input id="nota-titulo" name="titulo" required maxlength="110" value="<?php echo $nota ? esc_attr( $nota->post_title ) : ''; ?>">
					<span class="campo__dica">Até 110 caracteres. Diga o que muda para o morador, sem nome de autoridade.</span>
				</p>
				<p class="campo">
					<label for="nota-resumo">Resumo em uma frase</label>
					<input id="nota-resumo" name="resumo" maxlength="200" value="<?php echo $nota ? esc_attr( $nota->post_excerpt ) : ''; ?>">
				</p>
				<p class="campo">
					<label for="nota-texto">Texto</label>
					<textarea id="nota-texto" name="texto" rows="9" required><?php echo $nota ? esc_textarea( trim( html_entity_decode( wp_strip_all_tags( preg_replace( '#</p>\s*#i', "\n\n", $nota->post_content ) ), ENT_QUOTES ) ) ) : ''; ?></textarea>
					<span class="campo__dica">Parágrafos separados por uma linha em branco. Links começando com https:// viram links.</span>
				</p>
				<div class="formulario__dupla">
					<p class="campo">
						<label for="nota-foto">Foto <?php echo $nota && has_post_thumbnail( $nota ) ? '(já tem uma; envie outra para trocar)' : ''; ?></label>
						<input id="nota-foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
						<span class="campo__dica">JPG, PNG ou WebP, até 8 MB. Sem texto escrito na imagem.</span>
					</p>
					<p class="campo">
						<label for="nota-credito">Crédito da foto</label>
						<input id="nota-credito" name="credito" maxlength="80" value="<?php echo esc_attr( $m( 'credito' ) ? $m( 'credito' ) : $orgao->name ); ?>">
						<span class="campo__dica">Cidade: <?php echo esc_html( $cidade && ! is_wp_error( $cidade ) ? $cidade->name : '' ); ?> (fixa para este órgão)</span>
					</p>
				</div>
				<details class="campo-servico" <?php echo $m( 'servico_quando' ) || 'servico' === $tipo ? 'open' : ''; ?>>
					<summary>Bloco “Serviço” (opcional)</summary>
					<div class="formulario__dupla">
						<p class="campo"><label for="sv-quando">Quando</label><input id="sv-quando" name="servico_quando" maxlength="120" value="<?php echo esc_attr( $m( 'servico_quando' ) ); ?>"></p>
						<p class="campo"><label for="sv-onde">Onde</label><input id="sv-onde" name="servico_onde" maxlength="120" value="<?php echo esc_attr( $m( 'servico_onde' ) ); ?>"></p>
						<p class="campo"><label for="sv-levar">O que levar</label><input id="sv-levar" name="servico_levar" maxlength="120" value="<?php echo esc_attr( $m( 'servico_levar' ) ); ?>"></p>
						<p class="campo"><label for="sv-contato">Contato</label><input id="sv-contato" name="servico_contato" maxlength="120" value="<?php echo esc_attr( $m( 'servico_contato' ) ); ?>"></p>
					</div>
				</details>
				<p class="campo campo--check">
					<input id="nota-declara" type="checkbox" name="declaracao" value="1">
					<label for="nota-declara">Confirmo que o texto é de interesse público, não faz promoção pessoal de autoridades e segue os termos do Mural 17.</label>
				</p>
				<p class="formulario__acoes">
					<button type="submit" name="destino" value="enviar" class="botao botao--pilula botao--oficial"><?php echo mural17_publicacao_direta( $orgao ) ? 'Publicar' : 'Enviar para revisão'; ?></button>
					<button type="submit" name="destino" value="rascunho" class="botao botao--pilula botao--contorno-escuro" formnovalidate>Salvar rascunho</button>
					<?php if ( $nota ) : ?>
						<a href="<?php echo esc_url( mural17_link_oficial( 'painel' ) ); ?>">Cancelar edição</a>
					<?php endif; ?>
				</p>
			</form>

			<aside class="oficial-minhas">
				<h2>Minhas notas</h2>
				<?php if ( $minhas->have_posts() ) : ?>
					<ul>
						<?php
						foreach ( $minhas->posts as $p ) :
							$sit    = mural17_situacao_nota( $p );
							$motivo = (string) get_post_meta( $p->ID, '_mural17_motivo_devolucao', true );
							?>
							<li>
								<span class="situacao situacao--<?php echo esc_attr( $sit[0] ); ?>"><?php echo esc_html( $sit[1] ); ?></span>
								<b><?php echo esc_html( get_the_title( $p ) ); ?></b>
								<small>
									<?php echo esc_html( get_the_date( 'd/m H\hi', $p ) ); ?>
									<?php if ( 'publish' === $p->post_status ) : ?>
										· <?php echo esc_html( number_format_i18n( (int) get_post_meta( $p->ID, '_mural17_views', true ) ) ); ?> leituras · <a href="<?php echo esc_url( get_permalink( $p ) ); ?>">ver</a>
									<?php endif; ?>
								</small>
								<?php if ( 'devolvida' === $p->post_status && $motivo ) : ?>
									<span class="oficial-minhas__motivo"><b>Redação:</b> <?php echo esc_html( $motivo ); ?></span>
								<?php endif; ?>
								<?php if ( 'publish' !== $p->post_status ) : ?>
									<a href="<?php echo esc_url( add_query_arg( 'editar', $p->ID, mural17_link_oficial( 'painel' ) ) ); ?>"><?php echo 'devolvida' === $p->post_status ? 'Editar e reenviar' : 'Editar'; ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="vazio">Nenhuma nota ainda.</p>
				<?php endif; ?>
				<div class="oficial-minhas__como">
					<b>Como funciona</b>
					<span>1. Você envia; a redação recebe um aviso.</span>
					<span>2. Revisamos em até 2 horas no horário comercial.</span>
					<span>3. Publicada, a nota entra no Plantão, na página da sua cidade e na página do órgão.</span>
				</div>
			</aside>
		</div>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'mural17_painel_orgao', 'mural17_sc_painel' );

/**
 * Texto simples do formulário em HTML seguro: parágrafos e links.
 *
 * @param string $texto Texto digitado.
 * @return string
 */
function mural17_texto_nota( $texto ) {
	$texto = wp_strip_all_tags( $texto );
	return wp_kses_post( make_clickable( wpautop( esc_html( $texto ) ) ) );
}

/**
 * Recebe uma nota do painel.
 */
function mural17_recebe_nota() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_nota' ) ) {
		mural17_volta_oficial( 'painel', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}

	$rascunho = isset( $_POST['destino'] ) && 'rascunho' === sanitize_key( wp_unslash( $_POST['destino'] ) );
	$titulo   = isset( $_POST['titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['titulo'] ) ) : '';
	$texto    = isset( $_POST['texto'] ) ? sanitize_textarea_field( wp_unslash( $_POST['texto'] ) ) : '';
	if ( ! $rascunho && ( '' === $titulo || '' === $texto ) ) {
		mural17_volta_oficial( 'painel', 'erro-titulo' );
	}
	if ( ! $rascunho && empty( $_POST['declaracao'] ) ) {
		mural17_volta_oficial( 'painel', 'erro-declaracao' );
	}

	$editar = isset( $_POST['editar'] ) ? absint( $_POST['editar'] ) : 0;
	if ( $editar ) {
		$p = get_post( $editar );
		$o = $p ? mural17_orgao_do_post( $p->ID ) : null;
		if ( ! $p || ! $o || $o->term_id !== $orgao->term_id || ! in_array( $p->post_status, array( 'draft', 'pending', 'devolvida' ), true ) ) {
			mural17_volta_oficial( 'painel', 'erro-permissao' );
		}
	}

	$direta   = mural17_publicacao_direta( $orgao );
	$situacao = $rascunho ? 'draft' : ( $direta ? 'publish' : 'pending' );
	$dados    = array(
		'post_type'    => 'post',
		'post_title'   => $titulo ? $titulo : 'Rascunho sem título',
		'post_excerpt' => isset( $_POST['resumo'] ) ? sanitize_text_field( wp_unslash( $_POST['resumo'] ) ) : '',
		'post_content' => mural17_texto_nota( $texto ),
		'post_status'  => $situacao,
		'post_author'  => get_current_user_id(),
	);
	if ( $editar ) {
		$dados['ID'] = $editar;
		$post_id     = wp_update_post( $dados, true );
	} else {
		$post_id = wp_insert_post( $dados, true );
	}
	if ( is_wp_error( $post_id ) ) {
		mural17_volta_oficial( 'painel', 'erro-titulo' );
	}

	wp_set_object_terms( $post_id, $orgao->term_id, 'orgao' );
	$cidade = (int) mural17_orgao_meta( $orgao, 'cidade' );
	if ( $cidade ) {
		wp_set_object_terms( $post_id, $cidade, 'cidade' );
	}
	$tipo = isset( $_POST['nota_tipo'] ) ? sanitize_key( wp_unslash( $_POST['nota_tipo'] ) ) : 'servico';
	update_post_meta( $post_id, '_mural17_nota_tipo', isset( mural17_tipos_nota()[ $tipo ] ) ? $tipo : 'servico' );
	foreach ( array( 'credito', 'servico_quando', 'servico_onde', 'servico_levar', 'servico_contato' ) as $k ) {
		$valor = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
		if ( '' === $valor ) {
			delete_post_meta( $post_id, '_mural17_' . $k );
		} else {
			update_post_meta( $post_id, '_mural17_' . $k, $valor );
		}
	}
	if ( ! $rascunho ) {
		delete_post_meta( $post_id, '_mural17_motivo_devolucao' );
		update_post_meta( $post_id, '_mural17_enviada_em', current_time( 'mysql' ) );
		if ( mural17_periodo_eleitoral() && mural17_orgao_eleitoral( $orgao ) ) {
			update_post_meta( $post_id, '_mural17_eleitoral', 1 );
		}
	}

	$codigo = $rascunho ? 'nota-rascunho' : ( $direta ? 'nota-publicada' : 'nota-revisao' );

	// Foto: só imagem de verdade, até 8 MB.
	if ( ! empty( $_FILES['foto']['name'] ) ) {
		$ok   = false;
		$size = isset( $_FILES['foto']['size'] ) ? (int) $_FILES['foto']['size'] : 0;
		$tmp  = isset( $_FILES['foto']['tmp_name'] ) ? sanitize_text_field( $_FILES['foto']['tmp_name'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$nome = sanitize_file_name( wp_unslash( $_FILES['foto']['name'] ) );
		if ( $size > 0 && $size <= 8 * MB_IN_BYTES && $tmp ) {
			$tipo_arq = wp_check_filetype_and_ext( $tmp, $nome, array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
			if ( ! empty( $tipo_arq['type'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$foto = media_handle_upload( 'foto', $post_id, array(), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
				if ( ! is_wp_error( $foto ) ) {
					set_post_thumbnail( $post_id, $foto );
					$credito = (string) get_post_meta( $post_id, '_mural17_credito', true );
					if ( $credito ) {
						wp_update_post( array( 'ID' => $foto, 'post_excerpt' => 'Foto: ' . $credito ) );
					}
					$ok = true;
				}
			}
		}
		if ( ! $ok ) {
			$codigo = 'erro-foto';
		}
	}

	if ( 'pending' === $situacao ) {
		wp_mail(
			get_option( 'admin_email' ),
			'[Mural 17] Nota para revisar: ' . $titulo,
			sprintf( "%s enviou uma nota oficial.\n\n%s\n\nRevisar: %s", $orgao->name, $titulo, admin_url( 'edit.php?page=mural17-notas' ) )
		);
	}

	mural17_volta_oficial( 'painel', $codigo, 'erro-foto' === $codigo ? array( 'editar' => $post_id ) : array() );
}
add_action( 'admin_post_mural17_nota', 'mural17_recebe_nota' );
