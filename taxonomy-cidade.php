<?php
/**
 * Região ou cidade: o mesmo mural da home, só com o que é de lá, e embaixo
 * todas as notícias em ordem, com paginação.
 *
 * @package Mural17
 */

get_header();

$escopo = get_queried_object();

if ( ! is_paged() ) {
	get_template_part( 'template-parts/mural', null, array( 'escopo' => $escopo ) );
}
?>
<div class="wrap">
	<section class="secao secao--com-lateral" aria-labelledby="todas-titulo">
		<div class="secao__principal">
			<div class="secao__topo">
				<h2 class="secao__titulo" id="todas-titulo">
					<?php
					echo esc_html( 'Todas as notícias de ' . mural17_nome_escopo( $escopo ) );
					if ( is_paged() ) {
						echo ' · página ' . esc_html( get_query_var( 'paged' ) );
					}
					?>
				</h2>
			</div>
			<?php if ( have_posts() ) : ?>
				<div class="lista">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card', null, array( 'variante' => 'lateral' ) );
					endwhile;
					?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			<?php else : ?>
				<p class="vazio">Ainda não há notícias aqui.</p>
			<?php endif; ?>
		</div>
		<?php get_template_part( 'template-parts/mais-lidas' ); ?>
	</section>
</div>
<?php
get_footer();
