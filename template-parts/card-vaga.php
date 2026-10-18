<?php
/**
 * Cartão de vaga de emprego.
 *
 * Args: destaque (bool) = cartão grande da página Empregos, com salário e
 * botão de candidatura.
 *
 * @package Mural17
 */

$empresa = mural17_meta( 'vaga_empresa' );
$tipo    = mural17_meta( 'vaga_tipo' );
$cidade  = mural17_cidade();
$onde    = implode( ' · ', array_filter( array( $empresa, $cidade ? $cidade->name : '' ) ) );

if ( ! empty( $args['destaque'] ) ) :
	$salario  = mural17_meta( 'vaga_salario' );
	$validade = mural17_meta( 'vaga_validade' );
	?>
	<article <?php post_class( 'vaga-d' ); ?>>
		<?php if ( $tipo ) : ?>
			<p class="vaga-d__tags"><span class="pilula"><?php echo esc_html( $tipo ); ?></span></p>
		<?php endif; ?>
		<h3 class="vaga-d__cargo"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $onde ) : ?>
			<p class="vaga-d__onde"><?php echo esc_html( $onde ); ?></p>
		<?php endif; ?>
		<?php if ( $salario ) : ?>
			<p class="vaga-d__salario"><?php echo esc_html( $salario ); ?></p>
		<?php endif; ?>
		<?php if ( $validade ) : ?>
			<p class="vaga-d__prazo">Inscrições até <?php echo esc_html( wp_date( 'j/m', strtotime( $validade . ' 12:00' ) ) ); ?></p>
		<?php endif; ?>
		<a class="botao botao--pilula vaga-d__botao" href="<?php the_permalink(); ?>">Quero me candidatar</a>
	</article>
	<?php
	return;
endif;
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
