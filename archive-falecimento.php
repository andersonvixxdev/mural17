<?php
/**
 * Falecimentos e velórios.
 *
 * @package Mural17
 */

get_header();

$escopo = isset( $_GET['cidade'] ) ? mural17_escopo_servico() : null; // phpcs:ignore WordPress.Security.NonceVerification
$dia    = mural17_dia_falecimentos();
$nome   = isset( $_GET['nome'] ) ? sanitize_text_field( wp_unslash( $_GET['nome'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$link   = function ( $d ) use ( $escopo, $nome ) {
	return add_query_arg(
		array(
			'dia'    => $d,
			'cidade' => $escopo ? $escopo->slug : false,
			'nome'   => $nome ? $nome : false,
		),
		get_post_type_archive_link( 'falecimento' )
	);
};
?>
<div class="sv-pagina">
	<?php
	get_template_part(
		'template-parts/servicos/topo',
		null,
		array(
			'titulo'  => 'Falecimentos e velórios',
			'texto'   => 'Notas publicadas pelas funerárias da região, com autorização da família. Horários de velório, sepultamento e missa de sétimo dia.',
			'seletor' => false,
		)
	);
	?>
	<form class="filtros" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'falecimento' ) ); ?>">
		<span class="filtros__chips">
			<?php foreach ( array( 'hoje' => 'Hoje', 'ontem' => 'Ontem', '7' => 'Últimos 7 dias' ) as $k => $rotulo ) : ?>
				<a class="filtro__item<?php echo $dia === $k ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( $link( $k ) ); ?>"><?php echo esc_html( $rotulo ); ?></a>
			<?php endforeach; ?>
		</span>
		<input type="hidden" name="dia" value="<?php echo esc_attr( $dia ); ?>">
		<label class="screen-reader-text" for="fal-cidade">Cidade</label>
		<select id="fal-cidade" name="cidade" data-envia>
			<option value="">Todas da região</option>
			<?php foreach ( mural17_lista_cidades() as $grupo ) : ?>
				<optgroup label="<?php echo esc_attr( $grupo['regiao']->name ); ?>">
					<option value="<?php echo esc_attr( $grupo['regiao']->slug ); ?>" <?php selected( $escopo ? $escopo->term_id : 0, $grupo['regiao']->term_id ); ?>>Toda a <?php echo esc_html( lcfirst( $grupo['regiao']->name ) ); ?></option>
					<?php foreach ( $grupo['cidades'] as $c ) : ?>
						<option value="<?php echo esc_attr( $c->slug ); ?>" <?php selected( $escopo ? $escopo->term_id : 0, $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<label class="screen-reader-text" for="fal-nome">Buscar por nome</label>
		<input type="search" id="fal-nome" name="nome" value="<?php echo esc_attr( $nome ); ?>" placeholder="Buscar por nome">
	</form>

	<div class="sv-pagina__grade">
		<div class="sv-pagina__principal">
			<?php if ( have_posts() ) : ?>
				<?php
				$primeiro = true;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/servicos/card-falecimento', null, array( 'completo' => $primeiro ) );
					$primeiro = false;
				endwhile;
				?>
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			<?php else : ?>
				<div class="caixa">
					<p class="vazio">Nenhuma nota de falecimento <?php echo esc_html( 'hoje' === $dia ? 'hoje' : ( 'ontem' === $dia ? 'ontem' : 'nos últimos 7 dias' ) ); ?> <?php echo esc_html( mural17_em_escopo( $escopo ) ); ?>.</p>
					<?php if ( '7' !== $dia ) : ?>
						<p><a href="<?php echo esc_url( $link( '7' ) ); ?>">Ver os últimos 7 dias</a></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<aside class="sv-pagina__lateral">
			<section class="caixa">
				<h2 class="caixa__titulo">Nosso cuidado com estas notas</h2>
				<p>Só publicamos com autorização da família, enviada pela funerária.</p>
				<p>Sem causa da morte, sem foto sem permissão e sem comentários.</p>
				<p>A família pode pedir a retirada a qualquer momento.</p>
			</section>
			<section class="caixa caixa--oficial">
				<h2 class="caixa__titulo">Funerárias</h2>
				<p>Publique as notas pelo mesmo painel dos órgãos, com o nome da sua funerária. Sem custo.</p>
				<a class="botao botao--pilula botao--oficial" href="<?php echo esc_url( mural17_link_oficial( 'cadastro' ) ); ?>">Cadastrar funerária</a>
			</section>
		</aside>
	</div>
</div>
<?php
get_footer();
