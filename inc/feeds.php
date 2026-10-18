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

/**
 * Editorias das matérias importadas e as palavras que puxam cada uma. As
 * palavras são começos de palavra, sem acento ("prend" pega prende, prendeu,
 * prendem). Na dúvida entre duas, vale a que vem antes na lista.
 *
 * @return array<string, array{nome:string, palavras:string[]}>
 */
function mural17_editorias() {
	return array(
		'policia'   => array(
			'nome'     => 'Polícia',
			'palavras' => array( 'polici', 'pm ', 'delegaci', 'preso', 'presos', 'presa ', 'presas', 'prend', 'prisao', 'crime', 'criminos', 'homicid', 'assassin', 'feminicid', 'latrocin', 'furt', 'roub', 'assalt', 'trafic', 'droga', 'cocaina', 'maconha', 'golpe', 'golpist', 'estelionat', 'flagrante', 'mandado de', 'suspeit', 'baleado', 'a tiros', 'facada', 'esfaque', 'apreens', 'gcm', 'guarda civil', 'investigad', 'boletim de ocorrencia', 'agress', 'violencia domestica', 'refem', 'refens', 'sequestr', 'com faca', 'ameacad', 'medida protetiva' ),
		),
		'transito'  => array(
			'nome'     => 'Trânsito',
			'palavras' => array( 'acidente', 'batida', 'bate de frente', 'batem de frente', 'colisao', 'capot', 'atropel', 'engavet', 'rodovia', 'transito', 'motorista', 'motociclist', 'caminhao', 'carreta', 'sp 310', 'sp 425', 'sp 320', 'sp 326', 'br 153', 'radar', 'cnh', 'detran', 'pedagio', 'recapeamento', 'semaforo', 'sinalizacao' ),
		),
		'politica'  => array(
			'nome'     => 'Política',
			'palavras' => array( 'prefeito', 'vice prefeit', 'vereador', 'camara municipal', 'sessao ordinaria', 'sessao extraordinaria', 'projeto de lei', 'eleic', 'eleit', 'votacao', 'candidat', 'deputad', 'governador', 'senador', 'partido', 'mandato', 'cassa', 'tomou posse', 'toma posse', 'apuracao', 'urna' ),
		),
		'saude'     => array(
			'nome'     => 'Saúde',
			'palavras' => array( 'saude', 'vacin', 'imuniza', 'dengue', 'chikungunya', 'hospital', 'santa casa', 'ubs', 'pronto socorro', 'upa ', 'sus ', 'medic', 'enferm', 'cirurgi', 'paciente', 'covid', 'gripe', 'setembro amarelo', 'outubro rosa', 'novembro azul', 'mamograf', 'hemocentro', 'doacao de sangue', 'farmacia', 'zoonose', 'castracao' ),
		),
		'educacao'  => array(
			'nome'     => 'Educação',
			'palavras' => array( 'escola', 'educac', 'aluno', 'alunos', 'estudante', 'professor', 'ensino', 'creche', 'matricula', 'enem', 'vestibular', 'universidade', 'faculdade', 'fatec', 'etec', 'unesp', 'famerp', 'merenda', 'sala de aula', 'volta as aulas', 'formatura' ),
		),
		'empregos'  => array(
			'nome'     => 'Empregos e concursos',
			'palavras' => array( 'concurso', 'processo seletivo', 'vagas de emprego', 'vaga de emprego', 'pat ', 'posto de atendimento ao trabalhador', 'inscric', 'edital', 'estagi', 'jovem aprendiz', 'contrata' ),
		),
		'economia'  => array(
			'nome'     => 'Economia',
			'palavras' => array( 'economi', 'empresa', 'empreendedor', 'comercio', 'comerciant', 'industria', 'agronegocio', 'safra', 'cana de acucar', 'canavi', 'laranja', 'preco', 'inflacao', 'imposto', 'iptu', 'refis', 'credito', 'investiment', 'mei ', 'sebrae', 'emprego', 'desemprego', 'caged', 'black friday', 'combustiv', 'gasolina' ),
		),
		'esporte'   => array(
			'nome'     => 'Esporte',
			'palavras' => array( 'esport', 'futebol', 'campeonato', 'paulistao', 'serie a', 'serie b', 'serie c', 'serie d', 'mirassol fc', 'rio preto ec', 'america fc', 'atleta', 'corrida', 'maratona', 'copa ', 'torneio', 'jogos regionais', 'jogos abertos', 'volei', 'basquete', 'futsal', 'campeao', 'campea', 'olimpi', 'judo', 'karate', 'natacao', 'ciclismo' ),
		),
		'cultura'   => array(
			'nome'     => 'Cultura e lazer',
			'palavras' => array( 'cultur', 'show', 'festa', 'festival', 'teatro', 'cinema', 'musica', 'musical', 'exposicao', 'artesanato', 'carnaval', 'rodeio', 'quermesse', 'biblioteca', 'turismo', 'aniversario da cidade', 'aniversario de', 'natal', 'cantata', 'apresentacao', 'danca', 'feira livre', 'feira de artesanato', 'expo ' ),
		),
		'clima'     => array(
			'nome'     => 'Clima e meio ambiente',
			'palavras' => array( 'chuva', 'temporal', 'tempestade', 'vendaval', 'granizo', 'calor', 'onda de frio', 'frente fria', 'previsao do tempo', 'defesa civil', 'queimada', 'incendio', 'seca ', 'estiagem', 'umidade', 'meio ambiente', 'arboriza', 'reciclag', 'coleta de lixo', 'desmatament', 'enchente', 'alagamento' ),
		),
	);
}

