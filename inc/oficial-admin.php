<?php
/**
 * Redação: Posts › Notas oficiais (fila, pedidos de cadastro, órgãos e
 * período eleitoral) e os campos do órgão em Posts › Órgãos oficiais.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notas oficiais aguardando revisão.
 *
 * @return WP_Query
 */
function mural17_fila_notas() {
	return new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'pending',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'orgao',
					'operator' => 'EXISTS',
				),
			),
		)
	);
}

/**
 * Órgãos por situação (pendente, ativo).
 *
 * @param string $status Situação.
 * @return WP_Term[]
 */
function mural17_orgaos_por_status( $status ) {
	$t = get_terms(
		array(
			'taxonomy'   => 'orgao',
			'hide_empty' => false,
			'meta_key'   => 'mural17_status', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => $status, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return is_wp_error( $t ) ? array() : $t;
}

/**
 * Menu da redação, com o número de notas esperando.
 */
function mural17_menu_notas() {
	$n     = mural17_fila_notas()->found_posts + count( mural17_orgaos_por_status( 'pendente' ) );
	$badge = $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '';
	add_submenu_page( 'edit.php', 'Notas oficiais', 'Notas oficiais' . $badge, 'edit_others_posts', 'mural17-notas', 'mural17_tela_notas' );
}
add_action( 'admin_menu', 'mural17_menu_notas' );

/**
 * Link de ação do admin com nonce.
 *
 * @param string $acao  Ação.
 * @param array  $args  Parâmetros.
 * @return string
 */
function mural17_link_acao( $acao, $args ) {
	return wp_nonce_url( add_query_arg( array_merge( array( 'action' => $acao ), $args ), admin_url( 'admin-post.php' ) ), $acao );
}

/**
 * Tela "Notas oficiais".
 */
function mural17_tela_notas() {
	$aba   = isset( $_GET['aba'] ) ? sanitize_key( wp_unslash( $_GET['aba'] ) ) : 'fila'; // phpcs:ignore WordPress.Security.NonceVerification
	$fila  = mural17_fila_notas();
	$ped   = mural17_orgaos_por_status( 'pendente' );
	$ativ  = mural17_orgaos_por_status( 'ativo' );
	$tipos = mural17_tipos_orgao();
	$base  = admin_url( 'edit.php?page=mural17-notas' );
	$msg   = isset( $_GET['feito'] ) ? sanitize_text_field( wp_unslash( $_GET['feito'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap m17-redacao">
		<h1>Notas oficiais</h1>
		<?php if ( $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<form class="m17-eleicao<?php echo mural17_periodo_eleitoral() ? ' is-ligado' : ''; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mural17_eleicao">
			<?php wp_nonce_field( 'mural17_eleicao' ); ?>
			<div>
				<h2>Período eleitoral <?php echo mural17_periodo_eleitoral() ? '<span class="m17-pill m17-pill--erro">em vigor</span>' : ''; ?></h2>
				<p>Em vigor, notas de prefeituras, câmaras e órgãos municipais não são publicadas sozinhas, nem por quem tem publicação direta: tudo cai na fila, marcado, e você aprova só aviso urgente de utilidade pública. Judiciário, Ministério Público, Defensoria e polícias seguem normais. Confirme as datas com a assessoria jurídica.</p>
			</div>
			<p>
				<label>Início <input type="date" name="inicio" value="<?php echo esc_attr( get_option( 'mural17_eleitoral_inicio' ) ); ?>"></label>
				<label>Fim <input type="date" name="fim" value="<?php echo esc_attr( get_option( 'mural17_eleitoral_fim' ) ); ?>"></label>
				<label><input type="checkbox" name="ligado" value="1" <?php checked( (bool) get_option( 'mural17_eleitoral_ligado' ) ); ?>> Ligar agora, fora das datas</label>
				<button class="button">Salvar</button>
			</p>
		</form>

		<nav class="nav-tab-wrapper">
			<a class="nav-tab<?php echo 'fila' === $aba ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( $base ); ?>">Aguardando revisão (<?php echo (int) $fila->found_posts; ?>)</a>
			<a class="nav-tab<?php echo 'pedidos' === $aba ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'aba', 'pedidos', $base ) ); ?>">Pedidos de cadastro (<?php echo count( $ped ); ?>)</a>
			<a class="nav-tab<?php echo 'orgaos' === $aba ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'aba', 'orgaos', $base ) ); ?>">Órgãos ativos (<?php echo count( $ativ ); ?>)</a>
		</nav>

		<?php if ( 'fila' === $aba ) : ?>
			<?php if ( ! $fila->have_posts() ) : ?>
				<p class="m17-vazio">Nenhuma nota esperando. Bom trabalho.</p>
			<?php endif; ?>
			<?php
			foreach ( $fila->posts as $p ) :
				$o = mural17_orgao_do_post( $p->ID );
				$c = mural17_cidade( $p->ID );
				?>
				<div class="m17-item">
					<div class="m17-item__topo">
						<b><?php echo esc_html( $o ? $o->name : '' ); ?></b>
						<span><?php echo esc_html( implode( ' · ', array_filter( array( mural17_tipos_nota()[ get_post_meta( $p->ID, '_mural17_nota_tipo', true ) ] ?? '', $c ? $c->name : '', 'enviada ' . human_time_diff( get_post_time( 'U', true, $p ), time() ) . ' atrás' ) ) ) ); ?></span>
						<?php if ( get_post_meta( $p->ID, '_mural17_eleitoral', true ) ) : ?>
							<span class="m17-pill m17-pill--erro">Período eleitoral: aprove só se for aviso urgente</span>
						<?php endif; ?>
					</div>
					<h2 class="m17-item__titulo"><?php echo esc_html( get_the_title( $p ) ); ?></h2>
					<div class="m17-item__texto"><?php echo wp_kses_post( wp_trim_words( $p->post_content, 60 ) ); ?></div>
					<div class="m17-item__acoes">
						<a class="button button-primary" href="<?php echo esc_url( mural17_link_acao( 'mural17_aprovar', array( 'post' => $p->ID ) ) ); ?>">Aprovar e publicar</a>
						<a class="button" href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>">Revisar texto</a>
						<a class="button" href="<?php echo esc_url( get_preview_post_link( $p ) ); ?>" target="_blank" rel="noopener">Ver como fica</a>
						<details>
							<summary class="button">Devolver</summary>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="mural17_devolver">
								<input type="hidden" name="post" value="<?php echo (int) $p->ID; ?>">
								<?php wp_nonce_field( 'mural17_devolver' ); ?>
								<label>Motivo (o órgão vê este texto)<textarea name="motivo" rows="3" required></textarea></label>
								<button class="button">Devolver com este motivo</button>
							</form>
						</details>
					</div>
				</div>
			<?php endforeach; ?>

		<?php elseif ( 'pedidos' === $aba ) : ?>
			<?php if ( ! $ped ) : ?>
				<p class="m17-vazio">Nenhum pedido de cadastro.</p>
			<?php endif; ?>
			<?php
			foreach ( $ped as $o ) :
				$cid   = get_term( (int) mural17_orgao_meta( $o, 'cidade' ), 'cidade' );
				$email = mural17_orgao_meta( $o, 'email' );
				?>
				<div class="m17-item">
					<h2 class="m17-item__titulo"><?php echo esc_html( $o->name ); ?></h2>
					<table class="m17-dados">
						<tr><th>Tipo</th><td><?php echo esc_html( $tipos[ mural17_orgao_meta( $o, 'tipo' ) ]['nome'] ?? '' ); ?></td></tr>
						<tr><th>Cidade</th><td><?php echo esc_html( $cid && ! is_wp_error( $cid ) ? $cid->name : '' ); ?></td></tr>
						<tr><th>E-mail</th><td><?php echo esc_html( $email ); ?> <?php echo mural17_email_oficial( $email ) ? '<span class="m17-pill m17-pill--ok">domínio oficial</span>' : '<span class="m17-pill m17-pill--erro">não é institucional</span>'; ?></td></tr>
						<tr><th>Responsável</th><td><?php echo esc_html( trim( mural17_orgao_meta( $o, 'responsavel' ) . ' · ' . mural17_orgao_meta( $o, 'cargo' ), ' ·' ) ); ?></td></tr>
						<tr><th>Telefone</th><td><?php echo esc_html( mural17_orgao_meta( $o, 'telefone' ) ); ?> <span class="m17-pill">ligue para confirmar antes de liberar</span></td></tr>
						<tr><th>Site</th><td><?php echo mural17_orgao_meta( $o, 'site' ) ? '<a href="' . esc_url( mural17_orgao_meta( $o, 'site' ) ) . '" target="_blank" rel="noopener">' . esc_html( mural17_orgao_meta( $o, 'site' ) ) . '</a>' : '—'; ?></td></tr>
					</table>
					<div class="m17-item__acoes">
						<a class="button button-primary" href="<?php echo esc_url( mural17_link_acao( 'mural17_liberar', array( 'orgao' => $o->term_id ) ) ); ?>">Liberar acesso</a>
						<a class="button" href="<?php echo esc_url( mural17_link_acao( 'mural17_recusar', array( 'orgao' => $o->term_id ) ) ); ?>" onclick="return window.confirm('Recusar o pedido e apagar a conta?');">Recusar</a>
					</div>
				</div>
			<?php endforeach; ?>

		<?php else : ?>
			<table class="widefat striped m17-orgaos">
				<thead><tr><th>Órgão</th><th>Tipo</th><th>Cidade</th><th>Notas</th><th>Devolvidas</th><th>Publicação direta</th></tr></thead>
				<tbody>
					<?php
					foreach ( $ativ as $o ) :
						$cid    = get_term( (int) mural17_orgao_meta( $o, 'cidade' ), 'cidade' );
						$dev    = (int) mural17_orgao_meta( $o, 'devolvidas' );
						$direta = (bool) mural17_orgao_meta( $o, 'direta' );
						?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_term_link( $o, 'orgao' ) ); ?>"><b><?php echo esc_html( $o->name ); ?></b></a> · <a href="<?php echo esc_url( get_term_link( $o ) ); ?>">ver página</a></td>
							<td><?php echo esc_html( $tipos[ mural17_orgao_meta( $o, 'tipo' ) ]['nome'] ?? '' ); ?></td>
							<td><?php echo esc_html( $cid && ! is_wp_error( $cid ) ? $cid->name : '' ); ?></td>
							<td><?php echo (int) $o->count; ?></td>
							<td><?php echo (int) $dev; ?></td>
							<td>
								<a class="button<?php echo $direta ? ' button-primary' : ''; ?>" href="<?php echo esc_url( mural17_link_acao( 'mural17_direta', array( 'orgao' => $o->term_id, 'valor' => $direta ? 0 : 1 ) ) ); ?>"><?php echo $direta ? 'Ligada · desligar' : 'Desligada · ligar'; ?></a>
								<?php if ( ! $direta && $o->count >= 10 && 0 === $dev ) : ?>
									<span class="m17-pill m17-pill--ok">10+ notas sem devolução</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( ! $ativ ) : ?>
						<tr><td colspan="6">Nenhum órgão ativo ainda. A página de cadastro é <a href="<?php echo esc_url( mural17_link_oficial( 'cadastro' ) ); ?>"><?php echo esc_html( mural17_link_oficial( 'cadastro' ) ); ?></a>.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Confere permissão e nonce das ações da redação.
 *
 * @param string $acao Ação.
 */
function mural17_confere_redacao( $acao ) {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Sem permissão.', 403 );
	}
	check_admin_referer( $acao );
}

/**
 * Volta para a tela da redação.
 *
 * @param string $aba   Aba.
 * @param string $feito Mensagem.
 */
function mural17_volta_redacao( $aba, $feito ) {
	wp_safe_redirect( add_query_arg( array( 'aba' => $aba, 'feito' => rawurlencode( $feito ) ), admin_url( 'edit.php?page=mural17-notas' ) ) );
	exit;
}

/**
 * E-mail para a conta do órgão.
 *
 * @param WP_Term $orgao   Órgão.
 * @param string  $assunto Assunto.
 * @param string  $texto   Texto.
 */
function mural17_avisa_orgao( $orgao, $assunto, $texto ) {
	$email = mural17_orgao_meta( $orgao, 'email' );
	if ( $email ) {
		wp_mail( $email, '[Mural 17] ' . $assunto, $texto . "\n\nPainel do órgão: " . mural17_link_oficial( 'painel' ) );
	}
}

/**
 * Aprova e publica uma nota.
 */
function mural17_acao_aprovar() {
	mural17_confere_redacao( 'mural17_aprovar' );
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$o  = $id ? mural17_orgao_do_post( $id ) : null;
	if ( $o && 'pending' === get_post_status( $id ) ) {
		// wp_update_post (e não wp_publish_post) para criar o endereço da nota, que fica vazio enquanto está pendente.
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
				'post_name'   => wp_unique_post_slug( sanitize_title( get_the_title( $id ) ), $id, 'publish', 'post', 0 ),
			)
		);
		update_post_meta( $id, '_mural17_aprovada_em', current_time( 'mysql' ) );
		mural17_avisa_orgao( $o, 'Nota publicada: ' . get_the_title( $id ), 'Sua nota foi aprovada e já está no ar: ' . get_permalink( $id ) );
	}
	mural17_volta_redacao( 'fila', 'Nota publicada.' );
}
add_action( 'admin_post_mural17_aprovar', 'mural17_acao_aprovar' );

/**
 * Devolve uma nota com o motivo.
 */
function mural17_acao_devolver() {
	mural17_confere_redacao( 'mural17_devolver' );
	$id     = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
	$motivo = isset( $_POST['motivo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['motivo'] ) ) : '';
	$o      = $id ? mural17_orgao_do_post( $id ) : null;
	if ( $o && $motivo ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'devolvida' ) );
		update_post_meta( $id, '_mural17_motivo_devolucao', $motivo );
		update_term_meta( $o->term_id, 'mural17_devolvidas', (int) mural17_orgao_meta( $o, 'devolvidas' ) + 1 );
		mural17_avisa_orgao( $o, 'Nota devolvida: ' . get_the_title( $id ), "A redação devolveu sua nota com este comentário:\n\n" . $motivo . "\n\nVocê pode editar e reenviar pelo painel." );
	}
	mural17_volta_redacao( 'fila', 'Nota devolvida ao órgão.' );
}
add_action( 'admin_post_mural17_devolver', 'mural17_acao_devolver' );

/**
 * Libera o acesso de um órgão.
 */
function mural17_acao_liberar() {
	mural17_confere_redacao( 'mural17_liberar' );
	$id = isset( $_GET['orgao'] ) ? absint( $_GET['orgao'] ) : 0;
	$o  = $id ? get_term( $id, 'orgao' ) : null;
	if ( $o && ! is_wp_error( $o ) ) {
		update_term_meta( $id, 'mural17_status', 'ativo' );
		update_term_meta( $id, 'mural17_desde', current_time( 'mysql' ) );
		$uid = (int) mural17_orgao_meta( $o, 'usuario' );
		if ( $uid ) {
			$u = new WP_User( $uid );
			$u->set_role( 'orgao_oficial' );
			// E-mail padrão do WordPress com o link para criar a senha.
			wp_new_user_notification( $uid, null, 'user' );
		}
	}
	mural17_volta_redacao( 'pedidos', 'Acesso liberado. O órgão recebeu o link para criar a senha.' );
}
add_action( 'admin_post_mural17_liberar', 'mural17_acao_liberar' );

/**
 * Recusa um pedido: avisa, apaga a conta e o órgão.
 */
function mural17_acao_recusar() {
	mural17_confere_redacao( 'mural17_recusar' );
	$id = isset( $_GET['orgao'] ) ? absint( $_GET['orgao'] ) : 0;
	$o  = $id ? get_term( $id, 'orgao' ) : null;
	if ( $o && ! is_wp_error( $o ) && 'pendente' === mural17_orgao_meta( $o, 'status' ) ) {
		mural17_avisa_orgao( $o, 'Pedido de cadastro não aprovado', 'Não conseguimos confirmar o cadastro de ' . $o->name . '. Se for um engano, responda este e-mail com um telefone oficial para contato.' );
		$uid = (int) mural17_orgao_meta( $o, 'usuario' );
		if ( $uid ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $uid );
		}
		wp_delete_term( $id, 'orgao' );
	}
	mural17_volta_redacao( 'pedidos', 'Pedido recusado.' );
}
add_action( 'admin_post_mural17_recusar', 'mural17_acao_recusar' );

/**
 * Liga ou desliga a publicação direta.
 */
function mural17_acao_direta() {
	mural17_confere_redacao( 'mural17_direta' );
	$id = isset( $_GET['orgao'] ) ? absint( $_GET['orgao'] ) : 0;
	if ( $id ) {
		update_term_meta( $id, 'mural17_direta', ! empty( $_GET['valor'] ) ? 1 : 0 );
	}
	mural17_volta_redacao( 'orgaos', 'Publicação direta atualizada.' );
}
add_action( 'admin_post_mural17_direta', 'mural17_acao_direta' );

/**
 * Salva o período eleitoral.
 */
function mural17_acao_eleicao() {
	mural17_confere_redacao( 'mural17_eleicao' );
	$data = function ( $k ) {
		$v = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
	};
	update_option( 'mural17_eleitoral_inicio', $data( 'inicio' ) );
	update_option( 'mural17_eleitoral_fim', $data( 'fim' ) );
	update_option( 'mural17_eleitoral_ligado', ! empty( $_POST['ligado'] ) ? 1 : 0 );
	mural17_volta_redacao( 'fila', 'Período eleitoral salvo.' );
}
add_action( 'admin_post_mural17_eleicao', 'mural17_acao_eleicao' );

/* -------------------------------------------------------------------------
 * Campos do órgão em Posts › Órgãos oficiais
 * ---------------------------------------------------------------------- */

/**
 * Campos na edição do órgão.
 *
 * @param WP_Term $term Órgão.
 */
function mural17_campos_orgao( $term ) {
	wp_enqueue_media();
	$v      = function ( $k ) use ( $term ) {
		return mural17_orgao_meta( $term, $k );
	};
	$logo   = (int) $v( 'logo' );
	$status = $v( 'status' ) ? $v( 'status' ) : 'ativo';
	wp_nonce_field( 'mural17_orgao_campos', 'mural17_orgao_nonce' );
	?>
	<tr class="form-field"><th><label for="m17-tipo">Tipo</label></th><td>
		<select id="m17-tipo" name="mural17_tipo">
			<?php foreach ( mural17_tipos_orgao() as $k => $t ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $v( 'tipo' ), $k ); ?>><?php echo esc_html( $t['nome'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</td></tr>
	<tr class="form-field"><th><label for="m17-cidade">Cidade</label></th><td>
		<select id="m17-cidade" name="mural17_cidade"><option value="">—</option><?php mural17_opcoes_cidades( (int) $v( 'cidade' ) ); ?></select>
		<p class="description">As notas do órgão entram nesta cidade.</p>
	</td></tr>
	<tr class="form-field"><th><label for="m17-status">Situação</label></th><td>
		<select id="m17-status" name="mural17_status">
			<option value="ativo" <?php selected( $status, 'ativo' ); ?>>Ativo (pode enviar notas)</option>
			<option value="pendente" <?php selected( $status, 'pendente' ); ?>>Aguardando aprovação</option>
			<option value="suspenso" <?php selected( $status, 'suspenso' ); ?>>Suspenso</option>
		</select>
	</td></tr>
	<tr class="form-field"><th>Publicação direta</th><td><label><input type="checkbox" name="mural17_direta" value="1" <?php checked( (bool) $v( 'direta' ) ); ?> style="width:auto"> Publica sem passar pela fila (nunca no período eleitoral, para prefeituras e câmaras)</label></td></tr>
	<tr class="form-field"><th><label for="m17-email">E-mail institucional</label></th><td><input id="m17-email" name="mural17_email" value="<?php echo esc_attr( $v( 'email' ) ); ?>"></td></tr>
	<tr class="form-field"><th><label for="m17-tel">Telefone oficial</label></th><td><input id="m17-tel" name="mural17_telefone" value="<?php echo esc_attr( $v( 'telefone' ) ); ?>"></td></tr>
	<tr class="form-field"><th><label for="m17-site">Site oficial</label></th><td><input id="m17-site" name="mural17_site" type="url" value="<?php echo esc_attr( $v( 'site' ) ); ?>"></td></tr>
	<tr class="form-field"><th>Brasão</th><td>
		<div id="m17-logo-prev"><?php echo $logo ? wp_get_attachment_image( $logo, 'thumbnail' ) : ''; ?></div>
		<input type="hidden" id="m17-logo" name="mural17_logo" value="<?php echo (int) $logo; ?>">
		<button type="button" class="button" id="m17-logo-escolher">Escolher imagem</button>
		<button type="button" class="button-link" id="m17-logo-tirar">Tirar</button>
		<script>
		( function () {
			var campo = document.getElementById( 'm17-logo' ), prev = document.getElementById( 'm17-logo-prev' ), frame;
			document.getElementById( 'm17-logo-escolher' ).addEventListener( 'click', function () {
				frame = frame || wp.media( { title: 'Brasão do órgão', library: { type: 'image' }, multiple: false } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					campo.value = a.id;
					prev.innerHTML = '';
					var img = document.createElement( 'img' );
					img.src = ( a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url );
					img.width = 96;
					prev.appendChild( img );
				} );
				frame.open();
			} );
			document.getElementById( 'm17-logo-tirar' ).addEventListener( 'click', function () { campo.value = ''; prev.innerHTML = ''; } );
		}() );
		</script>
	</td></tr>
	<?php
}
add_action( 'orgao_edit_form_fields', 'mural17_campos_orgao' );

/**
 * Salva os campos do órgão.
 *
 * @param int $term_id Órgão.
 */
function mural17_salva_orgao( $term_id ) {
	if ( ! isset( $_POST['mural17_orgao_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_orgao_nonce'] ) ), 'mural17_orgao_campos' ) || ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	foreach ( array( 'tipo', 'status' ) as $k ) {
		update_term_meta( $term_id, 'mural17_' . $k, isset( $_POST[ 'mural17_' . $k ] ) ? sanitize_key( wp_unslash( $_POST[ 'mural17_' . $k ] ) ) : '' );
	}
	foreach ( array( 'email', 'telefone' ) as $k ) {
		update_term_meta( $term_id, 'mural17_' . $k, isset( $_POST[ 'mural17_' . $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'mural17_' . $k ] ) ) : '' );
	}
	update_term_meta( $term_id, 'mural17_site', isset( $_POST['mural17_site'] ) ? esc_url_raw( wp_unslash( $_POST['mural17_site'] ) ) : '' );
	update_term_meta( $term_id, 'mural17_cidade', isset( $_POST['mural17_cidade'] ) ? absint( $_POST['mural17_cidade'] ) : 0 );
	update_term_meta( $term_id, 'mural17_logo', isset( $_POST['mural17_logo'] ) ? absint( $_POST['mural17_logo'] ) : 0 );
	update_term_meta( $term_id, 'mural17_direta', ! empty( $_POST['mural17_direta'] ) ? 1 : 0 );
}
add_action( 'edited_orgao', 'mural17_salva_orgao' );

/**
 * Caixa "Nota oficial" na edição do post, para a redação marcar ou trocar o órgão.
 */
function mural17_caixa_nota() {
	add_meta_box(
		'mural17-nota',
		'Nota oficial',
		function ( $post ) {
			$atual = mural17_orgao_do_post( $post->ID );
			$todos = get_terms(
				array(
					'taxonomy'   => 'orgao',
					'hide_empty' => false,
				)
			);
			wp_nonce_field( 'mural17_caixa_nota', 'mural17_caixa_nonce' );
			echo '<p><label for="m17-orgao-post">Órgão</label><br><select id="m17-orgao-post" name="mural17_orgao_post" style="width:100%"><option value="">Não é nota oficial</option>';
			foreach ( is_wp_error( $todos ) ? array() : $todos as $t ) {
				printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $t->term_id, selected( $atual ? $atual->term_id : 0, $t->term_id, false ), esc_html( $t->name ) );
			}
			echo '</select></p><p><label for="m17-nota-tipo">Tipo</label><br><select id="m17-nota-tipo" name="mural17_nota_tipo" style="width:100%">';
			foreach ( mural17_tipos_nota() as $k => $n ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $k ), selected( get_post_meta( $post->ID, '_mural17_nota_tipo', true ), $k, false ), esc_html( $n ) );
			}
			echo '</select></p>';
			foreach ( array( 'servico_quando' => 'Quando', 'servico_onde' => 'Onde', 'servico_levar' => 'O que levar', 'servico_contato' => 'Contato' ) as $k => $rot ) {
				printf( '<p><label for="m17-%1$s">%2$s</label><br><input id="m17-%1$s" name="mural17_%1$s" value="%3$s" style="width:100%%"></p>', esc_attr( $k ), esc_html( $rot ), esc_attr( get_post_meta( $post->ID, '_mural17_' . $k, true ) ) );
			}
		},
		'post',
		'side'
	);
}
add_action( 'add_meta_boxes', 'mural17_caixa_nota' );

