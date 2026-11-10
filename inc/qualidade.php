<?php
/**
 * Qualidade do portal (o que o Google AdSense e a Pesquisa olham):
 * páginas institucionais, matéria repetida, matéria de fora do 17 e
 * noindex nas páginas sem conteúdo.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MURAL17_INSTITUCIONAL_VERSAO = 1;

/* ---- Páginas institucionais --------------------------------------------- */

/**
 * As páginas institucionais (slug => título). O texto vem do shortcode, para
 * acompanhar o que estiver em Aparência › Personalizar › Mural 17.
 *
 * @return array<string, string>
 */
function mural17_paginas_institucionais() {
	return array(
		'quem-somos'              => 'Quem somos',
		'expediente'              => 'Expediente',
		'politica-editorial'      => 'Política editorial',
		'politica-de-privacidade' => 'Política de privacidade',
		'termos-de-uso'           => 'Termos de uso',
		'contato'                 => 'Contato',
	);
}

/**
 * Cria as páginas que faltam (uma vez por versão) e marca a de privacidade
 * como a oficial do WordPress.
 */
function mural17_instala_institucional() {
	if ( (int) get_option( 'mural17_institucional_versao' ) >= MURAL17_INSTITUCIONAL_VERSAO || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	foreach ( mural17_paginas_institucionais() as $slug => $titulo ) {
		$p  = get_page_by_path( $slug );
		$id = $p ? $p->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $titulo,
				'post_name'    => $slug,
				'post_content' => '[mural17_institucional qual="' . $slug . '"]',
			)
		);
		if ( 'politica-de-privacidade' === $slug && $id ) {
			update_option( 'wp_page_for_privacy_policy', (int) $id );
		}
	}
	update_option( 'mural17_institucional_versao', MURAL17_INSTITUCIONAL_VERSAO );
}
add_action( 'admin_init', 'mural17_instala_institucional' );

/**
 * Link de uma página institucional.
 *
 * @param string $slug Slug.
 * @return string
 */
function mural17_link_institucional( $slug ) {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
}

/**
 * E-mail da redação (o do Personalizar, ou o contato@ do domínio).
 *
 * @return string
 */
function mural17_email_redacao() {
	$e = (string) mural17_opt( 'mural17_email' );
	return is_email( $e ) ? $e : 'contato@mural17.com.br';
}

/**
 * Texto de cada página institucional.
 *
 * @param array $atts Atributos (qual).
 * @return string
 */
