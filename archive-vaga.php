<?php
/**
 * Empregos: lista de vagas.
 *
 * @package Mural17
 */

get_header();

get_template_part(
	'template-parts/arquivo-topo',
	null,
	array(
		'titulo'   => 'Empregos',
		'texto'    => 'Vagas, concursos e oportunidades de trabalho na região do 17.',
		'acao'     => 'Anunciar vaga',
		'mensagem' => 'Olá, Mural 17! Quero anunciar uma vaga:',
	)
);
?>
<?php if ( have_posts() ) : ?>
	<div class="lista-vagas lista-vagas--arquivo">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/card-vaga' );
		endwhile;
		?>
	</div>
	<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
<?php else : ?>
	<p class="vazio">Nenhuma vaga aberta no momento.</p>
<?php endif; ?>
<?php
get_footer();
