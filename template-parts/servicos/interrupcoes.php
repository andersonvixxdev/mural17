<?php
/**
 * Falta de água e de energia: agora, programadas e normalizadas há pouco.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$escopo = mural17_escopo_servico();
$tipo   = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$tipo   = in_array( $tipo, array( 'agua', 'energia' ), true ) ? $tipo : '';
$lista  = mural17_interrupcoes( $escopo, 80 );
$base   = add_query_arg( 'cidade', $escopo ? $escopo->slug : '', mural17_servicos()['interrupcoes']['url'] );
if ( $tipo ) {
	foreach ( $lista as $k => $grupo ) {
		$lista[ $k ] = array_values(
			array_filter(
				$grupo,
				function ( $p ) use ( $tipo ) {
					return ( 'energia' === mural17_campo( $p->ID, 'int_tipo' ) ? 'energia' : 'agua' ) === $tipo;
				}
			)
		);
	}
}
// Números do 17 inteiro para a lateral.
$tudo   = mural17_interrupcoes( null, 200 );
$sem    = array(
	'agua'    => array(),
	'energia' => array(),
);
foreach ( $tudo['agora'] as $p ) {
	$c = mural17_cidade( $p->ID );
	if ( $c ) {
		$sem[ 'energia' === mural17_campo( $p->ID, 'int_tipo' ) ? 'energia' : 'agua' ][ $c->term_id ] = true;
	}
}
$icone = array(
	'agua'    => '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2s7 8 7 13a7 7 0 0 1-14 0c0-5 7-13 7-13z"/></svg>',
	'energia' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>',
);
$texto_falta = function ( $p ) {
	return 'energia' === mural17_campo( $p->ID, 'int_tipo' ) ? 'energia' : 'agua';
};

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo'  => 'Falta de água e de energia',
		'texto'   => 'O que está sem água ou sem luz agora e o que já tem desligamento marcado. Avisos postados pelos serviços de água e pelas concessionárias, com o selo Oficial.',
		'cidade'  => $escopo,
		'regioes' => true,
	)
);
?>
<div class="sv-pagina__barra">
	<nav class="segmentos" aria-label="Tipo">
		<a class="segmentos__item<?php echo '' === $tipo ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( $base ); ?>">Tudo</a>
		<a class="segmentos__item<?php echo 'agua' === $tipo ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tipo', 'agua', $base ) ); ?>"><?php echo $icone['agua']; // phpcs:ignore WordPress.Security.EscapeOutput ?> Água</a>
		<a class="segmentos__item<?php echo 'energia' === $tipo ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tipo', 'energia', $base ) ); ?>"><?php echo $icone['energia']; // phpcs:ignore WordPress.Security.EscapeOutput ?> Energia</a>
	</nav>
	<label class="screen-reader-text" for="busca-bairro">Seu bairro ou rua</label>
	<input type="search" id="busca-bairro" class="busca-bairro" placeholder="Seu bairro ou rua" data-busca-bairro>
</div>

<div class="sv-pagina__grade">
	<div class="sv-pagina__principal" data-lista-bairros>
		<h2 class="secao__titulo sv-agora"><span class="sv-agora__ponto" aria-hidden="true"></span>Agora</h2>
		<?php if ( $lista['agora'] ) : ?>
			<?php
			foreach ( $lista['agora'] as $p ) :
				$t    = $texto_falta( $p );
				$c    = mural17_cidade( $p->ID );
				$ini  = mural17_data( mural17_campo( $p->ID, 'int_inicio' ) );
				$prev = mural17_data( mural17_campo( $p->ID, 'int_previsao' ) );
				$pct  = $prev && $prev > $ini ? min( 100, max( 4, ( time() - $ini->getTimestamp() ) / ( $prev->getTimestamp() - $ini->getTimestamp() ) * 100 ) ) : 0;
				$mot  = mural17_campo( $p->ID, 'int_motivo' );
				$bai  = mural17_campo( $p->ID, 'int_bairros' );
				$qtd  = count( array_filter( array_map( 'trim', preg_split( '/[,;\n]+/', $bai ) ) ) );
				?>
				<article class="falta falta--<?php echo esc_attr( $t ); ?>" data-texto="<?php echo esc_attr( mural17_normaliza( $bai . ' ' . ( $c ? $c->name : '' ) ) ); ?>">
					<p class="falta__rotulos">
						<span class="falta__tipo"><?php echo $icone[ $t ]; // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo 'energia' === $t ? 'Sem energia' : 'Sem água'; ?></span>
						<?php if ( (int) get_post_meta( $p->ID, '_mural17_orgao_id', true ) ) : ?>
							<span class="selo selo--oficial"><?php echo mural17_icone_oficial( 11 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Oficial</span>
						<?php endif; ?>
						<span class="falta__fonte"><?php echo esc_html( trim( mural17_campo( $p->ID, 'int_fonte' ) . ' · atualizado há ' . human_time_diff( get_post_modified_time( 'U', true, $p ) ), ' ·' ) ); ?></span>
					</p>
					<h3 class="falta__titulo"><?php echo esc_html( ( $mot ? ucfirst( $mot ) . ': ' : '' ) . ( $qtd > 1 ? $qtd . ' bairros' : 'bairro' ) . ' sem ' . ( 'energia' === $t ? 'energia' : 'água' ) . ( $c ? ' em ' . mural17_nome_curto( $c ) : '' ) ); ?></h3>
					<p class="falta__bairros"><?php echo esc_html( $bai ); ?></p>
					<div class="falta__tempo">
						<p><span>Começou <?php echo esc_html( ( 'Hoje' === mural17_dia( $ini ) ? '' : strtolower( mural17_dia( $ini, true ) ) . ', ' ) . mural17_hora( $ini ) ); ?></span><b><?php echo $prev ? esc_html( 'Previsão de volta: ' . ( 'Hoje' === mural17_dia( $prev ) ? '' : strtolower( mural17_dia( $prev, true ) ) . ', ' ) . mural17_hora( $prev ) ) : 'Sem previsão'; ?></b></p>
						<?php if ( $pct ) : ?>
							<span class="falta__barra" role="img" aria-label="<?php echo esc_attr( round( $pct ) . '% do tempo previsto' ); ?>"><span style="width:<?php echo esc_attr( round( $pct ) ); ?>%"></span></span>
						<?php endif; ?>
						<?php if ( mural17_campo( $p->ID, 'int_obs' ) ) : ?>
							<span class="falta__obs"><?php echo esc_html( mural17_campo( $p->ID, 'int_obs' ) ); ?></span>
						<?php endif; ?>
					</div>
					<p class="falta__acoes">
						<a class="botao botao--pilula" href="#avisos">Avisar quando voltar</a>
						<a class="botao botao--pilula botao--contorno-escuro" href="<?php echo esc_url( add_query_arg( array( 'cidade' => $c ? $c->slug : '', 'tipo' => 'energia' === $t ? 'iluminacao' : 'agua-esgoto' ), mural17_servicos()['fala']['url'] ) . '#mandar' ); ?>">Meu bairro também está sem <?php echo 'energia' === $t ? 'luz' : 'água'; ?></a>
					</p>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="caixa"><p class="vazio">Nenhuma falta de <?php echo esc_html( 'energia' === $tipo ? 'energia' : ( 'agua' === $tipo ? 'água' : 'água ou energia' ) ); ?> informada <?php echo esc_html( mural17_em_escopo( $escopo ) ); ?> agora.</p></div>
		<?php endif; ?>
		<p class="vazio" data-busca-vazio hidden>Nenhum aviso com esse bairro.</p>

		<?php if ( $lista['programadas'] ) : ?>
			<h2 class="secao__titulo">Programados</h2>
			<div class="faltas-prog">
				<?php
				foreach ( $lista['programadas'] as $p ) :
					$t    = $texto_falta( $p );
					$c    = mural17_cidade( $p->ID );
					$ini  = mural17_data( mural17_campo( $p->ID, 'int_inicio' ) );
					$prev = mural17_data( mural17_campo( $p->ID, 'int_previsao' ) );
					?>
					<article class="falta-prog falta-prog--<?php echo esc_attr( $t ); ?>" data-texto="<?php echo esc_attr( mural17_normaliza( mural17_campo( $p->ID, 'int_bairros' ) . ' ' . ( $c ? $c->name : '' ) ) ); ?>">
						<span class="falta-prog__quando"><?php echo esc_html( ( 'energia' === $t ? 'Energia' : 'Água' ) . ' · ' . mural17_dia( $ini, true ) ); ?></span>
						<b><?php echo esc_html( ( $c ? mural17_nome_curto( $c ) . ' · ' : '' ) . mural17_campo( $p->ID, 'int_bairros' ) ); ?></b>
						<span><?php echo esc_html( mural17_hora( $ini ) . ( $prev ? ' às ' . mural17_hora( $prev ) : '' ) . ( mural17_campo( $p->ID, 'int_motivo' ) ? ' · ' . mural17_campo( $p->ID, 'int_motivo' ) : '' ) ); ?></span>
						<small><?php echo esc_html( mural17_campo( $p->ID, 'int_fonte' ) ); ?></small>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $lista['normalizadas'] ) : ?>
			<section class="caixa">
				<h2 class="caixa__titulo">Já voltou</h2>
				<ul class="faltas-ok">
					<?php
					foreach ( $lista['normalizadas'] as $p ) :
						$c = mural17_cidade( $p->ID );
						$n = mural17_data( mural17_campo( $p->ID, 'int_normalizado' ) );
						?>
						<li data-texto="<?php echo esc_attr( mural17_normaliza( mural17_campo( $p->ID, 'int_bairros' ) . ' ' . ( $c ? $c->name : '' ) ) ); ?>"><b><?php echo esc_html( ( 'energia' === $texto_falta( $p ) ? 'Energia' : 'Água' ) . ( $c ? ' · ' . mural17_nome_curto( $c ) : '' ) ); ?></b> <?php echo esc_html( wp_trim_words( mural17_campo( $p->ID, 'int_bairros' ), 10, '…' ) ); ?> <small>normalizada às <?php echo esc_html( mural17_hora( $n ) ); ?></small></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</div>

	<aside class="sv-pagina__lateral">
		<section class="caixa caixa--escura">
			<h2 class="caixa__titulo">Agora no 17</h2>
			<div class="sv-dupla">
				<span class="sv-dupla__item sv-dupla__item--agua"><b><?php echo count( $sem['agua'] ); ?></b><?php echo 1 === count( $sem['agua'] ) ? 'cidade sem água' : 'cidades sem água'; ?></span>
				<span class="sv-dupla__item sv-dupla__item--energia"><b><?php echo count( $sem['energia'] ); ?></b><?php echo 1 === count( $sem['energia'] ) ? 'cidade sem luz' : 'cidades sem luz'; ?></span>
			</div>
		</section>
		<?php
		mural17_form_avisos(
			array(
				'temas'  => array( 'agua-luz' ),
				'titulo' => 'Avisar quando faltar e quando voltar',
				'texto'  => 'Mandamos um e-mail quando o serviço de água ou a concessionária avisar uma falta na sua cidade, e outro quando normalizar.',
				'cidade' => $escopo && $escopo->parent ? $escopo : null,
			)
		);
		?>
		<section class="caixa">
			<h2 class="caixa__titulo">Faltou aí e não está aqui?</h2>
			<p>Avise pelo Fala 17. Quando vários moradores do mesmo bairro avisam, a redação confirma com o serviço e publica.</p>
			<a class="botao botao--pilula" href="<?php echo esc_url( add_query_arg( array( 'cidade' => $escopo ? $escopo->slug : '', 'tipo' => 'agua-esgoto' ), mural17_servicos()['fala']['url'] ) . '#mandar' ); ?>">Avisar falta</a>
		</section>
		<section class="caixa caixa--oficial">
			<h2 class="caixa__titulo">Serviços de água e concessionárias</h2>
			<p>Postem pelo painel dos órgãos: escolha os bairros, o início e a previsão de volta. Atualizar o aviso leva um clique.</p>
			<a class="botao botao--pilula botao--oficial" href="<?php echo esc_url( mural17_link_aba( 'interrupcoes' ) ); ?>">Acessar painel</a>
		</section>
	</aside>
</div>