function mural17_shortcode_institucional( $atts ) {
	$qual  = sanitize_key( $atts['qual'] ?? '' );
	$email = mural17_email_redacao();
	$mail  = '<a href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a>';
	$wa    = (string) mural17_opt( 'mural17_whatsapp' );
	$wa_l  = $wa ? '<a href="' . esc_url( mural17_whatsapp_link( $wa ) ) . '" target="_blank" rel="noopener">WhatsApp da redação</a>' : '';
	$resp  = (string) mural17_opt( 'mural17_responsavel' );
	$sede  = (string) mural17_opt( 'mural17_sede' );
	$cnpj  = (string) mural17_opt( 'mural17_cnpj' );
	$link  = function ( $slug, $txt ) {
		return '<a href="' . esc_url( mural17_link_institucional( $slug ) ) . '">' . esc_html( $txt ) . '</a>';
	};
	$cadastro = function_exists( 'mural17_link_oficial' ) ? mural17_link_oficial( 'cadastro' ) : home_url( '/' );
	$n        = wp_count_terms( array( 'taxonomy' => 'cidade', 'hide_empty' => false, 'parent' => 0 ) );
	ob_start();
	switch ( $qual ) {
		case 'quem-somos':
			?>
			<p>O <strong>Mural 17</strong> é um portal de notícias e serviços dedicado às 117 cidades do DDD 17, no noroeste paulista: de São José do Rio Preto a Santa Fé do Sul, de Barretos a Votuporanga, de Catanduva a Jales.</p>
			<p>A ideia é simples: reunir num lugar só o que o morador de cada cidade precisa saber no dia. Cada cidade tem a sua página, com as notícias dela e os serviços que mudam todo dia.</p>
			<h2>O que você encontra aqui</h2>
			<ul>
				<li><strong>Notícias da sua cidade e da região</strong>, organizadas por cidade e por editoria (polícia, trânsito, política, saúde, educação, economia, esporte, cultura e clima).</li>
				<li><strong>Serviços</strong>: farmácia de plantão, falta de água e luz, alertas da Defesa Civil e do INMET, preço dos combustíveis (pesquisa da ANP), concursos e trânsito nas rodovias.</li>
				<li><strong>Empregos</strong>: vagas anunciadas no portal, as listas dos PATs e do Balcão de Empregos de Rio Preto e vagas dos principais sites.</li>
				<li><strong>Agenda</strong>: shows, festas, feiras e eventos da semana, com a agenda oficial das prefeituras.</li>
				<li><strong>Notas oficiais</strong>: prefeituras, câmaras e órgãos públicos cadastrados publicam comunicados direto no portal, com o selo de fonte oficial.</li>
			</ul>
			<h2>Como trabalhamos</h2>
			<p>Parte do conteúdo vem de fontes públicas da região (prefeituras, câmaras, polícias, órgãos estaduais e veículos locais). Essas notícias são reescritas e resumidas, sempre com o link para a fonte original. Os serviços são atualizados automaticamente a partir de dados oficiais. Veja os detalhes na nossa <?php echo $link( 'politica-editorial', 'política editorial' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>.</p>
			<p>Sugestões de pauta, correções e reclamações: <?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $wa_l ? ' ou ' . $wa_l : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>.</p>
			<?php
			break;

		case 'expediente':
			?>
			<p><strong>Mural 17</strong> · portal de notícias e serviços do DDD 17 · <a href="<?php echo esc_url( home_url( '/' ) ); ?>">mural17.com.br</a></p>
			<ul>
				<?php if ( $resp ) : ?>
					<li><strong>Editor responsável:</strong> <?php echo esc_html( $resp ); ?></li>
				<?php endif; ?>
				<?php if ( $sede ) : ?>
					<li><strong>Redação:</strong> <?php echo esc_html( $sede ); ?> (SP)</li>
				<?php endif; ?>
				<?php if ( $cnpj ) : ?>
					<li><strong>CNPJ:</strong> <?php echo esc_html( $cnpj ); ?></li>
				<?php endif; ?>
				<li><strong>Cobertura:</strong> as 117 cidades do DDD 17, em 8 regiões</li>
				<li><strong>Contato da redação:</strong> <?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $wa_l ? ' · ' . $wa_l : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<li><strong>Publicidade:</strong> <?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
			</ul>
			<p>Leia também: <?php echo $link( 'quem-somos', 'Quem somos' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> · <?php echo $link( 'politica-editorial', 'Política editorial' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> · <?php echo $link( 'politica-de-privacidade', 'Política de privacidade' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php
			break;

		case 'politica-editorial':
			?>
			<h2>Compromissos</h2>
			<ul>
				<li><strong>A cidade primeiro.</strong> Publicamos o que afeta a vida de quem mora no 17: serviço, prazo, endereço, horário e número.</li>
				<li><strong>Fonte sempre citada.</strong> Toda notícia baseada em informação de outro veículo ou de um órgão público traz o nome e o link da fonte original.</li>
				<li><strong>Sem sensacionalismo.</strong> Em casos de polícia e tragédias, não divulgamos nome de vítimas menores de idade nem imagens chocantes, e tratamos suspeitos como suspeitos.</li>
				<li><strong>Separação entre notícia e publicidade.</strong> Anúncios e conteúdo patrocinado são sempre identificados como tal.</li>
				<li><strong>Notas oficiais identificadas.</strong> Textos enviados por prefeituras, câmaras e órgãos públicos aparecem com o selo "Oficial" e o nome do órgão. A redação revisa, mas o órgão responde pelo conteúdo. Propaganda política e promoção pessoal de autoridades não são publicadas.</li>
			</ul>
			<h2>Uso de automação e inteligência artificial</h2>
			<p>Usamos ferramentas automáticas para acompanhar as fontes públicas da região, resumir e reescrever comunicados e organizar as notícias por cidade e editoria. Os serviços (farmácias, alertas, combustíveis, vagas, trânsito, agenda) são lidos de fontes oficiais e atualizados sozinhos. Matérias repetidas ou sem relação com o 17 são retiradas da publicação.</p>
			<h2>Correções</h2>
			<p>Errou? Corrigimos. Se você encontrou uma informação errada, escreva para <?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?> com o link da matéria. Correções relevantes são feitas no próprio texto, com a indicação de que ele foi atualizado.</p>
			<h2>Pedido de remoção</h2>
			<p>Pedidos de remoção ou de direito de resposta são analisados pela redação. Envie o link e o motivo para o mesmo e-mail.</p>
			<?php
			break;

		case 'politica-de-privacidade':
			?>
			<p>Esta política explica quais dados o <strong>Mural 17</strong> (mural17.com.br) coleta, para quê e quais são os seus direitos, de acordo com a Lei Geral de Proteção de Dados (Lei 13.709/2018). Última atualização: <?php echo esc_html( wp_date( 'j/m/Y', strtotime( get_post_field( 'post_modified', get_the_ID() ) ) ) ); ?>.</p>
			<h2>Dados que você nos envia</h2>
			<ul>
				<li><strong>Avisos por e-mail</strong> (vagas, agenda, alertas da sua cidade): e-mail e cidade escolhida. Usados só para enviar os avisos que você pediu. Todo e-mail tem o link para cancelar.</li>
				<li><strong>Cadastro de órgãos públicos</strong>: nome do órgão, responsável, cargo, e-mail institucional e telefone. Usados para confirmar o cadastro e para o contato com a redação.</li>
				<li><strong>Anúncios, classificados, vagas e reclamações</strong> enviados pelo site: os dados que você preencher no formulário, usados para publicar o que você pediu e falar com você, se preciso.</li>
				<li><strong>Contato com a redação</strong> por e-mail ou WhatsApp: as informações da conversa.</li>
			</ul>
			<h2>Localização</h2>
			<p>O botão "Usar minha localização" acha a cidade mais próxima <strong>no seu próprio aparelho</strong>. A sua posição não é enviada ao site nem guardada por nós. A cidade escolhida fica salva só no seu navegador.</p>
			<h2>Cookies e publicidade</h2>
			<p>O site usa cookies e tecnologias parecidas para lembrar a sua cidade, medir a audiência e exibir anúncios.</p>
			<ul>
				<li>Usamos o <strong>Google AdSense</strong> para exibir anúncios. O Google e seus parceiros usam cookies para exibir anúncios com base nas suas visitas a este e a outros sites. O uso do cookie de publicidade permite ao Google e aos parceiros mostrar anúncios com base nessas visitas.</li>
				<li>Terceiros, incluindo o Google, podem colocar e ler cookies no seu navegador, ou usar web beacons e o endereço IP, para coletar informações como resultado da exibição de anúncios no site.</li>
				<li>Você pode desativar a publicidade personalizada nas <a href="https://adssettings.google.com" target="_blank" rel="noopener">Configurações de anúncios do Google</a> ou em <a href="https://www.aboutads.info/choices" target="_blank" rel="noopener">aboutads.info</a>.</li>
				<li>Saiba <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">como o Google usa as informações de sites que usam os serviços dele</a>.</li>
			</ul>
			<p>Você pode apagar ou bloquear cookies nas configurações do seu navegador. Algumas funções, como lembrar a cidade, deixam de funcionar.</p>
			<h2>Com quem compartilhamos</h2>
			<p>Não vendemos dados pessoais. Os dados podem ser processados por serviços que mantêm o site funcionando (hospedagem, envio de e-mail, publicidade e medição de audiência), só para essas finalidades.</p>
			<h2>Seus direitos</h2>
			<p>Você pode pedir a qualquer momento acesso, correção ou exclusão dos seus dados, ou cancelar os avisos por e-mail. Escreva para <?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?>.</p>
			<?php
			break;

		case 'termos-de-uso':
			?>
			<p>Ao usar o <strong>Mural 17</strong> (mural17.com.br), você concorda com estes termos.</p>
			<h2>Conteúdo</h2>
			<p>As notícias e serviços têm caráter informativo. Os serviços automáticos (farmácia de plantão, falta de água e luz, alertas, vagas, preços e agenda) vêm de fontes oficiais e podem mudar sem aviso: confirme com o órgão ou o estabelecimento antes de se deslocar.</p>
			<h2>O que você publica</h2>
			<p>Quem envia classificados, vagas, eventos, reclamações ou notas oficiais responde pelo que publica. A redação pode revisar, recusar ou retirar conteúdo ilegal, ofensivo, enganoso, com propaganda política ou que viole direitos de terceiros.</p>
			<h2>Direitos autorais</h2>
			<p>Os textos e as artes do Mural 17 podem ser compartilhados com o link para a matéria. Conteúdo de outros veículos e órgãos pertence aos seus autores e é citado com a fonte.</p>
			<h2>Contato</h2>
			<p><?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php
			break;

		case 'contato':
			?>
			<p>Fale com a redação do Mural 17.</p>
			<ul>
				<li><strong>E-mail:</strong> <?php echo $mail; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<?php if ( $wa_l ) : ?>
					<li><strong>WhatsApp:</strong> <?php echo $wa_l; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<?php endif; ?>
				<li><strong>Instagram:</strong> <a href="https://www.instagram.com/mural17br/" target="_blank" rel="noopener">@mural17br</a></li>
			</ul>
			<h2>Para quê</h2>
			<ul>
				<li><strong>Sugestão de pauta ou denúncia:</strong> conte o que aconteceu, onde e quando. Se puder, mande foto.</li>
				<li><strong>Correção:</strong> mande o link da matéria e o que está errado.</li>
				<li><strong>Anunciar:</strong> fale com a gente pelo e-mail.</li>
				<li><strong>Prefeituras, câmaras e órgãos públicos:</strong> <a href="<?php echo esc_url( $cadastro ); ?>">cadastrem-se aqui</a> para publicar notas oficiais.</li>
			</ul>
			<?php
			break;
	}
	return ob_get_clean();
}
add_shortcode( 'mural17_institucional', 'mural17_shortcode_institucional' );

/**
 * Links institucionais do rodapé.
 */
function mural17_links_institucionais() {
	$l = array();
	foreach ( mural17_paginas_institucionais() as $slug => $titulo ) {
		$l[] = '<a href="' . esc_url( mural17_link_institucional( $slug ) ) . '">' . esc_html( $titulo ) . '</a>';
	}
	echo '<nav class="rodape__inst" aria-label="Institucional">' . implode( '', $l ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* ---- Matéria repetida ou de fora do 17 ---------------------------------- */

/**
 * Palavras que contam numa comparação de títulos (sem acento, sem as miúdas).
 *
 * @param string $texto Texto.
 * @return string[]
 */
function mural17_palavras_chave( $texto ) {
	$fora = array( 'de', 'da', 'do', 'das', 'dos', 'em', 'no', 'na', 'nos', 'nas', 'e', 'a', 'o', 'as', 'os', 'um', 'uma', 'para', 'com', 'por', 'que', 'se', 'ao', 'aos', 'pela', 'pelo', 'apos', 'sobre', 'entre', 'mais', 'tem', 'sao', 'jose', 'rio', 'preto' );
	$p    = preg_split( '/\s+/', mural17_normaliza( wp_strip_all_tags( (string) $texto ) ), -1, PREG_SPLIT_NO_EMPTY );
	return array_values( array_unique( array_diff( $p, $fora ) ) );
}

/**
 * Quanto do conjunto menor está no maior (0 a 1).
 *
 * @param string[] $a Palavras.
 * @param string[] $b Palavras.
 * @return float
 */
function mural17_sobreposicao( $a, $b ) {
	$menor = min( count( $a ), count( $b ) );
	return $menor ? count( array_intersect( $a, $b ) ) / $menor : 0.0;
}

/**
 * Matéria publicada nos 3 dias anteriores que conta a mesma história, ou 0.
 *
 * Mesma história = títulos com 60% das palavras em comum (pelo menos 4) e
 * texto com 40% das palavras principais em comum. Exemplo real: "2º Torneio
 * Saboraki de Futebol tem primeira rodada disputada em Jales" e "Jales
 * realiza 1ª rodada do 2º Torneio Saboraki de Futebol de Base".
 *
 * @param WP_Post $post Matéria nova.
 * @return int
 */
function mural17_materia_repetida( $post ) {
	$tit = mural17_palavras_chave( $post->post_title );
	if ( count( $tit ) < 4 ) {
		return 0;
	}
	$corpo = array_slice( mural17_palavras_chave( wp_trim_words( $post->post_content, 160, '' ) ), 0, 120 );
	$antes = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'future' ),
			'posts_per_page' => 150,
			'post__not_in'   => array( $post->ID ),
			'date_query'     => array( array( 'after' => '3 days ago' ) ),
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	foreach ( $antes as $o ) {
		if ( strtotime( $o->post_date_gmt ) > strtotime( $post->post_date_gmt ) ) {
			continue; // Só a mais nova sai; a primeira fica.
		}
		$t2 = mural17_palavras_chave( $o->post_title );
		if ( count( array_intersect( $tit, $t2 ) ) < 4 || mural17_sobreposicao( $tit, $t2 ) < 0.6 ) {
			continue;
		}
		$c2 = array_slice( mural17_palavras_chave( wp_trim_words( $o->post_content, 160, '' ) ), 0, 120 );
		if ( ! $corpo || ! $c2 || mural17_sobreposicao( $corpo, $c2 ) >= 0.4 ) {
			return (int) $o->ID;
		}
	}
	return 0;
}

/**
 * Matéria importada sem nenhuma cidade do 17 no texto, vinda de busca do
 * Google News ou de feed sem cidade fixa ("Itapeva decreta calamidade").
 *
 * @param WP_Post $post Matéria.
 * @return bool
 */
function mural17_fora_do_17( $post ) {
	$feed  = (int) get_post_meta( $post->ID, '_pfai_feed_id', true );
	$fonte = (string) get_post_meta( $post->ID, '_pfai_source_url', true );
	if ( ! $feed ) {
		return false;
	}
	if ( false === strpos( $fonte, 'news.google.com' ) && function_exists( 'mural17_cidade_do_feed' ) && mural17_cidade_do_feed( $feed ) ) {
		return false; // Feed da própria prefeitura ou câmara: é da cidade.
	}
	return ! mural17_cidades_no_texto( $post->post_title, $post->post_excerpt . ' ' . $post->post_content );
}

/**
 * Tira da publicação (rascunho) a matéria repetida ou de fora do 17. Dá para
 * publicar de novo à mão: o rascunho guarda o motivo.
 *
 * @param int $post_id Matéria.
 * @return string Motivo, ou '' se ficou como estava.
 */
function mural17_confere_qualidade( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) || get_post_meta( $post_id, '_mural17_liberada', true ) ) {
		return '';
	}
	$motivo = '';
	$igual  = mural17_materia_repetida( $post );
	if ( $igual ) {
		$motivo = 'repetida';
		update_post_meta( $post_id, '_mural17_repetida_de', $igual );
	} elseif ( mural17_fora_do_17( $post ) ) {
		$motivo = 'fora-do-17';
	}
	if ( $motivo ) {
		update_post_meta( $post_id, '_mural17_retida', $motivo );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
	}
	return $motivo;
}

add_action(
	'added_post_meta',
	function ( $meta_id, $post_id, $chave, $valor ) {
		if ( '_pfai_feed_id' === $chave && (int) $valor ) {
			mural17_confere_qualidade( (int) $post_id );
		}
	},
	25,
	4
);

// Quem publicar de novo um rascunho retido à mão decide: não retém outra vez.
add_action(
	'transition_post_status',
	function ( $novo, $velho, $post ) {
		if ( 'publish' === $novo && 'draft' === $velho && get_post_meta( $post->ID, '_mural17_retida', true ) && is_admin() ) {
			update_post_meta( $post->ID, '_mural17_liberada', 1 );
		}
	},
	10,
	3
);

/**
 * Revisão única das matérias dos últimos 30 dias (repetidas e fora do 17).
 */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'mural17_qualidade_revisao' ) || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		update_option( 'mural17_qualidade_revisao', time(), false );
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 600,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'ASC',
				'date_query'     => array( array( 'after' => '30 days ago' ) ),
				'meta_query'     => array( array( 'key' => '_pfai_feed_id', 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$n = array( 'repetida' => 0, 'fora-do-17' => 0 );
		foreach ( $ids as $id ) {
			$m = mural17_confere_qualidade( $id );
			if ( $m ) {
				++$n[ $m ];
			}
		}
		update_option( 'mural17_qualidade_resultado', $n, false );
	}
);

/**
 * Aviso no painel com o que foi retido, e a lista em Posts › Rascunhos.
 */
add_action(
	'admin_notices',
	function () {
		$n = get_option( 'mural17_qualidade_resultado' );
		if ( ! is_array( $n ) || ! array_sum( $n ) || ! current_user_can( 'edit_others_posts' ) || get_user_meta( get_current_user_id(), 'mural17_qualidade_visto', true ) ) {
			return;
		}
		if ( isset( $_GET['mural17_visto'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_user_meta( get_current_user_id(), 'mural17_qualidade_visto', 1 );
			return;
		}
		$url = admin_url( 'edit.php?post_status=draft&post_type=post&mural17_retida=1' );
		printf(
			'<div class="notice notice-info"><p><b>Mural 17 · revisão de qualidade:</b> %1$d matéria(s) repetida(s) e %2$d sem relação com o 17 saíram do ar e viraram rascunho. <a href="%3$s">Ver as matérias</a> · <a href="%4$s">Ok, entendi</a></p></div>',
			(int) $n['repetida'],
			(int) $n['fora-do-17'],
			esc_url( $url ),
			esc_url( add_query_arg( 'mural17_visto', 1 ) )
		);
	}
);

add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() && $q->is_main_query() && ! empty( $_GET['mural17_retida'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$q->set( 'meta_key', '_mural17_retida' );
		}
	}
);

/* ---- Noindex nas páginas sem conteúdo ----------------------------------- */

/**
 * Página com pouco conteúdo não deve ser indexada: cidade sem notícia
 * recente, busca, autor, anexo, tag e listagem vazia.
 *
 * @param array $robots Diretivas.
 * @return array
 */
function mural17_robots_qualidade( $robots ) {
	$fraca = is_search() || is_author() || is_attachment() || is_tag() || is_date() || ( is_paged() && get_query_var( 'paged' ) > 5 );
	if ( ! $fraca && is_tax( 'cidade' ) ) {
		$t     = get_queried_object();
		$chave = 'mural17_cid_n_' . $t->term_id;
		$n     = get_transient( $chave );
		if ( false === $n ) {
			$q = new WP_Query(
				array(
					'post_type'      => 'post',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => array( array( 'taxonomy' => 'cidade', 'terms' => $t->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
					'date_query'     => array( array( 'after' => '60 days ago' ) ),
				)
			);
			$n = (int) $q->found_posts;
			set_transient( $chave, $n, 6 * HOUR_IN_SECONDS );
		}
		$fraca = $n < 3;
	}
	if ( ! $fraca && ( is_post_type_archive() || is_category() ) && ! have_posts() ) {
		$fraca = true;
	}
	if ( $fraca ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'mural17_robots_qualidade', 20 );

/**
 * Tira do sitemap do WordPress as cidades sem notícia recente e as tags.
 */
add_filter(
	'wp_sitemaps_taxonomies',
	function ( $tax ) {
		unset( $tax['post_tag'] );
		return $tax;
	}
);
add_filter(
	'wp_sitemaps_taxonomies_query_args',
	function ( $args, $taxonomy ) {
		if ( 'cidade' === $taxonomy ) {
			$args['hide_empty'] = true;
		}
		return $args;
	},
	10,
	2
);
add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);
