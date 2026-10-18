<?php
/**
 * Serviços de utilidade pública: farmácia de plantão, falecimentos, guia da
 * cidade, alertas, Fala 17, combustível, concursos e falta de água e luz.
 *
 * Cada serviço tem um tipo de conteúdo próprio (fora do fluxo de notícias) e
 * uma página: /servicos/<nome>/ para os que não têm arquivo do WordPress,
 * /falecimentos/ e /concursos/ para os que têm. A cidade de cada página vem
 * do ?cidade= (ID ou slug); sem ele, a página usa Rio Preto e o navegador
 * troca para a cidade guardada no aparelho.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Os oito serviços, na ordem do menu.
 *
 * @return array<string, array{nome:string,texto:string,url:string}>
 */
function mural17_servicos() {
	return array(
		'farmacia'     => array(
			'nome'  => 'Farmácia de plantão',
			'texto' => 'Qual farmácia está aberta agora, com telefone e endereço.',
			'url'   => home_url( '/servicos/farmacia-de-plantao/' ),
		),
		'interrupcoes' => array(
			'nome'  => 'Falta de água e luz',
			'texto' => 'Bairros sem água ou sem energia agora e desligamentos marcados.',
			'url'   => home_url( '/servicos/falta-de-agua-e-luz/' ),
		),
		'alertas'      => array(
			'nome'  => 'Alertas e tempo',
			'texto' => 'Avisos do INMET para a região, previsão do tempo e estoque de sangue.',
			'url'   => home_url( '/servicos/alertas/' ),
		),
		'combustivel'  => array(
			'nome'  => 'Preço da gasolina',
			'texto' => 'Pesquisa semanal da ANP nas cidades do 17 e a conta do etanol.',
			'url'   => home_url( '/servicos/combustivel/' ),
		),
		'concursos'    => array(
			'nome'  => 'Concursos',
			'texto' => 'Concursos, processos seletivos e estágios com inscrição aberta.',
			'url'   => home_url( '/concursos/' ),
		),
		'falecimentos' => array(
			'nome'  => 'Falecimentos',
			'texto' => 'Velórios, sepultamentos e missas de sétimo dia.',
			'url'   => home_url( '/falecimentos/' ),
		),
		'fala'         => array(
			'nome'  => 'Fala, 17',
			'texto' => 'Mande o problema do seu bairro. A gente publica e cobra a resposta.',
			'url'   => home_url( '/servicos/fala-17/' ),
		),
		'guia'         => array(
			'nome'  => 'Guia da cidade',
			'texto' => 'Telefones úteis, saúde, feiras, coleta de lixo e transporte.',
			'url'   => home_url( '/servicos/guia/' ),
		),
	);
}

/**
 * Escolaridades dos concursos.
 *
 * @return array<string, string>
 */
function mural17_escolaridades() {
	return array(
		'fundamental' => 'Fundamental',
		'medio'       => 'Médio',
		'tecnico'     => 'Técnico',
		'superior'    => 'Superior',
	);
}

/**
 * Tipos de reclamação do Fala 17.
 *
 * @return array<string, string>
 */
function mural17_tipos_fala() {
	return array(
		'buraco'      => 'Buraco',
		'iluminacao'  => 'Iluminação',
		'mato-lixo'   => 'Mato e lixo',
		'agua-esgoto' => 'Água e esgoto',
		'transito'    => 'Trânsito e sinalização',
		'outro'       => 'Outro',
	);
}

/**
 * Páginas em /servicos/<slug>/ e o modelo de cada uma.
 *
 * @return array<string, string> slug da URL => arquivo em template-parts/servicos/
 */
function mural17_rotas_servicos() {
	return array(
		'inicio'               => 'inicio',
		'farmacia-de-plantao'  => 'farmacia',
		'guia'                 => 'guia',
		'alertas'              => 'alertas',
		'fala-17'              => 'fala',
		'combustivel'          => 'combustivel',
		'falta-de-agua-e-luz'  => 'interrupcoes',
	);
}

/**
 * Tipos de conteúdo dos serviços.
 */
