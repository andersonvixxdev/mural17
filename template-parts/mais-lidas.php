<?php
/**
 * Bloco "Mais lidas" + anúncio lateral + widgets da barra lateral.
 *
 * @package Mural17
 */

$lidas = mural17_mais_lidas( 5 );
?>
<aside class="lateral" aria-label="Mais lidas e publicidade">
	<?php if ( $lidas->have_posts() ) : ?>
		<section class="mais-lidas">
			<?php mural17_titulo_secao( 'Mais lidas' ); ?>
			<ol class="mais-lidas__lista">
				<?php
				while ( $lidas->have_posts() ) :
					$lidas->the_post();
					?>
					<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
				<?php endwhile; ?>
			</ol>
		</section>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>

	<?php mural17_anuncio( 'anuncio-lado', 'anuncio--300' ); ?>

	<?php if ( is_active_sidebar( 'barra-lateral' ) ) : ?>
		<div class="lateral__widgets"><?php dynamic_sidebar( 'barra-lateral' ); ?></div>
	<?php endif; ?>
</aside>
