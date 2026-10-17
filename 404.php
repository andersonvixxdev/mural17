<?php
/**
 * Página não encontrada.
 *
 * @package Mural17
 */

get_header();
?>
<section class="pagina pagina--404">
	<p class="arquivo__rotulo">Erro 404</p>
	<h1 class="materia__titulo">Essa página não existe mais</h1>
	<p>Talvez a notícia tenha mudado de endereço. Tente buscar:</p>
	<?php get_search_form(); ?>
	<p><a class="botao" href="<?php echo esc_url( home_url( '/' ) ); ?>">Voltar para o início</a></p>
</section>
<?php
get_footer();
