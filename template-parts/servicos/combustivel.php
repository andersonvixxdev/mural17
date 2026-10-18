<?php
/**
 * Combustível no 17: faixa de preços por cidade (ANP), tabela, etanol ou
 * gasolina e a calculadora do posto.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$cidade   = $args['cidade'];
$dados    = mural17_combustivel();
$produtos = array();
foreach ( mural17_produtos_anp() as $p ) {
	$produtos[ $p['chave'] ] = $p['nome'];
}
$produto = isset( $_GET['produto'] ) ? sanitize_key( wp_unslash( $_GET['produto'] ) ) : 'gasolina'; // phpcs:ignore WordPress.Security.NonceVerification
$produto = isset( $produtos[ $produto ] ) ? $produto : 'gasolina';
$ref     = mural17_combustivel_referencia( $cidade );
$base    = add_query_arg( 'cidade', $cidade->slug, mural17_servicos()['combustivel']['url'] );

$linhas = array();
if ( $dados ) {
	foreach ( $dados['cidades'] as $id => $precos ) {
		$c = get_term( (int) $id, 'cidade' );
		if ( $c && ! is_wp_error( $c ) && isset( $precos[ $produto ] ) ) {
			$linhas[] = array(
				'cidade' => $c,
				'p'      => $precos[ $produto ],
			);
		}
	}
	usort(
		$linhas,
		function ( $a, $b ) {
			return $a['p']['media'] <=> $b['p']['media'];
		}
	);
}
$texto = $dados
	? 'Preços da pesquisa semanal da ANP nos postos das cidades do 17 que ela visita. Semana de ' . wp_date( 'j \d\e F', mural17_data( $dados['semana'][0] )->getTimestamp() ) . ' a ' . wp_date( 'j \d\e F', mural17_data( $dados['semana'][1] )->getTimestamp() ) . '.'
	: 'Preços da pesquisa semanal da ANP nos postos das cidades do 17.';

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo' => 'Combustível no 17',
		'texto'  => $texto,
		'cidade' => $cidade,
	)
);
?>
<?php if ( ! $linhas ) : ?>
	<div class="caixa"><p class="vazio">A pesquisa da ANP ainda não foi importada. Ela sai toda sexta-feira e entra aqui sozinha.</p></div>
	<?php
	return;
endif;
$min_g = floor( min( wp_list_pluck( wp_list_pluck( $linhas, 'p' ), 'min' ) ) * 10 ) / 10;
$max_g = ceil( max( wp_list_pluck( wp_list_pluck( $linhas, 'p' ), 'max' ) ) * 10 ) / 10;
$faixa = max( 0.1, $max_g - $min_g );
$pos   = function ( $v ) use ( $min_g, $faixa ) {
	return round( ( $v - $min_g ) / $faixa * 100, 2 );
};
$passo = $faixa > 1.2 ? 0.5 : ( $faixa > 0.6 ? 0.3 : 0.1 );
?>
<div class="sv-pagina__grade">
	<div class="sv-pagina__principal">
		<nav class="filtro" aria-label="Combustível">
			<?php foreach ( $produtos as $k => $nome ) : ?>
				<a class="filtro__item<?php echo $produto === $k ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'produto', $k, $base ) ); ?>"><?php echo esc_html( $nome ); ?></a>
			<?php endforeach; ?>
		</nav>

		<section class="caixa comb-grafico">
			<div class="comb-grafico__cab">
				<h2 class="caixa__titulo">Do posto mais barato ao mais caro</h2>
				<span class="comb-grafico__legenda"><i class="comb-grafico__l-faixa"></i> faixa de preços <i class="comb-grafico__l-media"></i> média</span>
			</div>
			<ol class="comb-grafico__linhas">
				<?php foreach ( $linhas as $l ) : ?>
					<li class="comb-grafico__linha<?php echo $ref && $ref['cidade']->term_id === $l['cidade']->term_id ? ' is-sua' : ''; ?>">
						<span class="comb-grafico__cidade"><?php echo esc_html( mural17_nome_curto( $l['cidade'] ) ); ?> <b><?php echo esc_html( mural17_preco( $l['p']['media'] ) ); ?></b></span>
						<span class="comb-grafico__trilho" role="img" aria-label="<?php echo esc_attr( sprintf( '%s: de %s a %s, média %s', mural17_nome_curto( $l['cidade'] ), mural17_preco( $l['p']['min'] ), mural17_preco( $l['p']['max'] ), mural17_preco( $l['p']['media'] ) ) ); ?>">
							<span class="comb-grafico__faixa" style="left:<?php echo esc_attr( $pos( $l['p']['min'] ) ); ?>%;width:<?php echo esc_attr( max( 0.8, $pos( $l['p']['max'] ) - $pos( $l['p']['min'] ) ) ); ?>%"></span>
							<span class="comb-grafico__media" style="left:<?php echo esc_attr( $pos( $l['p']['media'] ) ); ?>%"></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
			<div class="comb-grafico__escala" aria-hidden="true">
				<?php for ( $v = $min_g; $v <= $max_g + 0.001; $v += $passo ) : ?>
					<span style="left:<?php echo esc_attr( $pos( $v ) ); ?>%"><?php echo esc_html( number_format( $v, 2, ',', '' ) ); ?></span>
				<?php endfor; ?>
			</div>
		</section>

		<div class="tabela-rolagem">
			<table class="tabela">
				<thead><tr><th scope="col">Cidade</th><th scope="col">Mais barato</th><th scope="col">Média</th><th scope="col">Mais caro</th><th scope="col">Na semana</th><th scope="col">Postos</th></tr></thead>
				<tbody>
					<?php
					foreach ( $linhas as $l ) :
						$var = mural17_variacao_combustivel( $l['cidade']->term_id, $produto );
						?>
						<tr>
							<th scope="row"><?php echo esc_html( mural17_nome_curto( $l['cidade'] ) ); ?></th>
							<td><?php echo esc_html( mural17_preco( $l['p']['min'] ) ); ?></td>
							<td><b><?php echo esc_html( mural17_preco( $l['p']['media'] ) ); ?></b></td>
							<td><?php echo esc_html( mural17_preco( $l['p']['max'] ) ); ?></td>
							<td><?php echo $var ? '<span class="variacao variacao--' . esc_attr( $var[0] ) . '">' . esc_html( $var[1] ) . '</span>' : '—'; ?></td>
							<td><?php echo (int) $l['p']['postos']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="sv-pagina__fonte">Fonte: ANP, Levantamento de Preços de Combustíveis, atualizado toda sexta. A ANP só pesquisa as cidades maiores; nas outras, a referência é a cidade mais próxima.<?php echo 'gas' === $produto ? ' Gás de cozinha: botijão de 13 kg.' : ''; ?></p>
	</div>

	<aside class="sv-pagina__lateral">
		<?php if ( $ref && isset( $ref['precos']['etanol'], $ref['precos']['gasolina'] ) ) : ?>
			<?php
			$razao  = $ref['precos']['etanol']['media'] / $ref['precos']['gasolina']['media'];
			$vale   = $razao <= 0.7;
			?>
			<section class="caixa etanol">
				<h2 class="caixa__titulo">Etanol ou gasolina?</h2>
				<p>O etanol compensa quando custa até 70% do preço da gasolina.</p>
				<p class="etanol__num"><b><?php echo esc_html( number_format( $razao * 100, 1, ',', '' ) ); ?>%</b> em <?php echo esc_html( mural17_nome_curto( $ref['cidade'] ) ); ?></p>
				<p class="etanol__veredito etanol__veredito--<?php echo $vale ? 'etanol' : 'gasolina'; ?>"><?php echo $vale ? 'Etanol compensa esta semana' : 'Gasolina compensa esta semana'; ?></p>
				<?php if ( $ref['km'] > 0 ) : ?>
					<p class="sv-pagina__fonte">A ANP não pesquisa <?php echo esc_html( $cidade->name ); ?>. Referência: <?php echo esc_html( mural17_nome_curto( $ref['cidade'] ) ); ?>, a <?php echo esc_html( number_format_i18n( $ref['km'] ) ); ?> km.</p>
				<?php endif; ?>
			</section>
		<?php endif; ?>
		<section class="caixa calc" data-calc-etanol>
			<h2 class="caixa__titulo">Faça a conta no seu posto</h2>
			<div class="formulario__dupla">
				<p class="campo"><label for="calc-g">Gasolina (R$)</label><input id="calc-g" inputmode="decimal" data-calc-g value="<?php echo $ref && isset( $ref['precos']['gasolina'] ) ? esc_attr( number_format( $ref['precos']['gasolina']['media'], 2, ',', '' ) ) : ''; ?>"></p>
				<p class="campo"><label for="calc-e">Etanol (R$)</label><input id="calc-e" inputmode="decimal" data-calc-e value="<?php echo $ref && isset( $ref['precos']['etanol'] ) ? esc_attr( number_format( $ref['precos']['etanol']['media'], 2, ',', '' ) ) : ''; ?>"></p>
			</div>
			<p class="calc__resultado" data-calc-resultado aria-live="polite"></p>
		</section>
		<?php mural17_anuncio( 'anuncio-lado', 'anuncio--300' ); ?>
	</aside>
</div>
