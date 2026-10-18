<?php
/**
 * Notas oficiais: prefeituras, câmaras e órgãos da Justiça publicando no portal.
 *
 * Cada órgão é um termo da taxonomia "orgao" (página /orgao/<slug>/). A conta
 * de quem envia tem o papel "orgao_oficial" e fica ligada a um órgão pelo meta
 * de usuário mural17_orgao. As notas são posts comuns com o órgão marcado;
 * por padrão entram como "pendente" e a redação aprova em Posts › Notas oficiais.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tipos de órgão. Os marcados como eleitorais seguem a lei eleitoral
 * (publicidade institucional suspensa nos 3 meses antes da eleição).
 *
 * @return array<string, array{nome:string,eleitoral:bool}>
 */
function mural17_tipos_orgao() {
	return array(
		'prefeitura' => array( 'nome' => 'Prefeitura', 'eleitoral' => true ),
		'camara'     => array( 'nome' => 'Câmara Municipal', 'eleitoral' => true ),
		'municipal'  => array( 'nome' => 'Órgão municipal', 'eleitoral' => true ),
		'judiciario' => array( 'nome' => 'Judiciário', 'eleitoral' => false ),
		'mp'         => array( 'nome' => 'Ministério Público', 'eleitoral' => false ),
		'defensoria' => array( 'nome' => 'Defensoria Pública', 'eleitoral' => false ),
		'policia'    => array( 'nome' => 'Polícia', 'eleitoral' => false ),
		'saneamento' => array( 'nome' => 'Serviço de água e esgoto', 'eleitoral' => false, 'livre' => true ),
		'energia'    => array( 'nome' => 'Concessionária de energia', 'eleitoral' => false, 'livre' => true ),
		'outro'      => array( 'nome' => 'Outro órgão público', 'eleitoral' => false ),
		'funeraria'  => array( 'nome' => 'Funerária', 'eleitoral' => false, 'livre' => true, 'privado' => true ),
	);
}

/**
 * O tipo de órgão aceita e-mail de qualquer domínio? (concessionárias e
 * funerárias não usam .gov.br; a redação confere pelo telefone do mesmo jeito.)
 *
 * @param string $tipo Tipo.
 * @return bool
 */
function mural17_tipo_livre( $tipo ) {
	$tipos = mural17_tipos_orgao();
	return ! empty( $tipos[ $tipo ]['livre'] );
}

/**
 * Funerária (empresa privada): não publica notas oficiais, só falecimentos.
 *
 * @param WP_Term $orgao Órgão.
 * @return bool
 */
function mural17_orgao_privado( $orgao ) {
	$tipos = mural17_tipos_orgao();
	$tipo  = mural17_orgao_meta( $orgao, 'tipo' );
	return ! empty( $tipos[ $tipo ]['privado'] );
}

/**
 * Tipos de nota que o órgão escolhe ao enviar.
 *
 * @return array<string, string>
 */
function mural17_tipos_nota() {
	return array(
		'servico' => 'Serviço',
		'agenda'  => 'Agenda',
		'alerta'  => 'Alerta',
		'obras'   => 'Obras',
		'editais' => 'Editais',
	);
}

/**
 * Domínios de e-mail aceitos no cadastro.
 *
 * @return string[]
 */
function mural17_dominios_oficiais() {
	return apply_filters( 'mural17_dominios_oficiais', array( '.sp.gov.br', '.sp.leg.br', '.jus.br', '.mp.br', '.def.br', '.gov.br', '.leg.br' ) );
}

/**
 * O e-mail é de um domínio oficial?
 *
 * @param string $email E-mail.
 * @return bool
 */
function mural17_email_oficial( $email ) {
	$email = strtolower( trim( (string) $email ) );
	if ( ! is_email( $email ) ) {
		return false;
	}
	$dominio = substr( strrchr( $email, '@' ), 1 );
	foreach ( mural17_dominios_oficiais() as $fim ) {
		if ( substr( '.' . $dominio, -strlen( $fim ) ) === $fim ) {
			return true;
		}
	}
	return false;
}

