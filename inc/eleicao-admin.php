<?php
/**
 * Painel do especial de apuração: liga/desliga, horários, códigos do TSE e
 * o teste com os dados do 1º turno.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'mural17-servicos', 'Eleição 2026', 'Eleição 2026', 'manage_options', 'mural17-eleicao', 'mural17_pagina_eleicao_admin' );
	},
	20
);

/**
 * Tela Eleição 2026.
 */
function mural17_pagina_eleicao_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c       = mural17_eleicao_config();
	$d       = get_option( 'mural17_apuracao' );
	$t       = get_option( 'mural17_apuracao_teste' );
	$erro    = get_option( 'mural17_apuracao_erro' );
	$erro_t  = get_option( 'mural17_apuracao_teste_erro' );
	$res     = get_transient( 'mural17_eleicao_resultado' );
	$achados = (array) get_option( 'mural17_eleicao_codigos' );
	$lista   = get_transient( 'mural17_eleicao_lista' );
	delete_transient( 'mural17_eleicao_resultado' );
	$form = function ( $acao ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px">';
		echo '<input type="hidden" name="action" value="mural17_especial">';
		echo '<input type="hidden" name="o_que" value="' . esc_attr( $acao ) . '">';
		wp_nonce_field( 'mural17_especial', 'mural17_nonce' );
	};
	$resumo = function ( $x ) {
		if ( ! is_array( $x ) || empty( $x['br']['cand'] ) ) {
			return 'sem leitura';
		}
		$p = array();
		foreach ( $x['br']['cand'] as $cand ) {
			$p[] = $cand['nome'] . ' ' . number_format_i18n( $cand['pct'], 2 ) . '%';
		}
		return wp_date( 'd/m H:i:s', $x['em'] ) . ' · Brasil ' . number_format_i18n( $x['br']['pst'], 2 ) . '% apurado · ' . implode( ' × ', $p ) . ' · ' . count( $x['cid'] ) . ' de 117 cidades';
	};
	?>
	<div class="wrap">
		<h1>Eleição 2026: apuração ao vivo</h1>
		<?php if ( $res ) : ?>
			<div class="notice notice-<?php echo esc_attr( $res[0] ); ?> is-dismissible"><p><?php echo esc_html( $res[1] ); ?></p></div>
		<?php endif; ?>

		<p><b>Agora:</b> <?php echo mural17_eleicao_ativa() ? '<span style="color:#b32d2e;font-weight:700">● especial NO AR</span>' : 'especial fora do ar'; ?>
			· página: <a href="<?php echo esc_url( home_url( '/eleicoes-2026/' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/eleicoes-2026/' ) ); ?></a></p>

		<h2>Quando fica no ar</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mural17_especial">
			<input type="hidden" name="o_que" value="salvar">
			<?php wp_nonce_field( 'mural17_especial', 'mural17_nonce' ); ?>
			<table class="form-table">
				<tr><th>Modo</th><td>
					<label><input type="radio" name="modo" value="auto" <?php checked( $c['modo'], 'auto' ); ?>> Automático, nos horários abaixo</label><br>
					<label><input type="radio" name="modo" value="ligado" <?php checked( $c['modo'], 'ligado' ); ?>> Ligado agora</label><br>
					<label><input type="radio" name="modo" value="desligado" <?php checked( $c['modo'], 'desligado' ); ?>> Desligado</label>
				</td></tr>
				<tr><th><label for="el-ini">Entra no ar</label></th><td><input id="el-ini" type="datetime-local" name="inicio" value="<?php echo esc_attr( $c['inicio'] ); ?>"> <span class="description">faixa no topo e destaque na capa, com contagem até o fechamento</span></td></tr>
				<tr><th><label for="el-fec">Urnas fecham</label></th><td><input id="el-fec" type="datetime-local" name="fechamento" value="<?php echo esc_attr( $c['fechamento'] ); ?>"> <span class="description">a leitura do TSE começa 5 minutos antes</span></td></tr>
				<tr><th>Fotos dos candidatos</th><td>
					<?php foreach ( (array) $c['fotos'] + array( '' => '' ) as $num => $url ) : ?>
						<p class="m17-foto-cand">
							<label>Número <input name="foto_num[]" value="<?php echo esc_attr( $num ); ?>" size="4" inputmode="numeric"></label>
							<input name="foto_url[]" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="endereço da imagem">
							<button type="button" class="button" data-m17-midia>Escolher na Biblioteca</button>
							<?php if ( $url ) : ?><img src="<?php echo esc_url( $url ); ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;vertical-align:middle"><?php endif; ?>
						</p>
					<?php endforeach; ?>
					<p class="description">Use as fotos oficiais de registro (as da urna), quadradas, com o rosto no centro. Sem foto, o placar mostra a inicial do nome.</p>
				</td></tr>
				<tr><th>Redes sociais</th><td><label><input type="checkbox" name="publicar" value="1" <?php checked( ! empty( $c['publicar'] ) ); ?>> Publicar os marcos principais como matéria, para o Portal PRO postar no Instagram e no Facebook</label><p class="description">Brasil passa de 50% e 90%, termina a apuração, virada, eleito declarado pelo TSE, e quando Rio Preto, Catanduva, Votuporanga, Fernandópolis, Jales, Barretos e Santa Fé do Sul fecham a contagem. No teste com o 1º turno nada é publicado.</p></td></tr>
				<tr><th><label for="el-fim">Sai do ar</label></th><td><input id="el-fim" type="datetime-local" name="fim" value="<?php echo esc_attr( $c['fim'] ); ?>"> <span class="description">a página /eleicoes-2026/ continua com o resultado final</span></td></tr>
			</table>
			<details>
				<summary>Avançado: endereços e códigos do TSE</summary>
				<table class="form-table">
					<tr><th>Endereço base</th><td><input class="regular-text" name="base" value="<?php echo esc_attr( $c['base'] ); ?>"></td></tr>
					<tr><th>Pasta da eleição</th><td><input name="pasta" value="<?php echo esc_attr( $c['pasta'] ); ?>"></td></tr>
					<tr><th>Código do 2º turno</th><td><input name="eleicao" value="<?php echo esc_attr( $c['eleicao'] ); ?>" placeholder="<?php echo esc_attr( $achados['eleicao'] ?? 'automático' ); ?>"> <span class="description">vazio = descobre no TSE (achado: <?php echo esc_html( $achados['eleicao'] ?? 'ainda não' ); ?>)</span></td></tr>
					<tr><th>Código do 1º turno</th><td><input name="eleicao_teste" value="<?php echo esc_attr( $c['eleicao_teste'] ); ?>" placeholder="<?php echo esc_attr( $achados['eleicao_teste'] ?? 'automático' ); ?>"> <span class="description">usado no teste (achado: <?php echo esc_html( $achados['eleicao_teste'] ?? 'ainda não' ); ?>)</span></td></tr>
				</table>
			</details>
			<?php submit_button( 'Salvar' ); ?>
		</form>

		<h2>Teste antes de domingo</h2>
		<p>Lê os resultados do <b>1º turno</b> (4 de outubro) do TSE, do mesmo jeito que o domingo vai ler, e mostra o especial com eles numa prévia que só administradores veem.</p>
		<p><b>Último teste:</b> <?php echo esc_html( $resumo( $t ) ); ?></p>
		<?php if ( is_array( $erro_t ) ) : ?>
			<p style="color:#b32d2e"><b>Erro em <?php echo esc_html( wp_date( 'd/m H:i', $erro_t[1] ) ); ?>:</b> <?php echo esc_html( $erro_t[0] ); ?></p>
		<?php endif; ?>
		<?php $form( 'teste' ); ?><?php submit_button( 'Testar com o 1º turno', 'primary', 'submit', false ); ?></form>
		<?php if ( is_array( $t ) ) : ?>
			<a class="button" href="<?php echo esc_url( home_url( '/eleicoes-2026/?teste=1' ) ); ?>" target="_blank">Ver a prévia do especial</a>
			<a class="button" href="<?php echo esc_url( home_url( '/?teste=1' ) ); ?>" target="_blank">Ver a capa com o destaque</a>
		<?php endif; ?>
		<?php $form( 'lista' ); ?><?php submit_button( 'Ver eleições publicadas no TSE', 'secondary', 'submit', false ); ?></form>
		<?php if ( is_array( $lista ) ) : ?>
			<table class="widefat striped" style="max-width:720px;margin-top:12px"><thead><tr><th>Código</th><th>Eleição</th><th>Turno</th><th>Data</th><th>Pasta</th></tr></thead><tbody>
				<?php foreach ( $lista as $e ) : ?>
					<tr><td><?php echo esc_html( $e['cd'] ); ?></td><td><?php echo esc_html( $e['nm'] ); ?></td><td><?php echo esc_html( $e['turno'] ); ?></td><td><?php echo esc_html( $e['data'] ); ?></td><td><?php echo esc_html( $e['pasta'] ); ?></td></tr>
				<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>

		<h2>Domingo</h2>
		<p><b>Última leitura:</b> <?php echo esc_html( $resumo( $d ) ); ?></p>
		<?php if ( is_array( $erro ) ) : ?>
			<p style="color:#b32d2e"><b>Erro em <?php echo esc_html( wp_date( 'd/m H:i', $erro[1] ) ); ?>:</b> <?php echo esc_html( $erro[0] ); ?></p>
		<?php endif; ?>
		<?php $form( 'agora' ); ?><?php submit_button( 'Ler o TSE agora', 'secondary', 'submit', false ); ?></form>
		<p class="description">No ar, o site lê sozinho a cada minuto (quando há visitantes na página) e a cada 2 minutos pelo agendador. As notícias da categoria <b>Eleições 2026</b> entram no minuto a minuto; matérias importadas sobre a eleição no dia entram nela sozinhas.</p>
	</div>
	<?php
}

