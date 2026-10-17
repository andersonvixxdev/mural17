<?php
/**
 * Listagem genérica (blog, categorias, cidades, tags, busca).
 *
 * @package Mural17
 */

get_header();
?>
<section class="secao secao--com-lateral">
	<div class="secao__principal">
		<header class="arquivo__topo">
			<?php if ( is_search() ) : ?>
				<p class="arquivo__rotulo">Busca</p>
				<h1 class="arquivo__titulo">
					<?php
					/* translators: %s: termo buscado. */
					printf( esc_html__( 'Resultados para “%s”', 'mural17' ), esc_html( get_search_query() ) );
					?>
				</h1>
			<?php elseif ( is_archive() ) : ?>
				<p class="arquivo__rotulo"><?php echo is_tax( 'cidade' ) ? 'Cidade' : 'Seção'; ?></p>
				<h1 class="arquivo__titulo"><?php single_term_title(); ?></h1>
				<?php the_archive_description( '<div class="arquivo__desc">', '</div>' ); ?>
			<?php else : ?>
				<h1 class="arquivo__titulo">Últimas notícias</h1>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="lista">
				<?php
				while ( have_posts() ) :
					the_post();
					switch ( get_post_type() ) {
						case 'evento':
							get_template_part( 'template-parts/card-evento' );
							break;
						case 'vaga':
							get_template_part( 'template-parts/card-vaga' );
							break;
						default:
							get_template_part( 'template-parts/card', null, array( 'variante' => 'lateral' ) );
					}
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => 'Anteriores',
					'next_text' => 'Próximas',
				)
			);
			?>
		<?php else : ?>
			<p class="vazio">Nada encontrado por aqui.</p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
	<?php get_template_part( 'template-parts/mais-lidas' ); ?>
</section>
<?php
get_footer();
