<?php
/**
 * Página de um classificado.
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	$preco   = mural17_meta( 'anuncio_preco' );
	$wa      = mural17_whatsapp_link( mural17_meta( 'anuncio_whatsapp' ), 'Olá! Vi seu anúncio "' . get_the_title() . '" no Mural 17.' );
	$contato = mural17_meta( 'anuncio_contato' );
	$cidade  = mural17_cidade();
	$cats    = get_the_terms( get_the_ID(), 'categoria-anuncio' );
	?>
	<article <?php post_class( 'item' ); ?>>
		<header class="item__topo">
			<p class="arquivo__rotulo">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'classificado' ) ); ?>">Classificados</a>
				<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
					› <a href="<?php echo esc_url( get_term_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
				<?php endif; ?>
			</p>
			<h1 class="materia__titulo"><?php the_title(); ?></h1>
		</header>

		<div class="item__grade">
			<div class="item__conteudo">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="materia__foto"><?php the_post_thumbnail( 'mural17-destaque' ); ?></figure>
				<?php endif; ?>
				<div class="materia__texto"><?php the_content(); ?></div>
			</div>

			<aside class="ficha" aria-label="Contato do anunciante">
				<?php if ( $preco ) : ?>
					<p class="ficha__preco"><?php echo esc_html( $preco ); ?></p>
				<?php endif; ?>
				<dl class="ficha__lista">
					<?php if ( $cidade ) : ?>
						<dt>Cidade</dt><dd><?php echo esc_html( $cidade->name ); ?></dd>
					<?php endif; ?>
					<dt>Publicado em</dt><dd><?php echo esc_html( get_the_date( 'j/m/Y' ) ); ?></dd>
					<?php if ( $contato ) : ?>
						<dt>Contato</dt><dd><?php echo esc_html( $contato ); ?></dd>
					<?php endif; ?>
				</dl>
				<?php if ( $wa ) : ?>
					<a class="botao botao--largo botao--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Chamar no WhatsApp</a>
				<?php endif; ?>
				<p class="ficha__aviso">Negocie com segurança: veja o produto antes de pagar e prefira locais públicos.</p>
			</aside>
		</div>
		<?php get_template_part( 'template-parts/compartilhar' ); ?>
	</article>
	<?php
endwhile;

get_footer();
