<?php
/**
 * Portal de Notícias PRO: cada matéria importada entra na cidade certa.
 *
 * 1. Feed com cidade marcada no cadastro: usa essa.
 * 2. Feed de prefeitura ou câmara: a cidade do nome ("Prefeitura de
 *    Mirassol") ou do endereço (mirassol.sp.gov.br).
 * 3. Feed regional (G1, jornais): as cidades citadas no título e no texto.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Portal de Notícias PRO: liga o campo "Cidade" no cadastro de feed também
 * em sites onde o plugin chegou depois do tema (a ativação do tema já tinha
 * passado). Só liga se ninguém escolheu outra coisa.
 */
function mural17_liga_cidade_no_feed() {
	if ( '' === get_option( 'pfai_feed_tax_ativo', '' ) ) {
		update_option( 'pfai_feed_tax_ativo', 1 );
	}
}
add_action( 'admin_init', 'mural17_liga_cidade_no_feed' );

/**
 * No cadastro de feed, só a Cidade: órgão oficial não vem de feed.
 *
 * @param array $taxonomias Taxonomias extras do feed.
 * @return array
 */
function mural17_taxonomias_do_feed( $taxonomias ) {
	return array_intersect_key( (array) $taxonomias, array( 'cidade' => true ) );
}
add_filter( 'pfai_feed_taxonomias_extras', 'mural17_taxonomias_do_feed' );

/**
 * Cidade de um feed do Portal PRO: a marcada no cadastro do feed ou, se não
 * tiver (feeds do "Importar lista" não têm), a que aparece no nome
 * ("Prefeitura de Mirassol") ou no endereço (mirassol.sp.gov.br).
 *
 * @param int $feed_id Feed.
 * @return int ID da cidade, ou 0.
 */