add_action(
	'admin_enqueue_scripts',
	function ( $tela ) {
		if ( false === strpos( (string) $tela, 'mural17-eleicao' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			"document.addEventListener('click',function(e){var b=e.target.closest('[data-m17-midia]');if(!b)return;e.preventDefault();var f=wp.media({title:'Foto do candidato',library:{type:'image'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();b.previousElementSibling.value=(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url);});f.open();});"
		);
	}
);

add_action(
	'admin_post_mural17_especial',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_especial' ) ) {
			wp_die( 'Sem permissão.' );
		}
		$o_que = sanitize_key( wp_unslash( $_POST['o_que'] ?? '' ) );
		$volta = function ( $tipo, $msg ) {
			set_transient( 'mural17_eleicao_resultado', array( $tipo, $msg ), 120 );
			wp_safe_redirect( admin_url( 'admin.php?page=mural17-eleicao' ) );
			exit;
		};
		if ( 'salvar' === $o_que ) {
			$c = mural17_eleicao_config();
			$c['modo'] = in_array( $_POST['modo'] ?? '', array( 'auto', 'ligado', 'desligado' ), true ) ? sanitize_key( wp_unslash( $_POST['modo'] ) ) : 'auto';
			foreach ( array( 'inicio', 'fechamento', 'fim' ) as $k ) {
				$v = sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) );
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $v ) ) {
					$c[ $k ] = $v;
				}
			}
			$c['publicar']      = empty( $_POST['publicar'] ) ? 0 : 1;
			$c['fotos']         = array();
			$nums               = array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['foto_num'] ?? array() ) );
			$urls               = (array) wp_unslash( $_POST['foto_url'] ?? array() );
			foreach ( $nums as $i => $n ) {
				$n = preg_replace( '/\D/', '', $n );
				if ( '' !== $n ) {
					$c['fotos'][ $n ] = esc_url_raw( $urls[ $i ] ?? '' );
				}
			}
			$c['base']          = esc_url_raw( wp_unslash( $_POST['base'] ?? $c['base'] ) );
			$c['pasta']         = sanitize_key( wp_unslash( $_POST['pasta'] ?? $c['pasta'] ) );
			$c['eleicao']       = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['eleicao'] ?? '' ) );
			$c['eleicao_teste'] = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['eleicao_teste'] ?? '' ) );
			update_option( 'mural17_eleicao', $c, false );
			$volta( 'success', 'Salvo.' );
		}
		if ( 'lista' === $o_que ) {
			$l = mural17_tse_eleicoes();
			if ( is_wp_error( $l ) ) {
				$volta( 'error', 'Não consegui ler o TSE: ' . $l->get_error_message() );
			}
			set_transient( 'mural17_eleicao_lista', $l, HOUR_IN_SECONDS );
			$volta( 'success', count( $l ) . ' eleição(ões) publicadas no TSE.' );
		}
		if ( 'teste' === $o_que || 'agora' === $o_que ) {
			$teste = 'teste' === $o_que;
			delete_option( 'mural17_eleicao_codigos' ); // Descobre de novo, caso o TSE tenha publicado o 2º turno.
			$r = mural17_apuracao_atualiza( $teste );
			if ( is_wp_error( $r ) ) {
				$volta( 'error', 'Falhou: ' . $r->get_error_message() );
			}
			$msg = 'Leitura feita: Brasil ' . number_format_i18n( $r['br']['pst'], 2 ) . '% apurado, ' . $r['cidades'] . ' de 117 cidades do 17 lidas.';
			if ( $r['mun_erro'] ) {
				$msg .= ' Lista de municípios do TSE falhou: ' . $r['mun_erro'];
			} elseif ( $r['cidades'] < 117 ) {
				$msg .= ' ' . ( 117 - $r['cidades'] ) . ' cidade(s) sem resultado ainda.';
			}
			$volta( $r['cidades'] >= 110 || ! $teste ? 'success' : 'warning', $msg );
		}
		$volta( 'error', 'Ação desconhecida.' );
	}
);
