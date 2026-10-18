<?php
/**
 * O "mural vivo": topo "Hoje no 17", bolinhas, blocos do dia e seções.
 * Usado na home (escopo null) e nas páginas de região e cidade.
 *
 * Args: escopo (WP_Term|null).
 *
 * @package Mural17
 */

$escopo   = isset( $args['escopo'] ) ? $args['escopo'] : null;
$nome     = mural17_nome_escopo( $escopo );
$numeros  = mural17_numeros( $escopo );
$wa_num   = mural17_opt( 'mural17_whatsapp' );
$exibidos = array();
$em       = $escopo ? ( mural17_e_regiao( $escopo ) ? 'na região de ' . $nome : 'em ' . $nome ) : 'no 17';

$manchete = mural17_manchete( $escopo );
if ( $manchete ) {
	$exibidos[] = $manchete->ID;
}
$plantao = mural17_consulta(
	$escopo,
	array(
		'posts_per_page' => 6,
		'post__not_in'   => $exibidos,
	)
);
?>
<section class="hoje" aria-labelledby="hoje-titulo">
	<div class="wrap hoje__in">
		<div class="hoje__topo">
			<div class="hoje__titulos">
				<?php if ( $escopo ) : ?>
					<p class="hoje__caminho">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">DDD 17</a>
						<?php
						if ( $escopo->parent ) {
							$pai = get_term( $escopo->parent, 'cidade' );
							echo ' / <a href="' . esc_url( get_term_link( $pai ) ) . '">' . esc_html( $pai->name ) . '</a>';
						}
						?>
					</p>
				<?php endif; ?>
				<h1 id="hoje-titulo" class="hoje__titulo">Hoje <?php echo $escopo ? 'em' : 'no'; ?> <span><?php echo esc_html( $nome ); ?></span></h1>
			</div>
			<?php if ( ! $escopo ) : ?>
				<a class="hoje__minha" href="#" data-atalho-cidade hidden>Ver só <b></b> <span aria-hidden="true">→</span></a>
			<?php endif; ?>
			<p class="hoje__data">
				<?php echo esc_html( ucfirst( date_i18n( 'l, j \d\e F' ) ) ); ?>
				<?php if ( $numeros['atualizado'] ) : ?>
					· atualizado às <?php echo esc_html( gmdate( 'H\hi', $numeros['atualizado'] ) ); ?>
				<?php endif; ?>
			</p>
		</div>

		<div class="numeros">
			<a class="num num--noticias" href="#plantao"><b><?php echo esc_html( $numeros['noticias'] ); ?></b><span><?php echo 1 === $numeros['noticias'] ? 'notícia hoje' : 'notícias hoje'; ?></span></a>
			<a class="num num--vagas" href="<?php echo esc_url( $escopo ? add_query_arg( 'cidade', $escopo->slug, get_post_type_archive_link( 'vaga' ) ) : get_post_type_archive_link( 'vaga' ) ); ?>"><b><?php echo esc_html( $numeros['vagas'] ); ?></b><span><?php echo 1 === $numeros['vagas'] ? 'vaga aberta' : 'vagas abertas'; ?></span></a>
			<a class="num num--eventos" href="#semana"><b><?php echo esc_html( $numeros['eventos'] ); ?></b><span><?php echo 1 === $numeros['eventos'] ? 'evento esta semana' : 'eventos esta semana'; ?></span></a>
			<a class="num num--anuncios" href="<?php echo esc_url( $escopo ? add_query_arg( 'cidade', $escopo->slug, get_post_type_archive_link( 'classificado' ) ) : get_post_type_archive_link( 'classificado' ) ); ?>"><b><?php echo esc_html( $numeros['anuncios'] ); ?></b><span>classificados</span></a>
		</div>

		<?php $rodas = mural17_rodas( $escopo ); ?>
		<?php if ( $rodas ) : ?>
			<nav class="rodas" aria-label="Atalhos">
				<?php foreach ( $rodas as $r ) : ?>
					<a class="roda roda--<?php echo esc_attr( $r['tipo'] ); ?>" href="<?php echo esc_url( $r['url'] ); ?>" data-ultima="<?php echo esc_attr( $r['ultima'] ); ?>">
						<span class="roda__anel"><span class="roda__sigla"><?php echo esc_html( $r['sigla'] ); ?></span></span>
						<span class="roda__rotulo"><?php echo esc_html( $r['rotulo'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</section>

<div class="wrap mural">
	<div class="bento">
		<?php if ( $manchete ) : ?>
			<?php
			$GLOBALS['post'] = $manchete; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			setup_postdata( $manchete );
			?>
			<article class="t t--manchete">
				<a class="t__foto" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'mural17-destaque', array( 'alt' => '' ) );
					}
					?>
				</a>
				<div class="t__texto">
					<div class="card__rotulos"><?php mural17_selo(); ?><?php mural17_chapeu( 'chapeu--destaque' ); ?></div>
					<h2 class="t--manchete__titulo"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php if ( has_excerpt() ) : ?>
						<p class="t--manchete__fina"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<p class="card__meta"><?php echo esc_html( mural17_quando() ); ?></p>
				</div>
			</article>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<div class="t t--manchete t--vazio">
				<h2 class="t--manchete__titulo">Ainda não há notícias <?php echo esc_html( $em ); ?>.</h2>
				<p>Assim que a redação publicar, a manchete aparece aqui.</p>
			</div>
		<?php endif; ?>

		<section class="t t--plantao" id="plantao" aria-labelledby="plantao-titulo">
			<div class="t__cab"><h2 class="t__titulo" id="plantao-titulo">Plantão</h2><span class="aovivo">Ao vivo</span></div>
			<?php if ( $plantao->have_posts() ) : ?>
				<ol class="plantao">
					<?php
					while ( $plantao->have_posts() ) :
						$plantao->the_post();
						$exibidos[] = get_the_ID();
						$cid        = mural17_cidade();
						?>
						<li>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( mural17_hora_plantao() ); ?></time>
							<div>
								<a class="plantao__titulo" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<?php if ( $cid ) : ?>
									<span class="plantao__onde"><?php echo esc_html( $cid->name ); ?></span>
								<?php endif; ?>
							</div>
						</li>
					<?php endwhile; ?>
				</ol>
				<?php if ( $escopo ) : ?>
					<a class="t__mais" href="#todas-titulo">Todas as notícias de <?php echo esc_html( $nome ); ?> <span aria-hidden="true">↓</span></a>
				<?php endif; ?>
			<?php else : ?>
				<p class="vazio">Nada publicado ainda.</p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</section>

		<?php
		$evento = mural17_proximo_evento( $escopo );
		if ( $evento ) :
			$dia    = mural17_evento_dia( $evento->ID );
			$hora   = mural17_meta( 'evento_hora', $evento->ID );
			$e_hoje = mural17_meta( 'evento_data', $evento->ID ) === current_time( 'Y-m-d' );
			$cid    = mural17_cidade( $evento->ID );
			?>
			<a class="t t--evento" href="<?php echo esc_url( get_permalink( $evento ) ); ?>">
				<span class="t__cab"><span class="t__titulo"><?php echo $e_hoje ? 'Evento de hoje' : 'Próximo evento'; ?></span><?php echo $cid ? '<span class="t__lugar">' . esc_html( $cid->name ) . '</span>' : ''; ?></span>
				<span class="t--evento__data"><b><?php echo esc_html( $dia['dia'] ); ?></b><span><?php echo esc_html( strtolower( $dia['mes'] ) ); ?><?php echo $hora ? ' · ' . esc_html( str_replace( ':00', 'h', $hora ) ) : ''; ?></span></span>
				<span class="t--evento__nome"><?php echo esc_html( get_the_title( $evento ) ); ?></span>
				<?php $valor = mural17_meta( 'evento_valor', $evento->ID ); ?>
				<?php if ( $valor ) : ?>
					<span class="t__rodape">Entrada: <?php echo esc_html( $valor ); ?></span>
				<?php endif; ?>
			</a>
		<?php else : ?>
			<a class="t t--evento t--cta" href="<?php echo esc_url( $wa_num ? mural17_whatsapp_link( $wa_num, 'Olá, Mural 17! Quero divulgar um evento:' ) : get_post_type_archive_link( 'evento' ) ); ?>">
				<span class="t__cab"><span class="t__titulo">Agenda</span></span>
				<span class="t--evento__nome">Tem evento <?php echo esc_html( $em ); ?>? Divulgue de graça.</span>
				<span class="t__rodape">Mandar pelo WhatsApp</span>
			</a>
		<?php endif; ?>

		<?php
		$vaga = mural17_vaga_do_dia( $escopo );
		if ( $vaga ) :
			$tipo    = mural17_meta( 'vaga_tipo', $vaga->ID );
			$salario = mural17_meta( 'vaga_salario', $vaga->ID );
			$empresa = mural17_meta( 'vaga_empresa', $vaga->ID );
			$cid     = mural17_cidade( $vaga->ID );
			?>
			<a class="t t--vaga" href="<?php echo esc_url( get_permalink( $vaga ) ); ?>">
				<span class="t__cab"><span class="t__titulo">Vaga do dia</span><?php echo $tipo ? '<span class="t__pilula">' . esc_html( $tipo ) . '</span>' : ''; ?></span>
				<span class="t--vaga__cargo"><?php echo esc_html( get_the_title( $vaga ) ); ?></span>
				<span class="t--vaga__onde"><?php echo esc_html( implode( ' · ', array_filter( array( $empresa, $cid ? $cid->name : '' ) ) ) ); ?></span>
				<?php if ( $salario ) : ?>
					<span class="t--vaga__salario"><?php echo esc_html( $salario ); ?></span>
				<?php endif; ?>
				<span class="t__botao">Quero me candidatar</span>
			</a>
		<?php else : ?>
			<a class="t t--vaga t--cta" href="<?php echo esc_url( $wa_num ? mural17_whatsapp_link( $wa_num, 'Olá, Mural 17! Quero anunciar uma vaga:' ) : get_post_type_archive_link( 'vaga' ) ); ?>">
				<span class="t__cab"><span class="t__titulo">Empregos</span></span>
				<span class="t--vaga__cargo">Sua empresa está contratando? Anuncie a vaga de graça.</span>
				<span class="t__botao">Anunciar vaga</span>
			</a>
		<?php endif; ?>

		<?php
		$lidas       = mural17_mais_lidas( 4, 7, $escopo );
		$lidas_todas = false;
		if ( $escopo && ! $lidas->have_posts() ) {
			$lidas       = mural17_mais_lidas( 4 );
			$lidas_todas = true;
		}
		?>
		<section class="t t--lidas" aria-labelledby="lidas-titulo">
			<div class="t__cab"><h2 class="t__titulo" id="lidas-titulo">Mais lidas</h2><span class="t__lugar"><?php echo $lidas_todas ? 'no 17 · 7 dias' : '7 dias'; ?></span></div>
			<?php if ( $lidas->have_posts() ) : ?>
				<ol class="lidas">
					<?php
					while ( $lidas->have_posts() ) :
						$lidas->the_post();
						?>
						<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php endwhile; ?>
				</ol>
			<?php else : ?>
				<p class="vazio">As mais lidas aparecem depois das primeiras visitas.</p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</section>

		<?php
		$transito = get_category_by_slug( 'transito' );
		$tq       = $transito ? mural17_consulta(
			$escopo,
			array(
				'posts_per_page' => 4,
				'cat'            => $transito->term_id,
				'date_query'     => array( array( 'after' => '7 days ago' ) ),
			)
		) : null;
		?>
		<section class="t t--transito" aria-labelledby="transito-titulo">
			<div class="t__cab"><h2 class="t__titulo" id="transito-titulo">Trânsito</h2><span class="t__lugar">7 dias</span></div>
			<?php if ( $tq && $tq->have_posts() ) : ?>
				<ul class="vias">
					<?php
					while ( $tq->have_posts() ) :
						$tq->the_post();
						?>
						<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><span><?php echo esc_html( mural17_hora_plantao() ); ?></span></li>
					<?php endwhile; ?>
				</ul>
			<?php else : ?>
				<p class="t--transito__ok"><b>Sem ocorrências</b> registradas <?php echo esc_html( $em ); ?> nos últimos 7 dias.</p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</section>

		<?php $wa = $wa_num ? mural17_whatsapp_link( $wa_num, 'Olá, Mural 17! Tenho uma notícia:' ) : ''; ?>
		<<?php echo $wa ? 'a href="' . esc_url( $wa ) . '" target="_blank" rel="noopener"' : 'div'; ?> class="t t--envie">
			<span class="t--envie__icone" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg></span>
			<span class="t--envie__texto">
				<span class="t--envie__titulo">Viu algo acontecendo?</span>
				<span>Mande foto, vídeo ou áudio pelo WhatsApp. A redação confere e publica.</span>
			</span>
		</<?php echo $wa ? 'a' : 'div'; ?>>

		<?php
		$anuncio = mural17_consulta(
			$escopo,
			array(
				'post_type'      => 'classificado',
				'posts_per_page' => 1,
			)
		);
		if ( $anuncio->posts ) :
			$a     = $anuncio->posts[0];
			$preco = mural17_meta( 'anuncio_preco', $a->ID );
			$cid   = mural17_cidade( $a->ID );
			?>
			<a class="t t--classif" href="<?php echo esc_url( get_permalink( $a ) ); ?>">
				<span class="t--classif__foto"><?php echo get_the_post_thumbnail( $a, 'mural17-mini', array( 'alt' => '' ) ); ?></span>
				<span class="t--classif__info">
					<span class="t__titulo">Classificado</span>
					<span class="t--classif__nome"><?php echo esc_html( get_the_title( $a ) ); ?></span>
					<?php if ( $preco ) : ?>
						<span class="t--classif__preco"><?php echo esc_html( $preco ); ?></span>
					<?php endif; ?>
					<?php if ( $cid ) : ?>
						<span class="t__lugar"><?php echo esc_html( $cid->name ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		<?php else : ?>
			<a class="t t--classif t--cta" href="<?php echo esc_url( get_post_type_archive_link( 'classificado' ) ); ?>">
				<span class="t--classif__info">
					<span class="t__titulo">Classificados</span>
					<span class="t--classif__nome">Venda, alugue ou ofereça serviços <?php echo esc_html( $em ); ?>.</span>
					<span class="t__lugar">Anunciar é grátis</span>
				</span>
			</a>
		<?php endif; ?>

		<div class="t t--pub">
			<?php if ( is_active_sidebar( 'anuncio-lado' ) ) : ?>
				<span class="t--pub__rotulo">Publicidade</span>
				<?php dynamic_sidebar( 'anuncio-lado' ); ?>
			<?php else : ?>
				<a class="t--pub__vazio" href="<?php echo esc_url( $wa_num ? mural17_whatsapp_link( $wa_num, 'Olá, Mural 17! Quero anunciar no portal.' ) : home_url( '/' ) ); ?>">
					<span class="t__titulo">Anuncie aqui</span>
					<span>Seu negócio visto por quem mora <?php echo esc_html( $em ); ?>.</span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	$lugares = mural17_por_lugar( $escopo, $exibidos );
	if ( $lugares ) :
		$cores = array( 'tinta', 'vermelho', 'azul', 'verde' );
		?>
		<section class="secao" aria-labelledby="lugares-titulo">
			<div class="secao__topo"><h2 class="secao__titulo" id="lugares-titulo"><?php echo $escopo ? 'Por cidade' : 'Por região'; ?></h2></div>
			<div class="lugares<?php echo 0 === count( $lugares ) % 3 && 0 !== count( $lugares ) % 4 ? ' lugares--3' : ''; ?>">
				<?php foreach ( $lugares as $i => $l ) : ?>
					<article class="lugar">
						<a class="lugar__nome lugar__nome--<?php echo esc_attr( $cores[ $i % 4 ] ); ?>" href="<?php echo esc_url( get_term_link( $l['termo'] ) ); ?>">
							<?php echo esc_html( preg_replace( '/^Região de /', '', $l['termo']->name ) ); ?>
							<span aria-hidden="true">→</span>
						</a>
						<?php foreach ( $l['posts'] as $j => $p ) : ?>
							<?php if ( 0 === $j && has_post_thumbnail( $p ) ) : ?>
								<a class="lugar__foto" href="<?php echo esc_url( get_permalink( $p ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo get_the_post_thumbnail( $p, 'mural17-card', array( 'alt' => '' ) ); ?></a>
							<?php endif; ?>
							<a class="lugar__item<?php echo 0 === $j ? ' lugar__item--primeiro' : ''; ?>" href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
						<?php endforeach; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php $semana = mural17_semana( $escopo ); ?>
	<section class="secao" id="semana" aria-labelledby="semana-titulo">
		<div class="secao__topo">
			<h2 class="secao__titulo" id="semana-titulo">Agenda da semana</h2>
			<a class="secao__link" href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>">Ver agenda</a>
		</div>
		<div class="semana">
			<?php
			$dias_semana = array( 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb' );
			$k           = 0;
			foreach ( $semana as $data => $evs ) :
				$ts = strtotime( $data );
				?>
				<div class="dia<?php echo 0 === $k ? ' dia--hoje' : ''; ?><?php echo $evs ? '' : ' dia--vazio'; ?>">
					<div class="dia__n"><b><?php echo esc_html( gmdate( 'd', $ts ) ); ?></b><span><?php echo esc_html( $dias_semana[ (int) gmdate( 'w', $ts ) ] . ( 0 === $k ? ' · hoje' : '' ) ); ?></span></div>
					<?php foreach ( $evs as $ev ) : ?>
						<?php
						$hora = mural17_meta( 'evento_hora', $ev->ID );
						$cid  = mural17_cidade( $ev->ID );
						?>
						<a class="dia__ev" href="<?php echo esc_url( get_permalink( $ev ) ); ?>">
							<?php echo esc_html( get_the_title( $ev ) ); ?>
							<small><?php echo esc_html( implode( ' · ', array_filter( array( $cid ? $cid->name : '', $hora ? str_replace( ':00', 'h', $hora ) : '' ) ) ) ); ?></small>
						</a>
					<?php endforeach; ?>
				</div>
				<?php
				++$k;
			endforeach;
			?>
		</div>
	</section>

	<?php
	$anuncios = mural17_consulta(
		$escopo,
		array(
			'post_type'      => 'classificado',
			'posts_per_page' => 4,
		)
	);
	if ( $anuncios->have_posts() ) :
		$cats = get_terms(
			array(
				'taxonomy'   => 'categoria-anuncio',
				'hide_empty' => true,
			)
		);
		?>
		<section class="secao" aria-labelledby="classif-titulo">
			<div class="secao__topo">
				<h2 class="secao__titulo" id="classif-titulo">Classificados</h2>
				<a class="secao__link" href="<?php echo esc_url( get_post_type_archive_link( 'classificado' ) ); ?>">Ver todos</a>
			</div>
			<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
				<nav class="filtro" aria-label="Categorias de anúncio">
					<?php foreach ( $cats as $c ) : ?>
						<a class="filtro__item" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<div class="grade-classificados grade-classificados--home">
				<?php
				while ( $anuncios->have_posts() ) :
					$anuncios->the_post();
					get_template_part( 'template-parts/card-classificado' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
	<?php endif; ?>
</div>