/**
 * Salva a caixa "Nota oficial".
 *
 * @param int $post_id Post.
 */
function mural17_salva_caixa_nota( $post_id ) {
	if ( ! isset( $_POST['mural17_caixa_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_caixa_nonce'] ) ), 'mural17_caixa_nota' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$orgao = isset( $_POST['mural17_orgao_post'] ) ? absint( $_POST['mural17_orgao_post'] ) : 0;
	wp_set_object_terms( $post_id, $orgao ? array( $orgao ) : array(), 'orgao' );
	if ( $orgao ) {
		$tipo = isset( $_POST['mural17_nota_tipo'] ) ? sanitize_key( wp_unslash( $_POST['mural17_nota_tipo'] ) ) : 'servico';
		update_post_meta( $post_id, '_mural17_nota_tipo', $tipo );
	}
	foreach ( array( 'servico_quando', 'servico_onde', 'servico_levar', 'servico_contato' ) as $k ) {
		$v = isset( $_POST[ 'mural17_' . $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'mural17_' . $k ] ) ) : '';
		if ( '' === $v ) {
			delete_post_meta( $post_id, '_mural17_' . $k );
		} else {
			update_post_meta( $post_id, '_mural17_' . $k, $v );
		}
	}
}
add_action( 'save_post_post', 'mural17_salva_caixa_nota' );