function mural17_cidade_do_feed( $feed_id ) {
	static $cache = array();
	$feed_id = (int) $feed_id;
	if ( isset( $cache[ $feed_id ] ) ) {
		return $cache[ $feed_id ];
	}
	$cache[ $feed_id ] = 0;
	if ( class_exists( 'PFAI_Feed_Taxonomias' ) ) {
		$marcadas = PFAI_Feed_Taxonomias::do_feed( $feed_id );
		if ( ! empty( $marcadas['cidade'] ) ) {
			$cache[ $feed_id ] = (int) $marcadas['cidade'];
			return $cache[ $feed_id ];
		}
	}
	global $wpdb;
	$tabela = $wpdb->prefix . 'pfai_feeds';
	$feed   = $wpdb->get_row( $wpdb->prepare( "SELECT name, url FROM {$tabela} WHERE id = %d", $feed_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	if ( ! $feed ) {
		return 0;
	}
	$nome = ' ' . mural17_normaliza( $feed->name ) . ' ';
	$host = (string) wp_parse_url( (string) $feed->url, PHP_URL_HOST );
	$host = preg_replace( '/^www\./', '', strtolower( $host ) );
	$sub  = str_replace( '-', '', strtok( $host, '.' ) );

	$mapa = mural17_mapa_nomes_cidades();
	// Nomes mais longos primeiro: "Santa Fé do Sul" antes de "Sul".
	uksort(
		$mapa,
		function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);
	foreach ( $mapa as $n => $id ) {
		$compacto = str_replace( ' ', '', $n );
		if ( false !== strpos( $nome, ' ' . $n . ' ' ) || ( $host && ( $sub === $compacto || 0 === strpos( $host, $compacto . '.' ) ) ) ) {
			$cache[ $feed_id ] = (int) $id;
			return $cache[ $feed_id ];
		}
	}
	// Endereços que não usam o nome inteiro.
	$apelidos = array(
		'riopreto' => 'rio-preto',
	);
	if ( isset( $apelidos[ $sub ] ) ) {
		$t = get_term_by( 'slug', $apelidos[ $sub ], 'cidade' );
		$cache[ $feed_id ] = $t ? (int) $t->term_id : 0;
	}
	return $cache[ $feed_id ];
}

/**
 * Matéria importada sem cidade ganha a cidade do feed.
 *
 * @param int    $meta_id ID do meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 * @param mixed  $valor   Valor.
 */
function mural17_cidade_na_importacao( $meta_id, $post_id, $chave, $valor ) {
	if ( '_pfai_feed_id' !== $chave || ! (int) $valor ) {
		return;
	}
	$atuais = wp_get_object_terms( $post_id, 'cidade', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $atuais ) && $atuais ) {
		return;
	}
	$cidade = mural17_cidade_do_feed( (int) $valor );
	if ( $cidade ) {
		wp_set_object_terms( $post_id, array( $cidade ), 'cidade', false );
		return;
	}
	// Feed regional (G1, jornais da região): a cidade sai do texto da matéria.
	$post    = get_post( $post_id );
	$cidades = $post ? mural17_cidades_no_texto( $post->post_title, $post->post_excerpt . ' ' . $post->post_content ) : array();
	if ( $cidades ) {
		wp_set_object_terms( $post_id, $cidades, 'cidade', false );
	}
}
add_action( 'added_post_meta', 'mural17_cidade_na_importacao', 20, 4 );
add_action( 'updated_post_meta', 'mural17_cidade_na_importacao', 20, 4 );

/**
 * Nomes de cidade que também são palavra comum, sobrenome, nome de pessoa,
 * país ou cidade de outro estado. Só contam com sinal claro de lugar: "em
 * Sales", "Prefeitura de Sales", "Sales (SP)".
 *
 * @return string[]
 */
function mural17_cidades_ambiguas() {
	return array( 'Adolfo', 'Altair', 'Bálsamo', 'Jaci', 'Mendonça', 'Palestina', 'Sales', 'Novais', 'Paraíso', 'Cardoso', 'Floreal', 'Parisi', 'Macedônia', 'São Francisco', 'Turmalina', 'Colina', 'Colômbia', 'Guaíra', 'Terra Roxa', 'Magda', 'Novo Horizonte', 'Onda Verde', 'Paulo de Faria', 'Poloni', 'Uchoa', 'Américo de Campos', 'Pontes Gestal', 'Valentim Gentil', 'Meridiano', 'Aspásia', 'Dirce Reis', 'Santa Albertina', 'Santa Salete', 'Vitória Brasil', 'Três Fronteiras', 'Guaraci', 'Jaborandi', 'Monções', 'Gastão Vidigal', 'General Salgado', 'Pindorama', 'Cedral', 'Nova Aliança', 'Urânia' );
}

/**
 * Cidades do 17 citadas numa matéria, da mais citada para a menos (até 3).
 * O título vale mais que o texto. Nomes ambíguos só contam com sinal de
 * lugar ao lado.
 *
 * @param string $titulo Título.
 * @param string $texto  Resumo e texto.
 * @return int[] IDs das cidades.
 */
function mural17_cidades_no_texto( $titulo, $texto ) {
	$titulo = wp_strip_all_tags( (string) $titulo );
	$texto  = wp_strip_all_tags( (string) $texto );
	if ( '' === trim( $titulo . $texto ) ) {
		return array();
	}
	$ambiguas = array_map( 'mural17_normaliza', mural17_cidades_ambiguas() );
	$nomes    = array();
	foreach ( mural17_lista_cidades() as $grupo ) {
		foreach ( $grupo['cidades'] as $c ) {
			$nomes[ $c->term_id ] = array( $c->name );
		}
	}
	// "Rio Preto" é como a região fala de São José do Rio Preto.
	$rp = get_term_by( 'slug', 'rio-preto', 'cidade' );
	if ( $rp ) {
		$nomes[ $rp->term_id ][] = 'Rio Preto';
	}

	// Comparação sem acento, mas mantendo maiúsculas: cidade é nome próprio.
	$t_tit = remove_accents( $titulo );
	$t_txt = remove_accents( $texto );
	$lugar = '(?:\b[Ee]m|\b[Nn]a cidade de|\bno municipio de|\bmunicipio de|\bcidade de|\bPrefeitura de|\bPrefeitura Municipal de|\bCamara (?:Municipal )?de|\bSanta Casa de)\s+';

	// Nomes maiores primeiro, e cada achado sai do texto: "São José do Rio
	// Preto" não conta de novo como "Rio Preto", nem "Mirassolândia" como
	// "Mirassol".
	$todos = array();
	foreach ( $nomes as $id => $lista ) {
		foreach ( $lista as $nome ) {
			$todos[] = array( $id, $nome );
		}
	}
	usort(
		$todos,
		function ( $a, $b ) {
			return strlen( $b[1] ) <=> strlen( $a[1] );
		}
	);

	$pontos = array();
	foreach ( $todos as $par ) {
		list( $id, $nome ) = $par;
		$n   = preg_quote( remove_accents( $nome ), '/' );
		$n   = str_replace( "'", "['’ ]?", $n );
		$amb = in_array( mural17_normaliza( $nome ), $ambiguas, true );
		$re  = $amb
			? '/(?:' . $lugar . $n . '(?![\p{L}])|(?<![\p{L}-])' . $n . '\s*(?:\(SP\)|-SP\b|\/SP\b| - SP\b))/u'
			: '/(?<![\p{L}-])' . $n . '(?![\p{L}])/u';
		$t_tit = preg_replace( $re, ' ', $t_tit, -1, $c1 );
		$t_txt = preg_replace( $re, ' ', $t_txt, -1, $c2 );
		$p     = 3 * $c1 + $c2;
		if ( $p ) {
			$pontos[ $id ] = ( $pontos[ $id ] ?? 0 ) + $p;
		}
	}
	arsort( $pontos );
	return array_slice( array_keys( $pontos ), 0, 3 );
}

/**
 * Busca do Google News de cada cidade do 17. Nomes que também são palavra
 * comum ou sobrenome vão com a cidade-polo da região, para a busca não trazer
 * notícia de outro lugar.
 *
 * @return array[] Lista de array( 'cidade' => ID, 'nome' => nome do feed, 'url' => endereço ).
 */
function mural17_feeds_google_news() {
	$com_polo = array( 'Adolfo', 'Altair', 'Bálsamo', 'Jaci', 'Mendonça', 'Palestina', 'Sales', 'Novais', 'Paraíso', 'Cardoso', 'Floreal', 'Parisi', 'Macedônia', 'São Francisco', 'Turmalina', 'Colina', 'Colômbia', 'Guaíra', 'Terra Roxa', 'Magda' );
	$feeds    = array();
	foreach ( mural17_regioes() as $slug_regiao => $regiao ) {
		$polo = 'regiao-rio-preto' === $slug_regiao ? 'São José do Rio Preto' : str_replace( 'Região de ', '', $regiao['nome'] );
		foreach ( $regiao['cidades'] as $slug => $nome ) {
			$termo = get_term_by( 'slug', $slug, 'cidade' );
			$busca = '"' . $nome . '" ' . ( in_array( $nome, $com_polo, true ) ? '"' . $polo . '"' : 'SP' );
			$feeds[] = array(
				'cidade' => $termo ? (int) $termo->term_id : 0,
				'nome'   => 'Google News - ' . $nome,
				'url'    => 'https://news.google.com/rss/search?q=' . rawurlencode( $busca ) . '&hl=pt-BR&gl=BR&ceid=BR:pt-419',
			);
		}
	}
	return $feeds;
}

/**
 * Cadastra no Portal de Notícias PRO os feeds do Google News que ainda não
 * existem. A importação em massa do plugin passa cada linha por
 * sanitize_text_field, que apaga o %22/%20 dos endereços de busca; aqui o
 * endereço vai inteiro para o save_feed do próprio plugin.
 *
 * @param array $opcoes frequency, ai_rewrite, ai_image.
 * @return array|WP_Error array( 'criados' => n, 'existiam' => n ).
 */
function mural17_cadastra_google_news( $opcoes ) {
	if ( ! class_exists( 'PFAI_Feed_Manager' ) || ! method_exists( 'PFAI_Feed_Manager', 'save_feed' ) ) {
		return new WP_Error( 'sem_plugin', 'O plugin Portal de Notícias PRO não está ativo.' );
	}
	global $wpdb;
	$tabela = $wpdb->prefix . 'pfai_feeds';
	$r      = array(
		'criados'  => 0,
		'existiam' => 0,
	);
	foreach ( mural17_feeds_google_news() as $f ) {
		$ja = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tabela} WHERE url = %s LIMIT 1", esc_url_raw( $f['url'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		if ( $ja ) {
			++$r['existiam'];
			continue;
		}
		$id = PFAI_Feed_Manager::save_feed(
			array(
				'name'        => $f['nome'],
				'url'         => $f['url'],
				'type'        => 'rss',
				'category_id' => 0,
				'frequency'   => $opcoes['frequency'],
				'ai_rewrite'  => $opcoes['ai_rewrite'],
				'ai_image'    => $opcoes['ai_image'],
				'status'      => 'active',
			)
		);
		if ( $id ) {
			++$r['criados'];
		}
	}
	return $r;
}

add_action(
	'admin_post_mural17_admin_google_news',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_google_news' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		$r = mural17_cadastra_google_news(
			array(
				'frequency'  => sanitize_key( mural17_post( 'frequency' ) ),
				'ai_rewrite' => (int) ! empty( $_POST['ai_rewrite'] ), // phpcs:ignore WordPress.Security.NonceVerification
				'ai_image'   => (int) ! empty( $_POST['ai_image'] ), // phpcs:ignore WordPress.Security.NonceVerification
			)
		);
		update_option( 'mural17_google_news', is_wp_error( $r ) ? $r->get_error_message() : $r, false );
		mural17_volta_admin_servicos( is_wp_error( $r ) ? 'gn-erro' : 'gn', 'google-news' );
	}
);