function mural17_registra_servicos() {
	$privado = array(
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => 'mural17-servicos',
		'show_in_rest'       => false,
		'supports'           => array( 'title' ),
		'taxonomies'         => array( 'cidade' ),
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
	);

	register_post_type(
		'plantao',
		array_merge(
			$privado,
			array( 'labels' => mural17_cpt_labels( 'Plantão de farmácia', 'Farmácias de plantão' ) )
		)
	);
	register_post_type(
		'interrupcao',
		array_merge(
			$privado,
			array( 'labels' => mural17_cpt_labels( 'Falta de água ou luz', 'Falta de água e luz', true ) )
		)
	);
	register_post_type(
		'guia',
		array_merge(
			$privado,
			array( 'labels' => mural17_cpt_labels( 'Item do guia', 'Guia da cidade' ) )
		)
	);
	register_post_type(
		'fala',
		array_merge(
			$privado,
			array(
				'labels'   => mural17_cpt_labels( 'Reclamação', 'Fala, 17', true ),
				'supports' => array( 'title', 'editor', 'thumbnail' ),
			)
		)
	);
	register_post_type(
		'inscrito',
		array_merge(
			$privado,
			array(
				'labels'     => mural17_cpt_labels( 'Inscrito nos avisos', 'Inscritos nos avisos' ),
				'taxonomies' => array(),
			)
		)
	);

	register_post_type(
		'falecimento',
		array(
			'labels'       => mural17_cpt_labels( 'Falecimento', 'Falecimentos' ),
			'public'       => true,
			'has_archive'  => 'falecimentos',
			'rewrite'      => array( 'slug' => 'falecimentos', 'with_front' => false ),
			'show_in_menu' => 'mural17-servicos',
			'show_in_rest' => false,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'   => array( 'cidade' ),
		)
	);
	register_post_type(
		'concurso',
		array(
			'labels'       => mural17_cpt_labels( 'Concurso', 'Concursos' ),
			'public'       => true,
			'has_archive'  => 'concursos',
			'rewrite'      => array( 'slug' => 'concursos', 'with_front' => false ),
			'show_in_menu' => 'mural17-servicos',
			'show_in_rest' => false,
			'supports'     => array( 'title', 'editor' ),
			'taxonomies'   => array( 'cidade' ),
		)
	);

	foreach ( array( 'plantao', 'interrupcao', 'guia', 'fala', 'falecimento', 'concurso' ) as $tipo ) {
		register_taxonomy_for_object_type( 'cidade', $tipo );
	}

	register_taxonomy(
		'guia-secao',
		array( 'guia' ),
		array(
			'labels'            => array(
				'name'          => 'Seções do guia',
				'singular_name' => 'Seção do guia',
				'all_items'     => 'Todas as seções',
				'add_new_item'  => 'Adicionar seção',
				'menu_name'     => 'Seções do guia',
			),
			'public'            => false,
			'show_ui'           => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => false,
		)
	);

	add_rewrite_rule( '^servicos/?$', 'index.php?m17_servico=inicio', 'top' );
	add_rewrite_rule( '^servicos/([a-z0-9-]+)/?$', 'index.php?m17_servico=$matches[1]', 'top' );
}
add_action( 'init', 'mural17_registra_servicos', 11 );

/**
 * Seções iniciais do guia.
 *
 * @return array<string, string>
 */
function mural17_secoes_guia() {
	return array(
		'saude'      => 'Saúde',
		'publicos'   => 'Serviços públicos',
		'transporte' => 'Transporte',
		'feiras'     => 'Feiras livres',
		'coleta'     => 'Coleta de lixo',
	);
}

/**
 * Instala o que os serviços precisam (seções do guia, links) e roda de novo
 * quando a versão muda.
 */
function mural17_atualiza_servicos() {
	if ( (int) get_option( 'mural17_servicos_versao' ) >= MURAL17_SERVICOS_VERSAO ) {
		return;
	}
	mural17_registra_servicos();
	foreach ( mural17_secoes_guia() as $slug => $nome ) {
		mural17_garante_termo( $nome, $slug, 'guia-secao' );
	}
	update_option( 'mural17_servicos_versao', MURAL17_SERVICOS_VERSAO );
	flush_rewrite_rules();
}
add_action( 'admin_init', 'mural17_atualiza_servicos' );
add_action( 'after_switch_theme', 'mural17_atualiza_servicos', 20 );

/**
 * Variável da URL /servicos/<slug>/.
 *
 * @param string[] $vars Variáveis.
 * @return string[]
 */
function mural17_query_vars_servicos( $vars ) {
	$vars[] = 'm17_servico';
	return $vars;
}
add_filter( 'query_vars', 'mural17_query_vars_servicos' );

/**
 * Slug do serviço da página atual, ou ''.
 *
 * @return string
 */
