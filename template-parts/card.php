<?php
/**
 * Cartão de notícia.
 *
 * Variantes ($args['variante']):
 *  - grande: manchete principal
 *  - padrao: foto em cima, título embaixo
 *  - lateral: foto à esquerda (polícia, listas)
 *  - texto:  só título
 *
 * @package Mural17
 */

$variante = isset( $args['variante'] ) ? $args['variante'] : 'padrao';
$tag      = isset( $args['tag'] ) ? $args['tag'] : 'h3';
$tamanhos = array(
	'grande'  => 'mural17-destaque',
	'padrao'  => 'mural17-card',
	'lateral' => 'mural17-mini',
);
?>
<article <?php post_class( 'card card--' . $variante ); ?>>
	<?php
	if ( isset( $tamanhos[ $variante ] ) ) {
		mural17_imagem( $tamanhos[ $variante ], 'midia card__midia' );
	}
	?>
	<div class="card__corpo">
		<div class="card__rotulos">
			<?php mural17_selo(); ?>
			<?php mural17_chapeu(); ?>
		</div>
		<?php printf( '<%1$s class="card__titulo"><a href="%2$s">%3$s</a></%1$s>', tag_escape( $tag ), esc_url( get_permalink() ), esc_html( get_the_title() ) ); ?>
		<?php if ( 'grande' === $variante && has_excerpt() ) : ?>
			<p class="card__linha-fina"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<?php $orgao = mural17_orgao_do_post(); ?>
		<p class="card__meta"><?php echo esc_html( ( $orgao ? 'Enviado por ' . $orgao->name . ' · ' : '' ) . mural17_quando() ); ?></p>
	</div>
</article>