/**
 * Taxonomia dos órgãos e papéis de usuário.
 */
function mural17_registra_oficial() {
	register_taxonomy(
		'orgao',
		array( 'post' ),
		array(
			'labels'            => array(
				'name'          => 'Órgãos oficiais',
				'singular_name' => 'Órgão oficial',
				'search_items'  => 'Buscar órgãos',
				'all_items'     => 'Todos os órgãos',
				'edit_item'     => 'Editar órgão',
				'add_new_item'  => 'Adicionar órgão',
				'menu_name'     => 'Órgãos oficiais',
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => false,
			'rewrite'           => array( 'slug' => 'orgao' ),
		)
	);

	register_post_status(
		'devolvida',
		array(
			'label'                     => 'Devolvida',
			'public'                    => false,
			'protected'                 => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: quantidade. */
			'label_count'               => _n_noop( 'Devolvida <span class="count">(%s)</span>', 'Devolvidas <span class="count">(%s)</span>', 'mural17' ),
		)
	);
}
add_action( 'init', 'mural17_registra_oficial' );

/**
 * Cria os papéis e as páginas de cadastro e de painel. Roda na ativação e,
 * em sites que já usavam o tema, no primeiro acesso ao painel.
 */
function mural17_instala_oficial() {
	// Só "read": o painel recebe a foto pelo próprio formulário, já conferida;
	// sem upload_files a conta não envia arquivos pela API de mídia.
	if ( ! get_role( 'orgao_oficial' ) ) {
		add_role( 'orgao_oficial', 'Órgão oficial', array( 'read' => true ) );
	}
	get_role( 'orgao_oficial' )->remove_cap( 'upload_files' );
	if ( ! get_role( 'orgao_pendente' ) ) {
		add_role( 'orgao_pendente', 'Órgão aguardando aprovação', array( 'read' => true ) );
	}

	$paginas = array(
		'mural17_pagina_cadastro' => array( 'Cadastro de órgão', 'cadastro-de-orgao', '[mural17_cadastro_orgao]' ),
		'mural17_pagina_painel'   => array( 'Painel do órgão', 'painel-do-orgao', '[mural17_painel_orgao]' ),
	);
	foreach ( $paginas as $opcao => $p ) {
		$id = (int) get_option( $opcao );
		if ( $id && get_post( $id ) ) {
			continue;
		}
		$existe = get_page_by_path( $p[1] );
		$id     = $existe ? $existe->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $p[0],
				'post_name'    => $p[1],
				'post_content' => $p[2],
			)
		);
		update_option( $opcao, (int) $id );
	}
}

/**
 * Atualização em sites que já usavam o tema.
 */
function mural17_atualiza_oficial() {
	if ( (int) get_option( 'mural17_oficial_versao' ) >= MURAL17_OFICIAL_VERSAO ) {
		return;
	}
	mural17_registra_oficial();
	mural17_instala_oficial();
	update_option( 'mural17_oficial_versao', MURAL17_OFICIAL_VERSAO );
	flush_rewrite_rules();
}
add_action( 'admin_init', 'mural17_atualiza_oficial' );
add_action( 'after_switch_theme', 'mural17_atualiza_oficial', 20 );

/**
 * Link de uma das páginas do órgão (cadastro ou painel).
 *
 * @param string $qual "cadastro" ou "painel".
 * @return string
 */
function mural17_link_oficial( $qual ) {
	$id = (int) get_option( 'mural17_pagina_' . $qual );
	return $id ? get_permalink( $id ) : home_url( '/' );
}

/**
 * Dado de um órgão (meta do termo).
 *
 * @param WP_Term|int $orgao Órgão.
 * @param string      $chave Chave sem prefixo.
 * @return string
 */
function mural17_orgao_meta( $orgao, $chave ) {
	$id = is_object( $orgao ) ? $orgao->term_id : (int) $orgao;
	return (string) get_term_meta( $id, 'mural17_' . $chave, true );
}