function mural17_servico_atual() {
	$slug = (string) get_query_var( 'm17_servico' );
	return isset( mural17_rotas_servicos()[ $slug ] ) ? $slug : '';
}

/**
 * Página de serviço: usa o modelo certo ou dá 404.
 *
 * @param WP_Query $query Consulta.
 */
function mural17_consulta_servico( $query ) {
	if ( is_admin() || ! $query->is_main_query() || '' === (string) $query->get( 'm17_servico' ) ) {
		return;
	}
	if ( ! isset( mural17_rotas_servicos()[ (string) $query->get( 'm17_servico' ) ] ) ) {
		$query->set_404();
		return;
	}
	// Não é a home nem um arquivo de posts: só a moldura do tema. O
	// ?cidade= desta página escolhe a cidade do serviço, não filtra posts.
	$query->set( 'cidade', '' );
	$query->is_home    = false;
	$query->is_tax     = false;
	$query->is_archive = false;
	$query->set( 'post_type', 'page' );
	$query->set( 'post__in', array( 0 ) );
	$query->set( 'no_found_rows', true );
}
add_action( 'pre_get_posts', 'mural17_consulta_servico' );

/**
 * A página de serviço não tem posts e não é 404.
 *
 * @param bool     $pula  Pular o 404.
 * @param WP_Query $query Consulta.
 * @return bool
 */
function mural17_sem_404_servico( $pula, $query ) {
	return $query->is_main_query() && mural17_servico_atual() ? true : $pula;
}
add_filter( 'pre_handle_404', 'mural17_sem_404_servico', 10, 2 );

/**
 * Escolhe o modelo da página de serviço.
 *
 * @param string $template Modelo.
 * @return string
 */
function mural17_modelo_servico( $template ) {
	$slug = mural17_servico_atual();
	if ( ! $slug ) {
		return $template;
	}
	$achado = locate_template( 'servicos.php' );
	return $achado ? $achado : $template;
}
add_filter( 'template_include', 'mural17_modelo_servico', 20 );

/**
 * Título da aba nas páginas de serviço.
 *
 * @param array $partes Partes do título.
 * @return array
 */
function mural17_titulo_servico( $partes ) {
	$slug = mural17_servico_atual();
	if ( $slug ) {
		$arquivo = mural17_rotas_servicos()[ $slug ];
		$s       = mural17_servicos();
		$partes['title'] = isset( $s[ $arquivo ] ) ? $s[ $arquivo ]['nome'] : 'Serviços';
		if ( 'inicio' !== $slug && 'guia' !== $arquivo ) {
			$cidade = mural17_cidade_servico();
			if ( $cidade && in_array( $arquivo, array( 'farmacia', 'interrupcoes', 'alertas' ), true ) ) {
				$partes['title'] .= ' em ' . $cidade->name;
			}
		} elseif ( 'guia' === $arquivo ) {
			$cidade = mural17_cidade_servico();
			$partes['title'] = 'Guia de ' . ( $cidade ? $cidade->name : 'cidade' );
		}
	}
	return $partes;
}
add_filter( 'document_title_parts', 'mural17_titulo_servico' );

/**
 * Classe no body das páginas de serviço.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function mural17_body_servico( $classes ) {
	$slug = mural17_servico_atual();
	if ( $slug ) {
		$classes[] = 'servico-pagina';
		$classes[] = 'servico-' . mural17_rotas_servicos()[ $slug ];
		$classes   = array_diff( $classes, array( 'error404', 'home', 'blog' ) );
	}
	return $classes;
}
add_filter( 'body_class', 'mural17_body_servico' );

/* -------------------------------------------------------------------------
 * Cidade, datas e telefones
 * ---------------------------------------------------------------------- */

/**
 * Cidade (não região) pelo ID ou slug.
 *
 * @param int|string $valor ID ou slug.
 * @return WP_Term|null
 */
function mural17_acha_cidade( $valor ) {
	if ( ! $valor ) {
		return null;
	}
	$t = is_numeric( $valor ) ? get_term( (int) $valor, 'cidade' ) : get_term_by( 'slug', sanitize_title( $valor ), 'cidade' );
	return $t && ! is_wp_error( $t ) && $t->parent ? $t : null;
}

/**
 * Cidade usada nas páginas de serviço: ?cidade= ou Rio Preto.
 *
 * @return WP_Term|null
 */