/**
 * Estilo da tela da redação.
 *
 * @param string $hook Tela.
 */
function mural17_css_redacao( $hook ) {
	if ( 'posts_page_mural17-notas' !== $hook ) {
		return;
	}
	wp_add_inline_style(
		'common',
		'.m17-redacao .m17-eleicao{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:flex-end;justify-content:space-between;background:#fff;border:2px solid #FFC531;border-radius:10px;padding:14px 18px;margin:16px 0}'
		. '.m17-redacao .m17-eleicao.is-ligado{border-color:#E5232D}.m17-redacao .m17-eleicao h2{margin:0 0 4px}.m17-redacao .m17-eleicao>div{flex:1 1 420px}.m17-redacao .m17-eleicao label{margin-right:12px}'
		. '.m17-item{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 18px;margin:14px 0;max-width:900px}.m17-item__topo{display:flex;gap:10px;flex-wrap:wrap;align-items:center;color:#50575e}'
		. '.m17-item__titulo{margin:8px 0;font-size:19px}.m17-item__acoes{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;margin-top:10px}.m17-item__acoes details form{margin-top:8px;display:flex;flex-direction:column;gap:6px;min-width:360px}'
		. '.m17-item__acoes textarea{width:100%}.m17-pill{display:inline-block;border-radius:999px;padding:2px 9px;font-size:12px;font-weight:600;background:#f0f0f1}.m17-pill--ok{background:#E2F3EA;color:#0B5E31}.m17-pill--erro{background:#FDE3E4;color:#A3141B}'
		. '.m17-dados th{text-align:left;padding:3px 16px 3px 0;color:#50575e;font-weight:600}.m17-vazio{font-size:15px;margin-top:20px}'
	);
}
add_action( 'admin_enqueue_scripts', 'mural17_css_redacao' );
