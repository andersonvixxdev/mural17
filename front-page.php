<?php
/**
 * Página inicial do portal.
 *
 * Para usar: Configurações › Leitura › "Sua página inicial exibe: Seus posts mais recentes"
 * (ou uma página estática qualquer — este template é usado nos dois casos).
 *
 * @package Mural17
 */

get_header();

$exibidos = array();

/*
 * 1. Destaques: posts fixados primeiro, depois os mais recentes.
 */
$fixados   = get_option( 'sticky_posts' );
$destaques = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'post__in'            => $fixados ? $fixados : null,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( $destaques->post_count < 3 ) {
	$destaques = new WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		)
	);
}

if ( $destaques->have_posts() ) :
	?>
	<section class="destaques" aria-label="Destaques">
		<?php
		$i = 0;
		while ( $destaques->have_posts() ) :
			$destaques->the_post();
			$exibidos[] = get_the_ID();
			if ( 0 === $i ) {
				get_template_part(
					'template-parts/card',
					null,
					array(
						'variante' => 'grande',
						'tag'      => 'h2',
					)
				);
				echo '<div class="destaques__lado">';
			} else {
				get_template_part( 'template-parts/card', null, array( 'variante' => 1 === $i ? 'padrao' : 'texto' ) );
			}
			++$i;
		endwhile;
		if ( $i > 0 ) {
			echo '</div>';
		}
		?>
	</section>
	<?php
endif;
wp_reset_postdata();

mural17_anuncio( 'anuncio-meio' );

/*
 * 2. Notícias por cidade.
 */
$cidades = get_terms(
	array(
		'taxonomy'   => 'cidade',
		'hide_empty' => true,
		'parent'     => 0,
	)
);
if ( ! is_wp_error( $cidades ) && $cidades ) :
	?>
	<section class="secao">
		<?php mural17_titulo_secao( 'Notícias por cidade' ); ?>
		<div class="grade-cidades">
			<?php foreach ( $cidades as $cidade ) : ?>
				<?php
				$q = new WP_Query(
					array(
						'post_type'      => 'post',
						'posts_per_page' => 3,
						'post__not_in'   => $exibidos,
						'no_found_rows'  => true,
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array(
								'taxonomy' => 'cidade',
								'field'    => 'term_id',
								'terms'    => $cidade->term_id,
							),
						),
					)
				);
				if ( ! $q->have_posts() ) {
					continue;
				}
				?>
				<div class="cidade">
					<h3 class="cidade__nome"><a href="<?php echo esc_url( get_term_link( $cidade ) ); ?>"><?php echo esc_html( $cidade->name ); ?></a></h3>
					<?php
					$n = 0;
					while ( $q->have_posts() ) :
						$q->the_post();
						if ( 0 === $n ) {
							mural17_imagem( 'mural17-card', 'midia cidade__midia' );
							echo '<a class="cidade__manchete" href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
						} else {
							echo '<a class="cidade__item" href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
						}
						++$n;
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endif;

/*
 * 3. Polícia e trânsito + mais lidas.
 */
$policia = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 4,
		'category_name'  => 'policia,transito',
		'no_found_rows'  => true,
	)
);
?>
<section class="secao secao--com-lateral">
	<div class="secao__principal">
		<?php mural17_titulo_secao( 'Polícia e trânsito', mural17_cat_link( 'policia' ) ); ?>
		<?php if ( $policia->have_posts() ) : ?>
			<div class="lista">
				<?php
				while ( $policia->have_posts() ) :
					$policia->the_post();
					get_template_part( 'template-parts/card', null, array( 'variante' => 'lateral' ) );
				endwhile;
				?>
			</div>
		<?php else : ?>
			<p class="vazio">Nenhuma ocorrência publicada ainda. Use as categorias “Polícia” e “Trânsito”.</p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
	<?php get_template_part( 'template-parts/mais-lidas' ); ?>
</section>

<?php
/*
 * 4. Empregos e classificados.
 */
$vagas   = new WP_Query(
	array(
		'post_type'      => 'vaga',
		'posts_per_page' => 4,
		'no_found_rows'  => true,
	)
);
$anuncio = new WP_Query(
	array(
		'post_type'      => 'classificado',
		'posts_per_page' => 4,
		'no_found_rows'  => true,
	)
);
?>
<section class="secao duas-colunas">
	<div>
		<?php mural17_titulo_secao( 'Empregos', get_post_type_archive_link( 'vaga' ), 'Ver vagas' ); ?>
		<?php if ( $vagas->have_posts() ) : ?>
			<div class="lista-vagas">
				<?php
				while ( $vagas->have_posts() ) :
					$vagas->the_post();
					get_template_part( 'template-parts/card-vaga' );
				endwhile;
				?>
			</div>
		<?php else : ?>
			<p class="vazio">Nenhuma vaga publicada ainda.</p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
	<div>
		<?php mural17_titulo_secao( 'Classificados', get_post_type_archive_link( 'classificado' ), 'Ver anúncios' ); ?>
		<?php if ( $anuncio->have_posts() ) : ?>
			<div class="grade-classificados">
				<?php
				while ( $anuncio->have_posts() ) :
					$anuncio->the_post();
					get_template_part( 'template-parts/card-classificado' );
				endwhile;
				?>
			</div>
		<?php else : ?>
			<p class="vazio">Nenhum anúncio publicado ainda.</p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</section>

<?php
/*
 * 5. Agenda: próximos eventos.
 */
$eventos = new WP_Query(
	array(
		'post_type'      => 'evento',
		'posts_per_page' => 4,
		'no_found_rows'  => true,
		'meta_key'       => '_mural17_evento_data', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_mural17_evento_data',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	)
);
?>
<section class="secao">
	<?php mural17_titulo_secao( 'Agenda da região', get_post_type_archive_link( 'evento' ), 'Ver agenda' ); ?>
	<?php if ( $eventos->have_posts() ) : ?>
		<div class="grade-eventos">
			<?php
			while ( $eventos->have_posts() ) :
				$eventos->the_post();
				get_template_part( 'template-parts/card-evento' );
			endwhile;
			?>
		</div>
	<?php else : ?>
		<p class="vazio">Nenhum evento agendado.</p>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>
</section>

<?php
get_footer();
