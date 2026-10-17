<?php
/**
 * Agenda: próximos eventos.
 *
 * @package Mural17
 */

get_header();

get_template_part(
	'template-parts/arquivo-topo',
	null,
	array(
		'titulo'   => 'Agenda da região',
		'texto'    => 'Shows, festas, feiras e eventos em Neves Paulista, Mirassol, Jaci e Rio Preto.',
		'acao'     => 'Divulgar evento',
		'mensagem' => 'Olá, Mural 17! Quero divulgar um evento:',
	)
);
?>
<?php if ( have_posts() ) : ?>
	<div class="grade-eventos grade-eventos--arquivo">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/card-evento' );
		endwhile;
		?>
	</div>
	<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
<?php else : ?>
	<p class="vazio">Nenhum evento agendado por enquanto.</p>
<?php endif; ?>
<?php
get_footer();
