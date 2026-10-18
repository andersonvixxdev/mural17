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
	<?php $larga = has_shortcode( get_post()->post_content, 'mural17_cadastro_orgao' ) || has_shortcode( get_post()->post_content, 'mural17_painel_orgao' ); ?>
	<article <?php post_class( $larga ? 'pagina pagina--larga' : 'pagina' ); ?>>
		<h1 class="materia__titulo"><?php the_title(); ?></h1>
		<div class="materia__texto"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