/**
 * Órgão de uma nota, ou null se o post não é nota oficial.
 *
 * @param int|null $post_id Post.
 * @return WP_Term|null
 */
function mural17_orgao_do_post( $post_id = null ) {
	$terms = get_the_terms( $post_id ? $post_id : get_the_ID(), 'orgao' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}
	return $terms[0];
}

/**
 * Órgão da conta logada (só se o órgão estiver ativo).
 *
 * @param int|null $user_id Usuário.
 * @return WP_Term|null
 */
function mural17_orgao_do_usuario( $user_id = null ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	$id      = (int) get_user_meta( $user_id, 'mural17_orgao', true );
	$term    = $id ? get_term( $id, 'orgao' ) : null;
	if ( ! $term || is_wp_error( $term ) || 'ativo' !== mural17_orgao_meta( $term, 'status' ) ) {
		return null;
	}
	return $term;
}

/**
 * Iniciais para o círculo do órgão sem brasão (Prefeitura de Mirassol → PM).
 *
 * @param string $nome Nome.
 * @return string
 */
function mural17_iniciais( $nome ) {
	$fora     = array( 'de', 'do', 'da', 'dos', 'das', 'e', 'municipal' );
	$palavras = array_values(
		array_filter(
			preg_split( '/\s+/', remove_accents( $nome ) ),
			function ( $p ) use ( $fora ) {
				return '' !== $p && ! in_array( strtolower( $p ), $fora, true );
			}
		)
	);
	$ini      = '';
	foreach ( array_slice( $palavras, 0, 2 ) as $p ) {
		$ini .= strtoupper( $p[0] );
	}
	return $ini ? $ini : 'MO';
}

/**
 * Brasão do órgão, ou o círculo com as iniciais.
 *
 * @param WP_Term $orgao   Órgão.
 * @param string  $classe  Classe extra (tamanho).
 */
function mural17_brasao( $orgao, $classe = '' ) {
	$logo = (int) mural17_orgao_meta( $orgao, 'logo' );
	$tipo = mural17_orgao_meta( $orgao, 'tipo' );
	$cls  = 'brasao ' . $classe . ( in_array( $tipo, array( 'judiciario', 'mp', 'defensoria', 'policia' ), true ) ? ' brasao--justica' : '' );
	if ( $logo ) {
		echo '<span class="' . esc_attr( $cls . ' brasao--img' ) . '">' . wp_get_attachment_image( $logo, 'thumbnail', false, array( 'alt' => '' ) ) . '</span>';
		return;
	}
	echo '<span class="' . esc_attr( $cls ) . '" aria-hidden="true">' . esc_html( mural17_iniciais( $orgao->name ) ) . '</span>';
}

/**
 * Ícone do selo (escudo com visto).
 *
 * @param int $tam Tamanho em px.
 * @return string
 */
function mural17_icone_oficial( $tam = 12 ) {
	return '<svg width="' . (int) $tam . '" height="' . (int) $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>';
}

/**
 * Período eleitoral ligado agora? Ligado à mão ou dentro das datas.
 *
 * @return bool
 */
function mural17_periodo_eleitoral() {
	if ( get_option( 'mural17_eleitoral_ligado' ) ) {
		return true;
	}
	$de   = (string) get_option( 'mural17_eleitoral_inicio' );
	$ate  = (string) get_option( 'mural17_eleitoral_fim' );
	$hoje = current_time( 'Y-m-d' );
	return $de && $ate && $hoje >= $de && $hoje <= $ate;
}

/**
 * O órgão é atingido pelo período eleitoral?
 *
 * @param WP_Term $orgao Órgão.
 * @return bool
 */
function mural17_orgao_eleitoral( $orgao ) {
	$tipos = mural17_tipos_orgao();
	$tipo  = mural17_orgao_meta( $orgao, 'tipo' );
	return isset( $tipos[ $tipo ] ) && $tipos[ $tipo ]['eleitoral'];
}

/**
 * Pode publicar sem passar pela redação agora?
 *
 * @param WP_Term $orgao Órgão.
 * @return bool
 */
