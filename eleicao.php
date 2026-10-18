<?php
/**
 * Especial de apuração em /eleicoes-2026/. A página sai com a última
 * leitura e o assets/js/eleicao.js atualiza tudo a cada minuto.
 *
 * @package Mural17
 */

get_header();

$mural17_c      = mural17_eleicao_config();
$mural17_d      = mural17_apuracao();
$mural17_fecha  = mural17_eleicao_hora( 'fechamento' );
$mural17_teste  = mural17_eleicao_teste();
$mural17_no_ar  = mural17_eleicao_ativa() || $mural17_teste;
$mural17_cidades = array();
foreach ( mural17_lista_cidades() as $mural17_g ) {
	foreach ( $mural17_g['cidades'] as $mural17_cid ) {
		$mural17_cidades[ $mural17_cid->slug ] = $mural17_cid->name;
	}
}
asort( $mural17_cidades );
?>
<div class="ap" data-ap-pagina>
	<section class="ap-hero">
		<div class="wrap">
			<?php if ( $mural17_teste ) : ?>
				<p class="ap-teste">Prévia com os dados do 1º turno · só administradores veem</p>
			<?php endif; ?>
			<p class="ap-hero__chapeu">Eleições 2026 · <?php echo esc_html( $mural17_c['titulo'] . ' · ' . $mural17_c['data'] ); ?></p>
			<h1 class="ap-hero__titulo">Apuração ao vivo</h1>
			<p class="ap-hero__sub" data-ap-hora>
				<?php
				if ( ! $mural17_no_ar && ! $mural17_d ) {
					echo esc_html( 'O especial entra no ar ' . $mural17_c['data'] . ', às ' . wp_date( 'G\h', mural17_eleicao_hora( 'inicio' ) ) . '. A apuração começa às ' . wp_date( 'G\h', $mural17_fecha ) . ', quando as urnas fecham.' );
				} else {
					echo esc_html( 'Resultados oficiais do TSE, atualizados a cada minuto.' );
				}
				?>
			</p>

			<div class="ap-contagem" data-ap-contagem hidden>
				<p class="ap-contagem__rotulo">As urnas fecham às <?php echo esc_html( wp_date( 'G\hi', $mural17_fecha ) ); ?>. Faltam</p>
				<p class="ap-contagem__tempo" data-ap-relogio>--:--:--</p>
				<p class="ap-contagem__dica">Vota quem está na fila até as <?php echo esc_html( wp_date( 'G\hi', $mural17_fecha ) ); ?>. Leve documento com foto ou o e-Título. <a href="https://www.tse.jus.br/servicos-eleitorais/autoatendimento-eleitoral#/" target="_blank" rel="noopener">Consulte seu local de votação no TSE</a>.</p>
			</div>

			<div class="ap-abas" role="tablist" aria-label="Abrangência">
				<button type="button" class="is-on" data-ap-aba="br" role="tab">Brasil</button>
				<button type="button" data-ap-aba="sp" role="tab">Estado de São Paulo</button>
				<button type="button" data-ap-aba="ddd" role="tab">DDD 17 · 117 cidades</button>
				<label class="ap-abas__cidade">
					<span class="screen-reader-text">Sua cidade</span>
					<select data-ap-cidade-sel>
						<option value="">Sua cidade…</option>
						<?php foreach ( $mural17_cidades as $mural17_slug => $mural17_nome ) : ?>
							<option value="<?php echo esc_attr( $mural17_slug ); ?>"><?php echo esc_html( $mural17_nome ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="ap-placar" data-ap-placar>
				<?php for ( $mural17_i = 0; $mural17_i < 2; $mural17_i++ ) : ?>
					<div class="ap-cand ap-cand--<?php echo (int) $mural17_i; ?>" data-ap-cand="<?php echo (int) $mural17_i; ?>">
						<span class="ap-cand__inicial" data-ap-inicial>·</span>
						<div class="ap-cand__quem"><p class="ap-cand__nome" data-ap-nome>Aguardando o TSE</p><p class="ap-cand__selo" data-ap-selo></p></div>
						<div class="ap-cand__num"><b data-ap-pct>—</b><span data-ap-votos></span></div>
					</div>
				<?php endfor; ?>
			</div>
			<div class="ap-barra" aria-hidden="true"><i class="ap-barra__0" data-ap-barra="0"></i><i class="ap-barra__1" data-ap-barra="1"></i></div>
			<div class="ap-urnas"><span data-ap-pst><b>0%</b> das seções totalizadas</span><span data-ap-extra></span></div>
		</div>
	</section>

	<div class="wrap ap-grade">
		<section class="ap-caixa ap-mapa" aria-labelledby="ap-mapa-titulo">
			<h2 class="ap-caixa__titulo" id="ap-mapa-titulo">Como votou o 17</h2>
			<p class="ap-caixa__desc">Cor de quem lidera em cada cidade; quanto mais forte, maior a vantagem. Toque numa cidade para ver o resultado.</p>
			<div class="ap-mapa__area" data-ap-mapa>
				<?php echo file_get_contents( get_template_directory() . '/assets/eleicao/mapa-17.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions ?>
				<div class="ap-tip" data-ap-tip hidden></div>
			</div>
			<p class="ap-legenda">
				<span><i class="ap-legenda__0"></i><b data-ap-leg="0">Candidato 1</b> lidera</span>
				<span><i class="ap-legenda__1"></i><b data-ap-leg="1">Candidato 2</b> lidera</span>
				<span><i class="ap-legenda__fraco"></i>Vantagem de até 5 pontos</span>
				<span><i class="ap-legenda__vazio"></i>Apuração não começou</span>
			</p>
		</section>

		<section class="ap-caixa ap-feed" aria-labelledby="ap-feed-titulo">
			<h2 class="ap-caixa__titulo" id="ap-feed-titulo">Minuto a minuto</h2>
			<p class="ap-caixa__desc">Atualiza sozinho. Notícias da redação e marcos da apuração.</p>
			<ul class="ap-feed__lista" data-ap-feed>
				<li class="ap-feed__vazio">As notícias do dia aparecem aqui.</li>
			</ul>
		</section>
	</div>

	<div class="wrap">
		<section class="ap-caixa ap-tabela" aria-labelledby="ap-tabela-titulo">
			<div class="ap-tabela__cab">
				<div>
					<h2 class="ap-caixa__titulo" id="ap-tabela-titulo">Cidade por cidade</h2>
					<p class="ap-caixa__desc">As 117 cidades do DDD 17.</p>
				</div>
				<div class="ap-ordem" role="group" aria-label="Ordenar">
					<button type="button" class="is-on" data-ap-ordem="votos">Maiores</button>
					<button type="button" data-ap-ordem="nome">A a Z</button>
					<button type="button" data-ap-ordem="margem">Maior vantagem</button>
					<button type="button" data-ap-ordem="pst">Menos apuradas</button>
				</div>
			</div>
			<div class="tabela-rolagem">
				<table class="ap-tab">
					<thead><tr><th scope="col">Cidade</th><th scope="col" data-ap-col="0">—</th><th scope="col" data-ap-col="1">—</th><th scope="col"><span class="screen-reader-text">Barra</span></th><th scope="col">Apuradas</th></tr></thead>
					<tbody data-ap-tabela><tr><td colspan="5" class="vazio">Os resultados das cidades aparecem quando a apuração começar.</td></tr></tbody>
				</table>
			</div>
			<p class="ap-fonte">Fonte: Tribunal Superior Eleitoral (TSE), Portal de Resultados. Percentuais sobre os votos válidos.</p>
		</section>
	</div>
</div>
<?php
get_footer();
