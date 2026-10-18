<?php
/**
 * Convites aos órgãos: envio em massa, saindo de contato@mural17.com.br,
 * para as prefeituras e câmaras do 17 (lista levantada nos sites oficiais).
 *
 * O envio usa o wp_mail do próprio site, em lotes pela fila (o servidor de
 * e-mail da hospedagem limita quantos saem por hora), e nunca manda duas
 * vezes para o mesmo órgão sem pedir.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quantos e-mails saem a cada rodada da fila (a cada 5 minutos).
 */
const MURAL17_CONVITES_LOTE = 15;

/**
 * Remetente dos convites.
 *
 * @return string
 */
function mural17_convites_remetente() {
	return apply_filters( 'mural17_convites_remetente', 'contato@mural17.com.br' );
}

/**
 * Contatos: os da planilha (data/convites-orgaos.json) mais os adicionados no
 * painel, com o e-mail trocado quando foi corrigido no painel.
 *
 * @return array<string, array> id => contato.
 */
function mural17_convites_contatos() {
	static $lista = null;
	if ( null !== $lista ) {
		return $lista;
	}
	$base  = json_decode( (string) file_get_contents( MURAL17_DIR . '/data/convites-orgaos.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$base  = is_array( $base ) ? $base : array();
	$base  = array_merge( $base, (array) get_option( 'mural17_convites_extra', array() ) );
	$troca = (array) get_option( 'mural17_convites_email', array() );
	$lista = array();
	foreach ( $base as $c ) {
		$id = substr( md5( mural17_normaliza( $c['cidade'] . ' ' . $c['orgao'] ) ), 0, 10 );
		if ( isset( $troca[ $id ] ) ) {
			$c['email']    = $troca[ $id ];
			$c['situacao'] = $troca[ $id ] ? 'Pronto' : 'Sem e-mail';
		}
		$c['id']     = $id;
		$lista[ $id ] = $c;
	}
	return $lista;
}

/**
 * Situação de envio de cada contato.
 *
 * @return array<string, array> id => array( enviado?, fila?, erro?, fora? ).
 */
function mural17_convites_status() {
	return (array) get_option( 'mural17_convites_status', array() );
}

/**
 * Quantos convites estão na fila.
 *
 * @param array $st Situação de envio.
 * @return int
 */
function mural17_convites_na_fila( $st ) {
	return count(
		array_filter(
			$st,
			function ( $s ) {
				return ! empty( $s['fila'] );
			}
		)
	);
}

/**
 * Órgãos já cadastrados no portal, por cidade e tipo ("rio-preto|prefeitura").
 *
 * @return array<string, string> chave => status do cadastro (ativo, pendente…).
 */
function mural17_convites_cadastrados() {
	$r = array();
	foreach ( get_terms( array( 'taxonomy' => 'orgao', 'hide_empty' => false ) ) as $o ) {
		$cid  = (int) mural17_orgao_meta( $o, 'cidade' );
		$tipo = mural17_orgao_meta( $o, 'tipo' );
		if ( $cid && in_array( $tipo, array( 'prefeitura', 'camara' ), true ) ) {
			$r[ $cid . '|' . $tipo ] = mural17_orgao_meta( $o, 'status' );
		}
	}
	return $r;
}

/**
 * Cadastro no portal do órgão de um contato, ou ''.
 *
 * @param array $c   Contato.
 * @param array $cad Saída de mural17_convites_cadastrados().
 * @return string
 */
function mural17_convites_cadastro_de( $c, $cad ) {
	$mapa = mural17_mapa_nomes_cidades();
	$cid  = $mapa[ mural17_normaliza( $c['cidade'] ) ] ?? 0;
	$tipo = 'Câmara' === $c['orgao'] ? 'camara' : 'prefeitura';
	return (string) ( $cad[ $cid . '|' . $tipo ] ?? '' );
}

/**
 * Nome do órgão por extenso ("Câmara Municipal de Adolfo").
 *
 * @param array $c Contato.
 * @return string
 */
function mural17_convites_nome( $c ) {
	return ( 'Câmara' === $c['orgao'] ? 'Câmara Municipal' : 'Prefeitura' ) . ' de ' . $c['cidade'];
}

/**
 * Modelo do convite (assunto e texto), com o padrão do Mural 17.
 *
 * @return array{assunto:string,texto:string}
 */
function mural17_convites_modelo() {
	$m = (array) get_option( 'mural17_convites_modelo', array() );
	return wp_parse_args(
		$m,
		array(
			'assunto' => 'Convite: {orgao} no Mural 17, o portal das cidades do 17',
			'texto'   => "Prezados,\n\n"
				. "O Mural 17 (mural17.com.br) é o novo portal de notícias e serviços das 117 cidades do DDD 17. Cada cidade tem sua própria página, com notícias, farmácia de plantão, avisos de falta de água e luz, concursos, vagas de emprego e alertas da Defesa Civil.\n\n"
				. "Convidamos a {orgao} a publicar suas notas oficiais diretamente no portal, sem custo. As publicações aparecem na página de {cidade} com o selo de fonte oficial e chegam aos moradores que acompanham a cidade.\n\n"
				. "Como participar (5 minutos):\n"
				. "1. Acesse {cadastro}\n"
				. "2. Cadastre-se com o e-mail institucional do órgão\n"
				. "3. Após a liberação, publique pelo Painel do órgão\n\n"
				. "Pelo mesmo painel também é possível informar a escala de farmácias de plantão, interrupções de água e luz, concursos e as vagas do PAT, que aparecem em destaque para a população.\n\n"
				. "Apresentação com o passo a passo (PDF): {pdf}\n\n"
				. "Fico à disposição para ajudar no cadastro. É só responder este e-mail.\n\n"
				. "Atenciosamente,\n"
				. "Equipe Mural 17\n"
				. "mural17.com.br · contato@mural17.com.br",
		)
	);
}

/**
 * Assunto e texto prontos para um contato.
 *
 * @param array $c Contato.
 * @return array( assunto, texto ).
 */
function mural17_convites_monta( $c ) {
	$m     = mural17_convites_modelo();
	$troca = array(
		'{orgao}'    => mural17_convites_nome( $c ),
		'{cidade}'   => $c['cidade'],
		'{cadastro}' => mural17_link_oficial( 'cadastro' ),
		'{pdf}'      => MURAL17_URI . '/assets/marca/mural17-convite-orgaos.pdf',
	);
	$texto = strtr( $m['texto'], $troca )
		. "\n\n--\nRecebeu por engano ou não quer receber outras mensagens do Mural 17? Responda com a palavra REMOVER.";
	return array( strtr( $m['assunto'], $troca ), $texto );
}

/**
 * Envia o convite a um contato (ou a outro endereço, no teste).
 *
 * @param array       $c    Contato.
 * @param string|null $para Endereço do teste.
 * @return bool
 */
function mural17_convites_envia( $c, $para = null ) {
	list( $assunto, $texto ) = mural17_convites_monta( $c );
	$de = mural17_convites_remetente();
	return wp_mail(
		$para ? $para : $c['email'],
		( $para ? '[TESTE] ' : '' ) . $assunto,
		$texto,
		array(
			'From: Mural 17 <' . $de . '>',
			'Reply-To: Mural 17 <' . $de . '>',
			'List-Unsubscribe: <mailto:' . $de . '?subject=REMOVER>',
		)
	);
}

/**
 * Uma rodada da fila: manda até MURAL17_CONVITES_LOTE convites.
 *
 * @return int Enviados nesta rodada.
 */
function mural17_convites_rodada() {
	if ( get_transient( 'mural17_convites_rodando' ) ) {
		return 0;
	}
	set_transient( 'mural17_convites_rodando', 1, 4 * MINUTE_IN_SECONDS );
	$contatos = mural17_convites_contatos();
	$st       = mural17_convites_status();
	$feitos   = 0;
	$erro_wp  = '';
	$pega     = function ( $e ) use ( &$erro_wp ) {
		$erro_wp = $e->get_error_message();
	};
	add_action( 'wp_mail_failed', $pega );
	foreach ( $st as $id => $s ) {
		if ( empty( $s['fila'] ) ) {
			continue;
		}
		if ( $feitos >= MURAL17_CONVITES_LOTE ) {
			break;
		}
		unset( $st[ $id ]['fila'] );
		$c = $contatos[ $id ] ?? null;
		if ( ! $c || ! is_email( $c['email'] ) ) {
			$st[ $id ]['erro'] = 'sem e-mail válido';
			continue;
		}
		$erro_wp = '';
		if ( mural17_convites_envia( $c ) ) {
			$st[ $id ]['enviado'] = time();
			$st[ $id ]['para']    = $c['email'];
			unset( $st[ $id ]['erro'] );
		} else {
			$st[ $id ]['erro'] = $erro_wp ? $erro_wp : 'o servidor recusou o envio';
		}
		++$feitos;
	}
	remove_action( 'wp_mail_failed', $pega );
	update_option( 'mural17_convites_status', $st, false );
	delete_transient( 'mural17_convites_rodando' );
	if ( ! mural17_convites_na_fila( $st ) ) {
		wp_clear_scheduled_hook( 'mural17_convites_fila' );
	}
	return $feitos;
}
add_action( 'mural17_convites_fila', 'mural17_convites_rodada' );

add_filter(
	'cron_schedules',
	function ( $s ) {
		$s['mural17_5min'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => 'A cada 5 minutos (Mural 17)',
		);
		return $s;
	}
);

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'mural17-servicos', 'Convites aos órgãos', 'Convites', 'manage_options', 'mural17-convites', 'mural17_pagina_convites' );
	},
	20
);

