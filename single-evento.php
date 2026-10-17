<?php
/**
 * Página de um evento.
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	$dia    = mural17_evento_dia();
	$quando = mural17_evento_quando();
	$local  = mural17_meta( 'evento_local' );
	$valor  = mural17_meta( 'evento_valor' );
	$link   = mural17_meta( 'evento_link' );
	$cidade = mural17_cidade();
	?>
	<article <?php post_class( 'item' ); ?>>
		<header class="item__topo">
			<p class="arquivo__rotulo"><a href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>">Agenda</a></p>
			<h1 class="materia__titulo"><?php the_title(); ?></h1>
		</header>

		<div class="item__grade">
			<div class="item__conteudo">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="materia__foto"><?php the_post_thumbnail( 'mural17-destaque' ); ?></figure>
				<?php endif; ?>
				<div class="materia__texto"><?php the_content(); ?></div>
			</div>

			<aside class="ficha" aria-label="Informações do evento">
				<div class="ficha__data">
					<span class="evento__mes"><?php echo esc_html( $dia['mes'] ); ?></span>
					<span class="evento__dia"><?php echo esc_html( $dia['dia'] ); ?></span>
				</div>
				<dl class="ficha__lista">
					<?php if ( $quando ) : ?>
						<dt>Quando</dt><dd><?php echo esc_html( ucfirst( $quando ) ); ?></dd>
					<?php endif; ?>
					<?php if ( $local || $cidade ) : ?>
						<dt>Onde</dt><dd><?php echo esc_html( implode( ' · ', array_filter( array( $local, $cidade ? $cidade->name : '' ) ) ) ); ?></dd>
					<?php endif; ?>
					<?php if ( $valor ) : ?>
						<dt>Entrada</dt><dd><?php echo esc_html( $valor ); ?></dd>
					<?php endif; ?>
				</dl>
				<?php if ( $link ) : ?>
					<a class="botao botao--largo" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Ingressos e informações</a>
				<?php endif; ?>
			</aside>
		</div>
		<?php get_template_part( 'template-parts/compartilhar' ); ?>
	</article>
	<?php
endwhile;

get_footer();
