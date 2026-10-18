<?php
/**
 * Linha de um concurso na lista.
 *
 * @package Mural17
 */

$id     = get_the_ID();
$prazo  = mural17_prazo_concurso( $id );
$tipos  = mural17_fields()['concurso']['campos']['conc_tipo']['options'];
$esc    = array_intersect_key( mural17_escolaridades(), array_flip( array_filter( explode( ',', mural17_campo( $id, 'conc_escolaridade' ) ) ) ) );
$sal    = mural17_campo( $id, 'conc_salario' );
$ate    = mural17_data( mural17_campo( $id, 'conc_inscricoes_ate' ) );
$prova  = mural17_data( mural17_campo( $id, 'conc_prova' ) );
$edital = mural17_campo( $id, 'conc_edital' );
$cidade = mural17_cidade();
$linha  = array_filter(
	array(
		mural17_campo( $id, 'conc_vagas' ) ? mural17_campo( $id, 'conc_vagas' ) . ( is_numeric( mural17_campo( $id, 'conc_vagas' ) ) ? ' vagas' : '' ) : '',
		$esc ? implode( ', ', $esc ) : '',
		mural17_campo( $id, 'conc_taxa' ) ? ( false !== stripos( mural17_campo( $id, 'conc_taxa' ), 'sem' ) ? mural17_campo( $id, 'conc_taxa' ) : 'Taxa ' . mural17_campo( $id, 'conc_taxa' ) ) : '',
	)
);
?>
<article class="concurso concurso--<?php echo esc_attr( $prazo[0] ); ?>">
	<div class="concurso__texto">
		<p class="concurso__rotulos">
			<span class="situacao situacao--<?php echo esc_attr( $prazo[0] ); ?>"><?php echo esc_html( $prazo[1] ); ?></span>
			<span><?php echo esc_html( ( $tipos[ mural17_campo( $id, 'conc_tipo' ) ] ?? 'Concurso' ) . ' · ' . mural17_campo( $id, 'conc_orgao' ) . ( $cidade && false === stripos( mural17_campo( $id, 'conc_orgao' ), $cidade->name ) ? ' · ' . mural17_nome_curto( $cidade ) : '' ) ); ?></span>
		</p>
		<h2 class="concurso__titulo"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php if ( $linha ) : ?>
			<p class="concurso__linha"><?php echo esc_html( implode( ' · ', $linha ) ); ?></p>
		<?php endif; ?>
	</div>
	<div class="concurso__valor">
		<span><?php echo 'estagio' === mural17_campo( $id, 'conc_tipo' ) ? 'Bolsa' : 'Salário'; ?></span>
		<b><?php echo $sal ? esc_html( 'até ' . mural17_reais( $sal ) ) : '—'; ?></b>
		<small><?php echo esc_html( $prazo[0] === 'ok' && $prova ? 'Prova em ' . $prova->format( 'd/m' ) : ( $ate ? 'Inscrições até ' . $ate->format( 'd/m' ) : '' ) ); ?></small>
	</div>
	<?php if ( $edital ) : ?>
		<a class="botao botao--pilula concurso__botao" href="<?php echo esc_url( $edital ); ?>" target="_blank" rel="noopener">Ver edital</a>
	<?php endif; ?>
</article>
