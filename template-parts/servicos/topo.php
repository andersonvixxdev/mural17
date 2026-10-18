<?php
/**
 * Topo das páginas de serviço: caminho, título, texto e seletor de cidade.
 *
 * Args: titulo, texto, cidade (WP_Term|null), seletor (bool), regioes (bool),
 * selo (texto do selo amarelo, opcional), extra (HTML à direita, opcional).
 *
 * @package Mural17
 */

$args = wp_parse_args(
	$args,
	array(
		'titulo'  => '',
		'texto'   => '',
		'cidade'  => null,
		'seletor' => true,
		'regioes' => false,
		'selo'    => '',
		'extra'   => '',
	)
);
?>
<header class="sv-pagina__topo">
	<div class="sv-pagina__titulos">
		<p class="sv-pagina__caminho"><a href="<?php echo esc_url( home_url( '/servicos/' ) ); ?>">Serviços</a></p>
		<?php if ( $args['selo'] ) : ?>
			<span class="sv-pagina__selo"><?php echo esc_html( $args['selo'] ); ?></span>
		<?php endif; ?>
		<h1 class="sv-pagina__titulo"><?php echo esc_html( $args['titulo'] ); ?></h1>
		<?php if ( $args['texto'] ) : ?>
			<p class="sv-pagina__texto"><?php echo esc_html( $args['texto'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $args['seletor'] || $args['extra'] ) : ?>
		<div class="sv-pagina__lado">
			<?php
			if ( $args['extra'] ) {
				echo $args['extra']; // phpcs:ignore WordPress.Security.EscapeOutput -- montado pelo modelo.
			}
			if ( $args['seletor'] ) {
				mural17_seletor_cidade( $args['cidade'], $args['regioes'] );
			}
			?>
		</div>
	<?php endif; ?>
</header>
