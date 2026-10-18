<?php
/**
 * Formulários dos serviços: abas do Painel do órgão (falta de água e luz,
 * plantão de farmácia, concursos, respostas do Fala 17 e falecimentos), o
 * envio público do Fala 17, o "eu também" e a inscrição nos avisos por e-mail.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mensagens dos serviços (?m17=codigo), somadas às das notas oficiais.
 *
 * @return array<string, array{0:string,1:string}>
 */
function mural17_mensagens_servicos() {
	return array(
		'interrupcao-ok'        => array( 'ok', 'Aviso publicado. Ele já aparece na página de falta de água e luz e na faixa da cidade.' ),
		'interrupcao-volta'     => array( 'ok', 'Marcado como normalizado. O aviso sai da lista em 6 horas.' ),
		'erro-interrupcao'      => array( 'erro', 'Preencha o que falta, os bairros e quando começa.' ),
		'plantao-ok'            => array( 'ok', 'Escala salva. O site troca a farmácia sozinho no horário de cada plantão.' ),
		'pat-ok'                => array( 'ok', 'Lista publicada. Ela aparece em Empregos, na sua cidade, e entra no resumo de vagas por e-mail.' ),
		'erro-pat'              => array( 'erro', 'Nenhuma linha da lista foi entendida. Use uma vaga por linha: quantidade; cargo; requisito.' ),
		'erro-plantao'          => array( 'erro', 'Nenhuma linha da escala foi entendida. Use uma linha por dia: data; farmácia; endereço; telefone.' ),
		'concurso-ok'           => array( 'ok', 'Concurso publicado. Quem pediu aviso de concursos na região vai receber um e-mail.' ),
		'erro-concurso'         => array( 'erro', 'Preencha cargos, órgão, data final das inscrições e o link do edital.' ),
		'fala-respondida'       => array( 'ok', 'Resposta publicada junto da reclamação.' ),
		'erro-fala-resposta'    => array( 'erro', 'Escreva a resposta antes de enviar.' ),
		'falecimento-ok'        => array( 'ok', 'Nota de falecimento publicada.' ),
		'falecimento-revisao'   => array( 'ok', 'Nota enviada. A redação revisa e publica em seguida.' ),
		'erro-falecimento'      => array( 'erro', 'Preencha nome, cidade e confirme que a família autorizou a publicação.' ),
		'fala-ok'               => array( 'ok', 'Recebemos. A redação confere e publica, normalmente no mesmo dia. Seu nome e telefone não aparecem no site.' ),
		'erro-fala'             => array( 'erro', 'Conte o que está acontecendo, onde é e escolha a cidade.' ),
		'erro-fala-limite'      => array( 'erro', 'Recebemos vários envios deste aparelho. Tente de novo em uma hora ou mande pelo WhatsApp.' ),
		'inscricao-enviada'     => array( 'ok', 'Quase lá: mandamos um e-mail de confirmação. Clique no link para começar a receber.' ),
		'inscricao-ok'          => array( 'ok', 'Pronto, este e-mail já recebe os avisos escolhidos.' ),
		'inscricao-confirmada'  => array( 'ok', 'E-mail confirmado. A partir de agora você recebe os avisos escolhidos.' ),
		'inscricao-cancelada'   => array( 'ok', 'Inscrição cancelada. Você não recebe mais avisos do Mural 17.' ),
		'erro-inscricao'        => array( 'erro', 'Confira o e-mail e escolha pelo menos um tipo de aviso.' ),
		'erro-inscricao-limite' => array( 'erro', 'Muitas tentativas seguidas. Tente de novo mais tarde.' ),
	);
}

/**
 * Aviso solto (fora dos formulários) para os códigos de inscrição.
 */