function mural17_publicacao_direta( $orgao ) {
	if ( ! mural17_orgao_meta( $orgao, 'direta' ) ) {
		return false;
	}
	return ! ( mural17_periodo_eleitoral() && mural17_orgao_eleitoral( $orgao ) );
}

/**
 * Conta de órgão não entra no painel do WordPress nem vê a barra de admin.
 */
function mural17_bloqueia_admin_orgao() {
	// admin-post.php (envio de nota) e o upload de mídia passam por admin_init e precisam seguir.
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
	if ( wp_doing_ajax() || ! is_user_logged_in() || in_array( $script, array( 'admin-post.php', 'async-upload.php' ), true ) ) {
		return;
	}
	$u = wp_get_current_user();
	if ( array_intersect( array( 'orgao_oficial', 'orgao_pendente' ), (array) $u->roles ) && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( mural17_link_oficial( 'painel' ) );
		exit;
	}
}
add_action( 'admin_init', 'mural17_bloqueia_admin_orgao', 1 );

/**
 * Esconde a barra de admin das contas de órgão.
 *
 * @param bool $mostra Mostrar.
 * @return bool
 */
function mural17_barra_admin_orgao( $mostra ) {
	$u = wp_get_current_user();
	if ( $u && array_intersect( array( 'orgao_oficial', 'orgao_pendente' ), (array) $u->roles ) ) {
		return false;
	}
	return $mostra;
}
add_filter( 'show_admin_bar', 'mural17_barra_admin_orgao' );

/**
 * Depois do login, conta de órgão vai para o painel do órgão.
 *
 * @param string           $para    Destino.
 * @param string           $pedido  Destino pedido.
 * @param WP_User|WP_Error $usuario Usuário.
 * @return string
 */
function mural17_login_orgao( $para, $pedido, $usuario ) {
	if ( $usuario instanceof WP_User && array_intersect( array( 'orgao_oficial', 'orgao_pendente' ), (array) $usuario->roles ) ) {
		return mural17_link_oficial( 'painel' );
	}
	return $para;
}
add_filter( 'login_redirect', 'mural17_login_orgao', 10, 3 );

/**
 * No editor de blocos, o órgão é escolhido na caixa "Nota oficial", não no
 * painel padrão de taxonomia.
 *
 * @param WP_REST_Response $resposta Resposta.
 * @param WP_Taxonomy      $tax      Taxonomia.
 * @return WP_REST_Response
 */
function mural17_esconde_painel_orgao( $resposta, $tax ) {
	if ( 'orgao' === $tax->name && isset( $resposta->data['visibility'] ) ) {
		$resposta->data['visibility']['show_ui'] = false;
	}
	return $resposta;
}
add_filter( 'rest_prepare_taxonomy', 'mural17_esconde_painel_orgao', 10, 2 );

/**
 * Notas oficiais recentes de um escopo (região, cidade ou o 17 inteiro).
 *
 * @param WP_Term|null $escopo Escopo.
 * @param int          $qtd    Quantidade.
 * @return WP_Query
 */
function mural17_notas_oficiais( $escopo, $qtd = 3 ) {
	// Direto no WP_Query: mural17_consulta() trocaria o tax_query pelo do escopo.
	return new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => $qtd,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'relation' => 'AND' ),
				mural17_tax_escopo( $escopo ),
				array(
					array(
						'taxonomy' => 'orgao',
						'operator' => 'EXISTS',
					),
				)
			),
		)
	);
}

/**
 * Filtro por tipo de nota na página do órgão (?tipo=servico).
 *
 * @param WP_Query $query Consulta.
 */
function mural17_filtro_tipo_nota( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'orgao' ) ) {
		return;
	}
	$tipo = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $tipo && isset( mural17_tipos_nota()[ $tipo ] ) ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_mural17_nota_tipo',
					'value' => $tipo,
				),
			)
		);
	}
	$query->set( 'posts_per_page', 12 );
}
add_action( 'pre_get_posts', 'mural17_filtro_tipo_nota' );