/**
 * Editoria de uma matéria pelo título e texto. Título vale 3 vezes.
 *
 * @param string $titulo Título.
 * @param string $texto  Texto.
 * @return string Slug da editoria, ou 'geral' quando nada pesa.
 */
function mural17_editoria_do_texto( $titulo, $texto ) {
	$tit = ' ' . mural17_normaliza( wp_strip_all_tags( (string) $titulo ) ) . ' ';
	$txt = ' ' . mural17_normaliza( wp_strip_all_tags( (string) $texto ) ) . ' ';
	$melhor = 'geral';
	$maior  = 1; // Uma palavra solta no texto não basta.
	foreach ( mural17_editorias() as $slug => $e ) {
		$pontos = 0;
		foreach ( $e['palavras'] as $p ) {
			$p   = mural17_normaliza( $p ) . ( ' ' === substr( $p, -1 ) ? ' ' : '' );
			$re  = '/ ' . preg_quote( $p, '/' ) . '/';
			$pontos += 3 * preg_match_all( $re, $tit ) + min( 3, preg_match_all( $re, $txt ) );
		}
		if ( $pontos > $maior ) {
			$maior  = $pontos;
			$melhor = $slug;
		}
	}
	return $melhor;
}

/**
 * ID da categoria de uma editoria, criando se ainda não existe.
 *
 * @param string $slug Slug da editoria ou 'geral'.
 * @return int
 */
function mural17_categoria_editoria( $slug ) {
	$editorias = mural17_editorias();
	$nome      = isset( $editorias[ $slug ] ) ? $editorias[ $slug ]['nome'] : 'Geral';
	$termo     = get_term_by( 'slug', $slug, 'category' );
	if ( $termo ) {
		return (int) $termo->term_id;
	}
	$novo = wp_insert_term( $nome, 'category', array( 'slug' => $slug ) );
	return is_wp_error( $novo ) ? 0 : (int) $novo['term_id'];
}

/**
 * A matéria está sem editoria de verdade (nenhuma, ou só a padrão)?
 *
 * @param int $post_id Post.
 * @return bool
 */
function mural17_sem_editoria( $post_id ) {
	$cats   = wp_get_post_categories( $post_id );
	$padrao = (int) get_option( 'default_category' );
	return ! $cats || ( 1 === count( $cats ) && (int) $cats[0] === $padrao );
}

/**
 * Matéria importada sem editoria no feed ganha uma pelo texto.
 *
 * @param int    $meta_id Meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 * @param mixed  $valor   Valor.
 */
