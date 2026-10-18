<?php
/**
 * Guia da cidade: emergência, saúde, serviços públicos, transporte, feiras
 * e coleta de lixo.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$cidade = $args['cidade'];
$itens  = new WP_Query(
	array(
		'post_type'      => 'guia',
		'posts_per_page' => 300,
		'no_found_rows'  => true,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'tax_query'      => mural17_tax_cidade( $cidade ), // phpcs:ignore WordPress.DB.SlowDBQuery
	)
);
// Ordem pedida no item (menor primeiro); sem ordem, vai depois, por nome.
usort(
	$itens->posts,
	function ( $a, $b ) {
		$oa = mural17_campo( $a->ID, 'guia_ordem' );
		$ob = mural17_campo( $b->ID, 'guia_ordem' );
		return array( '' === $oa ? 999 : (float) $oa, $a->post_title ) <=> array( '' === $ob ? 999 : (float) $ob, $b->post_title );
	}
);
$secoes = get_terms(
	array(
		'taxonomy'   => 'guia-secao',
		'hide_empty' => false,
		'orderby'    => 'term_order',
	)
);
$ordem  = array_keys( mural17_secoes_guia() );
usort(
	$secoes,
	function ( $a, $b ) use ( $ordem ) {
		$ia = array_search( $a->slug, $ordem, true );
		$ib = array_search( $b->slug, $ordem, true );
		return ( false === $ia ? 99 : $ia ) <=> ( false === $ib ? 99 : $ib );
	}
);
$por     = array();
$revisto = '';
foreach ( $itens->posts as $p ) {
	$ts = wp_get_post_terms( $p->ID, 'guia-secao', array( 'fields' => 'slugs' ) );
	foreach ( is_wp_error( $ts ) || ! $ts ? array( 'outros' ) : $ts as $slug ) {
		$por[ $slug ][] = $p;
	}
	$revisto = max( $revisto, $p->post_modified );
}
$regiao = $cidade->parent ? get_term( $cidade->parent, 'cidade' ) : null;

$emergencia = array(
	'192' => 'SAMU',
	'193' => 'Bombeiros',
	'190' => 'Polícia Militar',
	'199' => 'Defesa Civil',
);

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo' => 'Guia de ' . $cidade->name,
		'texto'  => 'Telefones úteis, saúde, serviços públicos, transporte, feiras e coleta de lixo.',
		'cidade' => $cidade,
	)
);
?>
<div class="sv-pagina__corpo">
	<section class="guia-emergencia" aria-label="Emergência">
		<div class="guia-emergencia__grade">
			<?php foreach ( $emergencia as $n => $nome ) : ?>
				<a class="guia-emergencia__num" href="tel:<?php echo esc_attr( $n ); ?>"><b><?php echo esc_html( $n ); ?></b><span><?php echo esc_html( $nome ); ?></span></a>
			<?php endforeach; ?>
		</div>
		<p>Toque para ligar. Outros números nacionais: 100 (direitos humanos), 180 (violência contra a mulher), 181 (disque-denúncia SP).</p>
	</section>

	<nav class="filtro" aria-label="Seções do guia">
		<?php foreach ( $secoes as $s ) : ?>
			<?php if ( ! empty( $por[ $s->slug ] ) ) : ?>
				<a class="filtro__item" href="#guia-<?php echo esc_attr( $s->slug ); ?>"><?php echo esc_html( $s->name ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
		<a class="filtro__item" href="<?php echo esc_url( mural17_link_servico( 'farmacia', $cidade ) ); ?>">Farmácia de plantão</a>
	</nav>

	<?php if ( ! $itens->posts ) : ?>
		<div class="caixa">
			<h2 class="caixa__titulo">O guia de <?php echo esc_html( $cidade->name ); ?> está sendo preenchido</h2>
			<p>A redação está conferindo os telefones e endereços. Órgão da cidade? Mande correções pelo <a href="<?php echo esc_url( mural17_link_oficial( 'painel' ) ); ?>">Painel do órgão</a> ou pelo WhatsApp da redação.</p>
		</div>
	<?php endif; ?>

	<div class="guia-secoes">
		<?php foreach ( $secoes as $s ) : ?>
			<?php
			if ( empty( $por[ $s->slug ] ) ) {
				continue;
			}
			?>
			<section class="guia-secao guia-secao--<?php echo esc_attr( $s->slug ); ?>" id="guia-<?php echo esc_attr( $s->slug ); ?>">
				<h2 class="secao__titulo"><?php echo esc_html( $s->name ); ?></h2>
				<?php if ( 'coleta' === $s->slug ) : ?>
					<div class="tabela-rolagem">
						<table class="tabela">
							<thead><tr><th>Bairro</th><th>Lixo comum</th><th>Reciclável</th></tr></thead>
							<tbody>
								<?php foreach ( $por[ $s->slug ] as $p ) : ?>
									<tr><th scope="row"><?php echo esc_html( get_the_title( $p ) ); ?></th><td><?php echo esc_html( mural17_campo( $p->ID, 'guia_horario' ) ); ?></td><td><?php echo esc_html( mural17_campo( $p->ID, 'guia_obs' ) ); ?></td></tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<ul class="guia-itens<?php echo 'feiras' === $s->slug ? ' guia-itens--feiras' : ''; ?>">
						<?php
						foreach ( $por[ $s->slug ] as $p ) :
							$tel  = mural17_campo( $p->ID, 'guia_telefone' );
							$end  = mural17_campo( $p->ID, 'guia_endereco' );
							$hor  = mural17_campo( $p->ID, 'guia_horario' );
							$obs  = mural17_campo( $p->ID, 'guia_obs' );
							$link = mural17_campo( $p->ID, 'guia_link' );
							?>
							<li class="guia-item">
								<?php if ( 'feiras' === $s->slug && $hor ) : ?>
									<span class="guia-item__dia"><?php echo esc_html( $hor ); ?></span>
								<?php endif; ?>
								<b class="guia-item__nome"><?php echo esc_html( get_the_title( $p ) ); ?></b>
								<span class="guia-item__info"><?php echo esc_html( implode( ' · ', array_filter( array( $end, 'feiras' === $s->slug ? '' : $hor, $obs ) ) ) ); ?></span>
								<span class="guia-item__acoes">
									<?php if ( mural17_tel( $tel ) ) : ?>
										<a class="botao botao--pilula botao--mini" href="<?php echo esc_url( mural17_tel( $tel ) ); ?>" aria-label="Ligar para <?php echo esc_attr( get_the_title( $p ) ); ?>: <?php echo esc_attr( $tel ); ?>">Ligar</a>
									<?php endif; ?>
									<?php if ( $link ) : ?>
										<a class="botao botao--pilula botao--mini botao--contorno-escuro" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Ver</a>
									<?php elseif ( $end ) : ?>
										<a class="botao botao--pilula botao--mini botao--contorno-escuro" href="<?php echo esc_url( mural17_mapa( $end, $cidade ) ); ?>" target="_blank" rel="noopener">Mapa</a>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>

	<?php if ( $revisto ) : ?>
		<p class="sv-pagina__fonte">Conferido pela redação em <?php echo esc_html( mysql2date( 'd/m/Y', $revisto ) ); ?>. Viu algo errado? Avise a redação.</p>
	<?php endif; ?>
</div>
