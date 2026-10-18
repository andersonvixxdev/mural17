<?php
/**
 * Alertas e tempo: avisos que pegam a cidade, outros alertas do 17,
 * previsão do tempo e estoque de sangue.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$cidade  = $args['cidade'];
$todos   = mural17_alertas_ativos();
$daqui   = mural17_alertas_ativos( $cidade );
$ids     = wp_list_pluck( $daqui, 'id' );
$outros  = array_values(
	array_filter(
		$todos,
		function ( $a ) use ( $ids ) {
			return ! in_array( $a['id'], $ids, true );
		}
	)
);
$tempo   = mural17_tempo( $cidade );
$sangue  = mural17_sangue();
$niveis  = mural17_niveis_alerta();
$semana  = array( 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb' );
$nomes   = function ( $ids_cidades, $max = 5 ) {
	$n = array();
	foreach ( array_slice( $ids_cidades, 0, $max ) as $id ) {
		$t = get_term( (int) $id, 'cidade' );
		if ( $t && ! is_wp_error( $t ) ) {
			$n[] = mural17_nome_curto( $t );
		}
	}
	return $n;
};

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo' => 'Alertas e tempo',
		'texto'  => 'Avisos meteorológicos do INMET e da Defesa Civil para as cidades do 17, previsão do tempo e estoque de sangue.',
		'cidade' => $cidade,
	)
);
?>
<div class="sv-pagina__grade">
	<div class="sv-pagina__principal">
		<?php if ( $daqui ) : ?>
			<?php foreach ( $daqui as $a ) : ?>
				<article class="alerta alerta--<?php echo esc_attr( $a['nivel'] ); ?>">
					<p class="alerta__nivel">Alerta <?php echo esc_html( $a['nivel'] . ' · ' . $niveis[ $a['nivel'] ]['nome'] ); ?></p>
					<h2 class="alerta__titulo"><?php echo esc_html( $a['evento'] . ' ' . mural17_periodo_alerta( $a ) ); ?></h2>
					<?php if ( $a['riscos'] ) : ?>
						<p class="alerta__riscos"><?php echo esc_html( implode( ' ', $a['riscos'] ) ); ?></p>
					<?php endif; ?>
					<?php if ( $a['instrucoes'] ) : ?>
						<h3 class="alerta__sub">O que fazer</h3>
						<ul class="alerta__dicas">
							<?php foreach ( array_slice( $a['instrucoes'], 0, 6 ) as $i ) : ?>
								<li><?php echo esc_html( $i ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<p class="alerta__emergencia">Emergência: <a href="tel:199">Defesa Civil 199</a> · <a href="tel:193">Bombeiros 193</a></p>
					<?php $n = $nomes( $a['cidades'] ); ?>
					<p class="alerta__cidades"><b>Cidades do 17 no alerta (<?php echo count( $a['cidades'] ); ?>):</b> <?php echo esc_html( implode( ', ', $n ) . ( count( $a['cidades'] ) > count( $n ) ? ' e mais ' . ( count( $a['cidades'] ) - count( $n ) ) : '' ) ); ?></p>
					<p class="sv-pagina__fonte">Fonte: <?php echo esc_html( $a['fonte'] ); ?><?php echo 'INMET' === $a['fonte'] ? ', aviso meteorológico oficial' : ''; ?>.</p>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<article class="alerta alerta--nenhum">
				<h2 class="alerta__titulo">Nenhum alerta para <?php echo esc_html( mural17_nome_curto( $cidade ) ); ?> agora</h2>
				<p>O site confere os avisos do INMET a cada 30 minutos. Quando houver alerta para a sua cidade, ele aparece aqui e na faixa no topo do site.</p>
			</article>
		<?php endif; ?>

		<?php if ( $outros ) : ?>
			<section class="caixa">
				<h2 class="caixa__titulo">Outros alertas no 17</h2>
				<ul class="alerta-lista">
					<?php foreach ( $outros as $a ) : ?>
						<li class="alerta-lista__item alerta-lista__item--<?php echo esc_attr( $a['nivel'] ); ?>">
							<b><?php echo esc_html( $a['evento'] ); ?></b>
							<span><?php echo esc_html( $niveis[ $a['nivel'] ]['rotulo'] . ' · ' . mural17_periodo_alerta( $a ) . ' · ' . count( $a['cidades'] ) . ' cidades: ' . implode( ', ', $nomes( $a['cidades'], 3 ) ) . ( count( $a['cidades'] ) > 3 ? '…' : '' ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<section class="sv-niveis" aria-label="Níveis de alerta">
			<span class="sv-niveis__item sv-niveis__item--amarelo"><b>Atenção</b> perigo potencial</span>
			<span class="sv-niveis__item sv-niveis__item--laranja"><b>Perigo</b> risco de danos</span>
			<span class="sv-niveis__item sv-niveis__item--vermelho"><b>Grande perigo</b> risco à vida</span>
		</section>
	</div>

	<aside class="sv-pagina__lateral">
		<?php if ( $tempo ) : ?>
			<section class="caixa tempo">
				<h2 class="caixa__titulo">Tempo em <?php echo esc_html( mural17_nome_curto( $cidade ) ); ?> agora</h2>
				<p class="tempo__agora"><b><?php echo (int) $tempo['agora']; ?>°</b><span><?php echo esc_html( mural17_tempo_texto( $tempo['cod'] ) ); ?><br>máx. <?php echo (int) $tempo['dias'][0]['max']; ?>° · mín. <?php echo (int) $tempo['dias'][0]['min']; ?>°<?php echo $tempo['dias'][0]['chuva'] ? ' · chuva ' . (int) $tempo['dias'][0]['chuva'] . '%' : ''; ?></span></p>
				<ol class="tempo__dias">
					<?php
					foreach ( array_slice( $tempo['dias'], 1, 5 ) as $d ) :
						$dt = mural17_data( $d['data'] );
						?>
						<li><span><?php echo esc_html( $semana[ (int) $dt->format( 'w' ) ] ); ?></span><b><?php echo (int) $d['max']; ?>°</b><small><?php echo (int) $d['min']; ?>°</small></li>
					<?php endforeach; ?>
				</ol>
				<p class="sv-pagina__fonte">Previsão: Open-Meteo, atualizada de hora em hora.</p>
			</section>
		<?php endif; ?>

		<?php if ( $sangue ) : ?>
			<section class="caixa sangue">
				<h2 class="caixa__titulo">Estoque de sangue</h2>
				<p class="sangue__local"><?php echo esc_html( $sangue['local'] ); ?></p>
				<ul class="sangue__grade">
					<?php
					foreach ( mural17_tipos_sangue() as $t ) :
						$e = $sangue['tipos'][ $t ] ?? '';
						?>
						<li class="sangue__tipo sangue__tipo--<?php echo esc_attr( $e ? $e : 'sem' ); ?>"><b><?php echo esc_html( $t ); ?></b><span><?php echo esc_html( mural17_estados_sangue()[ $e ] ?? '—' ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( ! empty( $sangue['link'] ) ) : ?>
					<a class="botao botao--pilula" href="<?php echo esc_url( $sangue['link'] ); ?>" target="_blank" rel="noopener">Como doar sangue</a>
				<?php endif; ?>
				<p class="sv-pagina__fonte">Atualizado em <?php echo esc_html( mysql2date( 'd/m \à\s H\h', $sangue['em'] ) ); ?>.</p>
			</section>
		<?php endif; ?>

		<?php
		mural17_form_avisos(
			array(
				'temas'  => array( 'alertas' ),
				'titulo' => 'Alerta no seu e-mail',
				'texto'  => 'Mandamos os alertas de perigo e grande perigo que pegam a sua cidade.',
				'cidade' => $cidade,
			)
		);
		?>
	</aside>
</div>