function mural17_editoria_na_importacao( $meta_id, $post_id, $chave, $valor ) {
	if ( '_pfai_feed_id' !== $chave || ! (int) $valor || ! mural17_sem_editoria( $post_id ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}
	$cat = mural17_categoria_editoria( mural17_editoria_do_texto( $post->post_title, $post->post_excerpt . ' ' . $post->post_content ) );
	if ( $cat ) {
		wp_set_post_categories( $post_id, array( $cat ), false );
	}
}
add_action( 'added_post_meta', 'mural17_editoria_na_importacao', 21, 4 );
add_action( 'updated_post_meta', 'mural17_editoria_na_importacao', 21, 4 );

/**
 * Matérias que já entraram sem editoria são classificadas uma vez, no
 * primeiro acesso ao painel depois da atualização (200 por vez).
 */
function mural17_classifica_antigas() {
	if ( (int) get_option( 'mural17_editorias_versao' ) >= 1 ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_key'       => '_pfai_feed_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'cat'            => (int) get_option( 'default_category' ),
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		mural17_editoria_na_importacao( 0, $id, '_pfai_feed_id', 1 );
	}
	if ( count( $ids ) < 200 ) {
		update_option( 'mural17_editorias_versao', 1 );
	}
}
add_action( 'admin_init', 'mural17_classifica_antigas' );

/* ---- Foto das matérias do Google News ------------------------------------ */

/**
 * A "imagem original" de uma matéria do Google News é sempre o logo do Google
 * News: o link do feed abre a página de redirecionamento do Google, e o
 * og:image dela é o logo. O plugin aceita porque o logo é colorido demais
 * para o detector de logotipos dele. Aqui trocamos pela imagem padrão da
 * editoria ou do portal (as mesmas que o plugin usaria se não houvesse foto).
 *
 * @param int $post_id Matéria.
 * @return bool Trocou.
 */
function mural17_tira_logo_google_news( $post_id ) {
	$fonte = (string) get_post_meta( $post_id, '_pfai_source_url', true );
	$como  = (string) get_post_meta( $post_id, '_pfai_image_source', true );
	if ( false === strpos( $fonte, 'news.google.com' ) || ! in_array( $como, array( 'og', 'feed' ), true ) ) {
		return false;
	}
	$nova = 0;
	if ( class_exists( 'PFAI_Imagem_Editoria' ) ) {
		$nova = (int) PFAI_Imagem_Editoria::para_o_post( $post_id );
	}
	if ( ! $nova ) {
		$nova = (int) get_option( 'pfai_placeholder_att_id' );
		if ( ! $nova || ! wp_attachment_is_image( $nova ) ) {
			$nova = (int) attachment_url_to_postid( (string) get_option( 'pfai_placeholder_url' ) );
		}
	}
	if ( ! $nova || ! wp_attachment_is_image( $nova ) ) {
		return false;
	}
	$velha = (int) get_post_thumbnail_id( $post_id );
	if ( $velha === $nova ) {
		return false;
	}
	set_post_thumbnail( $post_id, $nova );
	update_post_meta( $post_id, '_pfai_image_source', 'placeholder' );
	// O logo baixado não serve para nada: apaga, se nenhuma outra matéria usa.
	if ( $velha && (int) get_post_field( 'post_parent', $velha ) === (int) $post_id ) {
		global $wpdb;
		$usos = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d", $velha ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! $usos ) {
			wp_delete_attachment( $velha, true );
		}
	}
	// Cards do Instagram/Stories gerados com o logo: o plugin refaz com a foto nova.
	delete_post_meta( $post_id, '_pnp_story_card_url' );
	delete_post_meta( $post_id, '_pnp_feed_card_url' );
	return true;
}

/**
 * Roda quando o plugin marca de onde veio a foto (é a última coisa que ele
 * grava depois de pôr a imagem destacada).
 *
 * @param int    $meta_id ID do meta.
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 */
function mural17_foto_na_importacao( $meta_id, $post_id, $chave ) {
	if ( '_pfai_image_source' === $chave ) {
		mural17_tira_logo_google_news( (int) $post_id );
	}
}
add_action( 'added_post_meta', 'mural17_foto_na_importacao', 30, 3 );
add_action( 'updated_post_meta', 'mural17_foto_na_importacao', 30, 3 );

/**
 * Corrige uma vez as matérias que já entraram com o logo (últimos 30 dias).
 */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'mural17_logo_gn_versao' ) || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		update_option( 'mural17_logo_gn_versao', 1, false );
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
				'posts_per_page' => 400,
				'fields'         => 'ids',
				'date_query'     => array( array( 'after' => '30 days ago' ) ),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_pfai_source_url', 'value' => 'news.google.com', 'compare' => 'LIKE' ),
				),
			)
		);
		foreach ( $ids as $id ) {
			mural17_tira_logo_google_news( $id );
		}
	}
);
