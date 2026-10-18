<?php
/**
 * Na nota oficial: quadro "Serviço" e cartão do órgão.
 *
 * Args: orgao (WP_Term).
 *
 * @package Mural17
 */

$orgao  = $args['orgao'];
$campos = array(
	'servico_quando'  => 'Quando',
	'servico_onde'    => 'Onde',
	'servico_levar'   => 'Levar',
	'servico_contato' => 'Contato',
);
$linhas = array();
foreach ( $campos as $k => $rotulo ) {
	$v = mural17_meta( $k );
	if ( '' !== $v ) {
		$linhas[ $rotulo ] = $v;
	}
}
$tipos = mural17_tipos_orgao();
$tipo  = mural17_orgao_meta( $orgao, 'tipo' );
$desde = mural17_orgao_meta( $orgao, 'desde' );
?>
<?php if ( $linhas ) : ?>
	<aside class="servico">
		<h2 class="servico__titulo">Serviço</h2>
		<dl>
			<?php foreach ( $linhas as $rotulo => $v ) : ?>
				<dt><?php echo esc_html( $rotulo ); ?></dt>
				<dd><?php echo esc_html( $v ); ?></dd>
			<?php endforeach; ?>
		</dl>
	</aside>
<?php endif; ?>

<a class="orgao-cartao" href="<?php echo esc_url( get_term_link( $orgao ) ); ?>">
	<?php mural17_brasao( $orgao, 'brasao--g' ); ?>
	<span class="orgao-cartao__info">
		<b><?php echo esc_html( $orgao->name ); ?></b>
		<span class="orgao-cartao__verificado"><?php echo mural17_icone_oficial( 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Órgão verificado pelo Mural 17</span>
		<small>
			<?php
			echo esc_html(
				implode(
					' · ',
					array_filter(
						array(
							isset( $tipos[ $tipo ] ) ? $tipos[ $tipo ]['nome'] : '',
							sprintf( _n( '%s nota publicada', '%s notas publicadas', $orgao->count, 'mural17' ), number_format_i18n( $orgao->count ) ),
							$desde ? 'desde ' . date_i18n( 'F \d\e Y', strtotime( $desde ) ) : '',
						)
					)
				)
			);
			?>
		</small>
	</span>
	<span class="orgao-cartao__ver">Ver todas as notas →</span>
</a>
