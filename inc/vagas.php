<?php
/**
 * Empregos no 17: vagas anunciadas no portal, listas dos PATs das
 * prefeituras e vagas de outros sites (Jooble), com o resumo diário por
 * e-mail para quem se inscreve.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---- Listas do PAT ------------------------------------------------------ */

/**
 * Tipo privado: a lista do dia de cada PAT.
 */
function mural17_registra_pat() {
	register_post_type(
		'listapat',
		array(
			'labels'             => array(
				'name'          => 'Listas do PAT',
				'singular_name' => 'Lista do PAT',
				'menu_name'     => 'Listas do PAT',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'mural17-servicos',
			'show_in_rest'       => false,
			'supports'           => array( 'title' ),
			'taxonomies'         => array( 'cidade' ),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		)
	);
	register_taxonomy_for_object_type( 'cidade', 'listapat' );
}
add_action( 'init', 'mural17_registra_pat', 11 );

/**
 * Lê as linhas coladas de uma lista do PAT. Aceita "12; Pedreiro; Experiência",
 * "12 - Pedreiro", "Pedreiro (12 vagas)", "Pedreiro - 12 vagas" e só o cargo.
 *
 * @param string $texto Texto.
 * @return array( itens, erros ).
 */
function mural17_le_lista_pat( $texto ) {
	$itens = array();
	$erros = array();
	foreach ( preg_split( '/\R/', (string) $texto ) as $linha ) {
		$linha = trim( preg_replace( '/^[\s•\-–*·]+/u', '', $linha ) );
		if ( '' === $linha || mb_strlen( $linha ) > 200 ) {
			if ( '' !== $linha ) {
				$erros[] = $linha;
			}
			continue;
		}
		$qtd  = 0;
		$req  = '';
		$p    = array_map( 'trim', preg_split( '/\s*[;\t|]\s*/', $linha ) );
		if ( count( $p ) >= 2 && preg_match( '/^\d{1,4}$/', $p[0] ) ) {
			$qtd   = (int) $p[0];
			$cargo = $p[1];
			$req   = isset( $p[2] ) ? implode( ' · ', array_slice( $p, 2 ) ) : '';
		} elseif ( count( $p ) >= 2 && preg_match( '/^\d{1,4}$/', end( $p ) ) ) {
			$qtd   = (int) end( $p );
			$cargo = $p[0];
			$req   = count( $p ) > 2 ? implode( ' · ', array_slice( $p, 1, -1 ) ) : '';
		} elseif ( preg_match( '/^(\d{1,4})\s*(?:vagas?)?\s*[-–:x]\s*(.+)$/iu', $linha, $m ) ) {
			$qtd   = (int) $m[1];
			$cargo = $m[2];
		} elseif ( preg_match( '/^(.+?)\s*[-–(:]\s*(\d{1,4})\s*vagas?\)?\s*$/iu', $linha, $m ) ) {
			$cargo = $m[1];
			$qtd   = (int) $m[2];
		} else {
			$cargo = $p[0];
			$req   = count( $p ) > 1 ? implode( ' · ', array_slice( $p, 1 ) ) : '';
		}
		$cargo = trim( $cargo, " \t-–:;," );
		if ( mb_strlen( $cargo ) < 3 ) {
			$erros[] = $linha;
			continue;
		}
		$itens[] = array(
			'qtd'   => max( 0, min( 999, $qtd ) ),
			'cargo' => mb_strtoupper( mb_substr( $cargo, 0, 1 ) ) . mb_substr( $cargo, 1 ),
			'req'   => $req,
		);
	}
	return array( $itens, $erros );
}

/**
 * Grava a lista do dia de um PAT (troca a do mesmo dia, se houver).
 *
 * @param WP_Term $cidade Cidade.
 * @param string  $texto  Linhas.
 * @param string  $fonte  Quem enviou.
 * @return array( criados, erros ).
 */
function mural17_importa_pat( $cidade, $texto, $fonte ) {
	list( $itens, $erros ) = mural17_le_lista_pat( $texto );
	return array( mural17_grava_pat( $cidade, $itens, $fonte ), $erros );
}

/**
 * Grava itens já lidos como a lista do dia do PAT de uma cidade.
 *
 * @param WP_Term $cidade Cidade.
 * @param array[] $itens  Itens (qtd, cargo, req).
 * @param string  $fonte  Quem enviou.
 * @param bool    $so_mesma_fonte Não troca a lista de hoje se ela veio de outra fonte.
 * @return int Itens gravados.
 */
function mural17_grava_pat( $cidade, $itens, $fonte, $so_mesma_fonte = false ) {
	if ( ! $cidade || ! $itens ) {
		return 0;
	}
	$hoje  = wp_date( 'Y-m-d' );
	$velha = get_posts(
		array(
			'post_type'      => 'listapat',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => $hoje . ' 00:00:00', 'inclusive' => true ) ),
			'tax_query'      => array( array( 'taxonomy' => 'cidade', 'field' => 'term_id', 'terms' => $cidade->term_id, 'include_children' => false ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $velha && $so_mesma_fonte && get_post_meta( $velha[0], '_mural17_pat_fonte', true ) !== $fonte ) {
		return 0; // O próprio PAT já mandou a lista de hoje: ela vale mais.
	}
	$id = wp_insert_post(
		array(
			'ID'          => $velha ? $velha[0] : 0,
			'post_type'   => 'listapat',
			'post_status' => 'publish',
			'post_title'  => 'PAT de ' . $cidade->name . ' · ' . wp_date( 'd/m/Y' ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	wp_set_object_terms( $id, array( (int) $cidade->term_id ), 'cidade' );
	update_post_meta( $id, '_mural17_pat_itens', $itens );
	update_post_meta( $id, '_mural17_pat_fonte', sanitize_text_field( $fonte ) );
	return count( $itens );
}

/**
 * Lista mais recente do PAT de uma cidade (até 4 dias), ou null.
 *
 * @param WP_Term $cidade Cidade.
 * @return array|null array( post, itens, total, quando ).
 */
function mural17_lista_pat( $cidade ) {
	if ( ! $cidade ) {
		return null;
	}
	$p = get_posts(
		array(
			'post_type'      => 'listapat',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'date_query'     => array( array( 'after' => '4 days ago' ) ),
			'tax_query'      => array( array( 'taxonomy' => 'cidade', 'field' => 'term_id', 'terms' => $cidade->term_id, 'include_children' => false ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( ! $p ) {
		return null;
	}
	$itens = (array) get_post_meta( $p[0]->ID, '_mural17_pat_itens', true );
	return array(
		'post'   => $p[0],
		'itens'  => $itens,
		'total'  => array_sum( wp_list_pluck( $itens, 'qtd' ) ),
		'quando' => (int) get_post_modified_time( 'U', true, $p[0] ),
		'fonte'  => (string) get_post_meta( $p[0]->ID, '_mural17_pat_fonte', true ),
	);
}

/**
 * Dados fixos do PAT de uma cidade: endereço, horário, documentos, telefone.
 *
 * @param WP_Term $cidade Cidade.
 * @return array
 */
function mural17_info_pat( $cidade ) {
	$i = $cidade ? (array) get_term_meta( $cidade->term_id, 'mural17_pat_info', true ) : array();
	return wp_parse_args(
		$i,
		array(
			'endereco'   => '',
			'horario'    => '',
			'documentos' => 'RG, CPF e carteira de trabalho (pode ser a digital)',
			'telefone'   => '',
			'link'       => '',
		)
	);
}

/**
 * Grava os dados fixos do PAT vindos de um formulário.
 *
 * @param WP_Term $cidade Cidade.
 */
function mural17_salva_info_pat( $cidade ) {
	if ( ! $cidade ) {
		return;
	}
	$i = array();
	foreach ( array( 'endereco', 'horario', 'documentos', 'telefone' ) as $k ) {
		$i[ $k ] = mural17_post( 'pat_' . $k );
	}
	$i['link'] = esc_url_raw( mural17_post( 'pat_link' ) );
	update_term_meta( $cidade->term_id, 'mural17_pat_info', $i );
}

/**
 * Campos do formulário da lista do PAT (painel do órgão e do WordPress).
 *
 * @param WP_Term|null $cidade Cidade (para preencher os dados fixos).
 */
function mural17_campos_pat( $cidade = null ) {
	$i = mural17_info_pat( $cidade );
	?>
	<p class="campo">
		<label for="pat-lista">Vagas de hoje, uma por linha</label>
		<textarea id="pat-lista" name="lista" rows="10" required placeholder="12; Ajudante de motorista; Ensino fundamental&#10;8; Operador de caixa; Ensino médio, 6 meses de experiência&#10;Pedreiro (5 vagas)"></textarea>
		<span class="campo__dica">Pode colar do jeito que a lista sai no site ou no WhatsApp: "quantidade; cargo; requisito", "Cargo (5 vagas)" ou só o cargo.</span>
	</p>
	<details class="campo">
		<summary>Endereço, horário e documentos do PAT</summary>
		<p class="campo"><label for="pat-end">Endereço</label><input id="pat-end" name="pat_endereco" value="<?php echo esc_attr( $i['endereco'] ); ?>"></p>
		<p class="campo"><label for="pat-hor">Horário de atendimento</label><input id="pat-hor" name="pat_horario" value="<?php echo esc_attr( $i['horario'] ); ?>" placeholder="seg a sex, 8h às 17h"></p>
		<p class="campo"><label for="pat-doc">Documentos para levar</label><input id="pat-doc" name="pat_documentos" value="<?php echo esc_attr( $i['documentos'] ); ?>"></p>
		<p class="campo"><label for="pat-tel">Telefone</label><input id="pat-tel" name="pat_telefone" value="<?php echo esc_attr( $i['telefone'] ); ?>"></p>
		<p class="campo"><label for="pat-link">Página oficial das vagas</label><input id="pat-link" name="pat_link" type="url" value="<?php echo esc_attr( $i['link'] ); ?>" placeholder="https://"></p>
	</details>
	<?php
}

/**
 * Aba "Vagas do PAT" no painel do órgão.
 *
 * @param WP_Term $orgao Órgão.
 */
function mural17_painel_pat( $orgao ) {
	$cidade = mural17_cidade_do_orgao( $orgao );
	$lista  = mural17_lista_pat( $cidade );
	$erros  = get_transient( 'mural17_pat_erros_' . get_current_user_id() );
	?>
	<div class="oficial-painel__grade">
		<form class="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Vagas do PAT de <?php echo esc_html( $cidade ? $cidade->name : '' ); ?></h2>
			<?php mural17_aviso_oficial(); ?>
			<?php if ( is_array( $erros ) && $erros ) : ?>
				<p class="aviso aviso--espera"><b>Linhas que não entendemos:</b> <?php echo esc_html( implode( ' | ', $erros ) ); ?></p>
				<?php delete_transient( 'mural17_pat_erros_' . get_current_user_id() ); ?>
			<?php endif; ?>
			<input type="hidden" name="action" value="mural17_pat_lista">
			<?php wp_nonce_field( 'mural17_pat_lista', 'mural17_nonce' ); ?>
			<?php mural17_campos_pat( $cidade ); ?>
			<p class="formulario__acoes"><button type="submit" class="botao botao--pilula botao--oficial">Publicar a lista de hoje</button></p>
		</form>
		<aside class="oficial-minhas">
			<h2>No ar agora</h2>
			<?php if ( $lista ) : ?>
				<p><b><?php echo (int) $lista['total']; ?> vagas</b> em <?php echo count( $lista['itens'] ); ?> cargos · <?php echo esc_html( mural17_quando_servico( wp_date( 'Y-m-d H:i', $lista['quando'] ) ) ); ?></p>
				<ul>
					<?php foreach ( array_slice( $lista['itens'], 0, 8 ) as $it ) : ?>
						<li><b><?php echo esc_html( $it['cargo'] ); ?></b><?php echo $it['qtd'] ? ' <small>' . (int) $it['qtd'] . ' vaga(s)</small>' : ''; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="vazio">Nenhuma lista nos últimos 4 dias. A lista some do site depois de 4 dias sem atualização.</p>
			<?php endif; ?>
		</aside>
	</div>
	<?php
}

/**
 * Recebe a lista do painel do órgão.
 */
function mural17_recebe_pat() {
	if ( ! isset( $_POST['mural17_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mural17_nonce'] ) ), 'mural17_pat_lista' ) ) {
		mural17_volta_aba( 'pat', 'erro-nonce' );
	}
	$orgao = mural17_orgao_do_usuario();
	if ( ! $orgao || ! isset( mural17_abas_painel( $orgao )['pat'] ) ) {
		mural17_volta_oficial( 'painel', 'erro-permissao' );
	}
	$cidade = mural17_cidade_do_orgao( $orgao );
	mural17_salva_info_pat( $cidade );
	list( $n, $erros ) = mural17_importa_pat( $cidade, mural17_post( 'lista', true ), 'Lista enviada pela ' . $orgao->name );
	if ( $erros ) {
		set_transient( 'mural17_pat_erros_' . get_current_user_id(), array_slice( $erros, 0, 10 ), 10 * MINUTE_IN_SECONDS );
	}
	mural17_volta_aba( 'pat', $n ? 'pat-ok' : 'erro-pat' );
}
add_action( 'admin_post_mural17_pat_lista', 'mural17_recebe_pat' );

add_action(
	'admin_post_mural17_admin_pat',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_pat' );
		$cidade = mural17_acha_cidade( absint( mural17_post( 'cidade' ) ) );
		if ( ! $cidade ) {
			mural17_volta_admin_servicos( 'pat-0', 'pat' );
		}
		mural17_salva_info_pat( $cidade );
		list( $n ) = mural17_importa_pat( $cidade, mural17_post( 'lista', true ), 'Redação do Mural 17' );
		mural17_volta_admin_servicos( $n ? 'pat' : 'pat-0', 'pat' );
	}
);

/* ---- Balcão de Empregos de Rio Preto ------------------------------------ */

/**
 * Nome da fonte gravado nas listas que vêm do Balcão.
 */
const MURAL17_BALCAO_FONTE = 'Balcão de Empregos da Prefeitura de Rio Preto';

/**
 * Endereço da lista pública do Balcão.
 *
 * @return string
 */
function mural17_balcao_url() {
	return apply_filters( 'mural17_balcao_url', 'https://www.riopreto.sp.gov.br/balcaoempregos/vagas.php' );
}

/**
 * Linhas da tabela de vagas de uma página do Balcão.
 *
 * @param string $html Página.
 * @return array[] chave, cargo, local, qtd, desc.
 */
function mural17_linhas_balcao( $html ) {
	$linhas = array();
	if ( ! preg_match( '/<table id="idTabelaVaga".*?<tbody>(.*?)<\/tbody>/s', (string) $html, $t ) ) {
		return $linhas;
	}
	preg_match_all( '/<tr>(.*?)<\/tr>/s', $t[1], $trs );
	foreach ( $trs[1] as $tr ) {
		preg_match_all( '/<td[^>]*>(.*?)<\/td>/s', $tr, $tds );
		if ( count( $tds[1] ) < 4 || ! preg_match( "/redirecionarVaga\('([0-9a-f]{16,})'\)/", $tr, $ch ) ) {
			continue;
		}
		$c = array_map(
			function ( $x ) {
				$x = html_entity_decode( html_entity_decode( wp_strip_all_tags( $x ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				return trim( preg_replace( '/\s+/u', ' ', $x ) );
			},
			$tds[1]
		);
		$cargo = mb_convert_case( mb_strtolower( $c[0] ), MB_CASE_TITLE );
		$cargo = preg_replace_callback(
			'/\b(De|Da|Do|Das|Dos|E|Em|Para|Com)\b/u',
			function ( $m ) {
				return mb_strtolower( $m[1] );
			},
			$cargo
		);
		$cargo = preg_replace_callback(
			'/\((\w{1,2})\)/u',
			function ( $m ) {
				return '(' . mb_strtolower( $m[1] ) . ')';
			},
			$cargo
		);
		if ( mb_strlen( $cargo ) < 3 ) {
			continue;
		}
		$desc = preg_replace( '/\s*\.\.\.$/', '…', $c[3] );
		if ( $desc === mb_strtoupper( $desc ) ) {
			$desc = mb_strtoupper( mb_substr( $desc, 0, 1 ) ) . mb_strtolower( mb_substr( $desc, 1 ) ); // Sem gritar.
		}
		$linhas[] = array(
			'chave' => $ch[1],
			'cargo' => mb_substr( $cargo, 0, 100 ),
			'local' => mb_substr( $c[1], 0, 120 ),
			'qtd'   => preg_match( '/(\d{1,4})/', $c[2], $m ) ? (int) $m[1] : 1,
			'desc'  => mb_substr( $desc, 0, 160 ),
		);
	}
	return $linhas;
}

/**
 * Cidade do 17 de um "Local" do Balcão. Quase tudo é Rio Preto (escrito de
 * vinte jeitos, ou só um endereço); vale outra cidade quando ela aparece no texto.
 *
 * @param string  $local Local.
 * @param WP_Term $rp    Rio Preto.
 * @return int term_id.
 */
function mural17_cidade_balcao( $local, $rp ) {
	static $mapa = null;
	if ( null === $mapa ) {
		$mapa = mural17_mapa_nomes_cidades();
		unset( $mapa[ mural17_normaliza( $rp->name ) ] );
		uksort(
			$mapa,
			function ( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			}
		);
	}
	$n = ' ' . mural17_normaliza( $local ) . ' ';
	if ( false === strpos( $n, ' rio preto ' ) && false === strpos( $n, ' sjrp ' ) ) {
		foreach ( $mapa as $nome => $id ) {
			// Nome de cidade em endereço ("Vicinal João Parisi") não é a cidade.
			if ( false !== strpos( $n, ' ' . $nome . ' ' ) && ! preg_match( '/ (vicinal|rua|r|av|avenida|rodovia|rod|estrada|jardim|jd|bairro|praca|joao|jose)( [a-z]+)? ' . preg_quote( $nome, '/' ) . ' /', $n ) ) {
				return (int) $id;
			}
		}
	}
	return (int) $rp->term_id;
}

/**
 * Lê todas as páginas do Balcão de Empregos de Rio Preto e grava as vagas
 * como a lista do dia de cada cidade (quase sempre Rio Preto).
 *
 * A lista é paginada por POST com um token que muda a cada página e vale
 * para a sessão (cookie), por isso cada página usa o token da anterior.
 *
 * @return int|WP_Error Vagas lidas.
 */
function mural17_balcao_atualiza() {
	$rp = get_term_by( 'slug', 'rio-preto', 'cidade' );
	if ( ! $rp ) {
		return new WP_Error( 'balcao', 'Cidade Rio Preto não cadastrada.' );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	$url     = mural17_balcao_url();
	$args    = array( 'timeout' => 20, 'user-agent' => 'Mozilla/5.0 (compatible; Mural17/1.0)' );
	$r       = wp_remote_get( $url, $args );
	$cookies = array();
	$linhas  = array();
	$erro    = '';
	for ( $p = 1; $p <= 120; $p++ ) {
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
			$erro = ( is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $r ) ) . ' na página ' . $p;
			break;
		}
		foreach ( wp_remote_retrieve_cookies( $r ) as $c ) {
			$cookies[ $c->name ] = $c;
		}
		$html  = wp_remote_retrieve_body( $r );
		$novas = 0;
		foreach ( mural17_linhas_balcao( $html ) as $l ) {
			if ( ! isset( $linhas[ $l['chave'] ] ) ) {
				$linhas[ $l['chave'] ] = $l;
				++$novas;
			}
		}
		if ( ! $novas ) {
			break; // Passou da última página (ou ela repetiu a anterior).
		}
		if ( ! preg_match( '/id="idFormBuscarVagas"[^>]*>\s*<input type="hidden" name="tokenCSRF" value="([0-9a-f]+)"/', $html, $tk ) ) {
			$erro = 'Não achei o token da paginação na página ' . $p . ' (o site mudou?)';
			break;
		}
		$r = wp_remote_post(
			$url,
			$args + array(
				'cookies' => array_values( $cookies ),
				'body'    => array(
					'tokenCSRF' => $tk[1],
					's'         => '',
					'p'         => (string) ( $p + 1 ),
				),
			)
		);
	}
	if ( ! $linhas ) {
		$erro = $erro ? $erro : 'A página abriu, mas sem nenhuma vaga na tabela (o site mudou?)';
		update_option( 'mural17_balcao_erro', array( $erro, time() ), false );
		return new WP_Error( 'balcao', $erro );
	}

	// Junta os anúncios do mesmo cargo na mesma cidade: na lista do portal
	// interessa "Ajudante geral, 14 vagas", não seis linhas iguais.
	$por_cidade = array();
	$vagas      = 0;
	foreach ( $linhas as $l ) {
		$cid = mural17_cidade_balcao( $l['local'], $rp );
		$k   = mural17_normaliza( $l['cargo'] );
		if ( ! isset( $por_cidade[ $cid ][ $k ] ) ) {
			$por_cidade[ $cid ][ $k ] = array(
				'qtd'   => 0,
				'cargo' => $l['cargo'],
				'req'   => $l['desc'],
				'n'     => 0,
			);
		}
		$i         = &$por_cidade[ $cid ][ $k ];
		$i['qtd'] += $l['qtd'];
		$i['n']++;
		if ( '' === $i['req'] ) {
			$i['req'] = $l['desc'];
		}
		unset( $i );
		$vagas += $l['qtd'];
	}
	$cidades = 0;
	foreach ( $por_cidade as $cid => $itens ) {
		ksort( $itens );
		$itens = array_map(
			function ( $i ) {
				if ( $i['n'] > 1 ) {
					$i['req'] = $i['n'] . ' anúncios de empresas diferentes';
				}
				unset( $i['n'] );
				return $i;
			},
			array_values( $itens )
		);
		if ( mural17_grava_pat( get_term( $cid, 'cidade' ), $itens, MURAL17_BALCAO_FONTE, (int) $cid !== (int) $rp->term_id ) ) {
			++$cidades;
		}
	}
	update_option(
		'mural17_balcao',
		array(
			'em'       => time(),
			'anuncios' => count( $linhas ),
			'vagas'    => $vagas,
			'cidades'  => $cidades,
		),
		false
	);
	if ( $erro ) {
		update_option( 'mural17_balcao_erro', array( $erro . ' (gravei o que veio até ali)', time() ), false );
	} else {
		delete_option( 'mural17_balcao_erro' );
	}
	delete_transient( 'mural17_contagem_vagas' );
	return $vagas;
}

add_action( 'mural17_le_balcao', 'mural17_balcao_atualiza' );

add_action(
	'admin_post_mural17_admin_balcao',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_balcao' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		$r = mural17_balcao_atualiza();
		mural17_volta_admin_servicos( is_wp_error( $r ) ? 'balcao-erro' : 'balcao', 'balcao' );
	}
);

/* ---- Vagas de outros sites (Jooble) ------------------------------------- */

/**
 * Chave da API do Jooble.
 *
 * @return string
 */
function mural17_jooble_chave() {
	if ( defined( 'MURAL17_JOOBLE_CHAVE' ) && MURAL17_JOOBLE_CHAVE ) {
		return (string) MURAL17_JOOBLE_CHAVE;
	}
	return trim( (string) get_option( 'mural17_jooble_chave' ) );
}

/**
 * Cidades consultadas no Jooble: as maiores do 17 (as pequenas quase não
 * aparecem nos sites de emprego, e as vagas delas vêm junto da cidade-polo).
 *
 * @return string[] Slugs.
 */
function mural17_cidades_jooble() {
	$lista = array( 'rio-preto', 'catanduva', 'votuporanga', 'barretos', 'fernandopolis', 'jales', 'mirassol', 'olimpia', 'bebedouro', 'santa-fe-do-sul', 'novo-horizonte', 'jose-bonifacio', 'tanabi', 'monte-aprazivel', 'bady-bassitt', 'guaira', 'monte-azul-paulista', 'nova-granada', 'pindorama', 'auriflama' );
	$coords = mural17_coordenadas();
	return array_values(
		array_filter(
			$lista,
			function ( $s ) use ( $coords ) {
				return isset( $coords[ $s ] );
			}
		)
	);
}

/**
 * Busca as vagas no Jooble, cidade por cidade, e guarda as dos últimos 15 dias.
 *
 * @return int|WP_Error Quantas vagas ficaram guardadas.
 */
function mural17_jooble_atualiza() {
	$chave = mural17_jooble_chave();
	if ( '' === $chave ) {
		return new WP_Error( 'sem_chave', 'Falta a chave do Jooble.' );
	}
	$guardado = get_option( 'mural17_vagas_ext' );
	$itens    = is_array( $guardado ) && isset( $guardado['itens'] ) ? $guardado['itens'] : array();
	$erro     = null;
	foreach ( mural17_cidades_jooble() as $slug ) {
		$c = get_term_by( 'slug', $slug, 'cidade' );
		if ( ! $c ) {
			continue;
		}
		$r = wp_remote_post(
			apply_filters( 'mural17_jooble_url', 'https://jooble.org/api/' . rawurlencode( $chave ) ),
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'keywords'     => '',
						'location'     => $c->name . ', SP',
						'page'         => '1',
						'ResultOnPage' => '50',
					)
				),
			)
		);
		$codigo = is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r );
		if ( 200 !== $codigo ) {
			$erro = is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . $codigo . ( 403 === $codigo || 401 === $codigo ? ' (chave recusada pelo Jooble)' : '' );
			if ( in_array( $codigo, array( 401, 403 ), true ) ) {
				break;
			}
			continue;
		}
		$j = json_decode( wp_remote_retrieve_body( $r ), true );
		foreach ( isset( $j['jobs'] ) && is_array( $j['jobs'] ) ? $j['jobs'] : array() as $v ) {
			$it = mural17_le_vaga_jooble( $v, $c );
			if ( $it ) {
				$itens[ $it['id'] ] = $it;
			}
		}
	}
	$limite = time() - 15 * DAY_IN_SECONDS;
	$itens  = array_filter(
		$itens,
		function ( $i ) use ( $limite ) {
			return $i['data'] >= $limite;
		}
	);
	uasort(
		$itens,
		function ( $a, $b ) {
			return $b['data'] <=> $a['data'];
		}
	);
	$itens = array_slice( $itens, 0, 1500, true );
	update_option(
		'mural17_vagas_ext',
		array(
			'em'    => time(),
			'itens' => $itens,
		),
		false
	);
	if ( $erro ) {
		update_option( 'mural17_jooble_erro', array( $erro, time() ), false );
	} else {
		delete_option( 'mural17_jooble_erro' );
	}
	return count( $itens );
}

/**
 * Vaga do Jooble no formato do portal, só se for de uma cidade do 17.
 *
 * @param array   $v       Vaga.
 * @param WP_Term $buscada Cidade consultada.
 * @return array|null
 */
function mural17_le_vaga_jooble( $v, $buscada ) {
	$titulo = trim( wp_strip_all_tags( html_entity_decode( (string) ( $v['title'] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) );
	$link   = esc_url_raw( (string) ( $v['link'] ?? '' ) );
	if ( '' === $titulo || '' === $link ) {
		return null;
	}
	$local = trim( wp_strip_all_tags( (string) ( $v['location'] ?? '' ) ) );
	$mapa  = mural17_mapa_nomes_cidades();
	$cid   = 0;
	$n     = ' ' . mural17_normaliza( $local ) . ' ';
	uksort(
		$mapa,
		function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);
	foreach ( $mapa as $nome => $id ) {
		if ( false !== strpos( $n, ' ' . $nome . ' ' ) ) {
			$cid = (int) $id;
			break;
		}
	}
	if ( ! $cid && false !== strpos( $n, ' rio preto ' ) ) {
		$cid = (int) $buscada->term_id;
	}
	if ( ! $cid ) {
		return null; // Vaga de fora do 17 (o Jooble busca num raio em volta).
	}
	$quando = strtotime( (string) ( $v['updated'] ?? '' ) );
	return array(
		'id'      => 'j' . substr( md5( (string) ( $v['id'] ?? $link ) ), 0, 12 ),
		'titulo'  => mb_substr( $titulo, 0, 140 ),
		'empresa' => trim( wp_strip_all_tags( html_entity_decode( (string) ( $v['company'] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) ),
		'cidade'  => $cid,
		'salario' => trim( wp_strip_all_tags( (string) ( $v['salary'] ?? '' ) ) ),
		'tipo'    => trim( wp_strip_all_tags( (string) ( $v['type'] ?? '' ) ) ),
		'resumo'  => wp_trim_words( wp_strip_all_tags( html_entity_decode( (string) ( $v['snippet'] ?? '' ), ENT_QUOTES, 'UTF-8' ) ), 24, '…' ),
		'link'    => $link,
		'fonte'   => trim( (string) ( $v['source'] ?? '' ) ),
		'data'    => $quando ? $quando : time(),
	);
}

/**
 * Vagas de outros sites numa cidade (ou região, ou no 17), filtradas.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @param string       $busca  Texto.
 * @param string       $tipo   Tipo.
 * @return array[]
 */
function mural17_vagas_externas( $escopo = null, $busca = '', $tipo = '' ) {
	$g     = get_option( 'mural17_vagas_ext' );
	$itens = is_array( $g ) && isset( $g['itens'] ) ? array_values( $g['itens'] ) : array();
	$ids   = $escopo ? mural17_ids_escopo( $escopo ) : null;
	$b     = mural17_normaliza( $busca );
	$t     = mural17_normaliza( $tipo );
	return array_values(
		array_filter(
			$itens,
			function ( $i ) use ( $ids, $b, $t ) {
				if ( $ids && ! in_array( (int) $i['cidade'], $ids, true ) ) {
					return false;
				}
				if ( $b && false === strpos( mural17_normaliza( $i['titulo'] . ' ' . $i['empresa'] ), $b ) ) {
					return false;
				}
				return ! $t || false !== strpos( mural17_normaliza( $i['tipo'] . ' ' . $i['titulo'] ), $t );
			}
		)
	);
}

/**
 * IDs das cidades de um escopo (a cidade, ou as da região).
 *
 * @param WP_Term $escopo Cidade ou região.
 * @return int[]
 */
function mural17_ids_escopo( $escopo ) {
	if ( mural17_e_regiao( $escopo ) ) {
		return array_map( 'intval', get_terms( array( 'taxonomy' => 'cidade', 'parent' => $escopo->term_id, 'fields' => 'ids', 'hide_empty' => false ) ) );
	}
	return array( (int) $escopo->term_id );
}

/* ---- Vagas do portal e contagens ---------------------------------------- */

/**
 * Consulta das vagas anunciadas no Mural 17 (abertas).
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @param string       $busca  Texto.
 * @param string       $tipo   Tipo.
 * @param int          $qtd    Quantas (-1 = todas).
 * @return WP_Query
 */
function mural17_vagas_portal( $escopo = null, $busca = '', $tipo = '', $qtd = -1 ) {
	$args = array(
		'post_type'           => 'vaga',
		'posts_per_page'      => $qtd,
		'ignore_sticky_posts' => true,
		'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array( 'key' => '_mural17_vaga_validade', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_mural17_vaga_validade', 'value' => '' ),
				array( 'key' => '_mural17_vaga_validade', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
			),
		),
		'date_query'          => array( array( 'after' => '60 days ago' ) ),
	);
	if ( $tipo ) {
		$args['meta_query'][] = array( 'key' => '_mural17_vaga_tipo', 'value' => $tipo );
	}
	if ( $busca ) {
		$args['s'] = $busca;
	}
	if ( $escopo ) {
		$args['tax_query'] = mural17_tax_cidade( $escopo ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	return new WP_Query( $args );
}

/**
 * Vagas abertas por cidade (portal + PAT + outros sites), para a lateral e
 * os contadores. Guardado por 30 minutos.
 *
 * @return array<int, int> term_id => vagas.
 */
function mural17_contagem_vagas() {
	$c = get_transient( 'mural17_contagem_vagas' );
	if ( is_array( $c ) ) {
		return $c;
	}
	$c = array();
	$q = mural17_vagas_portal( null, '', '', -1 );
	foreach ( $q->posts as $p ) {
		$cid = mural17_cidade( $p->ID );
		if ( $cid ) {
			$c[ $cid->term_id ] = ( $c[ $cid->term_id ] ?? 0 ) + 1;
		}
	}
	$listas = get_posts(
		array(
			'post_type'      => 'listapat',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'date_query'     => array( array( 'after' => '4 days ago' ) ),
		)
	);
	$vistas = array();
	foreach ( $listas as $l ) {
		$t = wp_get_object_terms( $l->ID, 'cidade', array( 'fields' => 'ids' ) );
		$id = $t && ! is_wp_error( $t ) ? (int) $t[0] : 0;
		if ( ! $id || isset( $vistas[ $id ] ) ) {
			continue; // Só a lista mais nova de cada cidade.
		}
		$vistas[ $id ] = true;
		$itens         = (array) get_post_meta( $l->ID, '_mural17_pat_itens', true );
		$c[ $id ]      = ( $c[ $id ] ?? 0 ) + max( count( $itens ), array_sum( wp_list_pluck( $itens, 'qtd' ) ) );
	}
	foreach ( mural17_vagas_externas() as $i ) {
		$c[ (int) $i['cidade'] ] = ( $c[ (int) $i['cidade'] ] ?? 0 ) + 1;
	}
	arsort( $c );
	set_transient( 'mural17_contagem_vagas', $c, 30 * MINUTE_IN_SECONDS );
	return $c;
}
add_action(
	'save_post',
	function ( $id, $p ) {
		if ( in_array( $p->post_type, array( 'vaga', 'listapat' ), true ) ) {
			delete_transient( 'mural17_contagem_vagas' );
		}
	},
	10,
	2
);

/* ---- Agendamento e resumo por e-mail ------------------------------------ */

add_action(
	'init',
	function () {
		if ( mural17_jooble_chave() && ! wp_next_scheduled( 'mural17_le_jooble' ) ) {
			wp_schedule_event( time() + 120, 'twicedaily', 'mural17_le_jooble' );
		} elseif ( ! mural17_jooble_chave() && wp_next_scheduled( 'mural17_le_jooble' ) ) {
			wp_clear_scheduled_hook( 'mural17_le_jooble' );
		}
		if ( ! wp_next_scheduled( 'mural17_le_balcao' ) ) {
			$hoje_6h = mural17_data( wp_date( 'Y-m-d' ) . ' 06:10' );
			wp_schedule_event( $hoje_6h && $hoje_6h->getTimestamp() > time() ? $hoje_6h->getTimestamp() : time() + 180, 'twicedaily', 'mural17_le_balcao' );
		}
		if ( ! wp_next_scheduled( 'mural17_resumo_vagas' ) ) {
			$amanha_7h = mural17_data( wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ) . ' 07:00' );
			wp_schedule_event( $amanha_7h ? $amanha_7h->getTimestamp() : time() + DAY_IN_SECONDS, 'daily', 'mural17_resumo_vagas' );
		}
	}
);
add_action(
	'mural17_le_jooble',
	function () {
		mural17_jooble_atualiza();
		delete_transient( 'mural17_contagem_vagas' );
	}
);

/**
 * Vagas novas das últimas 24 horas num escopo, em texto para o e-mail.
 *
 * @param WP_Term|null $escopo Cidade, região ou o 17 inteiro.
 * @return string Vazio se não houver nada novo.
 */
function mural17_texto_resumo_vagas( $escopo ) {
	$desde  = time() - DAY_IN_SECONDS;
	$linhas = array();
	$q      = mural17_vagas_portal( $escopo, '', '', 20 );
	foreach ( $q->posts as $p ) {
		if ( (int) get_post_time( 'U', true, $p ) >= $desde ) {
			$e        = (string) get_post_meta( $p->ID, '_mural17_vaga_empresa', true );
			$linhas[] = '• ' . get_the_title( $p ) . ( $e ? ' · ' . $e : '' ) . "\n  " . get_permalink( $p );
		}
	}
	$cidades = $escopo ? array_map( 'get_term', mural17_ids_escopo( $escopo ) ) : array();
	foreach ( $cidades as $c ) {
		$l = mural17_lista_pat( $c );
		if ( $l && $l['quando'] >= $desde ) {
			$linhas[] = '• ' . ( MURAL17_BALCAO_FONTE === $l['fonte'] ? 'Balcão de Empregos de Rio Preto' . ( 'rio-preto' === $c->slug ? '' : ' (vagas em ' . $c->name . ')' ) : 'PAT de ' . $c->name ) . ': ' . ( $l['total'] ? $l['total'] . ' vagas em ' : '' ) . count( $l['itens'] ) . ' cargos (' . implode( ', ', array_slice( wp_list_pluck( $l['itens'], 'cargo' ), 0, 5 ) ) . ( count( $l['itens'] ) > 5 ? '…' : '' ) . ')';
		}
	}
	$ext = array_filter(
		mural17_vagas_externas( $escopo ),
		function ( $i ) use ( $desde ) {
			return $i['data'] >= $desde;
		}
	);
	foreach ( array_slice( $ext, 0, 15 ) as $i ) {
		$c        = get_term( (int) $i['cidade'], 'cidade' );
		$linhas[] = '• ' . $i['titulo'] . ( $i['empresa'] ? ' · ' . $i['empresa'] : '' ) . ( $c && ! is_wp_error( $c ) ? ' · ' . $c->name : '' ) . "\n  " . $i['link'];
	}
	return $linhas ? implode( "\n", $linhas ) : '';
}

/**
 * Resumo diário: cada inscrito em "vagas" recebe um e-mail com as novas da
 * sua cidade (ou região, ou do 17), só se houver novidade. Lotes de 80.
 *
 * @param int $desde Offset do lote.
 */
function mural17_envia_resumo_vagas( $desde = 0 ) {
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
				array( 'key' => '_m17_confirmado', 'compare' => 'EXISTS' ),
				array( 'key' => '_m17_tema', 'value' => 'vagas' ),
			),
		)
	);
	$feitos = array();
	foreach ( $q->posts as $p ) {
		$cid = (int) get_post_meta( $p->ID, '_m17_cidade', true );
		if ( ! isset( $feitos[ $cid ] ) ) {
			$escopo         = $cid ? get_term( $cid, 'cidade' ) : null;
			$feitos[ $cid ] = array( $escopo, mural17_texto_resumo_vagas( $escopo && ! is_wp_error( $escopo ) ? $escopo : null ) );
		}
		list( $escopo, $texto ) = $feitos[ $cid ];
		if ( '' === $texto ) {
			continue;
		}
		$onde  = $escopo && ! is_wp_error( $escopo ) ? mural17_nome_curto( $escopo ) : 'no 17';
		$url   = add_query_arg( $escopo && ! is_wp_error( $escopo ) ? array( 'cidade' => $escopo->term_id ) : array(), get_post_type_archive_link( 'vaga' ) );
		$token = (string) get_post_meta( $p->ID, '_m17_token', true );
		wp_mail(
			(string) get_post_meta( $p->ID, '_m17_email', true ),
			'[Mural 17] Vagas novas ' . ( $escopo && ! is_wp_error( $escopo ) ? 'em ' : '' ) . $onde . ' · ' . wp_date( 'd/m' ),
			"Vagas que entraram nas últimas 24 horas:\n\n" . $texto . "\n\nTodas as vagas: " . $url . "\n\n--\nVocê recebe este resumo porque se inscreveu no Mural 17.\nParar de receber: " . mural17_link_inscricao( 'cancela', $token )
		);
	}
	if ( count( $q->posts ) === $lote ) {
		wp_schedule_single_event( time() + 60, 'mural17_resumo_vagas', array( (int) $desde + $lote ) );
	}
}
add_action( 'mural17_resumo_vagas', 'mural17_envia_resumo_vagas', 10, 1 );

add_action(
	'admin_post_mural17_admin_jooble',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_jooble' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		if ( isset( $_POST['chave'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$nova = trim( preg_replace( '/[^A-Za-z0-9\-]/', '', (string) wp_unslash( $_POST['chave'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			if ( '' !== $nova || ! empty( $_POST['apagar'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				update_option( 'mural17_jooble_chave', $nova, false );
			}
		}
		if ( '' === mural17_jooble_chave() ) {
			delete_option( 'mural17_vagas_ext' );
			wp_clear_scheduled_hook( 'mural17_le_jooble' );
			mural17_volta_admin_servicos( 'jooble-sem', 'vagas' );
		}
		$r = mural17_jooble_atualiza();
		delete_transient( 'mural17_contagem_vagas' );
		mural17_volta_admin_servicos( is_wp_error( $r ) || get_option( 'mural17_jooble_erro' ) ? 'jooble-erro' : 'jooble', 'vagas' );
	}
);

