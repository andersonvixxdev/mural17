<?php
/**
 * Cartão de comunicado oficial (faixa "Comunicados oficiais" e página do órgão).
 *
 * Args: sem_orgao (bool) esconde o órgão, para a própria página do órgão.
 *
 * @package Mural17
 */

$orgao  = mural17_orgao_do_post();
$tipos  = mural17_tipos_nota();
$tipo   = (string) get_post_meta( get_the_ID(), '_mural17_nota_tipo', true );
$alerta = 'alerta' === $tipo;
?>
<a <?php post_class( 'comunicado' . ( $alerta ? ' comunicado--alerta' : '' ) ); ?> href="<?php the_permalink(); ?>">
	<?php if ( empty( $args['sem_orgao'] ) && $orgao ) : ?>
		<span class="comunicado__quem">
			<?php mural17_brasao( $orgao, 'brasao--p' ); ?>
			<span><b><?php echo esc_html( $orgao->name ); ?></b><small><?php echo esc_html( mural17_quando() ); ?></small></span>
		</span>
	<?php elseif ( has_post_thumbnail() ) : ?>
		<span class="comunicado__foto"><?php the_post_thumbnail( 'mural17-card', array( 'alt' => '' ) ); ?></span>
	<?php endif; ?>
	<span class="comunicado__titulo"><?php the_title(); ?></span>
	<span class="comunicado__pe">
		<?php if ( isset( $tipos[ $tipo ] ) ) : ?>
			<span class="etiqueta<?php echo $alerta ? ' etiqueta--alerta' : ''; ?>"><?php echo esc_html( $tipos[ $tipo ] ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $args['sem_orgao'] ) ) : ?>
			<small><?php echo esc_html( mural17_quando() ); ?></small>
		<?php endif; ?>
	</span>
</a>
