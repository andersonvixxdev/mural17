<?php
/**
 * Nota de falecimento.
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="sv-pagina sv-pagina--estreito">
		<p class="sv-pagina__caminho"><a href="<?php echo esc_url( get_post_type_archive_link( 'falecimento' ) ); ?>">Falecimentos</a></p>
		<?php get_template_part( 'template-parts/servicos/card-falecimento', null, array( 'completo' => true ) ); ?>
		<p class="sv-pagina__fonte">Nota publicada com autorização da família. Para pedir correção ou retirada, fale com a funerária ou com a redação do Mural 17.</p>
	</div>
	<?php
endwhile;

get_footer();