/* ---- Página Empregos ---------------------------------------------------- */

/**
 * Na página Empregos, ?cidade= é filtro da própria página (cidade, região ou
 * todo o 17), não a taxonomia: sem isto o WordPress viraria arquivo de cidade.
 *
 * @param WP_Query $q Consulta.
 */
function mural17_consulta_empregos( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( 'vaga' ) ) {
		return;
	}
	if ( '' !== (string) $q->get( 'cidade' ) ) {
		$q->set( 'cidade', '' );
		$q->set( 'tax_query', array() );
		$q->is_tax = false;
	}
	$q->set( 'posts_per_page', 1 ); // A página monta as próprias listas.
}
add_action( 'pre_get_posts', 'mural17_consulta_empregos', 5 );

/**
 * Escopo da página Empregos: ?cidade=<id ou slug de cidade ou região>, ou
 * nada (todo o 17).
 *
 * @return WP_Term|null
 */
function mural17_escopo_empregos() {
	$v = isset( $_GET['cidade'] ) ? sanitize_title( wp_unslash( $_GET['cidade'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $v || '0' === $v ) {
		return null;
	}
	$t = is_numeric( $v ) ? get_term( (int) $v, 'cidade' ) : get_term_by( 'slug', $v, 'cidade' );
	return $t && ! is_wp_error( $t ) ? $t : null;
}
