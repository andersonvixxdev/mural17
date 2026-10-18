<?php
/**
 * Página de um concurso ou processo seletivo.
 *
 * @package Mural17
 */

get_header();

while ( have_posts() ) :
	the_post();
	$id     = get_the_ID();
	$prazo  = mural17_prazo_concurso( $id );
	$tipos  = mural17_fields()['concurso']['campos']['conc_tipo']['options'];
	$esc    = array_intersect_key( mural17_escolaridades(), array_flip( array_filter( explode( ',', mural17_campo( $id, 'conc_escolaridade' ) ) ) ) );
	$ate    = mural17_data( mural17_campo( $id, 'conc_inscricoes_ate' ) );
	$prova  = mural17_data( mural17_campo( $id, 'conc_prova' ) );
	$edital = mural17_campo( $id, 'conc_edital' );
	$cidade = mural17_cidade();
	$regiao = $cidade && $cidade->parent ? get_term( $cidade->parent, 'cidade' ) : null;
	?>
	<article <?php post_class( 'item' ); ?>>
		<header class="item__topo">
			<p class="arquivo__rotulo"><a href="<?php echo esc_url( get_post_type_archive_link( 'concurso' ) ); ?>">Concursos</a></p>
			<p class="concurso__rotulos"><span class="situacao situacao--<?php echo esc_attr( $prazo[0] ); ?>"><?php echo esc_html( $prazo[1] ); ?></span> <?php echo esc_html( $tipos[ mural17_campo( $id, 'conc_tipo' ) ] ?? 'Concurso' ); ?></p>
			<h1 class="materia__titulo"><?php the_title(); ?></h1>
			<p class="materia__linha-fina"><?php echo esc_html( mural17_campo( $id, 'conc_orgao' ) . ( $cidade ? ' · ' . $cidade->name : '' ) ); ?></p>
		</header>

		<div class="item__grade">
			<div class="item__conteudo">
				<div class="materia__texto">
					<?php the_content(); ?>
					<p>Leia o edital inteiro antes de se inscrever: requisitos, documentos e datas podem mudar por retificação. O Mural 17 só divulga; a inscrição é feita no site do órgão ou da banca.</p>
				</div>
			</div>

			<aside class="ficha" aria-label="Resumo do concurso">
				<dl class="ficha__lista">
					<?php if ( mural17_campo( $id, 'conc_vagas' ) ) : ?>
						<dt>Vagas</dt><dd><?php echo esc_html( mural17_campo( $id, 'conc_vagas' ) ); ?></dd>
					<?php endif; ?>
					<?php if ( $esc ) : ?>
						<dt>Escolaridade</dt><dd><?php echo esc_html( implode( ', ', $esc ) ); ?></dd>
					<?php endif; ?>
					<?php if ( mural17_campo( $id, 'conc_salario' ) ) : ?>
						<dt><?php echo 'estagio' === mural17_campo( $id, 'conc_tipo' ) ? 'Bolsa' : 'Salário'; ?></dt><dd>até <?php echo esc_html( mural17_reais( mural17_campo( $id, 'conc_salario' ) ) ); ?></dd>
					<?php endif; ?>
					<?php if ( mural17_campo( $id, 'conc_taxa' ) ) : ?>
						<dt>Taxa</dt><dd><?php echo esc_html( mural17_campo( $id, 'conc_taxa' ) ); ?></dd>
					<?php endif; ?>
					<?php if ( $ate ) : ?>
						<dt>Inscrições até</dt><dd><?php echo esc_html( $ate->format( 'd/m/Y' ) ); ?></dd>
					<?php endif; ?>
					<?php if ( $prova ) : ?>
						<dt>Prova</dt><dd><?php echo esc_html( $prova->format( 'd/m/Y' ) ); ?></dd>
					<?php endif; ?>
				</dl>
				<?php if ( $edital ) : ?>
					<a class="botao botao--largo" href="<?php echo esc_url( $edital ); ?>" target="_blank" rel="noopener">Ver edital</a>
				<?php endif; ?>
				<a class="ficha__mais" href="<?php echo esc_url( add_query_arg( 'cidade', $regiao ? $regiao->slug : '', get_post_type_archive_link( 'concurso' ) ) ); ?>">Outros concursos na região</a>
			</aside>
		</div>
		<?php get_template_part( 'template-parts/compartilhar' ); ?>
	</article>
	<?php
endwhile;

get_footer();
