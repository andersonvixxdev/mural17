<?php
/**
 * Cartão de vaga de emprego.
 *
 * @package Mural17
 */

$empresa = mural17_meta( 'vaga_empresa' );
$tipo    = mural17_meta( 'vaga_tipo' );
$cidade  = mural17_cidade();
$onde    = implode( ' · ', array_filter( array( $empresa, $cidade ? $cidade->name : '' ) ) );
?>
<a <?php post_class( 'vaga' ); ?> href="<?php the_permalink(); ?>">
	<span class="vaga__info">
		<span class="vaga__cargo"><?php the_title(); ?></span>
		<?php if ( $onde ) : ?>
			<span class="vaga__onde"><?php echo esc_html( $onde ); ?></span>
		<?php endif; ?>
	</span>
	<?php if ( $tipo ) : ?>
		<span class="pilula"><?php echo esc_html( $tipo ); ?></span>
	<?php endif; ?>
</a>
