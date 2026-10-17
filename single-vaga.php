<?php
/**
 * Página de uma vaga.
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	$empresa  = mural17_meta( 'vaga_empresa' );
	$tipo     = mural17_meta( 'vaga_tipo' );
	$salario  = mural17_meta( 'vaga_salario' );
	$contato  = mural17_meta( 'vaga_contato' );
	$validade = mural17_meta( 'vaga_validade' );
	$cidade   = mural17_cidade();

	$link_contato = '';
	if ( is_email( $contato ) ) {
		$link_contato = 'mailto:' . $contato;
	} elseif ( preg_match( '#^https?://#i', $contato ) ) {
		$link_contato = $contato;
	} elseif ( strlen( preg_replace( '/\D+/', '', $contato ) ) >= 10 ) {
		$link_contato = mural17_whatsapp_link( $contato, 'Olá! Vi a vaga "' . get_the_title() . '" no Mural 17.' );
	}
	?>
	<article <?php post_class( 'item' ); ?>>
		<header class="item__topo">
			<p class="arquivo__rotulo"><a href="<?php echo esc_url( get_post_type_archive_link( 'vaga' ) ); ?>">Empregos</a></p>
			<h1 class="materia__titulo"><?php the_title(); ?></h1>
			<?php if ( $empresa ) : ?>
				<p class="materia__linha-fina"><?php echo esc_html( $empresa ); ?></p>
			<?php endif; ?>
		</header>

		<div class="item__grade">
			<div class="item__conteudo">
				<div class="materia__texto"><?php the_content(); ?></div>
			</div>

			<aside class="ficha" aria-label="Resumo da vaga">
				<dl class="ficha__lista">
					<?php if ( $tipo ) : ?>
						<dt>Tipo</dt><dd><?php echo esc_html( $tipo ); ?></dd>
					<?php endif; ?>
					<?php if ( $cidade ) : ?>
						<dt>Cidade</dt><dd><?php echo esc_html( $cidade->name ); ?></dd>
					<?php endif; ?>
					<?php if ( $salario ) : ?>
						<dt>Salário</dt><dd><?php echo esc_html( $salario ); ?></dd>
					<?php endif; ?>
					<?php if ( $validade ) : ?>
						<dt>Inscrições até</dt><dd><?php echo esc_html( date_i18n( 'j/m/Y', strtotime( $validade ) ) ); ?></dd>
					<?php endif; ?>
					<?php if ( $contato && ! $link_contato ) : ?>
						<dt>Como se candidatar</dt><dd><?php echo esc_html( $contato ); ?></dd>
					<?php endif; ?>
				</dl>
				<?php if ( $link_contato ) : ?>
					<a class="botao botao--largo" href="<?php echo esc_url( $link_contato ); ?>" target="_blank" rel="noopener">Quero me candidatar</a>
				<?php endif; ?>
				<p class="ficha__aviso">O Mural 17 apenas divulga a vaga. Nunca pague para se candidatar.</p>
			</aside>
		</div>
		<?php get_template_part( 'template-parts/compartilhar' ); ?>
	</article>
	<?php
endwhile;

get_footer();
