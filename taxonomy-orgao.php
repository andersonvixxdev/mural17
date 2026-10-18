<?php
/**
 * Página do órgão: quem é, números e todas as notas, com filtro por tipo.
 *
 * @package Mural17
 */

get_header();

$orgao  = get_queried_object();
$tipos  = mural17_tipos_orgao();
$tipo   = mural17_orgao_meta( $orgao, 'tipo' );
$cidade = get_term( (int) mural17_orgao_meta( $orgao, 'cidade' ), 'cidade' );
$cidade = $cidade && ! is_wp_error( $cidade ) ? $cidade : null;
$site   = mural17_orgao_meta( $orgao, 'site' );
$fone   = mural17_orgao_meta( $orgao, 'telefone' );
$desde  = mural17_orgao_meta( $orgao, 'desde' );
$atual  = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$ultima = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		'fields'         => 'ids',
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'orgao',
				'field'    => 'term_id',
				'terms'    => $orgao->term_id,
			),
		),
	)
);
?>
<section class="orgao-topo">
	<div class="wrap orgao-topo__in">
		<p class="hoje__caminho">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">DDD 17</a>
			<?php if ( $cidade ) : ?>
				/ <a href="<?php echo esc_url( get_term_link( $cidade ) ); ?>"><?php echo esc_html( $cidade->name ); ?></a>
			<?php endif; ?>
			/ Órgãos oficiais
		</p>
		<div class="orgao-topo__quem">
			<?php mural17_brasao( $orgao, 'brasao--xg' ); ?>
			<div class="orgao-topo__nome">
				<div class="card__rotulos">
					<?php if ( isset( $tipos[ $tipo ] ) ) : ?>
						<span class="etiqueta etiqueta--clara"><?php echo esc_html( $tipos[ $tipo ]['nome'] ); ?></span>
					<?php endif; ?>
					<?php if ( 'ativo' === mural17_orgao_meta( $orgao, 'status' ) ) : ?>
						<span class="selo-oficial"><?php echo mural17_icone_oficial( 12 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Verificado</span>
					<?php endif; ?>
				</div>
				<h1><?php echo esc_html( $orgao->name ); ?></h1>
				<p><?php echo $orgao->description ? esc_html( $orgao->description ) : 'Comunicados, serviços e alertas enviados pelo órgão. Os textos são do órgão e passam pela revisão da redação antes de publicar.'; ?></p>
			</div>
			<?php if ( $site || $fone ) : ?>
				<div class="orgao-topo__contato">
					<?php if ( $site ) : ?>
						<a href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener">Site oficial <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg></a>
					<?php endif; ?>
					<?php if ( $fone ) : ?>
						<span><?php echo esc_html( $fone ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="numeros numeros--3">
			<div class="num"><b><?php echo esc_html( number_format_i18n( $orgao->count ) ); ?></b><span><?php echo 1 === (int) $orgao->count ? 'nota publicada' : 'notas publicadas'; ?></span></div>
			<div class="num num--eventos"><b><?php echo $ultima->posts ? esc_html( human_time_diff( get_post_time( 'U', true, $ultima->posts[0] ), time() ) ) : '—'; ?></b><span>desde a última nota</span></div>
			<div class="num"><b><?php echo $desde ? esc_html( date_i18n( 'M/y', strtotime( $desde ) ) ) : '—'; ?></b><span>no Mural 17 desde</span></div>
		</div>
	</div>
</section>

<div class="wrap">
	<div class="secao secao--com-lateral">
		<section class="secao__principal" aria-label="Notas do órgão">
			<nav class="filtro" aria-label="Filtrar por tipo de nota">
				<a class="filtro__item<?php echo '' === $atual ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( get_term_link( $orgao ) ); ?>">Todas</a>
				<?php foreach ( mural17_tipos_nota() as $k => $nome ) : ?>
					<a class="filtro__item<?php echo $atual === $k ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tipo', $k, get_term_link( $orgao ) ) ); ?>"><?php echo esc_html( $nome ); ?></a>
				<?php endforeach; ?>
			</nav>
			<?php if ( have_posts() ) : ?>
				<div class="comunicados comunicados--2">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card-oficial', null, array( 'sem_orgao' => true ) );
					endwhile;
					?>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			<?php else : ?>
				<p class="vazio">Nenhuma nota <?php echo $atual ? 'deste tipo' : 'publicada'; ?> ainda.</p>
			<?php endif; ?>
		</section>

		<aside class="lateral">
			<?php
			$outros = $cidade ? get_terms(
				array(
					'taxonomy'   => 'orgao',
					'hide_empty' => false,
					'exclude'    => array( $orgao->term_id ),
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'key'   => 'mural17_cidade',
							'value' => $cidade->term_id,
						),
						array(
							'key'   => 'mural17_status',
							'value' => 'ativo',
						),
					),
				)
			) : array();
			if ( $outros && ! is_wp_error( $outros ) ) :
				?>
				<section class="caixa">
					<h2 class="caixa__titulo">Outros órgãos em <?php echo esc_html( $cidade->name ); ?></h2>
					<?php foreach ( $outros as $o ) : ?>
						<a class="orgao-linha" href="<?php echo esc_url( get_term_link( $o ) ); ?>">
							<?php mural17_brasao( $o, 'brasao--p' ); ?>
							<span><b><?php echo esc_html( $o->name ); ?></b><small><?php echo esc_html( ( $tipos[ mural17_orgao_meta( $o, 'tipo' ) ]['nome'] ?? '' ) . ' · ' . sprintf( _n( '%s nota', '%s notas', $o->count, 'mural17' ), number_format_i18n( $o->count ) ) ); ?></small></span>
						</a>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>
			<section class="caixa caixa--oficial">
				<h2 class="caixa__titulo">Seu órgão publica aqui?</h2>
				<p>Prefeituras, câmaras e órgãos da Justiça do DDD 17 podem enviar comunicados de graça.</p>
				<a class="botao botao--pilula botao--oficial" href="<?php echo esc_url( mural17_link_oficial( 'cadastro' ) ); ?>">Cadastrar órgão</a>
			</section>
		</aside>
	</div>
</div>
<?php
get_footer();
