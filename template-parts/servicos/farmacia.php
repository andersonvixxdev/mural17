<?php
/**
 * Farmácia de plantão: a de agora, os próximos dias e as vizinhas.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$cidade   = $args['cidade'];
$plantoes = mural17_plantoes( $cidade, 9 );
$agora    = $plantoes && mural17_plantao_agora( $plantoes[0] ) ? $plantoes[0] : null;
$proximos = $agora ? array_slice( $plantoes, 1, 7 ) : array_slice( $plantoes, 0, 7 );
$wa       = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), 'Olá, Mural 17! A farmácia de plantão de ' . ( $cidade ? $cidade->name : '' ) . ' está errada:' );
$semana   = array( 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb' );

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo' => 'Farmácia de plantão',
		'texto'  => 'A farmácia aberta fora do horário comercial em ' . mural17_nome_curto( $cidade ) . ', com telefone e endereço.',
		'cidade' => $cidade,
	)
);
?>
<div class="sv-pagina__grade">
	<div class="sv-pagina__principal">
		<?php if ( $agora ) : ?>
			<?php
			$ini = mural17_data( mural17_campo( $agora->ID, 'plantao_inicio' ) );
			$fim = mural17_data( mural17_campo( $agora->ID, 'plantao_fim' ) );
			$tel = mural17_campo( $agora->ID, 'plantao_telefone' );
			$zap = mural17_whatsapp_link( mural17_campo( $agora->ID, 'plantao_whatsapp' ) );
			$end = mural17_campo( $agora->ID, 'plantao_endereco' );
			?>
			<article class="plantao-agora">
				<p class="plantao-agora__cab"><span class="aovivo">De plantão agora</span> <?php echo esc_html( ucfirst( wp_date( 'l, j \d\e F', $ini->getTimestamp() ) ) . ' · das ' . mural17_hora( $ini ) . ' de ' . strtolower( mural17_dia( $ini ) ) . ' às ' . mural17_hora( $fim ) . ' de ' . strtolower( mural17_dia( $fim ) ) ); ?></p>
				<h2 class="plantao-agora__nome"><?php echo esc_html( get_the_title( $agora ) ); ?></h2>
				<p class="plantao-agora__end"><?php echo esc_html( trim( $end . ' · ' . $cidade->name, ' ·' ) ); ?></p>
				<div class="plantao-agora__acoes">
					<?php if ( mural17_tel( $tel ) ) : ?>
						<a class="botao botao--pilula" href="<?php echo esc_url( mural17_tel( $tel ) ); ?>">Ligar <?php echo esc_html( $tel ); ?></a>
					<?php endif; ?>
					<?php if ( $zap ) : ?>
						<a class="botao botao--pilula botao--wa" href="<?php echo esc_url( $zap ); ?>" target="_blank" rel="noopener">WhatsApp</a>
					<?php endif; ?>
					<a class="botao botao--pilula botao--contorno-escuro" href="<?php echo esc_url( mural17_mapa( $end . ', ' . get_the_title( $agora ), $cidade ) ); ?>" target="_blank" rel="noopener">Como chegar</a>
				</div>
				<p class="sv-pagina__fonte"><?php echo esc_html( ( mural17_campo( $agora->ID, 'plantao_fonte' ) ? 'Fonte: ' . mural17_campo( $agora->ID, 'plantao_fonte' ) . '. ' : '' ) . 'Confirme por telefone antes de sair de casa.' ); ?></p>
			</article>
		<?php else : ?>
			<article class="plantao-agora plantao-agora--vazio">
				<h2 class="plantao-agora__nome">Ainda não temos a escala de <?php echo esc_html( mural17_nome_curto( $cidade ) ); ?></h2>
				<p>Enquanto isso, veja o plantão das cidades vizinhas abaixo ou ligue para a prefeitura.</p>
				<p class="sv-pagina__fonte">Prefeitura de <?php echo esc_html( $cidade->name ); ?>: mande a escala do mês pelo <a href="<?php echo esc_url( mural17_link_aba( 'plantao' ) ); ?>">Painel do órgão</a>. O site troca a farmácia sozinho todo dia.</p>
			</article>
		<?php endif; ?>

		<?php if ( $proximos ) : ?>
			<section class="caixa">
				<h2 class="caixa__titulo">Próximos plantões</h2>
				<ol class="plantao-dias">
					<?php
					foreach ( $proximos as $p ) :
						$d = mural17_data( mural17_campo( $p->ID, 'plantao_inicio' ) );
						?>
						<li>
							<span class="plantao-dias__dia"><b><?php echo esc_html( $d->format( 'j' ) ); ?></b><?php echo esc_html( $semana[ (int) $d->format( 'w' ) ] ); ?></span>
							<span class="plantao-dias__nome"><?php echo esc_html( get_the_title( $p ) ); ?><small><?php echo esc_html( mural17_campo( $p->ID, 'plantao_endereco' ) ); ?></small></span>
							<?php if ( mural17_tel( mural17_campo( $p->ID, 'plantao_telefone' ) ) ) : ?>
								<a href="<?php echo esc_url( mural17_tel( mural17_campo( $p->ID, 'plantao_telefone' ) ) ); ?>"><?php echo esc_html( mural17_campo( $p->ID, 'plantao_telefone' ) ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>

		<?php
		$vizinhas = array();
		foreach ( mural17_vizinhas( $cidade, 12 ) as $v ) {
			$pv = mural17_plantoes( $v['cidade'], 1 );
			if ( $pv && mural17_plantao_agora( $pv[0] ) ) {
				$vizinhas[] = array( $v['cidade'], $pv[0], $v['km'] );
			}
			if ( count( $vizinhas ) >= 4 ) {
				break;
			}
		}
		?>
		<?php if ( $vizinhas ) : ?>
			<section class="caixa">
				<h2 class="caixa__titulo">Plantão agora nas vizinhas</h2>
				<ul class="plantao-vizinhas">
					<?php foreach ( $vizinhas as $v ) : ?>
						<li>
							<a href="<?php echo esc_url( mural17_link_servico( 'farmacia', $v[0] ) ); ?>"><b><?php echo esc_html( mural17_nome_curto( $v[0] ) ); ?></b> <small><?php echo esc_html( number_format_i18n( $v[2] ) ); ?> km</small></a>
							<span><?php echo esc_html( get_the_title( $v[1] ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</div>

	<aside class="sv-pagina__lateral">
		<section class="caixa">
			<h2 class="caixa__titulo">Como a escala chega aqui</h2>
			<p>A prefeitura (ou a redação) cadastra a escala do mês de uma vez. O site troca a farmácia sozinho no horário de cada plantão.</p>
		</section>
		<?php if ( $wa ) : ?>
			<section class="caixa">
				<h2 class="caixa__titulo">Dado errado?</h2>
				<p>Se a farmácia de plantão mudou, avise a redação.</p>
				<a class="botao botao--pilula botao--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Avisar pelo WhatsApp</a>
			</section>
		<?php endif; ?>
		<?php mural17_anuncio( 'anuncio-lado', 'anuncio--300' ); ?>
	</aside>
</div>
