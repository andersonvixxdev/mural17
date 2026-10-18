<?php
/**
 * Concursos e processos seletivos com inscrição aberta.
 *
 * @package Mural17
 */

get_header();

$escopo  = mural17_escopo_servico();
$escopo  = isset( $_GET['cidade'] ) ? $escopo : null; // phpcs:ignore WordPress.Security.NonceVerification -- sem ?cidade=, todo o 17.
$esc     = isset( $_GET['esc'] ) ? sanitize_key( wp_unslash( $_GET['esc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$salario = isset( $_GET['salario'] ) ? absint( $_GET['salario'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$tipo    = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$tipos   = mural17_fields()['concurso']['campos']['conc_tipo']['options'];
$abertos = mural17_concursos_abertos( $escopo, array( 'posts_per_page' => 1, 'fields' => 'ids' ) );
$semana  = mural17_concursos_abertos(
	$escopo,
	array(
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_mural17_conc_inscricoes_ate',
				'value'   => array( current_time( 'Y-m-d' ), wp_date( 'Y-m-d', strtotime( '+7 days' ) ) ),
				'compare' => 'BETWEEN',
			),
		),
	)
);
$provas  = new WP_Query(
	array(
		'post_type'      => 'concurso',
		'posts_per_page' => 4,
		'no_found_rows'  => true,
		'meta_key'       => '_mural17_conc_prova', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_mural17_conc_prova',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
			),
		),
		'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
	)
);
$link    = function ( $mudar ) use ( $escopo, $esc, $salario, $tipo ) {
	$atual = array(
		'cidade'  => $escopo ? $escopo->slug : false,
		'esc'     => $esc ? $esc : false,
		'salario' => $salario ? $salario : false,
		'tipo'    => $tipo ? $tipo : false,
	);
	return add_query_arg( array_merge( $atual, $mudar ), get_post_type_archive_link( 'concurso' ) );
};

ob_start();
?>
<div class="sv-numeros">
	<span class="sv-numeros__item sv-numeros__item--escuro"><b><?php echo esc_html( number_format_i18n( $abertos->found_posts ) ); ?></b><?php echo 1 === (int) $abertos->found_posts ? 'edital aberto' : 'editais abertos'; ?></span>
	<span class="sv-numeros__item sv-numeros__item--aberta"><b><?php echo esc_html( number_format_i18n( $semana->found_posts ) ); ?></b>fecham esta semana</span>
</div>
<?php
$extra = ob_get_clean();
?>
<div class="sv-pagina">
	<?php
	get_template_part(
		'template-parts/servicos/topo',
		null,
		array(
			'titulo'  => 'Concursos e processos seletivos',
			'texto'   => 'Editais abertos nas 117 cidades do 17, enviados pelos órgãos e conferidos pela redação. Prazo, vagas e salário numa linha só.',
			'cidade'  => $escopo,
			'seletor' => false,
			'extra'   => $extra,
		)
	);
	?>
	<form class="filtros" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'concurso' ) ); ?>">
		<label class="screen-reader-text" for="conc-cidade">Cidade</label>
		<select id="conc-cidade" name="cidade" data-envia>
			<option value="">Toda a região</option>
			<?php foreach ( mural17_lista_cidades() as $grupo ) : ?>
				<optgroup label="<?php echo esc_attr( $grupo['regiao']->name ); ?>">
					<option value="<?php echo esc_attr( $grupo['regiao']->slug ); ?>" <?php selected( $escopo ? $escopo->term_id : 0, $grupo['regiao']->term_id ); ?>>Toda a <?php echo esc_html( lcfirst( $grupo['regiao']->name ) ); ?></option>
					<?php foreach ( $grupo['cidades'] as $c ) : ?>
						<option value="<?php echo esc_attr( $c->slug ); ?>" <?php selected( $escopo ? $escopo->term_id : 0, $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<label class="screen-reader-text" for="conc-esc">Escolaridade</label>
		<select id="conc-esc" name="esc" data-envia>
			<option value="">Qualquer escolaridade</option>
			<?php foreach ( mural17_escolaridades() as $k => $nome ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $esc, $k ); ?>><?php echo esc_html( $nome ); ?></option>
			<?php endforeach; ?>
		</select>
		<label class="screen-reader-text" for="conc-sal">Salário</label>
		<select id="conc-sal" name="salario" data-envia>
			<option value="">Qualquer salário</option>
			<?php foreach ( array( 2000, 4000, 6000 ) as $v ) : ?>
				<option value="<?php echo (int) $v; ?>" <?php selected( $salario, $v ); ?>>Acima de <?php echo esc_html( mural17_reais( $v ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( $tipo ) : ?>
			<input type="hidden" name="tipo" value="<?php echo esc_attr( $tipo ); ?>">
		<?php endif; ?>
		<noscript><button type="submit" class="botao botao--pilula">Filtrar</button></noscript>
		<span class="filtros__chips">
			<a class="filtro__item<?php echo '' === $tipo ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( $link( array( 'tipo' => false ) ) ); ?>">Todos</a>
			<?php foreach ( $tipos as $k => $nome ) : ?>
				<a class="filtro__item<?php echo $tipo === $k ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( $link( array( 'tipo' => $k ) ) ); ?>"><?php echo esc_html( $nome ); ?></a>
			<?php endforeach; ?>
		</span>
	</form>

	<div class="sv-pagina__grade">
		<div class="sv-pagina__principal">
			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/servicos/card-concurso' );
				endwhile;
				?>
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			<?php else : ?>
				<div class="caixa"><p class="vazio">Nenhum edital aberto com esses filtros <?php echo esc_html( mural17_em_escopo( $escopo ) ); ?>. Peça o aviso ao lado e mandamos quando abrir.</p></div>
			<?php endif; ?>
		</div>

		<aside class="sv-pagina__lateral">
			<?php
			mural17_form_avisos(
				array(
					'temas'  => array( 'concursos' ),
					'titulo' => 'Avise quando abrir',
					'texto'  => 'Escolha a cidade ou a região. A gente manda por e-mail quando sair edital novo.',
					'cidade' => $escopo && $escopo->parent ? $escopo : null,
				)
			);
			?>
			<?php if ( $provas->posts ) : ?>
				<section class="caixa">
					<h2 class="caixa__titulo">Próximas provas</h2>
					<ul class="lista-datas">
						<?php
						foreach ( $provas->posts as $p ) :
							$c = mural17_cidade( $p->ID );
							?>
							<li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( ( $c ? mural17_nome_curto( $c ) . ' · ' : '' ) . wp_trim_words( get_the_title( $p ), 4, '…' ) ); ?></a><b><?php echo esc_html( mural17_data( mural17_campo( $p->ID, 'conc_prova' ) )->format( 'd/m' ) ); ?></b></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
			<section class="caixa caixa--oficial">
				<h2 class="caixa__titulo">De onde vêm</h2>
				<p>Prefeituras, câmaras e órgãos cadastrados publicam os editais pelo Painel do órgão; a redação acrescenta os que saem nos diários oficiais. Sempre com link para o edital original.</p>
				<a class="botao botao--pilula botao--oficial" href="<?php echo esc_url( mural17_link_aba( 'concursos' ) ); ?>">Publicar edital</a>
			</section>
		</aside>
	</div>
</div>
<?php
get_footer();
