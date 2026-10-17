<?php
/**
 * Cartão de classificado.
 *
 * @package Mural17
 */

$preco = mural17_meta( 'anuncio_preco' );
$cats  = get_the_terms( get_the_ID(), 'categoria-anuncio' );
?>
<a <?php post_class( 'classificado' ); ?> href="<?php the_permalink(); ?>">
	<span class="midia classificado__midia">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'mural17-mini', array( 'loading' => 'lazy', 'alt' => '' ) );
		} else {
			echo '<span class="midia__vazia"></span>';
		}
		?>
	</span>
	<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
		<span class="classificado__cat"><?php echo esc_html( $cats[0]->name ); ?></span>
	<?php endif; ?>
	<span class="classificado__titulo"><?php the_title(); ?></span>
	<?php if ( $preco ) : ?>
		<span class="classificado__preco"><?php echo esc_html( $preco ); ?></span>
	<?php endif; ?>
</a>
