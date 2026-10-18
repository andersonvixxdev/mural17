<?php
/**
 * Nota de falecimento.
 *
 * Args: completo (bool): mostra texto, horários em caixas e ações.
 *
 * @package Mural17
 */

$completo = ! empty( $args['completo'] );
$id       = get_the_ID();
$cidade   = mural17_cidade();
$idade    = mural17_campo( $id, 'fal_idade' );
$vi       = mural17_data( mural17_campo( $id, 'fal_velorio_inicio' ) );
$vf       = mural17_data( mural17_campo( $id, 'fal_velorio_fim' ) );
$sq       = mural17_data( mural17_campo( $id, 'fal_sepult_quando' ) );
$mq       = mural17_data( mural17_campo( $id, 'fal_missa_quando' ) );
$tipo_d   = mural17_campo( $id, 'fal_sepult_tipo' ) ? mural17_campo( $id, 'fal_sepult_tipo' ) : 'Sepultamento';
$agora    = new DateTimeImmutable( 'now', wp_timezone() );
$fmt      = function ( $d ) {
	return mural17_dia( $d, true ) . ', ' . mural17_hora( $d );
};
$velorio  = '';
if ( $vf && $vi && $vi > $agora ) {
	$velorio = $fmt( $vi ) . ' às ' . mural17_hora( $vf );
} elseif ( $vf ) {
	$velorio = mural17_dia( $vf, true ) . ', até ' . mural17_hora( $vf );
} elseif ( $vi ) {
	$velorio = 'A partir de ' . strtolower( $fmt( $vi ) );
}
$blocos = array_filter(
	array(
		'Velório'          => $velorio ? array( $velorio, mural17_campo( $id, 'fal_velorio_local' ) ) : null,
		$tipo_d            => $sq ? array( $fmt( $sq ), mural17_campo( $id, 'fal_sepult_local' ) ) : null,
		'Missa de 7º dia'  => $mq ? array( $fmt( $mq ), mural17_campo( $id, 'fal_missa_local' ) ) : null,
	)
);
$quem = trim( $idade ? $idade . ' anos' : '' );
$quem = trim( $quem . ( $cidade ? ( $quem ? ' · ' : '' ) . mural17_nome_curto( $cidade ) : '' ) );
$wa   = 'https://wa.me/?text=' . rawurlencode( 'Falecimento: ' . get_the_title() . ( $velorio ? '. Velório: ' . $velorio . ( mural17_campo( $id, 'fal_velorio_local' ) ? ', ' . mural17_campo( $id, 'fal_velorio_local' ) : '' ) : '' ) . '. ' . get_permalink() );
?>
<article class="falecimento<?php echo $completo ? ' falecimento--completo' : ''; ?>">
	<div class="falecimento__cab">
		<h2 class="falecimento__nome"><?php echo $completo && ! is_singular() ? '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>' : esc_html( get_the_title() ); ?></h2>
		<?php if ( $quem ) : ?>
			<span class="falecimento__quem"><?php echo esc_html( $quem ); ?></span>
		<?php endif; ?>
	</div>
	<?php if ( $completo ) : ?>
		<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
			<div class="falecimento__texto"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php if ( $blocos ) : ?>
			<div class="falecimento__blocos">
				<?php foreach ( $blocos as $rotulo => $b ) : ?>
					<div class="falecimento__bloco"><span><?php echo esc_html( $rotulo ); ?></span><b><?php echo esc_html( $b[0] ); ?></b><?php echo $b[1] ? '<small>' . esc_html( $b[1] ) . '</small>' : ''; ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="falecimento__rodape">
			<span><?php echo esc_html( ( mural17_campo( $id, 'fal_funeraria' ) ? 'Informado por ' . mural17_campo( $id, 'fal_funeraria' ) . ' · ' : '' ) . get_the_date( 'd/m \à\s G\hi' ) ); ?></span>
			<span class="falecimento__acoes">
				<a class="botao botao--pilula botao--wa botao--mini" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Avisar no WhatsApp</a>
				<?php if ( mural17_campo( $id, 'fal_velorio_local' ) ) : ?>
					<a class="botao botao--pilula botao--contorno-escuro botao--mini" href="<?php echo esc_url( mural17_mapa( mural17_campo( $id, 'fal_velorio_local' ), $cidade ) ); ?>" target="_blank" rel="noopener">Como chegar ao velório</a>
				<?php endif; ?>
			</span>
		</div>
	<?php else : ?>
		<div class="falecimento__resumo">
			<?php foreach ( array_slice( $blocos, 0, 2, true ) as $rotulo => $b ) : ?>
				<span><b><?php echo esc_html( $rotulo . ' ' . lcfirst( $b[0] ) ); ?></b><?php echo $b[1] ? '<small>' . esc_html( $b[1] ) . '</small>' : ''; ?></span>
			<?php endforeach; ?>
			<a class="falecimento__ver" href="<?php the_permalink(); ?>">Ver nota</a>
		</div>
	<?php endif; ?>
</article>