function mural17_aviso_global() {
	$codigo = isset( $_GET['m17'] ) ? sanitize_key( wp_unslash( $_GET['m17'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 0 !== strpos( $codigo, 'inscricao-' ) || 'inscricao-enviada' === $codigo || 'inscricao-ok' === $codigo ) {
		return;
	}
	$msgs = mural17_mensagens_servicos();
	if ( isset( $msgs[ $codigo ] ) ) {
		printf( '<div class="wrap"><p class="aviso aviso--%1$s aviso--solto" role="status">%2$s</p></div>', esc_attr( $msgs[ $codigo ][0] ), esc_html( $msgs[ $codigo ][1] ) );
	}
}

/**
 * Lê um campo de texto do POST.
 *
 * @param string $chave Chave.
 * @param bool   $longo Texto com quebras de linha.
 * @return string
 */
function mural17_post( $chave, $longo = false ) {
	if ( ! isset( $_POST[ $chave ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return '';
	}
	$v = wp_unslash( $_POST[ $chave ] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( is_array( $v ) ) {
		return '';
	}
	return $longo ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );
}

/**
 * Guarda os campos de um tipo de serviço a partir do POST, com a mesma
 * limpeza das caixas do editor. Só as chaves pedidas.
 *
 * @param int      $post_id Post.
 * @param string   $tipo    Tipo de conteúdo.
 * @param string[] $chaves  Campos.
 */
function mural17_salva_campos_post( $post_id, $tipo, $chaves ) {
	$campos = mural17_fields()[ $tipo ]['campos'];
	foreach ( $chaves as $k ) {
		if ( ! isset( $campos[ $k ] ) ) {
			continue;
		}
		$raw   = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$valor = mural17_limpa_campo( $campos[ $k ], $raw );
		if ( '' === $valor ) {
			delete_post_meta( $post_id, '_mural17_' . $k );
		} else {
			update_post_meta( $post_id, '_mural17_' . $k, $valor );
		}
	}
}

/**
 * Recebe uma foto de formulário público ou do painel: só JPG, PNG ou WebP
 * de verdade, até 8 MB.
 *
 * @param string $campo   Nome do campo de arquivo.
 * @param int    $post_id Post dono da foto.
 * @param string $credito Legenda.
 * @return int|false|null ID do anexo, false se a foto veio e falhou, null se não veio.
 */
function mural17_recebe_foto( $campo, $post_id, $credito = '' ) {
	if ( empty( $_FILES[ $campo ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return null;
	}
	$size  = isset( $_FILES[ $campo ]['size'] ) ? (int) $_FILES[ $campo ]['size'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$tmp   = isset( $_FILES[ $campo ]['tmp_name'] ) ? sanitize_text_field( $_FILES[ $campo ]['tmp_name'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$nome  = sanitize_file_name( wp_unslash( $_FILES[ $campo ]['name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$mimes = array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
	);
	if ( $size <= 0 || $size > 8 * MB_IN_BYTES || ! $tmp ) {
		return false;
	}
	$tipo = wp_check_filetype_and_ext( $tmp, $nome, $mimes );
	if ( empty( $tipo['type'] ) ) {
		return false;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_handle_upload(
		$campo,
		$post_id,
		$credito ? array( 'post_excerpt' => $credito ) : array(),
		array(
			'test_form' => false,
			'mimes'     => $mimes,
		)
	);
	return is_wp_error( $id ) ? false : (int) $id;
}

/**
 * Limite simples por IP (guardado com hash) para formulários públicos.
 *
 * @param string $chave  Nome do limite.
 * @param int    $maximo Envios permitidos.
 * @param int    $janela Segundos.
 * @return bool Pode seguir.
 */
function mural17_dentro_do_limite( $chave, $maximo, $janela = HOUR_IN_SECONDS ) {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$t   = 'm17_lim_' . $chave . '_' . substr( wp_hash( $ip ), 0, 16 );
	$qtd = (int) get_transient( $t );
	if ( $qtd >= $maximo ) {
		return false;
	}
	set_transient( $t, $qtd + 1, $janela );
	return true;
}

/**
 * Volta para uma página do site (só do próprio site) com um código.
 *
 * @param string $url    Destino pedido.
 * @param string $codigo Código da mensagem.
 * @param string $ancora Âncora.
 */
function mural17_volta_para( $url, $codigo, $ancora = '' ) {
	$url = wp_validate_redirect( $url, home_url( '/' ) );
	$url = remove_query_arg( array( 'm17' ), $url );
	wp_safe_redirect( add_query_arg( 'm17', $codigo, $url ) . ( $ancora ? '#' . $ancora : '' ) );
	exit;
}

/* -------------------------------------------------------------------------
 * Painel do órgão: abas
 * ---------------------------------------------------------------------- */

/**
 * Abas do painel para o tipo do órgão.
 *
 * @param WP_Term $orgao Órgão.
 * @return array<string, string>
 */
function mural17_abas_painel( $orgao ) {
	if ( mural17_orgao_privado( $orgao ) ) {
		return array( 'falecimentos' => 'Falecimentos' );
	}
	$tipo = mural17_orgao_meta( $orgao, 'tipo' );
	$abas = array(
		'notas'        => 'Notas oficiais',
		'interrupcoes' => 'Falta de água e luz',
	);
	if ( in_array( $tipo, array( 'prefeitura', 'municipal' ), true ) ) {
		$abas['plantao'] = 'Farmácia de plantão';
		$abas['pat']     = 'Vagas do PAT';
	}
	if ( ! in_array( $tipo, array( 'saneamento', 'energia' ), true ) ) {
		$abas['concursos'] = 'Concursos';
	}
	if ( in_array( $tipo, array( 'prefeitura', 'municipal', 'saneamento', 'camara' ), true ) ) {
		$abas['fala'] = 'Fala, 17';
	}
	return $abas;
}

/**
 * Aba aberta no painel.
 *
 * @param WP_Term $orgao Órgão.
 * @return string
 */
function mural17_aba_painel( $orgao ) {
	$abas   = mural17_abas_painel( $orgao );
	$pedida = isset( $_GET['aba'] ) ? sanitize_key( wp_unslash( $_GET['aba'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return isset( $abas[ $pedida ] ) ? $pedida : array_key_first( $abas );
}

/**
 * Link de uma aba do painel.
 *
 * @param string $aba   Aba.
 * @param array  $extra Outros parâmetros.
 * @return string
 */
function mural17_link_aba( $aba, $extra = array() ) {
	return add_query_arg( array_merge( array( 'aba' => $aba ), $extra ), mural17_link_oficial( 'painel' ) );
}

/**
 * Volta para uma aba do painel com mensagem.
 *
 * @param string $aba    Aba.
 * @param string $codigo Código.
 * @param array  $extra  Outros parâmetros.
 */
function mural17_volta_aba( $aba, $codigo, $extra = array() ) {
	wp_safe_redirect( mural17_link_aba( $aba, array_merge( array( 'm17' => $codigo ), $extra ) ) );
	exit;
}

/**
 * Topo do painel: quem está logado e as abas.
 *
 * @param WP_Term $orgao Órgão.
 * @param string  $aba   Aba aberta.
 */
function mural17_painel_quem( $orgao, $aba ) {
	$abas = mural17_abas_painel( $orgao );
	?>
	<div class="oficial-painel__quem">
		<?php mural17_brasao( $orgao ); ?>
		<span><b><?php echo esc_html( $orgao->name ); ?></b><small><?php echo esc_html( wp_get_current_user()->user_email ); ?></small></span>
		<?php if ( ! mural17_orgao_privado( $orgao ) ) : ?>
			<a href="<?php echo esc_url( get_term_link( $orgao ) ); ?>">Ver página do órgão</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( wp_logout_url( mural17_link_oficial( 'painel' ) ) ); ?>">Sair</a>
	</div>
	<?php if ( count( $abas ) > 1 ) : ?>
		<nav class="painel-abas" aria-label="Seções do painel">
			<?php foreach ( $abas as $k => $nome ) : ?>
				<a class="painel-abas__item<?php echo $k === $aba ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( mural17_link_aba( $k ) ); ?>" <?php echo $k === $aba ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $nome ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
	<?php
}

/**
 * Conteúdo de uma aba de serviço.
 *
 * @param string  $aba   Aba.
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_servico( $aba, $orgao ) {
	switch ( $aba ) {
		case 'interrupcoes':
			mural17_painel_interrupcoes( $orgao );
			break;
		case 'pat':
			mural17_painel_pat( $orgao );
			break;
		case 'plantao':
			mural17_painel_plantao( $orgao );
			break;
		case 'concursos':
			mural17_painel_concursos( $orgao );
			break;
		case 'fala':
			mural17_painel_fala( $orgao );
			break;
		case 'falecimentos':
			mural17_painel_falecimentos( $orgao );
			break;
	}
}

/**
 * Cidade do órgão.
 *
 * @param WP_Term $orgao Órgão.
 * @return WP_Term|null
 */
function mural17_cidade_do_orgao( $orgao ) {
	return mural17_acha_cidade( (int) mural17_orgao_meta( $orgao, 'cidade' ) );
}

/**
 * O órgão atende várias cidades (concessionárias, Sabesp, funerárias)?
 *
 * @param WP_Term $orgao Órgão.
 * @return bool
 */
function mural17_orgao_regional( $orgao ) {
	return in_array( mural17_orgao_meta( $orgao, 'tipo' ), array( 'saneamento', 'energia', 'funeraria', 'judiciario', 'mp', 'defensoria', 'policia' ), true );
}

/**
 * Cidade escolhida no formulário do painel: a do órgão, ou outra se o
 * órgão atende várias cidades.
 *
 * @param WP_Term $orgao Órgão.
 * @return WP_Term|null
 */
function mural17_cidade_do_form( $orgao ) {
	if ( mural17_orgao_regional( $orgao ) ) {
		$c = mural17_acha_cidade( absint( mural17_post( 'cidade' ) ) );
		if ( $c ) {
			return $c;
		}
	}
	return mural17_cidade_do_orgao( $orgao );
}

/**
 * Campo de cidade do formulário do painel.
 *
 * @param WP_Term $orgao   Órgão.
 * @param int     $marcada Cidade marcada.
 */
function mural17_campo_cidade_painel( $orgao, $marcada = 0 ) {
	$propria = mural17_cidade_do_orgao( $orgao );
	if ( ! mural17_orgao_regional( $orgao ) ) {
		echo '<p class="campo"><span class="campo__rotulo">Cidade</span><b>' . esc_html( $propria ? $propria->name : '' ) . '</b><span class="campo__dica">Fixa para este órgão.</span></p>';
		return;
	}
	?>
	<p class="campo">
		<label for="painel-cidade">Cidade</label>
		<select id="painel-cidade" name="cidade" required>
			<?php mural17_opcoes_cidades( $marcada ? $marcada : ( $propria ? $propria->term_id : 0 ) ); ?>
		</select>
	</p>
	<?php
}

/**
 * O post é do órgão (enviado por ele pelo painel)?
 *
 * @param WP_Post|null $p     Post.
 * @param WP_Term      $orgao Órgão.
 * @param string       $tipo  Tipo de conteúdo.
 * @return bool
 */
function mural17_post_do_orgao( $p, $orgao, $tipo ) {
	return $p && $tipo === $p->post_type && (int) get_post_meta( $p->ID, '_mural17_orgao_id', true ) === (int) $orgao->term_id;
}

/**
 * Posts de um tipo enviados pelo órgão.
 *
 * @param WP_Term $orgao Órgão.
 * @param string  $tipo  Tipo.
 * @param int     $qtd   Quantos.
 * @param array   $extra Argumentos extras.
 * @return WP_Post[]
 */
function mural17_posts_do_orgao( $orgao, $tipo, $qtd = 12, $extra = array() ) {
	$q = new WP_Query(
		array_merge(
			array(
				'post_type'      => $tipo,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => $qtd,
				'no_found_rows'  => true,
				'meta_key'       => '_mural17_orgao_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => (int) $orgao->term_id, // phpcs:ignore WordPress.DB.SlowDBQuery
			),
			$extra
		)
	);
	return $q->posts;
}

/**
 * Campo datetime-local com valor de meta.
 *
 * @param string $valor Valor "Y-m-d H:i".
 * @return string
 */
function mural17_valor_dt( $valor ) {
	return str_replace( ' ', 'T', (string) $valor );
}

/* ---- Falta de água e luz ---------------------------------------------- */

/**
 * Aba "Falta de água e luz".
 *
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_interrupcoes( $orgao ) {
	$editar = isset( $_GET['editar'] ) ? get_post( absint( $_GET['editar'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
	$editar = mural17_post_do_orgao( $editar, $orgao, 'interrupcao' ) ? $editar : null;
	$m      = function ( $k ) use ( $editar ) {
		return $editar ? mural17_campo( $editar->ID, $k ) : '';
	};
	$tipo   = mural17_orgao_meta( $orgao, 'tipo' );
	$padrao = $m( 'int_tipo' ) ? $m( 'int_tipo' ) : ( 'energia' === $tipo ? 'energia' : 'agua' );
	$cid    = $editar ? mural17_cidade( $editar->ID ) : null;
	$ativos = mural17_posts_do_orgao(
		$orgao,
		'interrupcao',
		15,
		array(
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_mural17_int_normalizado',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	?>
	<div class="oficial-painel__grade">
		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2><?php echo $editar ? 'Atualizar aviso' : 'Avisar falta de água ou luz'; ?></h2>
			<?php mural17_aviso_oficial(); ?>
			<input type="hidden" name="action" value="mural17_interrupcao">
			<input type="hidden" name="editar" value="<?php echo $editar ? (int) $editar->ID : 0; ?>">
			<?php wp_nonce_field( 'mural17_interrupcao', 'mural17_nonce' ); ?>
			<fieldset class="campo">
				<legend>O que falta</legend>
				<div class="opcoes">
					<label class="opcao"><input type="radio" name="int_tipo" value="agua" <?php checked( $padrao, 'agua' ); ?>> Água</label>
					<label class="opcao"><input type="radio" name="int_tipo" value="energia" <?php checked( $padrao, 'energia' ); ?>> Energia</label>
				</div>
			</fieldset>
			<?php mural17_campo_cidade_painel( $orgao, $cid ? $cid->term_id : 0 ); ?>
			<p class="campo">
				<label for="int-bairros">Bairros ou ruas atingidos</label>
				<textarea id="int-bairros" name="int_bairros" rows="3" required maxlength="600"><?php echo esc_textarea( $m( 'int_bairros' ) ); ?></textarea>
				<span class="campo__dica">Separe por vírgula. Ex.: Jardim Europa, Vila Nova, Centro (parte alta).</span>
			</p>
			<p class="campo">
				<label for="int-motivo">Motivo</label>
				<input id="int-motivo" name="int_motivo" maxlength="120" value="<?php echo esc_attr( $m( 'int_motivo' ) ); ?>" placeholder="Ex.: rompimento de adutora, troca de transformador">
			</p>
			<div class="formulario__dupla">
				<p class="campo">
					<label for="int-inicio">Começa ou começou</label>
					<input id="int-inicio" name="int_inicio" type="datetime-local" required value="<?php echo esc_attr( mural17_valor_dt( $m( 'int_inicio' ) ? $m( 'int_inicio' ) : mural17_agora() ) ); ?>">
				</p>
				<p class="campo">
					<label for="int-previsao">Previsão de volta</label>
					<input id="int-previsao" name="int_previsao" type="datetime-local" value="<?php echo esc_attr( mural17_valor_dt( $m( 'int_previsao' ) ) ); ?>">
					<span class="campo__dica">Vazio = sem previsão.</span>
				</p>
			</div>
			<p class="campo">
				<label for="int-obs">Observação</label>
				<input id="int-obs" name="int_obs" maxlength="160" value="<?php echo esc_attr( $m( 'int_obs' ) ); ?>" placeholder="Ex.: caminhão-pipa na Escola X até 20h">
			</p>
			<p class="formulario__acoes">
				<button type="submit" class="botao botao--pilula botao--oficial"><?php echo $editar ? 'Salvar' : 'Publicar aviso'; ?></button>
				<?php if ( $editar ) : ?>
					<a href="<?php echo esc_url( mural17_link_aba( 'interrupcoes' ) ); ?>">Cancelar</a>
				<?php endif; ?>
			</p>
			<p class="campo__dica">Os avisos saem na hora, com o selo Oficial, e quem pediu aviso de falta de água e luz na cidade recebe um e-mail.</p>
		</form>

		<aside class="oficial-minhas">
			<h2>Avisos em aberto</h2>
			<?php if ( $ativos ) : ?>
				<ul>
					<?php
					foreach ( $ativos as $p ) :
						$c = mural17_cidade( $p->ID );
						?>
						<li>
							<span class="situacao situacao--<?php echo mural17_campo( $p->ID, 'int_inicio' ) > mural17_agora() ? 'neutro' : 'erro'; ?>"><?php echo mural17_campo( $p->ID, 'int_inicio' ) > mural17_agora() ? 'Programado' : 'Agora'; ?></span>
							<b><?php echo esc_html( ( 'energia' === mural17_campo( $p->ID, 'int_tipo' ) ? 'Energia' : 'Água' ) . ( $c ? ' · ' . $c->name : '' ) ); ?></b>
							<small><?php echo esc_html( wp_trim_words( mural17_campo( $p->ID, 'int_bairros' ), 12 ) ); ?></small>
							<small>Previsão: <?php echo esc_html( mural17_campo( $p->ID, 'int_previsao' ) ? mural17_quando_servico( mural17_campo( $p->ID, 'int_previsao' ) ) : 'sem previsão' ); ?></small>
							<span class="painel-linha-acoes">
								<a href="<?php echo esc_url( mural17_link_aba( 'interrupcoes', array( 'editar' => $p->ID ) ) ); ?>">Atualizar</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="mural17_interrupcao_volta">
									<input type="hidden" name="id" value="<?php echo (int) $p->ID; ?>">
									<?php wp_nonce_field( 'mural17_interrupcao_volta_' . $p->ID, 'mural17_nonce' ); ?>
									<button type="submit" class="botao botao--pilula botao--wa botao--mini">Já voltou</button>
								</form>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="vazio">Nenhum aviso em aberto.</p>
			<?php endif; ?>
			<div class="oficial-minhas__como">
				<b>Como funciona</b>
				<span>1. Publique assim que souber, mesmo sem previsão.</span>
				<span>2. Atualize a previsão quando mudar.</span>
				<span>3. Clique em “Já voltou” quando normalizar: o morador inscrito recebe o aviso.</span>
			</div>
		</aside>
	</div>
	<?php
}

/**
 * Recebe o aviso de falta de água ou luz.
 */
function mural17_recebe_interrupcao() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_interrupcao' ) ) {
		mural17_volta_aba( 'interrupcoes', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao || mural17_orgao_privado( $orgao ) ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}
	$editar = get_post( absint( mural17_post( 'editar' ) ) );
	if ( $editar && ! mural17_post_do_orgao( $editar, $orgao, 'interrupcao' ) ) {
		mural17_volta_aba( 'interrupcoes', 'erro-permissao' );
	}
	$tipo    = 'energia' === mural17_post( 'int_tipo' ) ? 'energia' : 'agua';
	$bairros = mural17_post( 'int_bairros', true );
	$cidade  = mural17_cidade_do_form( $orgao );
	if ( '' === $bairros || ! $cidade || '' === mural17_post( 'int_inicio' ) ) {
		mural17_volta_aba( 'interrupcoes', 'erro-interrupcao' );
	}
	$titulo = ( 'energia' === $tipo ? 'Sem energia' : 'Sem água' ) . ' em ' . $cidade->name . ': ' . wp_trim_words( $bairros, 8, '…' );
	$dados  = array(
		'post_type'   => 'interrupcao',
		'post_title'  => $titulo,
		'post_status' => 'publish',
		'post_author' => get_current_user_id(),
	);
	if ( $editar ) {
		$dados['ID'] = $editar->ID;
		$post_id     = wp_update_post( $dados, true );
	} else {
		$post_id = wp_insert_post( $dados, true );
	}
	if ( is_wp_error( $post_id ) ) {
		mural17_volta_aba( 'interrupcoes', 'erro-interrupcao' );
	}
	$_POST['int_tipo'] = $tipo;
	mural17_salva_campos_post( $post_id, 'interrupcao', array( 'int_tipo', 'int_bairros', 'int_motivo', 'int_inicio', 'int_previsao', 'int_obs' ) );
	update_post_meta( $post_id, '_mural17_int_fonte', $orgao->name );
	update_post_meta( $post_id, '_mural17_orgao_id', (int) $orgao->term_id );
	wp_set_object_terms( $post_id, (int) $cidade->term_id, 'cidade' );

	mural17_avisa_interrupcao( $post_id );
	mural17_volta_aba( 'interrupcoes', 'interrupcao-ok' );
}
add_action( 'admin_post_mural17_interrupcao', 'mural17_recebe_interrupcao' );

/**
 * Texto do e-mail de uma falta de água ou luz.
 *
 * @param int $post_id Aviso.
 * @return string
 */
function mural17_texto_aviso_interrupcao( $post_id ) {
	$linhas   = array();
	$linhas[] = 'Bairros: ' . mural17_campo( $post_id, 'int_bairros' );
	if ( mural17_campo( $post_id, 'int_motivo' ) ) {
		$linhas[] = 'Motivo: ' . mural17_campo( $post_id, 'int_motivo' );
	}
	$linhas[] = 'Começa: ' . mural17_quando_servico( mural17_campo( $post_id, 'int_inicio' ) );
	$linhas[] = 'Previsão de volta: ' . ( mural17_campo( $post_id, 'int_previsao' ) ? mural17_quando_servico( mural17_campo( $post_id, 'int_previsao' ) ) : 'sem previsão' );
	if ( mural17_campo( $post_id, 'int_obs' ) ) {
		$linhas[] = mural17_campo( $post_id, 'int_obs' );
	}
	$linhas[] = 'Fonte: ' . mural17_campo( $post_id, 'int_fonte' );
	return implode( "\n", $linhas );
}

/**
 * "Já voltou": marca como normalizado e avisa os inscritos.
 */
function mural17_recebe_interrupcao_volta() {
	$id = absint( mural17_post( 'id' ) );
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_interrupcao_volta_' . $id ) ) {
		mural17_volta_aba( 'interrupcoes', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	$p     = get_post( $id );
	if ( ! $orgao || ! mural17_post_do_orgao( $p, $orgao, 'interrupcao' ) ) {
		mural17_volta_aba( 'interrupcoes', 'erro-permissao' );
	}
	mural17_normaliza_interrupcao( $p );
	mural17_volta_aba( 'interrupcoes', 'interrupcao-volta' );
}
add_action( 'admin_post_mural17_interrupcao_volta', 'mural17_recebe_interrupcao_volta' );

/**
 * Marca a falta como normalizada (o e-mail sai pelo gancho abaixo).
 *
 * @param WP_Post $p Aviso.
 */
function mural17_normaliza_interrupcao( $p ) {
	if ( ! mural17_campo( $p->ID, 'int_normalizado' ) ) {
		update_post_meta( $p->ID, '_mural17_int_normalizado', mural17_agora() );
	}
}

/**
 * Avisa os inscritos de uma falta nova (uma vez por aviso).
 *
 * @param int $post_id Aviso.
 */
function mural17_avisa_interrupcao( $post_id ) {
	$cidade = mural17_cidade( $post_id );
	if ( ! $cidade || get_post_meta( $post_id, '_mural17_avisado', true ) || 'publish' !== get_post_status( $post_id ) || mural17_campo( $post_id, 'int_normalizado' ) ) {
		return;
	}
	update_post_meta( $post_id, '_mural17_avisado', 1 );
	mural17_avisa_inscritos(
		'agua-luz',
		$cidade->term_id,
		get_the_title( $post_id ),
		mural17_texto_aviso_interrupcao( $post_id ),
		add_query_arg( 'cidade', $cidade->slug, mural17_servicos()['interrupcoes']['url'] )
	);
}

/**
 * Quando a falta é marcada como normalizada (painel do órgão ou redação),
 * quem pediu aviso recebe "a água voltou".
 *
 * @param int    $meta_id ID do meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 */
function mural17_interrupcao_normalizada( $meta_id, $post_id, $chave ) {
	if ( '_mural17_int_normalizado' !== $chave || 'interrupcao' !== get_post_type( $post_id ) || ! get_post_meta( $post_id, '_mural17_avisado', true ) ) {
		return;
	}
	$cidade = mural17_cidade( $post_id );
	if ( ! $cidade ) {
		return;
	}
	$o = 'energia' === mural17_campo( $post_id, 'int_tipo' ) ? 'A energia' : 'A água';
	mural17_avisa_inscritos(
		'agua-luz',
		$cidade->term_id,
		$o . ' voltou em ' . $cidade->name,
		$o . ' foi normalizada em: ' . mural17_campo( $post_id, 'int_bairros' ) . "\nFonte: " . mural17_campo( $post_id, 'int_fonte' ),
		add_query_arg( 'cidade', $cidade->slug, mural17_servicos()['interrupcoes']['url'] )
	);
}
add_action( 'added_post_meta', 'mural17_interrupcao_normalizada', 10, 3 );

/**
 * Falta de água ou luz publicada pela redação também avisa os inscritos.
 *
 * @param string  $novo  Status novo.
 * @param string  $velho Status antigo.
 * @param WP_Post $post  Post.
 */
function mural17_interrupcao_publicada( $novo, $velho, $post ) {
	if ( 'interrupcao' === $post->post_type && 'publish' === $novo && 'publish' !== $velho ) {
		add_action(
			'shutdown',
			function () use ( $post ) {
				mural17_avisa_interrupcao( $post->ID );
			}
		);
	}
}
add_action( 'transition_post_status', 'mural17_interrupcao_publicada', 10, 3 );

/* ---- Farmácia de plantão ---------------------------------------------- */

/**
 * Aba "Farmácia de plantão": cola a escala do mês de uma vez.
 *
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_plantao( $orgao ) {
	$cidade = mural17_cidade_do_orgao( $orgao );
	$prox   = mural17_plantoes( $cidade, 10 );
	$erros  = get_transient( 'mural17_plantao_erros_' . get_current_user_id() );
	?>
	<div class="oficial-painel__grade">
		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Escala de plantão de <?php echo esc_html( $cidade ? $cidade->name : '' ); ?></h2>
			<?php mural17_aviso_oficial(); ?>
			<?php if ( is_array( $erros ) && $erros ) : ?>
				<p class="aviso aviso--espera"><b>Linhas que não entendemos:</b> <?php echo esc_html( implode( ' | ', $erros ) ); ?></p>
				<?php delete_transient( 'mural17_plantao_erros_' . get_current_user_id() ); ?>
			<?php endif; ?>
			<input type="hidden" name="action" value="mural17_plantao_escala">
			<?php wp_nonce_field( 'mural17_plantao_escala', 'mural17_nonce' ); ?>
			<?php mural17_campos_escala(); ?>
			<p class="formulario__acoes">
				<button type="submit" class="botao botao--pilula botao--oficial">Salvar escala</button>
			</p>
		</form>

		<aside class="oficial-minhas">
			<h2>Próximos plantões</h2>
			<?php if ( $prox ) : ?>
				<ul>
					<?php foreach ( $prox as $p ) : ?>
						<li>
							<?php if ( mural17_plantao_agora( $p ) ) : ?>
								<span class="situacao situacao--ok">Agora</span>
							<?php endif; ?>
							<b><?php echo esc_html( get_the_title( $p ) ); ?></b>
							<small><?php echo esc_html( mural17_quando_servico( mural17_campo( $p->ID, 'plantao_inicio' ) ) . ' até ' . mural17_quando_servico( mural17_campo( $p->ID, 'plantao_fim' ) ) ); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="vazio">Nenhum plantão cadastrado daqui para frente.</p>
			<?php endif; ?>
		</aside>
	</div>
	<?php
}

/**
 * Campos da escala (painel do órgão e painel do WordPress).
 */
function mural17_campos_escala() {
	?>
	<p class="campo">
		<label for="escala-texto">Uma linha por dia</label>
		<textarea id="escala-texto" name="escala" rows="12" required placeholder="17/10; Farmácia Central; Rua XV de Novembro, 100, Centro; (17) 3242-0000&#10;18/10; Drogaria São José; Av. Brasil, 2000; (17) 3242-1111&#10;19/10 a 21/10; Farmácia Popular; Rua Sete, 70; (17) 3242-2222"></textarea>
		<span class="campo__dica">Data; nome da farmácia; endereço; telefone; WhatsApp (opcional). Separe com ponto e vírgula. Para vários dias seguidos da mesma farmácia, use “19/10 a 21/10”. Um dia já cadastrado é substituído.</span>
	</p>
	<div class="formulario__dupla">
		<p class="campo">
			<label for="escala-hora">O plantão troca às</label>
			<input id="escala-hora" name="hora" type="time" value="08:00" required>
			<span class="campo__dica">Cada plantão vai desse horário até o mesmo horário do dia seguinte.</span>
		</p>
		<p class="campo">
			<label for="escala-fonte">Fonte</label>
			<input id="escala-fonte" name="fonte" maxlength="80" placeholder="Ex.: escala da Vigilância Sanitária">
		</p>
	</div>
	<?php
}

/**
 * Lê a escala colada e cria os plantões. Um dia já cadastrado na mesma
 * cidade é substituído.
 *
 * @param WP_Term $cidade Cidade.
 * @param string  $texto  Escala, uma linha por dia.
 * @param string  $hora   Hora da troca (H:i).
 * @param string  $fonte  Fonte mostrada na página.
 * @return array{criados:int,erros:string[]}
 */
function mural17_importa_plantoes( $cidade, $texto, $hora, $fonte ) {
	$hora  = preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $hora ) ? $hora : '08:00';
	$tz    = wp_timezone();
	$hoje  = new DateTimeImmutable( 'today', $tz );
	$saida = array(
		'criados' => 0,
		'erros'   => array(),
	);
	$data  = function ( $s ) use ( $hoje, $tz ) {
		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m ) ) {
			return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? new DateTimeImmutable( $s, $tz ) : null;
		}
		if ( ! preg_match( '/^(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?$/', $s, $m ) ) {
			return null;
		}
		$ano = isset( $m[3] ) && '' !== $m[3] ? (int) $m[3] : (int) $hoje->format( 'Y' );
		$ano = $ano < 100 ? 2000 + $ano : $ano;
		if ( ! checkdate( (int) $m[2], (int) $m[1], $ano ) ) {
			return null;
		}
		$d = $hoje->setDate( $ano, (int) $m[2], (int) $m[1] );
		// Sem ano: escala de janeiro colada em dezembro é do ano que vem.
		if ( ( ! isset( $m[3] ) || '' === $m[3] ) && $d < $hoje->modify( '-31 days' ) ) {
			$d = $d->modify( '+1 year' );
		}
		return $d;
	};

	foreach ( preg_split( '/\R/', (string) $texto ) as $linha ) {
		$linha = trim( $linha );
		if ( '' === $linha ) {
			continue;
		}
		$partes = array_map( 'trim', preg_split( '/\s*[;\t|]\s*/', $linha ) );
		if ( count( $partes ) < 2 || '' === $partes[1] ) {
			$saida['erros'][] = $linha;
			continue;
		}
		$dias = preg_split( '/\s+(?:a|até|ate|-)\s+/u', $partes[0] );
		$de   = $data( $dias[0] );
		$ate  = isset( $dias[1] ) ? $data( $dias[1] ) : $de;
		if ( ! $de || ! $ate || $ate < $de || $de->diff( $ate )->days > 62 ) {
			$saida['erros'][] = $linha;
			continue;
		}
		for ( $d = $de; $d <= $ate; $d = $d->modify( '+1 day' ) ) {
			$inicio = $d->format( 'Y-m-d' ) . ' ' . $hora;
			$fim    = $d->modify( '+1 day' )->format( 'Y-m-d' ) . ' ' . $hora;
			$velhos = new WP_Query(
				array(
					'post_type'      => 'plantao',
					'posts_per_page' => 5,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'key'     => '_mural17_plantao_inicio',
							'value'   => $d->format( 'Y-m-d' ),
							'compare' => 'LIKE',
						),
					),
					'tax_query'      => mural17_tax_cidade( $cidade ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			foreach ( $velhos->posts as $id ) {
				wp_delete_post( $id, true );
			}
			$id = wp_insert_post(
				array(
					'post_type'   => 'plantao',
					'post_status' => 'publish',
					'post_title'  => sanitize_text_field( $partes[1] ),
					'post_author' => get_current_user_id(),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			wp_set_object_terms( $id, (int) $cidade->term_id, 'cidade' );
			$meta = array(
				'plantao_endereco' => isset( $partes[2] ) ? $partes[2] : '',
				'plantao_telefone' => isset( $partes[3] ) ? $partes[3] : '',
				'plantao_whatsapp' => isset( $partes[4] ) ? $partes[4] : '',
				'plantao_inicio'   => $inicio,
				'plantao_fim'      => $fim,
				'plantao_fonte'    => $fonte,
			);
			foreach ( $meta as $k => $v ) {
				if ( '' !== $v ) {
					update_post_meta( $id, '_mural17_' . $k, sanitize_text_field( $v ) );
				}
			}
			++$saida['criados'];
		}
	}
	return $saida;
}

/**
 * Recebe a escala do painel do órgão.
 */
function mural17_recebe_escala() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_plantao_escala' ) ) {
		mural17_volta_aba( 'plantao', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao || ! isset( mural17_abas_painel( $orgao )['plantao'] ) ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}
	$cidade = mural17_cidade_do_orgao( $orgao );
	$fonte  = mural17_post( 'fonte' );
	$r      = mural17_importa_plantoes( $cidade, mural17_post( 'escala', true ), mural17_post( 'hora' ), $fonte ? $fonte : 'Escala enviada pela ' . $orgao->name );
	if ( $r['erros'] ) {
		set_transient( 'mural17_plantao_erros_' . get_current_user_id(), array_slice( $r['erros'], 0, 10 ), 10 * MINUTE_IN_SECONDS );
	}
	mural17_volta_aba( 'plantao', $r['criados'] ? 'plantao-ok' : 'erro-plantao' );
}
add_action( 'admin_post_mural17_plantao_escala', 'mural17_recebe_escala' );

/* ---- Concursos -------------------------------------------------------- */

/**
 * Aba "Concursos".
 *
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_concursos( $orgao ) {
	$meus = mural17_posts_do_orgao( $orgao, 'concurso', 10 );
	?>
	<div class="oficial-painel__grade">
		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Novo concurso ou processo seletivo</h2>
			<?php mural17_aviso_oficial(); ?>
			<input type="hidden" name="action" value="mural17_concurso">
			<?php wp_nonce_field( 'mural17_concurso', 'mural17_nonce' ); ?>
			<fieldset class="campo">
				<legend>Tipo</legend>
				<div class="opcoes">
					<label class="opcao"><input type="radio" name="conc_tipo" value="concurso" checked> Concurso</label>
					<label class="opcao"><input type="radio" name="conc_tipo" value="processo"> Processo seletivo</label>
					<label class="opcao"><input type="radio" name="conc_tipo" value="estagio"> Estágio</label>
				</div>
			</fieldset>
			<p class="campo">
				<label for="conc-titulo">Cargos</label>
				<input id="conc-titulo" name="titulo" required maxlength="140" placeholder="Ex.: Professor, agente de saúde e motorista">
			</p>
			<div class="formulario__dupla">
				<p class="campo"><label for="conc-vagas">Vagas</label><input id="conc-vagas" name="conc_vagas" maxlength="80" placeholder="Ex.: 42 + cadastro reserva"></p>
				<p class="campo"><label for="conc-salario">Maior salário ou bolsa (R$)</label><input id="conc-salario" name="conc_salario" inputmode="decimal" maxlength="12" placeholder="Ex.: 4870"></p>
				<p class="campo"><label for="conc-ate">Inscrições até</label><input id="conc-ate" name="conc_inscricoes_ate" type="date" required></p>
				<p class="campo"><label for="conc-prova">Data da prova</label><input id="conc-prova" name="conc_prova" type="date"></p>
				<p class="campo"><label for="conc-taxa">Taxa</label><input id="conc-taxa" name="conc_taxa" maxlength="60" placeholder="Ex.: R$ 45 a R$ 80"></p>
				<p class="campo"><label for="conc-edital">Link do edital</label><input id="conc-edital" name="conc_edital" type="url" required placeholder="https://"></p>
			</div>
			<fieldset class="campo">
				<legend>Escolaridade</legend>
				<div class="opcoes">
					<?php foreach ( mural17_escolaridades() as $k => $nome ) : ?>
						<label class="opcao"><input type="checkbox" name="conc_escolaridade[]" value="<?php echo esc_attr( $k ); ?>"> <?php echo esc_html( $nome ); ?></label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<p class="campo">
				<label for="conc-texto">Resumo (opcional)</label>
				<textarea id="conc-texto" name="texto" rows="4" maxlength="2000"></textarea>
			</p>
			<p class="formulario__acoes">
				<button type="submit" class="botao botao--pilula botao--oficial">Publicar concurso</button>
			</p>
		</form>
		<aside class="oficial-minhas">
			<h2>Concursos enviados</h2>
			<?php if ( $meus ) : ?>
				<ul>
					<?php
					foreach ( $meus as $p ) :
						$prazo = mural17_prazo_concurso( $p->ID );
						?>
						<li>
							<span class="situacao situacao--<?php echo esc_attr( $prazo[0] ); ?>"><?php echo esc_html( $prazo[1] ); ?></span>
							<b><?php echo esc_html( get_the_title( $p ) ); ?></b>
							<small><a href="<?php echo esc_url( get_permalink( $p ) ); ?>">ver</a></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="vazio">Nenhum concurso enviado.</p>
			<?php endif; ?>
			<div class="oficial-minhas__como">
				<b>Como funciona</b>
				<span>O concurso sai na hora em Concursos, com prazo e link do edital. Para corrigir algo depois, fale com a redação.</span>
			</div>
		</aside>
	</div>
	<?php
}

/**
 * Recebe um concurso do painel.
 */
function mural17_recebe_concurso() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_concurso' ) ) {
		mural17_volta_aba( 'concursos', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao || ! isset( mural17_abas_painel( $orgao )['concursos'] ) ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}
	$titulo = mural17_post( 'titulo' );
	$edital = isset( $_POST['conc_edital'] ) ? esc_url_raw( wp_unslash( $_POST['conc_edital'] ) ) : '';
	if ( '' === $titulo || '' === $edital || '' === mural17_post( 'conc_inscricoes_ate' ) ) {
		mural17_volta_aba( 'concursos', 'erro-concurso' );
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'concurso',
			'post_title'   => $titulo,
			'post_content' => mural17_texto_nota( mural17_post( 'texto', true ) ),
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		mural17_volta_aba( 'concursos', 'erro-concurso' );
	}
	$_POST['conc_orgao'] = $orgao->name;
	mural17_salva_campos_post( $post_id, 'concurso', array( 'conc_orgao', 'conc_tipo', 'conc_vagas', 'conc_escolaridade', 'conc_salario', 'conc_taxa', 'conc_inscricoes_ate', 'conc_prova', 'conc_edital' ) );
	update_post_meta( $post_id, '_mural17_orgao_id', (int) $orgao->term_id );
	$cidade = mural17_cidade_do_orgao( $orgao );
	if ( $cidade ) {
		wp_set_object_terms( $post_id, (int) $cidade->term_id, 'cidade' );
	}
	mural17_avisa_concurso( $post_id );
	mural17_volta_aba( 'concursos', 'concurso-ok' );
}
add_action( 'admin_post_mural17_concurso', 'mural17_recebe_concurso' );

/**
 * Avisa os inscritos de um concurso novo (uma vez por concurso).
 *
 * @param int $post_id Concurso.
 */
function mural17_avisa_concurso( $post_id ) {
	if ( get_post_meta( $post_id, '_mural17_avisado', true ) || 'publish' !== get_post_status( $post_id ) ) {
		return;
	}
	$cidade = mural17_cidade( $post_id );
	if ( ! $cidade ) {
		return;
	}
	update_post_meta( $post_id, '_mural17_avisado', 1 );
	$linhas = array( mural17_campo( $post_id, 'conc_orgao' ) . ' · ' . $cidade->name );
	if ( mural17_campo( $post_id, 'conc_vagas' ) ) {
		$linhas[] = 'Vagas: ' . mural17_campo( $post_id, 'conc_vagas' );
	}
	if ( mural17_campo( $post_id, 'conc_salario' ) ) {
		$linhas[] = 'Salário: até ' . mural17_reais( mural17_campo( $post_id, 'conc_salario' ) );
	}
	$ate = mural17_data( mural17_campo( $post_id, 'conc_inscricoes_ate' ) );
	if ( $ate ) {
		$linhas[] = 'Inscrições até ' . $ate->format( 'd/m/Y' );
	}
	mural17_avisa_inscritos( 'concursos', $cidade->term_id, 'Concurso aberto: ' . get_the_title( $post_id ), implode( "\n", $linhas ), get_permalink( $post_id ) );
}

/**
 * Concurso publicado pela redação também avisa os inscritos.
 *
 * @param string  $novo  Status novo.
 * @param string  $velho Status antigo.
 * @param WP_Post $post  Post.
 */
function mural17_concurso_publicado( $novo, $velho, $post ) {
	if ( 'concurso' === $post->post_type && 'publish' === $novo && 'publish' !== $velho && ! wp_doing_ajax() ) {
		// Os campos são salvos depois da mudança de status: o aviso sai no fim do pedido.
		add_action(
			'shutdown',
			function () use ( $post ) {
				mural17_avisa_concurso( $post->ID );
			}
		);
	}
}
add_action( 'transition_post_status', 'mural17_concurso_publicado', 10, 3 );

/* ---- Fala 17: resposta do órgão --------------------------------------- */

/**
 * Aba "Fala, 17": reclamações da cidade esperando resposta.
 *
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_fala( $orgao ) {
	$cidade = mural17_cidade_do_orgao( $orgao );
	$q      = new WP_Query(
		array(
			'post_type'      => 'fala',
			'posts_per_page' => 20,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => '_mural17_fala_situacao',
					'value' => 'aberta',
				),
			),
			'tax_query'      => mural17_tax_cidade( $cidade ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$tipos  = mural17_tipos_fala();
	?>
	<div class="painel-fala">
		<h2>Reclamações abertas em <?php echo esc_html( $cidade ? $cidade->name : '' ); ?></h2>
		<?php mural17_aviso_oficial(); ?>
		<p class="campo__dica">A resposta aparece no site junto da reclamação, com o nome do órgão. Escreva o que foi feito ou quando será feito.</p>
		<?php if ( $q->have_posts() ) : ?>
			<?php foreach ( $q->posts as $p ) : ?>
				<article class="formulario painel-fala__item" id="fala-<?php echo (int) $p->ID; ?>">
					<p class="painel-fala__meta">
						<span class="situacao situacao--erro">Aberta há <?php echo esc_html( human_time_diff( get_post_time( 'U', true, $p ) ) ); ?></span>
						<?php echo esc_html( ( $tipos[ mural17_campo( $p->ID, 'fala_tipo' ) ] ?? 'Outro' ) . ' · ' . mural17_campo( $p->ID, 'fala_onde' ) ); ?>
						· <?php echo esc_html( (int) get_post_meta( $p->ID, '_mural17_eutambem', true ) ); ?> moradores disseram “eu também”
					</p>
					<h3><?php echo esc_html( get_the_title( $p ) ); ?></h3>
					<div class="painel-fala__texto"><?php echo wp_kses_post( wpautop( esc_html( $p->post_content ) ) ); ?></div>
					<?php if ( has_post_thumbnail( $p ) ) : ?>
						<?php echo get_the_post_thumbnail( $p, 'mural17-mini', array( 'class' => 'painel-fala__foto' ) ); ?>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="painel-fala__resposta">
						<input type="hidden" name="action" value="mural17_fala_resposta">
						<input type="hidden" name="id" value="<?php echo (int) $p->ID; ?>">
						<?php wp_nonce_field( 'mural17_fala_resposta_' . $p->ID, 'mural17_nonce' ); ?>
						<p class="campo">
							<label for="resp-<?php echo (int) $p->ID; ?>">Resposta</label>
							<textarea id="resp-<?php echo (int) $p->ID; ?>" name="resposta" rows="3" required maxlength="1200"></textarea>
						</p>
						<p class="campo campo--check">
							<input id="resolv-<?php echo (int) $p->ID; ?>" type="checkbox" name="resolvido" value="1">
							<label for="resolv-<?php echo (int) $p->ID; ?>">O problema já foi resolvido</label>
						</p>
						<button type="submit" class="botao botao--pilula botao--oficial">Responder</button>
					</form>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="vazio">Nenhuma reclamação aberta na cidade agora.</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Recebe a resposta do órgão.
 */
function mural17_recebe_fala_resposta() {
	$id = absint( mural17_post( 'id' ) );
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_fala_resposta_' . $id ) ) {
		mural17_volta_aba( 'fala', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	$p     = get_post( $id );
	$c     = $p ? mural17_cidade( $p->ID ) : null;
	if ( ! $orgao || ! isset( mural17_abas_painel( $orgao )['fala'] ) || ! $p || 'fala' !== $p->post_type || 'publish' !== $p->post_status || ! $c || (int) $c->term_id !== (int) mural17_orgao_meta( $orgao, 'cidade' ) ) {
		mural17_volta_aba( 'fala', 'erro-permissao' );
	}
	$resposta = mural17_post( 'resposta', true );
	if ( '' === $resposta ) {
		mural17_volta_aba( 'fala', 'erro-fala-resposta' );
	}
	update_post_meta( $id, '_mural17_fala_resposta', $resposta );
	update_post_meta( $id, '_mural17_fala_resposta_de', $orgao->name );
	update_post_meta( $id, '_mural17_fala_resposta_em', mural17_agora() );
	update_post_meta( $id, '_mural17_fala_situacao', empty( $_POST['resolvido'] ) ? 'respondida' : 'resolvida' );
	mural17_volta_aba( 'fala', 'fala-respondida' );
}
add_action( 'admin_post_mural17_fala_resposta', 'mural17_recebe_fala_resposta' );

/* ---- Falecimentos (funerárias) ---------------------------------------- */

/**
 * Aba "Falecimentos" das funerárias.
 *
 * @param WP_Term $orgao Funerária.
 */
function mural17_painel_falecimentos( $orgao ) {
	$meus = mural17_posts_do_orgao( $orgao, 'falecimento', 12 );
	?>
	<div class="oficial-painel__grade">
		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Nova nota de falecimento</h2>
			<?php mural17_aviso_oficial(); ?>
			<input type="hidden" name="action" value="mural17_falecimento">
			<?php wp_nonce_field( 'mural17_falecimento', 'mural17_nonce' ); ?>
			<div class="formulario__dupla">
				<p class="campo"><label for="fal-nome">Nome completo</label><input id="fal-nome" name="titulo" required maxlength="120"></p>
				<p class="campo"><label for="fal-idade">Idade</label><input id="fal-idade" name="fal_idade" inputmode="numeric" maxlength="3"></p>
			</div>
			<?php mural17_campo_cidade_painel( $orgao ); ?>
			<p class="campo">
				<label for="fal-texto">Texto da família (opcional)</label>
				<textarea id="fal-texto" name="texto" rows="3" maxlength="600" placeholder="Ex.: Aposentado, deixa esposa, três filhos e sete netos."></textarea>
				<span class="campo__dica">Sem causa da morte.</span>
			</p>
			<div class="formulario__dupla">
				<p class="campo"><label for="fal-vi">Velório: começa</label><input id="fal-vi" name="fal_velorio_inicio" type="datetime-local"></p>
				<p class="campo"><label for="fal-vf">Velório: até</label><input id="fal-vf" name="fal_velorio_fim" type="datetime-local"></p>
			</div>
			<p class="campo"><label for="fal-vl">Local do velório</label><input id="fal-vl" name="fal_velorio_local" maxlength="120"></p>
			<div class="formulario__dupla">
				<p class="campo">
					<label for="fal-st">Despedida</label>
					<select id="fal-st" name="fal_sepult_tipo"><option>Sepultamento</option><option>Cremação</option></select>
				</p>
				<p class="campo"><label for="fal-sq">Quando</label><input id="fal-sq" name="fal_sepult_quando" type="datetime-local"></p>
			</div>
			<p class="campo"><label for="fal-sl">Cemitério ou crematório</label><input id="fal-sl" name="fal_sepult_local" maxlength="120"></p>
			<div class="formulario__dupla">
				<p class="campo"><label for="fal-mq">Missa ou culto de 7º dia</label><input id="fal-mq" name="fal_missa_quando" type="datetime-local"></p>
				<p class="campo"><label for="fal-ml">Onde</label><input id="fal-ml" name="fal_missa_local" maxlength="120"></p>
			</div>
			<p class="campo campo--check">
				<input id="fal-aut" type="checkbox" name="fal_autorizado" value="1" required>
				<label for="fal-aut">A família autorizou a publicação desta nota no Mural 17.</label>
			</p>
			<p class="formulario__acoes">
				<button type="submit" class="botao botao--pilula botao--oficial"><?php echo mural17_publicacao_direta( $orgao ) ? 'Publicar' : 'Enviar para a redação'; ?></button>
			</p>
		</form>
		<aside class="oficial-minhas">
			<h2>Notas enviadas</h2>
			<?php if ( $meus ) : ?>
				<ul>
					<?php
					foreach ( $meus as $p ) :
						$sit = mural17_situacao_nota( $p );
						?>
						<li>
							<span class="situacao situacao--<?php echo esc_attr( $sit[0] ); ?>"><?php echo esc_html( $sit[1] ); ?></span>
							<b><?php echo esc_html( get_the_title( $p ) ); ?></b>
							<small><?php echo esc_html( get_the_date( 'd/m H\hi', $p ) ); ?><?php echo 'publish' === $p->post_status ? ' · <a href="' . esc_url( get_permalink( $p ) ) . '">ver</a>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="vazio">Nenhuma nota enviada.</p>
			<?php endif; ?>
			<div class="oficial-minhas__como">
				<b>Nosso cuidado</b>
				<span>Só com autorização da família. Sem causa da morte e sem foto sem permissão.</span>
				<span>A família pode pedir a retirada a qualquer momento, pela funerária ou direto à redação.</span>
			</div>
		</aside>
	</div>
	<?php
}

/**
 * Recebe a nota de falecimento da funerária.
 */
function mural17_recebe_falecimento() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_falecimento' ) ) {
		mural17_volta_aba( 'falecimentos', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao || ! isset( mural17_abas_painel( $orgao )['falecimentos'] ) ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}
	$nome   = mural17_post( 'titulo' );
	$cidade = mural17_cidade_do_form( $orgao );
	if ( '' === $nome || ! $cidade || empty( $_POST['fal_autorizado'] ) ) {
		mural17_volta_aba( 'falecimentos', 'erro-falecimento' );
	}
	$direta  = mural17_publicacao_direta( $orgao );
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'falecimento',
			'post_title'   => $nome,
			'post_content' => mural17_texto_nota( mural17_post( 'texto', true ) ),
			'post_status'  => $direta ? 'publish' : 'pending',
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		mural17_volta_aba( 'falecimentos', 'erro-falecimento' );
	}
	$_POST['fal_funeraria'] = $orgao->name;
	mural17_salva_campos_post( $post_id, 'falecimento', array_keys( mural17_fields()['falecimento']['campos'] ) );
	update_post_meta( $post_id, '_mural17_orgao_id', (int) $orgao->term_id );
	wp_set_object_terms( $post_id, (int) $cidade->term_id, 'cidade' );
	if ( ! $direta ) {
		wp_mail(
			get_option( 'admin_email' ),
			'[Mural 17] Falecimento para revisar: ' . $nome,
			sprintf( "%s enviou uma nota de falecimento (%s).\n\nRevisar: %s", $orgao->name, $cidade->name, admin_url( 'edit.php?post_status=pending&post_type=falecimento' ) )
		);
	}
	mural17_volta_aba( 'falecimentos', $direta ? 'falecimento-ok' : 'falecimento-revisao' );
}
add_action( 'admin_post_mural17_falecimento', 'mural17_recebe_falecimento' );

/**
 * A página de uma funerária leva para os falecimentos.
 */
function mural17_funeraria_sem_pagina() {
	if ( is_tax( 'orgao' ) && mural17_orgao_privado( get_queried_object() ) ) {
		wp_safe_redirect( get_post_type_archive_link( 'falecimento' ) );
		exit;
	}
}
add_action( 'template_redirect', 'mural17_funeraria_sem_pagina' );

/* -------------------------------------------------------------------------
 * Fala 17: envio público e "eu também"
 * ---------------------------------------------------------------------- */

/**
 * Recebe uma reclamação do site.
 */
function mural17_recebe_fala() {
	$volta = isset( $_POST['voltar'] ) ? esc_url_raw( wp_unslash( $_POST['voltar'] ) ) : mural17_servicos()['fala']['url'];
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_fala' ) ) {
		mural17_volta_para( $volta, 'erro-nonce', 'mandar' );
	}
	if ( ! empty( $_POST['mural17_site_confirma'] ) ) {
		mural17_volta_para( $volta, 'fala-ok', 'mandar' );
	}
	$tipo   = sanitize_key( mural17_post( 'fala_tipo' ) );
	$onde   = mural17_post( 'fala_onde' );
	$texto  = mural17_post( 'texto', true );
	$cidade = mural17_acha_cidade( absint( mural17_post( 'cidade' ) ) );
	if ( ! isset( mural17_tipos_fala()[ $tipo ] ) || '' === $onde || '' === $texto || ! $cidade ) {
		mural17_volta_para( $volta, 'erro-fala', 'mandar' );
	}
	if ( ! mural17_dentro_do_limite( 'fala', 4 ) ) {
		mural17_volta_para( $volta, 'erro-fala-limite', 'mandar' );
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'fala',
			'post_title'   => wp_trim_words( $texto, 14, '…' ),
			'post_content' => $texto,
			'post_status'  => 'pending',
			'post_author'  => 0,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		mural17_volta_para( $volta, 'erro-fala', 'mandar' );
	}
	$_POST['fala_situacao'] = 'aberta';
	$_POST['fala_tipo']     = $tipo;
	mural17_salva_campos_post( $post_id, 'fala', array( 'fala_tipo', 'fala_onde', 'fala_situacao', 'fala_nome', 'fala_contato' ) );
	wp_set_object_terms( $post_id, (int) $cidade->term_id, 'cidade' );
	$foto = mural17_recebe_foto( 'foto', $post_id, 'Foto: leitor do Mural 17' );
	if ( $foto ) {
		set_post_thumbnail( $post_id, $foto );
	}
	wp_mail(
		get_option( 'admin_email' ),
		'[Mural 17] Fala 17: nova reclamação em ' . $cidade->name,
		sprintf( "%s · %s\n\n%s\n\nRevisar e publicar: %s", mural17_tipos_fala()[ $tipo ], $onde, $texto, admin_url( 'post.php?action=edit&post=' . $post_id ) )
	);
	mural17_volta_para( $volta, 'fala-ok', 'mandar' );
}
add_action( 'admin_post_nopriv_mural17_fala', 'mural17_recebe_fala' );
add_action( 'admin_post_mural17_fala', 'mural17_recebe_fala' );

/**
 * "Eu também" numa reclamação aberta. Um por aparelho e por IP.
 */
function mural17_recebe_eutambem() {
	$id    = absint( mural17_post( 'id' ) );
	$p     = get_post( $id );
	$volta = isset( $_POST['voltar'] ) ? esc_url_raw( wp_unslash( $_POST['voltar'] ) ) : mural17_servicos()['fala']['url'];
	$json  = ! empty( $_POST['json'] );
	$ok    = $p && 'fala' === $p->post_type && 'publish' === $p->post_status;
	$qtd   = $ok ? (int) get_post_meta( $id, '_mural17_eutambem', true ) : 0;
	if ( $ok && empty( $_COOKIE[ 'm17_et_' . $id ] ) ) {
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$hash   = substr( wp_hash( $ip . '|' . $id ), 0, 12 );
		$vistos = (array) get_post_meta( $id, '_mural17_eutambem_ips', true );
		if ( ! in_array( $hash, $vistos, true ) ) {
			$vistos[] = $hash;
			update_post_meta( $id, '_mural17_eutambem_ips', array_slice( $vistos, -2000 ) );
			update_post_meta( $id, '_mural17_eutambem', ++$qtd );
		}
		setcookie( 'm17_et_' . $id, '1', time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
	if ( $json ) {
		wp_send_json( array( 'qtd' => $qtd ) );
	}
	mural17_volta_para( $volta, 'eutambem', 'fala-' . $id );
}
add_action( 'admin_post_nopriv_mural17_eutambem', 'mural17_recebe_eutambem' );
add_action( 'admin_post_mural17_eutambem', 'mural17_recebe_eutambem' );

/**
 * Ao publicar uma reclamação, a data de "prefeitura avisada" fica marcada.
 *
 * @param string  $novo  Status novo.
 * @param string  $velho Status antigo.
 * @param WP_Post $post  Post.
 */
function mural17_fala_publicada( $novo, $velho, $post ) {
	if ( 'fala' === $post->post_type && 'publish' === $novo && 'publish' !== $velho ) {
		if ( ! get_post_meta( $post->ID, '_mural17_fala_situacao', true ) ) {
			update_post_meta( $post->ID, '_mural17_fala_situacao', 'aberta' );
		}
		if ( ! get_post_meta( $post->ID, '_mural17_fala_avisado_em', true ) ) {
			update_post_meta( $post->ID, '_mural17_fala_avisado_em', current_time( 'Y-m-d' ) );
		}
	}
}
add_action( 'transition_post_status', 'mural17_fala_publicada', 10, 3 );

/* -------------------------------------------------------------------------
 * Avisos por e-mail
 * ---------------------------------------------------------------------- */

/**
 * Temas de aviso.
 *
 * @return array<string, string>
 */
function mural17_temas_aviso() {
	return array(
		'alertas'   => 'Alertas de tempo e Defesa Civil',
		'agua-luz'  => 'Falta de água e luz',
		'concursos' => 'Concursos e processos seletivos',
		'vagas'     => 'Vagas de emprego (resumo diário)',
	);
}

/**
 * Formulário de inscrição nos avisos.
 *
 * Args: temas (marcados), titulo, texto, botao, escuro (bool), cidade (WP_Term|null).
 *
 * @param array $args Argumentos.
 */
function mural17_form_avisos( $args = array() ) {
	$args   = wp_parse_args(
		$args,
		array(
			'temas'  => array( 'alertas' ),
			'titulo' => 'Receba os avisos',
			'texto'  => 'Escolha a cidade e o que quer receber. Mandamos por e-mail; dá para cancelar com um clique.',
			'botao'  => 'Quero receber',
			'escuro' => true,
			'cidade' => null,
		)
	);
	$cidade = $args['cidade'];
	$uid    = wp_unique_id( 'aviso-' );
	$codigo = isset( $_GET['m17'] ) ? sanitize_key( wp_unslash( $_GET['m17'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$msgs   = mural17_mensagens_servicos();
	$voltar = remove_query_arg( 'm17', ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' ) );
	?>
	<section class="avisos<?php echo $args['escuro'] ? ' avisos--escuro' : ''; ?>" id="avisos" aria-labelledby="<?php echo esc_attr( $uid ); ?>">
		<h2 class="avisos__titulo" id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $args['titulo'] ); ?></h2>
		<?php if ( in_array( $codigo, array( 'inscricao-enviada', 'inscricao-ok', 'erro-inscricao', 'erro-inscricao-limite' ), true ) ) : ?>
			<p class="aviso aviso--<?php echo esc_attr( $msgs[ $codigo ][0] ); ?>" role="status"><?php echo esc_html( $msgs[ $codigo ][1] ); ?></p>
		<?php endif; ?>
		<p class="avisos__texto"><?php echo esc_html( $args['texto'] ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="avisos__form">
			<input type="hidden" name="action" value="mural17_inscrever">
			<input type="hidden" name="voltar" value="<?php echo esc_url( $voltar ); ?>">
			<?php wp_nonce_field( 'mural17_inscrever', 'mural17_nonce' ); ?>
			<p class="formulario__mel" aria-hidden="true"><label>Não preencha<input type="text" name="mural17_site_confirma" tabindex="-1" autocomplete="off"></label></p>
			<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-cidade">Cidade</label>
			<select id="<?php echo esc_attr( $uid ); ?>-cidade" name="cidade">
				<option value="0">Todo o 17</option>
				<?php foreach ( mural17_lista_cidades() as $grupo ) : ?>
					<optgroup label="<?php echo esc_attr( $grupo['regiao']->name ); ?>">
						<option value="<?php echo (int) $grupo['regiao']->term_id; ?>">Toda a <?php echo esc_html( lcfirst( $grupo['regiao']->name ) ); ?></option>
						<?php foreach ( $grupo['cidades'] as $c ) : ?>
							<option value="<?php echo (int) $c->term_id; ?>" <?php selected( $cidade ? $cidade->term_id : 0, $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
			<span class="avisos__temas">
				<?php foreach ( mural17_temas_aviso() as $k => $nome ) : ?>
					<label><input type="checkbox" name="temas[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $args['temas'], true ) ); ?>> <?php echo esc_html( $nome ); ?></label>
				<?php endforeach; ?>
			</span>
			<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-email">Seu e-mail</label>
			<input id="<?php echo esc_attr( $uid ); ?>-email" type="email" name="email" required placeholder="Seu e-mail" autocomplete="email">
			<button type="submit" class="botao botao--pilula"><?php echo esc_html( $args['botao'] ); ?></button>
		</form>
	</section>
	<?php
}

/**
 * Inscrito pelo e-mail.
 *
 * @param string $email E-mail.
 * @return WP_Post|null
 */
function mural17_inscrito_por_email( $email ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'inscrito',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => '_m17_email', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => strtolower( $email ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q->posts ? $q->posts[0] : null;
}

/**
 * Inscrito pelo código do link.
 *
 * @param string $token Código.
 * @return WP_Post|null
 */
function mural17_inscrito_por_token( $token ) {
	if ( strlen( $token ) < 20 ) {
		return null;
	}
	$q = new WP_Query(
		array(
			'post_type'      => 'inscrito',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => '_m17_token', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $token, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q->posts ? $q->posts[0] : null;
}

/**
 * Link de confirmar ou cancelar.
 *
 * @param string $acao  "confirma" ou "cancela".
 * @param string $token Código.
 * @return string
 */
function mural17_link_inscricao( $acao, $token ) {
	return add_query_arg(
		array(
			'action' => 'mural17_' . $acao,
			't'      => $token,
		),
		admin_url( 'admin-post.php' )
	);
}

/**
 * Recebe a inscrição: guarda pendente e manda o e-mail de confirmação.
 */
function mural17_recebe_inscricao() {
	$volta = isset( $_POST['voltar'] ) ? esc_url_raw( wp_unslash( $_POST['voltar'] ) ) : home_url( '/' );
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_inscrever' ) ) {
		mural17_volta_para( $volta, 'erro-nonce', 'avisos' );
	}
	if ( ! empty( $_POST['mural17_site_confirma'] ) ) {
		mural17_volta_para( $volta, 'inscricao-enviada', 'avisos' );
	}
	$email = strtolower( sanitize_email( mural17_post( 'email' ) ) );
	$temas = isset( $_POST['temas'] ) ? array_values( array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['temas'] ) ), array_keys( mural17_temas_aviso() ) ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$cid   = absint( mural17_post( 'cidade' ) );
	$termo = $cid ? get_term( $cid, 'cidade' ) : null;
	if ( ! is_email( $email ) || ! $temas || ( $cid && ( ! $termo || is_wp_error( $termo ) ) ) ) {
		mural17_volta_para( $volta, 'erro-inscricao', 'avisos' );
	}
	if ( ! mural17_dentro_do_limite( 'inscricao', 6 ) ) {
		mural17_volta_para( $volta, 'erro-inscricao-limite', 'avisos' );
	}

	$p = mural17_inscrito_por_email( $email );
	if ( ! $p ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'inscrito',
				'post_status' => 'private',
				'post_title'  => $email,
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			mural17_volta_para( $volta, 'erro-inscricao', 'avisos' );
		}
		update_post_meta( $id, '_m17_email', $email );
		update_post_meta( $id, '_m17_token', wp_generate_password( 32, false ) );
		$p = get_post( $id );
	}
	$cidades = get_post_meta( $p->ID, '_m17_cidade' );
	if ( ! in_array( (string) $cid, array_map( 'strval', $cidades ), true ) ) {
		add_post_meta( $p->ID, '_m17_cidade', $cid );
	}
	foreach ( $temas as $t ) {
		if ( ! in_array( $t, get_post_meta( $p->ID, '_m17_tema' ), true ) ) {
			add_post_meta( $p->ID, '_m17_tema', $t );
		}
	}

	if ( get_post_meta( $p->ID, '_m17_confirmado', true ) ) {
		mural17_volta_para( $volta, 'inscricao-ok', 'avisos' );
	}
	$token = (string) get_post_meta( $p->ID, '_m17_token', true );
	wp_mail(
		$email,
		'Confirme os avisos do Mural 17',
		"Olá!\n\nAlguém (esperamos que você) pediu para receber avisos do Mural 17 neste e-mail.\n\nPara confirmar, abra o link:\n" . mural17_link_inscricao( 'confirma', $token ) . "\n\nSe não foi você, é só ignorar este e-mail.\n\nMural 17 · " . home_url( '/' )
	);
	mural17_volta_para( $volta, 'inscricao-enviada', 'avisos' );
}
add_action( 'admin_post_nopriv_mural17_inscrever', 'mural17_recebe_inscricao' );
add_action( 'admin_post_mural17_inscrever', 'mural17_recebe_inscricao' );

/**
 * Link de confirmação do e-mail.
 */
function mural17_confirma_inscricao() {
	$token = isset( $_GET['t'] ) ? sanitize_key( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$p     = mural17_inscrito_por_token( $token );
	if ( $p ) {
		update_post_meta( $p->ID, '_m17_confirmado', current_time( 'mysql' ) );
	}
	wp_safe_redirect( add_query_arg( 'm17', $p ? 'inscricao-confirmada' : 'erro-inscricao', home_url( '/' ) ) );
	exit;
}
add_action( 'admin_post_nopriv_mural17_confirma', 'mural17_confirma_inscricao' );
add_action( 'admin_post_mural17_confirma', 'mural17_confirma_inscricao' );

/**
 * Link de cancelamento (vai no fim de todo aviso).
 */
function mural17_cancela_inscricao() {
	$token = isset( $_GET['t'] ) ? sanitize_key( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$p     = mural17_inscrito_por_token( $token );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
	wp_safe_redirect( add_query_arg( 'm17', 'inscricao-cancelada', home_url( '/' ) ) );
	exit;
}
add_action( 'admin_post_nopriv_mural17_cancela', 'mural17_cancela_inscricao' );
add_action( 'admin_post_mural17_cancela', 'mural17_cancela_inscricao' );

/**
 * Agenda o envio de um aviso para os inscritos (sai em segundo plano).
 *
 * @param string    $tema    Tema.
 * @param int|int[] $cidades Cidade(s) do aviso.
 * @param string    $assunto Assunto.
 * @param string    $texto   Texto.
 * @param string    $url     Link.
 */
function mural17_avisa_inscritos( $tema, $cidades, $assunto, $texto, $url ) {
	$cidades = array_values( array_unique( array_map( 'intval', (array) $cidades ) ) );
	wp_schedule_single_event( time() + 5, 'mural17_envia_aviso', array( $tema, $cidades, $assunto, $texto, $url, 0 ) );
}

/**
 * Envia um lote de 80 e-mails; se houver mais, agenda o próximo lote.
 * Quem se inscreveu na cidade, na região dela ou em todo o 17 recebe uma vez.
 *
 * @param string    $tema    Tema.
 * @param int|int[] $cidades Cidade(s).
 * @param string    $assunto Assunto.
 * @param string    $texto   Texto.
 * @param string    $url     Link.
 * @param int       $desde   Posição do lote.
 */
function mural17_envia_aviso( $tema, $cidades, $assunto, $texto, $url, $desde = 0 ) {
	$alvos = array( '0' );
	foreach ( (array) $cidades as $id ) {
		$alvos[] = (string) (int) $id;
		$c       = get_term( (int) $id, 'cidade' );
		if ( $c && ! is_wp_error( $c ) && $c->parent ) {
			$alvos[] = (string) $c->parent;
		}
	}
	$alvos = array_values( array_unique( $alvos ) );
	$lote = 80;
	$q    = new WP_Query(
		array(
			'post_type'      => 'inscrito',
			'post_status'    => 'private',
			'posts_per_page' => $lote,
			'offset'         => (int) $desde,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_m17_confirmado',
					'compare' => 'EXISTS',
				),
				array(
					'key'   => '_m17_tema',
					'value' => $tema,
				),
				array(
					'key'     => '_m17_cidade',
					'value'   => $alvos,
					'compare' => 'IN',
				),
			),
		)
	);
	foreach ( $q->posts as $p ) {
		$token = (string) get_post_meta( $p->ID, '_m17_token', true );
		wp_mail(
			(string) get_post_meta( $p->ID, '_m17_email', true ),
			'[Mural 17] ' . $assunto,
			$texto . "\n\nVeja no site: " . $url . "\n\n--\nVocê recebe este aviso porque se inscreveu no Mural 17.\nParar de receber: " . mural17_link_inscricao( 'cancela', $token )
		);
	}
	if ( count( $q->posts ) === $lote ) {
		wp_schedule_single_event( time() + 60, 'mural17_envia_aviso', array( $tema, (array) $cidades, $assunto, $texto, $url, (int) $desde + $lote ) );
	}
}
add_action( 'mural17_envia_aviso', 'mural17_envia_aviso', 10, 6 );