/**
 * Volta para a tela de Convites.
 *
 * @param string $feito Código da mensagem.
 * @param array  $extra Outros parâmetros.
 */
function mural17_convites_volta( $feito, $extra = array() ) {
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => 'mural17-convites', 'feito' => $feito ), $extra ), admin_url( 'admin.php' ) ) );
	exit;
}

add_action(
	'admin_post_mural17_convites',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_convites' ) ) {
			wp_die( 'Este link expirou. Volte, recarregue a página e tente de novo.' );
		}
		$o_que = sanitize_key( wp_unslash( $_POST['o_que'] ?? '' ) );
		$ids   = array_map( 'sanitize_key', (array) wp_unslash( $_POST['ids'] ?? array() ) );
		$cts   = mural17_convites_contatos();
		$ids   = array_values( array_filter( $ids, function ( $i ) use ( $cts ) { return isset( $cts[ $i ] ); } ) );
		$st    = mural17_convites_status();

		if ( 'modelo' === $o_que ) {
			update_option(
				'mural17_convites_modelo',
				array(
					'assunto' => sanitize_text_field( wp_unslash( $_POST['assunto'] ?? '' ) ),
					'texto'   => sanitize_textarea_field( wp_unslash( $_POST['texto'] ?? '' ) ),
				),
				false
			);
			mural17_convites_volta( 'modelo' );
		}
		if ( 'padrao' === $o_que ) {
			delete_option( 'mural17_convites_modelo' );
			mural17_convites_volta( 'padrao' );
		}
		if ( 'teste' === $o_que ) {
			$para = sanitize_email( wp_unslash( $_POST['para'] ?? '' ) );
			if ( ! is_email( $para ) ) {
				mural17_convites_volta( 'teste-email' );
			}
			$c = $ids ? $cts[ $ids[0] ] : reset( $cts );
			update_option( 'mural17_convites_teste', $para, false );
			mural17_convites_volta( mural17_convites_envia( $c, $para ) ? 'teste' : 'teste-erro' );
		}
		if ( 'contato' === $o_que ) {
			$id    = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
			$email = strtolower( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) );
			if ( isset( $cts[ $id ] ) && ( '' === $email || is_email( $email ) ) ) {
				$t        = (array) get_option( 'mural17_convites_email', array() );
				$t[ $id ] = $email;
				update_option( 'mural17_convites_email', $t, false );
				unset( $st[ $id ]['erro'] );
				update_option( 'mural17_convites_status', $st, false );
			}
			mural17_convites_volta( 'contato' );
		}
		if ( 'novo' === $o_que ) {
			$extra = (array) get_option( 'mural17_convites_extra', array() );
			$n     = 0;
			foreach ( preg_split( '/\R/', (string) wp_unslash( $_POST['novos'] ?? '' ) ) as $linha ) {
				$p = array_map( 'trim', preg_split( '/\s*[;\t]\s*/', $linha ) );
				if ( count( $p ) < 3 || ! is_email( $p[2] ) ) {
					continue;
				}
				$extra[] = array(
					'regiao'   => 'Adicionados',
					'cidade'   => sanitize_text_field( $p[0] ),
					'orgao'    => sanitize_text_field( $p[1] ),
					'email'    => strtolower( sanitize_email( $p[2] ) ),
					'telefone' => sanitize_text_field( $p[3] ?? '' ),
					'situacao' => 'Pronto',
					'obs'      => 'adicionado no painel',
				);
				++$n;
			}
			update_option( 'mural17_convites_extra', $extra, false );
			mural17_convites_volta( 'novo', array( 'n' => $n ) );
		}
		if ( 'cancela' === $o_que ) {
			foreach ( $st as $id => $s ) {
				unset( $st[ $id ]['fila'] );
			}
			update_option( 'mural17_convites_status', $st, false );
			wp_clear_scheduled_hook( 'mural17_convites_fila' );
			mural17_convites_volta( 'cancela' );
		}
		if ( ! $ids ) {
			mural17_convites_volta( 'nada' );
		}
		if ( 'enviar' === $o_que ) {
			$n = 0;
			foreach ( $ids as $id ) {
				if ( ! empty( $st[ $id ]['fora'] ) || ! is_email( $cts[ $id ]['email'] ) ) {
					continue;
				}
				if ( ! empty( $st[ $id ]['enviado'] ) && empty( $_POST['reenviar'] ) ) {
					continue; // Já recebeu: só manda de novo se pedir.
				}
				$st[ $id ]['fila'] = 1;
				++$n;
			}
			update_option( 'mural17_convites_status', $st, false );
			if ( $n && ! wp_next_scheduled( 'mural17_convites_fila' ) ) {
				wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'mural17_5min', 'mural17_convites_fila' );
			}
			mural17_convites_rodada(); // O primeiro lote sai na hora.
			mural17_convites_volta( 'enviar', array( 'n' => $n ) );
		}
		if ( 'fora' === $o_que || 'volta' === $o_que ) {
			foreach ( $ids as $id ) {
				if ( 'fora' === $o_que ) {
					$st[ $id ]['fora'] = time();
					unset( $st[ $id ]['fila'] );
				} else {
					unset( $st[ $id ]['fora'] );
				}
			}
			update_option( 'mural17_convites_status', $st, false );
			mural17_convites_volta( $o_que );
		}
		mural17_convites_volta( 'nada' );
	}
);