function mural17_cidade_servico() {
	static $cidade = false;
	if ( false !== $cidade ) {
		return $cidade;
	}
	$pedida = isset( $_GET['cidade'] ) ? sanitize_text_field( wp_unslash( $_GET['cidade'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$cidade = mural17_acha_cidade( $pedida );
	if ( ! $cidade ) {
		$cidade = mural17_acha_cidade( 'rio-preto' );
	}
	return $cidade;
}

/**
 * A cidade veio escolhida na URL (mesmo "todo o 17")?
 *
 * @return bool
 */
function mural17_cidade_na_url() {
	return isset( $_GET['cidade'] ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Escopo das páginas que aceitam região e "todo o 17": ?cidade=<cidade ou
 * região>, ?cidade= vazio (todo o 17) ou, sem o parâmetro, Rio Preto.
 *
 * @return WP_Term|null
 */
function mural17_escopo_servico() {
	if ( ! isset( $_GET['cidade'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return mural17_cidade_servico();
	}
	$v = sanitize_text_field( wp_unslash( $_GET['cidade'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $v ) {
		return null;
	}
	$t = is_numeric( $v ) ? get_term( (int) $v, 'cidade' ) : get_term_by( 'slug', sanitize_title( $v ), 'cidade' );
	return $t && ! is_wp_error( $t ) ? $t : mural17_cidade_servico();
}

/**
 * "em Mirassol", "na região de Rio Preto" ou "no 17".
 *
 * @param WP_Term|null $escopo Escopo.
 * @return string
 */
function mural17_em_escopo( $escopo ) {
	if ( ! $escopo ) {
		return 'no 17';
	}
	return $escopo->parent ? 'em ' . mural17_nome_curto( $escopo ) : 'na ' . lcfirst( $escopo->name );
}

/**
 * Link de um serviço já na cidade.
 *
 * @param string       $servico Chave do serviço.
 * @param WP_Term|null $cidade  Cidade.
 * @return string
 */
function mural17_link_servico( $servico, $cidade = null ) {
	$url = mural17_servicos()[ $servico ]['url'];
	return $cidade ? add_query_arg( 'cidade', $cidade->slug, $url ) : $url;
}

/**
 * Seletor de cidade (GET) para as páginas de serviço. O navegador envia o
 * formulário sozinho ao trocar a opção.
 *
 * @param WP_Term|null $atual   Cidade marcada.
 * @param bool         $regioes Aceita região e "toda a região".
 * @param string       $rotulo  Rótulo.
 */
function mural17_seletor_cidade( $atual, $regioes = false, $rotulo = 'Cidade' ) {
	$id = $atual ? (int) $atual->term_id : 0;
	?>
	<form class="seletor-cidade" method="get" data-seletor-cidade>
		<label for="seletor-cidade"><?php echo esc_html( $rotulo ); ?></label>
		<select id="seletor-cidade" name="cidade">
			<?php if ( $regioes ) : ?>
				<option value="">Todo o 17</option>
			<?php endif; ?>
			<?php foreach ( mural17_lista_cidades() as $grupo ) : ?>
				<optgroup label="<?php echo esc_attr( $grupo['regiao']->name ); ?>">
					<?php if ( $regioes ) : ?>
						<option value="<?php echo esc_attr( $grupo['regiao']->slug ); ?>" <?php selected( $id, $grupo['regiao']->term_id ); ?>>Toda a <?php echo esc_html( lcfirst( $grupo['regiao']->name ) ); ?></option>
					<?php endif; ?>
					<?php foreach ( $grupo['cidades'] as $c ) : ?>
						<option value="<?php echo esc_attr( $c->slug ); ?>" <?php selected( $id, $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit" class="botao botao--pilula">Ver</button></noscript>
	</form>
	<?php
}

/**
 * "Agora" no fuso do site, no formato guardado nos campos (Y-m-d H:i).
 *
 * @param string $mais Deslocamento ("+1 day").
 * @return string
 */
function mural17_agora( $mais = '' ) {
	$d = new DateTimeImmutable( 'now', wp_timezone() );
	if ( $mais ) {
		$d = $d->modify( $mais );
	}
	return $d->format( 'Y-m-d H:i' );
}

/**
 * Data e hora de um campo como objeto, no fuso do site.
 *
 * @param string $valor "Y-m-d H:i" ou "Y-m-d".
 * @return DateTimeImmutable|null
 */
function mural17_data( $valor ) {
	$valor = trim( str_replace( 'T', ' ', (string) $valor ) );
	if ( '' === $valor ) {
		return null;
	}
	try {
		return new DateTimeImmutable( $valor, wp_timezone() );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Hora curta: 8h, 16h30.
 *
 * @param DateTimeInterface $d Data.
 * @return string
 */
function mural17_hora( $d ) {
	return '00' === $d->format( 'i' ) ? $d->format( 'G\h' ) : $d->format( 'G\hi' );
}

/**
 * Dia relativo: Hoje, Amanhã, Ontem, Sexta (até 6 dias) ou 23/10.
 *
 * @param DateTimeInterface $d       Data.
 * @param bool              $com_dia Junta a data depois do dia da semana.
 * @return string
 */
function mural17_dia( $d, $com_dia = false ) {
	$hoje = new DateTimeImmutable( 'today', wp_timezone() );
	$dia  = DateTimeImmutable::createFromFormat( 'Y-m-d', $d->format( 'Y-m-d' ), wp_timezone() )->setTime( 0, 0 );
	$dif  = (int) round( ( $dia->getTimestamp() - $hoje->getTimestamp() ) / DAY_IN_SECONDS );
	if ( 0 === $dif ) {
		return 'Hoje';
	}
	if ( 1 === $dif ) {
		return 'Amanhã';
	}
	if ( -1 === $dif ) {
		return 'Ontem';
	}
	$semana = array( 'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado' );
	if ( $dif > 1 && $dif < 7 ) {
		return $semana[ (int) $d->format( 'w' ) ] . ( $com_dia ? ', ' . $d->format( 'd/m' ) : '' );
	}
	return $d->format( 'd/m' );
}

/**
 * "Hoje, 16h30", "Sexta, 23/10, 19h".
 *
 * @param string $valor Campo de data e hora.
 * @return string
 */
function mural17_quando_servico( $valor ) {
	$d = mural17_data( $valor );
	return $d ? mural17_dia( $d, true ) . ', ' . mural17_hora( $d ) : '';
}

/**
 * Link tel: de um telefone digitado do jeito que for.
 *
 * @param string $tel Telefone.
 * @return string
 */
function mural17_tel( $tel ) {
	$n = preg_replace( '/\D+/', '', (string) $tel );
	if ( '' === $n ) {
		return '';
	}
	if ( strlen( $n ) <= 4 ) {
		return 'tel:' . $n;
	}
	if ( strlen( $n ) <= 9 ) {
		$n = '17' . $n;
	}
	return 'tel:+55' . ltrim( $n, '0' );
}

/**
 * Link do Google Maps para um endereço na cidade.
 *
 * @param string       $endereco Endereço.
 * @param WP_Term|null $cidade   Cidade.
 * @return string
 */
function mural17_mapa( $endereco, $cidade = null ) {
	$q = trim( $endereco . ( $cidade ? ', ' . $cidade->name . ' - SP' : '' ), ', ' );
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $q );
}

/**
 * Valor de um campo do serviço (meta _mural17_<chave>).
 *
 * @param int    $post_id Post.
 * @param string $chave   Chave.
 * @return string
 */
function mural17_campo( $post_id, $chave ) {
	return (string) get_post_meta( $post_id, '_mural17_' . $chave, true );
}

/**
 * Cidades mais próximas de uma cidade, pela distância em linha reta.
 *
 * @param WP_Term $cidade Cidade.
 * @param int     $qtd    Quantas.
 * @return array<int, array{cidade:WP_Term,km:float}>
 */
function mural17_vizinhas( $cidade, $qtd = 3 ) {
	$coords = mural17_coordenadas();
	if ( ! $cidade || ! isset( $coords[ $cidade->slug ] ) ) {
		return array();
	}
	$saida = array();
	foreach ( mural17_lista_cidades() as $grupo ) {
		foreach ( $grupo['cidades'] as $c ) {
			if ( $c->term_id === $cidade->term_id || ! isset( $coords[ $c->slug ] ) ) {
				continue;
			}
			$saida[] = array(
				'cidade' => $c,
				'km'     => mural17_km( $coords[ $cidade->slug ], $coords[ $c->slug ] ),
			);
		}
	}
	usort(
		$saida,
		function ( $a, $b ) {
			return $a['km'] <=> $b['km'];
		}
	);
	return array_slice( $saida, 0, $qtd );
}

/**
 * Distância em km entre dois pontos [lat, lng].
 *
 * @param float[] $a Ponto.
 * @param float[] $b Ponto.
 * @return float
 */
function mural17_km( $a, $b ) {
	$r    = M_PI / 180;
	$dlat = ( $b[0] - $a[0] ) * $r;
	$dlng = ( $b[1] - $a[1] ) * $r;
	$h    = sin( $dlat / 2 ) ** 2 + cos( $a[0] * $r ) * cos( $b[0] * $r ) * sin( $dlng / 2 ) ** 2;
	return 6371 * 2 * atan2( sqrt( $h ), sqrt( 1 - $h ) );
}

/**
 * Consulta de tax_query de cidade: a própria cidade, ou todas da região.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @return array
 */
function mural17_tax_cidade( $escopo ) {
	if ( ! $escopo ) {
		return array();
	}
	return array(
		array(
			'taxonomy'         => 'cidade',
			'field'            => 'term_id',
			'terms'            => $escopo->term_id,
			'include_children' => true,
		),
	);
}

/**
 * Nome curto da cidade para as listas ("São José do Rio Preto" → "Rio Preto").
 *
 * @param WP_Term|null $c Cidade.
 * @return string
 */
function mural17_nome_curto( $c ) {
	if ( ! $c ) {
		return '';
	}
	return 'rio-preto' === $c->slug ? 'Rio Preto' : $c->name;
}

/* -------------------------------------------------------------------------
 * Consultas usadas pelas páginas e pela faixa "Serviços de hoje"
 * ---------------------------------------------------------------------- */

/**
 * Plantões de farmácia de uma cidade a partir de agora (o atual primeiro).
 *
 * @param WP_Term $cidade Cidade.
 * @param int     $qtd    Quantos.
 * @return WP_Post[]
 */
function mural17_plantoes( $cidade, $qtd = 8 ) {
	if ( ! $cidade ) {
		return array();
	}
	$q = new WP_Query(
		array(
			'post_type'      => 'plantao',
			'posts_per_page' => $qtd,
			'no_found_rows'  => true,
			'meta_key'       => '_mural17_plantao_inicio', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_mural17_plantao_fim',
					'value'   => mural17_agora(),
					'compare' => '>',
				),
			),
			'tax_query'      => mural17_tax_cidade( $cidade ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q->posts;
}

/**
 * O plantão vale agora?
 *
 * @param WP_Post $p Plantão.
 * @return bool
 */
function mural17_plantao_agora( $p ) {
	$agora = mural17_agora();
	return mural17_campo( $p->ID, 'plantao_inicio' ) <= $agora && mural17_campo( $p->ID, 'plantao_fim' ) > $agora;
}

/**
 * Faltas de água e luz de uma cidade ou região que ainda importam: em
 * andamento, marcadas, ou normalizadas há menos de 6 horas.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @param int          $qtd    Quantas.
 * @return array{agora:WP_Post[],programadas:WP_Post[],normalizadas:WP_Post[]}
 */
function mural17_interrupcoes( $escopo, $qtd = 40 ) {
	$q     = new WP_Query(
		array(
			'post_type'      => 'interrupcao',
			'posts_per_page' => $qtd,
			'no_found_rows'  => true,
			'meta_key'       => '_mural17_int_inicio', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array(
					'key'     => '_mural17_int_normalizado',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_mural17_int_normalizado',
					'value'   => mural17_agora( '-6 hours' ),
					'compare' => '>',
				),
			),
			'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$saida = array(
		'agora'        => array(),
		'programadas'  => array(),
		'normalizadas' => array(),
	);
	$agora = mural17_agora();
	foreach ( $q->posts as $p ) {
		if ( mural17_campo( $p->ID, 'int_normalizado' ) ) {
			$saida['normalizadas'][] = $p;
		} elseif ( mural17_campo( $p->ID, 'int_inicio' ) > $agora ) {
			$saida['programadas'][] = $p;
		} else {
			$saida['agora'][] = $p;
		}
	}
	return $saida;
}

/**
 * Concursos com inscrição aberta.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @param array        $extra  Argumentos extras do WP_Query.
 * @return WP_Query
 */
function mural17_concursos_abertos( $escopo = null, $extra = array() ) {
	return new WP_Query(
		array_merge(
			array(
				'post_type'      => 'concurso',
				'posts_per_page' => 5,
				'meta_key'       => '_mural17_conc_inscricoes_ate', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => '_mural17_conc_inscricoes_ate',
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '>=',
					),
				),
				'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
			),
			$extra
		)
	);
}

/**
 * Dias até o fim das inscrições.
 *
 * @param int $post_id Concurso.
 * @return int|null
 */
function mural17_dias_concurso( $post_id ) {
	$d = mural17_data( mural17_campo( $post_id, 'conc_inscricoes_ate' ) );
	if ( ! $d ) {
		return null;
	}
	$hoje = new DateTimeImmutable( 'today', wp_timezone() );
	return (int) round( ( $d->setTime( 0, 0 )->getTimestamp() - $hoje->getTimestamp() ) / DAY_IN_SECONDS );
}

/**
 * Selo de prazo do concurso: [classe, texto].
 *
 * @param int $post_id Concurso.
 * @return array{0:string,1:string}
 */
function mural17_prazo_concurso( $post_id ) {
	$dias = mural17_dias_concurso( $post_id );
	if ( null === $dias ) {
		return array( 'ok', 'Inscrições abertas' );
	}
	if ( $dias < 0 ) {
		return array( 'neutro', 'Inscrições encerradas' );
	}
	if ( 0 === $dias ) {
		return array( 'erro', 'Último dia' );
	}
	if ( $dias <= 2 ) {
		return array( 'erro', 1 === $dias ? 'Fecha amanhã' : 'Fecha em 2 dias' );
	}
	if ( $dias <= 7 ) {
		return array( 'espera', 'Fecha em ' . $dias . ' dias' );
	}
	$d = mural17_data( mural17_campo( $post_id, 'conc_inscricoes_ate' ) );
	return array( 'ok', 'Aberto até ' . $d->format( 'd/m' ) );
}

/**
 * Dinheiro: R$ 4.870 ou R$ 1.200,50.
 *
 * @param string|float $v Valor.
 * @return string
 */
function mural17_reais( $v ) {
	$v = (float) str_replace( ',', '.', (string) $v );
	return 'R$ ' . number_format( $v, floor( $v ) === $v ? 0 : 2, ',', '.' );
}

/**
 * Falecimentos publicados em um intervalo de dias.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @param int          $dias   Dias para trás (0 = só hoje).
 * @param int          $qtd    Quantidade (-1 = todos).
 * @return WP_Query
 */
function mural17_falecimentos( $escopo, $dias = 0, $qtd = 20 ) {
	$desde = ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '-' . (int) $dias . ' days' );
	return new WP_Query(
		array(
			'post_type'      => 'falecimento',
			'posts_per_page' => $qtd,
			'date_query'     => array( array( 'after' => $desde->format( 'Y-m-d 00:00:00' ), 'inclusive' => true ) ),
			'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/**
 * Reclamações do Fala 17 respondidas nos últimos 7 dias.
 *
 * @param WP_Term|null $escopo Cidade ou região.
 * @return int
 */
function mural17_falas_respondidas_semana( $escopo ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'fala',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_mural17_fala_resposta_em',
					'value'   => mural17_agora( '-7 days' ),
					'compare' => '>',
				),
			),
			'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) $q->found_posts;
}

/**
 * Cidade de referência para a faixa "Serviços de hoje" de um escopo:
 * a cidade, ou Rio Preto na home e nas páginas de região.
 *
 * @param WP_Term|null $escopo Escopo do mural.
 * @return WP_Term|null
 */
function mural17_cidade_da_faixa( $escopo ) {
	if ( $escopo && $escopo->parent ) {
		return $escopo;
	}
	if ( $escopo ) {
		// Região: a cidade da região com mais conteúdo.
		$filhas = get_terms(
			array(
				'taxonomy'   => 'cidade',
				'parent'     => $escopo->term_id,
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 1,
			)
		);
		if ( ! is_wp_error( $filhas ) && $filhas ) {
			return $filhas[0];
		}
	}
	return mural17_acha_cidade( 'rio-preto' );
}

/**
 * Faixa "Serviços de hoje em X" em HTML (também servida pela API para a
 * home trocar pela cidade do aparelho).
 *
 * @param WP_Term $cidade Cidade.
 * @return string
 */
function mural17_html_faixa_servicos( $cidade ) {
	ob_start();
	get_template_part( 'template-parts/servicos/faixa', null, array( 'cidade' => $cidade ) );
	return ob_get_clean();
}

/**
 * GET /wp-json/mural17/v1/servicos?cidade=ID → { html }.
 */
function mural17_rest_servicos() {
	register_rest_route(
		'mural17/v1',
		'/servicos',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'cidade' => array(
					'type'     => 'integer',
					'required' => true,
				),
			),
			'callback'            => function ( WP_REST_Request $req ) {
				$cidade = mural17_acha_cidade( (int) $req['cidade'] );
				if ( ! $cidade ) {
					return new WP_Error( 'cidade', 'Cidade não encontrada.', array( 'status' => 404 ) );
				}
				$r = rest_ensure_response( array( 'html' => mural17_html_faixa_servicos( $cidade ) ) );
				$r->header( 'Cache-Control', 'public, max-age=300' );
				return $r;
			},
		)
	);
}
add_action( 'rest_api_init', 'mural17_rest_servicos' );

/**
 * Menu "Serviços" no painel do WordPress (os tipos de conteúdo entram nele).
 */
function mural17_menu_servicos() {
	add_menu_page( 'Serviços', 'Serviços', 'edit_posts', 'mural17-servicos', 'mural17_pagina_servicos_admin', 'dashicons-sos', 6 );
}
add_action( 'admin_menu', 'mural17_menu_servicos', 9 );

/**
 * Período pedido na lista de falecimentos (?dia=hoje|ontem|7).
 *
 * @return string
 */
function mural17_dia_falecimentos() {
	$dia = isset( $_GET['dia'] ) ? sanitize_key( wp_unslash( $_GET['dia'] ) ) : 'hoje'; // phpcs:ignore WordPress.Security.NonceVerification
	return in_array( $dia, array( 'hoje', 'ontem', '7' ), true ) ? $dia : 'hoje';
}

/**
 * Filtros das listas de concursos e falecimentos.
 *
 * @param WP_Query $query Consulta.
 */
function mural17_filtros_servicos( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification
	if ( $query->is_post_type_archive( 'concurso' ) ) {
		$meta = array(
			array(
				'key'     => '_mural17_conc_inscricoes_ate',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
			),
		);
		$esc  = isset( $_GET['esc'] ) ? sanitize_key( wp_unslash( $_GET['esc'] ) ) : '';
		if ( isset( mural17_escolaridades()[ $esc ] ) ) {
			$meta[] = array(
				'key'     => '_mural17_conc_escolaridade',
				'value'   => $esc,
				'compare' => 'LIKE',
			);
		}
		$sal = isset( $_GET['salario'] ) ? absint( $_GET['salario'] ) : 0;
		if ( $sal ) {
			$meta[] = array(
				'key'     => '_mural17_conc_salario',
				'value'   => $sal,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		$tipo = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : '';
		if ( in_array( $tipo, array( 'concurso', 'processo', 'estagio' ), true ) ) {
			$meta[] = array(
				'key'   => '_mural17_conc_tipo',
				'value' => $tipo,
			);
		}
		$query->set( 'meta_query', $meta );
		$query->set( 'meta_key', '_mural17_conc_inscricoes_ate' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', 12 );
	}
	if ( $query->is_post_type_archive( 'falecimento' ) ) {
		$hoje = new DateTimeImmutable( 'today', wp_timezone() );
		$dia  = mural17_dia_falecimentos();
		if ( 'ontem' === $dia ) {
			$faixa = array(
				'after'     => $hoje->modify( '-1 day' )->format( 'Y-m-d 00:00:00' ),
				'before'    => $hoje->modify( '-1 day' )->format( 'Y-m-d 23:59:59' ),
				'inclusive' => true,
			);
		} else {
			$faixa = array(
				'after'     => $hoje->modify( '7' === $dia ? '-6 days' : '+0 days' )->format( 'Y-m-d 00:00:00' ),
				'inclusive' => true,
			);
		}
		$query->set( 'date_query', array( $faixa ) );
		$nome = isset( $_GET['nome'] ) ? sanitize_text_field( wp_unslash( $_GET['nome'] ) ) : '';
		if ( '' !== $nome ) {
			$query->set( 's', $nome );
			$query->set( 'search_columns', array( 'post_title' ) );
		}
		$query->set( 'posts_per_page', 20 );
	}
	// phpcs:enable
}
add_action( 'pre_get_posts', 'mural17_filtros_servicos' );

/**
 * Nota de falecimento antiga sai dos buscadores (60 dias).
 *
 * @param array $robots Diretivas.
 * @return array
 */
function mural17_robots_falecimento( $robots ) {
	if ( is_singular( 'falecimento' ) && get_post_time( 'U', true ) < time() - 60 * DAY_IN_SECONDS ) {
		$robots['noindex'] = true;
	}
	if ( is_singular( 'falecimento' ) || is_post_type_archive( 'falecimento' ) ) {
		$robots['noarchive'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'mural17_robots_falecimento' );
