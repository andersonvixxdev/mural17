<?php
/**
 * Página estática (Sobre, Expediente, Anuncie etc.).
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'pagina' ); ?>>
		<h1 class="materia__titulo"><?php the_title(); ?></h1>
		<div class="materia__texto"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