/**
 * Tela Convites.
 */
function mural17_pagina_convites() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cts    = mural17_convites_contatos();
	$st     = mural17_convites_status();
	$cad    = mural17_convites_cadastrados();
	$modelo = mural17_convites_modelo();
	$feito  = isset( $_GET['feito'] ) ? sanitize_key( wp_unslash( $_GET['feito'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$n      = isset( $_GET['n'] ) ? (int) $_GET['n'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$fila   = mural17_convites_na_fila( $st );
	$msgs   = array(
		'modelo'      => 'Modelo salvo.',
		'padrao'      => 'Modelo de volta ao padrão do Mural 17.',
		'teste'       => 'Teste enviado. Confira a caixa de entrada (e o spam) do endereço de teste.',
		'teste-erro'  => 'O servidor recusou o teste. Veja se o e-mail ' . mural17_convites_remetente() . ' existe no cPanel.',
		'teste-email' => 'Endereço de teste inválido.',
		'contato'     => 'E-mail do órgão atualizado.',
		'novo'        => $n . ' contato(s) adicionado(s).',
		'cancela'     => 'Fila cancelada. Nada mais sai até você mandar de novo.',
		'nada'        => 'Nenhum órgão marcado.',
		'enviar'      => $n . ' convite(s) na fila. O primeiro lote já saiu; o resto sai de ' . MURAL17_CONVITES_LOTE . ' em ' . MURAL17_CONVITES_LOTE . ' a cada 5 minutos.',
		'fora'        => 'Marcados como "não enviar".',
		'volta'       => 'Voltaram para a lista de envio.',
	);
	$total = array( 'email' => 0, 'enviados' => 0, 'cadastrou' => 0, 'erro' => 0 );
	foreach ( $cts as $id => $c ) {
		$total['email']     += is_email( $c['email'] ) ? 1 : 0;
		$total['enviados']  += ! empty( $st[ $id ]['enviado'] ) ? 1 : 0;
		$total['erro']      += ! empty( $st[ $id ]['erro'] ) ? 1 : 0;
		$total['cadastrou'] += mural17_convites_cadastro_de( $c, $cad ) ? 1 : 0;
	}
	$form = function ( $o_que, $extra = '' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" ' . $extra . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" name="action" value="mural17_convites">';
		echo '<input type="hidden" name="o_que" value="' . esc_attr( $o_que ) . '">';
		wp_nonce_field( 'mural17_convites', 'mural17_nonce' );
	};
	list( $prev_assunto, $prev_texto ) = mural17_convites_monta( reset( $cts ) );
	?>
	<div class="wrap m17-convites">
		<h1>Convites aos órgãos</h1>
		<?php if ( isset( $msgs[ $feito ] ) ) : ?>
			<div class="notice notice-<?php echo false !== strpos( $feito, 'erro' ) || 'nada' === $feito || 'teste-email' === $feito ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $msgs[ $feito ] ); ?></p></div>
		<?php endif; ?>
		<style>
			.m17-convites .m17-n{display:flex;gap:12px;flex-wrap:wrap;margin:14px 0}
			.m17-convites .m17-n div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 16px;min-width:120px}
			.m17-convites .m17-n b{display:block;font-size:24px;line-height:1.2}
			.m17-convites .m17-cx{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 18px 14px;margin:14px 0;max-width:1100px}
			.m17-convites table td,.m17-convites table th{vertical-align:middle}
			.m17-convites .s{display:inline-block;border-radius:10px;padding:1px 8px;font-size:12px;background:#f0f0f1}
			.m17-convites .s--ok{background:#e7f6ec;color:#0a6b2f}.m17-convites .s--fila{background:#fff4d6;color:#7a5200}
			.m17-convites .s--erro{background:#fde8e8;color:#9b1c1c}.m17-convites .s--cad{background:#e6effb;color:#1d4f91}
			.m17-convites pre{white-space:pre-wrap;background:#f6f7f7;padding:12px;max-height:320px;overflow:auto;font:13px/1.5 -apple-system,sans-serif}
			.m17-convites .m17-filtros a{margin-right:10px}
		</style>

		<div class="m17-n">
			<div><b><?php echo count( $cts ); ?></b>órgãos na lista</div>
			<div><b><?php echo (int) $total['email']; ?></b>com e-mail</div>
			<div><b><?php echo (int) $total['enviados']; ?></b>convidados</div>
			<div><b><?php echo (int) $fila; ?></b>na fila</div>
			<div><b><?php echo (int) $total['cadastrou']; ?></b>já se cadastraram</div>
			<?php if ( $total['erro'] ) : ?>
				<div><b style="color:#9b1c1c"><?php echo (int) $total['erro']; ?></b>com erro</div>
			<?php endif; ?>
		</div>

		<?php if ( $fila ) : ?>
			<?php $form( 'cancela', 'style="margin:0 0 10px"' ); ?>
				<p><b><?php echo (int) $fila; ?> convite(s) saindo</b>, <?php echo (int) MURAL17_CONVITES_LOTE; ?> a cada 5 minutos. <?php submit_button( 'Parar o envio', 'secondary small', 'submit', false ); ?></p>
			</form>
		<?php endif; ?>

		<div class="m17-cx">
			<h2>1. Confira o convite</h2>
			<p>Sai de <b>Mural 17 &lt;<?php echo esc_html( mural17_convites_remetente() ); ?>&gt;</b>, e as respostas chegam nessa caixa. Variáveis: <code>{orgao}</code> (ex.: Câmara Municipal de Adolfo), <code>{cidade}</code>, <code>{cadastro}</code> (link do cadastro) e <code>{pdf}</code> (link da apresentação).</p>
			<details>
				<summary>Editar o texto</summary>
				<?php $form( 'modelo' ); ?>
					<p><label>Assunto<br><input name="assunto" class="large-text" value="<?php echo esc_attr( $modelo['assunto'] ); ?>"></label></p>
					<p><label>Texto<br><textarea name="texto" rows="16" class="large-text"><?php echo esc_textarea( $modelo['texto'] ); ?></textarea></label></p>
					<?php submit_button( 'Salvar texto', 'primary', 'submit', false ); ?>
				</form>
				<?php $form( 'padrao', 'style="margin-top:6px"' ); ?>
					<?php submit_button( 'Voltar ao texto padrão', 'link', 'submit', false ); ?>
				</form>
			</details>
			<p><b>Como chega</b> (exemplo: <?php echo esc_html( mural17_convites_nome( reset( $cts ) ) ); ?>):</p>
			<p><b>Assunto:</b> <?php echo esc_html( $prev_assunto ); ?></p>
			<pre><?php echo esc_html( $prev_texto ); ?></pre>
		</div>

		<div class="m17-cx">
			<h2>2. Mande um teste para você</h2>
			<p>Antes do envio em massa, mande para o seu e-mail e veja se chega na caixa de entrada (e não no spam).</p>
			<?php $form( 'teste' ); ?>
				<input type="email" name="para" class="regular-text" required placeholder="seu@email.com" value="<?php echo esc_attr( (string) get_option( 'mural17_convites_teste', '' ) ); ?>">
				<?php submit_button( 'Enviar teste', 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<div class="m17-cx">
			<h2>3. Escolha os órgãos e envie</h2>
			<p>Nada vem marcado. Use <b>Prontos não convidados</b> (e-mail visto no site oficial, sem convite e sem cadastro), se quiser filtrando antes por região, ou marque um a um. Dá para mandar aos poucos, uma região por dia. Os de situação <b>Conferir</b> tiveram o e-mail achado em busca: confira antes de marcar. Ninguém recebe duas vezes, a menos que você marque "Mandar de novo".</p>
			<p class="m17-filtros">
				<b>Marcar:</b>
				<a href="#" data-marca="pronto">Prontos não convidados</a>
				<a href="#" data-marca="todos">Todos com e-mail não convidados</a>
				<a href="#" data-marca="nenhum">Nenhum</a>
				· <label>Região <select id="m17-reg"><option value="">todas</option>
					<?php foreach ( array_unique( wp_list_pluck( $cts, 'regiao' ) ) as $r ) : ?>
						<option><?php echo esc_html( $r ); ?></option>
					<?php endforeach; ?>
				</select></label>
				<label>Órgão <select id="m17-org"><option value="">todos</option><option>Prefeitura</option><option>Câmara</option></select></label>
			</p>
			<?php $form( 'enviar', 'id="m17-envio"' ); ?>
				<table class="widefat striped">
					<thead><tr><td class="check-column"><input type="checkbox" id="m17-todos"></td><th>Cidade</th><th>Órgão</th><th>E-mail</th><th>Telefone</th><th>Situação</th><th>Convite</th></tr></thead>
					<tbody>
					<?php foreach ( $cts as $id => $c ) : ?>
						<?php
						$s     = $st[ $id ] ?? array();
						$cadas = mural17_convites_cadastro_de( $c, $cad );
						$tem   = is_email( $c['email'] );
						?>
						<tr data-reg="<?php echo esc_attr( $c['regiao'] ); ?>" data-org="<?php echo esc_attr( $c['orgao'] ); ?>" data-sit="<?php echo esc_attr( $c['situacao'] ); ?>" data-livre="<?php echo $tem && empty( $s['enviado'] ) && empty( $s['fora'] ) && ! $cadas ? '1' : ''; ?>">
							<th class="check-column"><?php if ( $tem ) : ?><input type="checkbox" name="ids[]" value="<?php echo esc_attr( $id ); ?>"><?php endif; ?></th>
							<td><?php echo esc_html( $c['cidade'] ); ?><br><small style="color:#787c82"><?php echo esc_html( $c['regiao'] ); ?></small></td>
							<td><?php echo esc_html( $c['orgao'] ); ?></td>
							<td>
								<?php echo $tem ? esc_html( $c['email'] ) : '<span style="color:#9b1c1c">sem e-mail</span>'; ?>
								<a href="#" class="m17-edita" data-id="<?php echo esc_attr( $id ); ?>" data-email="<?php echo esc_attr( $c['email'] ); ?>" title="<?php echo esc_attr( $c['obs'] ); ?>">trocar</a>
							</td>
							<td><?php echo esc_html( $c['telefone'] ); ?></td>
							<td><span class="s<?php echo 'Pronto' === $c['situacao'] ? ' s--ok' : ''; ?>" title="<?php echo esc_attr( $c['obs'] ); ?>"><?php echo esc_html( $c['situacao'] ); ?></span></td>
							<td>
								<?php if ( $cadas ) : ?>
									<span class="s s--cad">cadastrou<?php echo 'ativo' !== $cadas ? ' (' . esc_html( $cadas ) . ')' : ''; ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $s['fora'] ) ) : ?>
									<span class="s">não enviar</span>
								<?php elseif ( ! empty( $s['fila'] ) ) : ?>
									<span class="s s--fila">na fila</span>
								<?php elseif ( ! empty( $s['erro'] ) ) : ?>
									<span class="s s--erro" title="<?php echo esc_attr( $s['erro'] ); ?>">erro: <?php echo esc_html( mb_substr( $s['erro'], 0, 40 ) ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $s['enviado'] ) ) : ?>
									<span class="s s--ok">enviado <?php echo esc_html( wp_date( 'd/m H:i', $s['enviado'] ) ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<b id="m17-qtd">0</b> marcado(s).
					<label style="margin:0 12px"><input type="checkbox" name="reenviar" value="1"> Mandar de novo para quem já recebeu</label>
					<button type="submit" class="button button-primary" onclick="return confirm('Enviar o convite para os ' + document.getElementById('m17-qtd').textContent + ' órgãos marcados?');">Enviar convites</button>
					<button type="submit" class="button" onclick="this.form.elements['o_que'].value='fora'">Marcar como "não enviar"</button>
					<button type="submit" class="button-link" onclick="this.form.elements['o_que'].value='volta'">Desfazer "não enviar"</button>
				</p>
			</form>
			<?php $form( 'contato', 'id="m17-troca" hidden' ); ?>
				<input type="hidden" name="id"><input type="hidden" name="email">
			</form>
		</div>

		<div class="m17-cx">
			<h2>Adicionar órgãos</h2>
			<p>Um por linha: <code>Cidade; Órgão; e-mail; telefone</code>. Ex.: <code>Mirassol; SAAE; contato@saaemirassol.sp.gov.br</code></p>
			<?php $form( 'novo' ); ?>
				<textarea name="novos" rows="4" class="large-text code"></textarea>
				<?php submit_button( 'Adicionar', 'secondary', 'submit', false ); ?>
			</form>
		</div>
	</div>
	<script>
	( function () {
		var tab = document.querySelector( '#m17-envio tbody' );
		var conta = function () {
			document.getElementById( 'm17-qtd' ).textContent = tab.querySelectorAll( 'input:checked' ).length;
		};
		var visivel = function ( tr ) { return ! tr.hidden; };
		var filtra = function () {
			var r = document.getElementById( 'm17-reg' ).value, o = document.getElementById( 'm17-org' ).value;
			tab.querySelectorAll( 'tr' ).forEach( function ( tr ) {
				tr.hidden = ( r && tr.dataset.reg !== r ) || ( o && tr.dataset.org !== o );
				if ( tr.hidden ) { var c = tr.querySelector( 'input' ); if ( c ) { c.checked = false; } }
			} );
			conta();
		};
		document.getElementById( 'm17-reg' ).onchange = filtra;
		document.getElementById( 'm17-org' ).onchange = filtra;
		document.querySelectorAll( '[data-marca]' ).forEach( function ( a ) {
			a.onclick = function ( e ) {
				e.preventDefault();
				var m = a.dataset.marca;
				tab.querySelectorAll( 'tr' ).forEach( function ( tr ) {
					var c = tr.querySelector( 'input' );
					if ( c ) { c.checked = visivel( tr ) && m !== 'nenhum' && tr.dataset.livre === '1' && ( m === 'todos' || tr.dataset.sit === 'Pronto' ); }
				} );
				conta();
			};
		} );
		document.getElementById( 'm17-todos' ).onchange = function () {
			var v = this.checked;
			tab.querySelectorAll( 'tr' ).forEach( function ( tr ) { var c = tr.querySelector( 'input' ); if ( c && visivel( tr ) ) { c.checked = v; } } );
			conta();
		};
		tab.addEventListener( 'change', conta );
		document.querySelectorAll( '.m17-edita' ).forEach( function ( a ) {
			a.onclick = function ( e ) {
				e.preventDefault();
				var novo = prompt( 'E-mail do órgão (deixe vazio para "sem e-mail"):', a.dataset.email );
				if ( novo === null ) { return; }
				var f = document.getElementById( 'm17-troca' );
				f.elements['id'].value = a.dataset.id; f.elements['email'].value = novo.trim();
				f.submit();
			};
		} );
		conta();
	} )();
	</script>
	<?php
}
