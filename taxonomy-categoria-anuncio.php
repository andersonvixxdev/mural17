<?php
/**
 * Classificados de uma categoria.
 *
 * @package Mural17
 */

get_header();
?>
<header class="arquivo__topo">
	<p class="arquivo__rotulo"><a href="<?php echo esc_url( get_post_type_archive_link( 'classificado' ) ); ?>">Classificados</a></p>
	<h1 class="arquivo__titulo"><?php single_term_title(); ?></h1>
</header>
<?php if ( have_posts() ) : ?>
	<div class="grade-classificados grade-classificados--arquivo">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/card-classificado' );
		endwhile;
		?>
	</div>
	<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
<?php else : ?>
	<p class="vazio">Nenhum anúncio nesta categoria.</p>
<?php endif; ?>
<?php
get_footer();
