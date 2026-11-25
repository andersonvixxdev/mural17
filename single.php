<?php
/**
 * Matéria.
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	$fonte = mural17_fonte();
	$orgao = mural17_orgao_do_post();
	?>
	<div class="secao--com-lateral materia-wrap">
		<article <?php post_class( 'materia' . ( $orgao ? ' materia--oficial' : '' ) ); ?>>
			<?php if ( $orgao ) : ?>
				<div class="faixa-oficial">
					<?php echo mural17_icone_oficial( 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<p><b>Nota oficial</b> Texto enviado por <?php echo esc_html( $orgao->name ); ?>. A redação do Mural 17 revisou, mas não produziu este conteúdo.</p>
				</div>
			<?php endif; ?>
			<header class="materia__topo">
				<div class="card__rotulos">
					<?php mural17_selo(); ?>
					<?php mural17_chapeu(); ?>
				</div>
				<h1 class="materia__titulo"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="materia__linha-fina"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<p class="materia__meta">
					<?php if ( $orgao ) : ?>
						<?php echo esc_html( $orgao->name ); ?>
					<?php elseif ( $fonte ) : ?>
						Fonte: <?php echo esc_html( $fonte['nome'] ); ?>
					<?php else : ?>
						Por <?php the_author(); ?>
					<?php endif; ?>
					· <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j/m/Y \à\s H\hi' ) ); ?></time>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="materia__foto">
					<?php the_post_thumbnail( 'mural17-destaque' ); ?>
					<?php if ( get_the_post_thumbnail_caption() ) : ?>
						<figcaption><?php the_post_thumbnail_caption(); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="materia__texto">
				<?php the_content(); ?>
			</div>

			<?php
			if ( $orgao ) {
				get_template_part( 'template-parts/servico-oficial', null, array( 'orgao' => $orgao ) );
			}
			?>

			<?php if ( $fonte && ! $orgao ) : ?>
				<aside class="fonte">
					<p>Este é um resumo. A matéria completa foi publicada por <strong><?php echo esc_html( $fonte['nome'] ); ?></strong>.</p>
					<a class="botao" href="<?php echo esc_url( $fonte['url'] ); ?>" target="_blank" rel="noopener nofollow">Ler na fonte original</a>
				</aside>
			<?php endif; ?>

			<?php mural17_anuncio( 'anuncio-post' ); ?>

			<?php get_template_part( 'template-parts/compartilhar' ); ?>

			<?php
			$cidade = mural17_cidade();
			if ( $cidade ) :
				$rel = new WP_Query(
					array(
						'post_type'      => 'post',
						'posts_per_page' => 3,
						'post__not_in'   => array( get_the_ID() ),
						'no_found_rows'  => true,
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array(
								'taxonomy' => 'cidade',
								'field'    => 'term_id',
								'terms'    => $cidade->term_id,
							),
						),
					)
				);
				if ( $rel->have_posts() ) :
					?>
					<section class="relacionadas">
						<?php mural17_titulo_secao( 'Mais de ' . $cidade->name, get_term_link( $cidade ), 'Ver todas' ); ?>
						<div class="grade-3">
							<?php
							while ( $rel->have_posts() ) :
								$rel->the_post();
								get_template_part( 'template-parts/card' );
							endwhile;
							?>
						</div>
					</section>
					<?php
				endif;
				wp_reset_postdata();
			endif;
			?>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</article>
		<?php get_template_part( 'template-parts/mais-lidas' ); ?>
	</div>
	<?php
endwhile;

get_footer();
