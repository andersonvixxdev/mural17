<?php
/**
 * Cartão de evento da agenda.
 *
 * @package Mural17
 */

$dia    = mural17_evento_dia();
$local  = mural17_meta( 'evento_local' );
$cidade = mural17_cidade();
$onde   = implode( ' · ', array_filter( array( $local, $cidade ? $cidade->name : '' ) ) );
?>
<a <?php post_class( 'evento' ); ?> href="<?php the_permalink(); ?>">
	<span class="evento__data" aria-hidden="true">
		<span class="evento__mes"><?php echo esc_html( $dia['mes'] ); ?></span>
		<span class="evento__dia"><?php echo esc_html( $dia['dia'] ); ?></span>
	</span>
	<span class="evento__info">
		<span class="evento__titulo"><?php the_title(); ?></span>
		<span class="screen-reader-text"><?php echo esc_html( mural17_evento_quando() ); ?></span>
		<?php if ( $onde ) : ?>
			<span class="evento__onde"><?php echo esc_html( $onde ); ?></span>
		<?php endif; ?>
	</span>
</a>
