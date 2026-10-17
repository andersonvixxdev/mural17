<?php
/**
 * Classificados.
 *
 * @package Mural17
 */

get_header();

get_template_part(
	'template-parts/arquivo-topo',
	null,
	array(
		'titulo'   => 'Classificados',
		'texto'    => 'Compre, venda e anuncie na região: veículos, imóveis, móveis e serviços.',
		'acao'     => 'Anunciar',
		'mensagem' => 'Olá, Mural 17! Quero anunciar nos classificados:',
	)
);

$cats = get_terms(
	array(
		'taxonomy'   => 'categoria-anuncio',
		'hide_empty' => true,
	)
);
if ( ! is_wp_error( $cats ) && $cats ) :
	?>
	<nav class="filtro filtro--secundario" aria-label="Categorias de anúncio">
		<?php foreach ( $cats as $c ) : ?>
			<a class="filtro__item" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>

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
	<p class="vazio">Nenhum anúncio publicado ainda.</p>
<?php endif; ?>
<?php
get_footer();
