<?php
/**
 * Faixa "Serviços de hoje em X": farmácia de plantão, gasolina, água e luz,
 * falecimentos, concursos e Fala 17 da cidade (e da região dela).
 *
 * Args: cidade (WP_Term), titulo (bool, mostra o título; padrão true).
 *
 * @package Mural17
 */

$cidade = isset( $args['cidade'] ) ? $args['cidade'] : null;
if ( ! $cidade ) {
	return;
}
$regiao   = $cidade->parent ? get_term( $cidade->parent, 'cidade' ) : null;
$plantoes = mural17_plantoes( $cidade, 2 );
$plantao  = $plantoes && mural17_plantao_agora( $plantoes[0] ) ? $plantoes[0] : null;
$comb     = mural17_combustivel_referencia( $cidade );
$faltas   = mural17_interrupcoes( $cidade, 10 );
$fals     = mural17_falecimentos( $regiao, 0, 1 );
$concs    = mural17_concursos_abertos( $regiao, array( 'posts_per_page' => 1 ) );
$falas    = mural17_falas_respondidas_semana( $cidade );
$curto    = mural17_nome_curto( $cidade );
?>
<section class="faixa-servicos" data-faixa-servicos="<?php echo (int) $cidade->term_id; ?>" aria-labelledby="faixa-servicos-titulo">
	<div class="faixa-servicos__topo">
		<h2 class="faixa-servicos__titulo" id="faixa-servicos-titulo">Serviços de hoje em <span><?php echo esc_html( $curto ); ?></span></h2>
		<a class="secao__link" href="<?php echo esc_url( mural17_link_servico( 'guia', $cidade ) ); ?>">Guia da cidade →</a>
	</div>
	<div class="faixa-servicos__grade">
		<article class="sv-tile sv-tile--farmacia">
			<p class="sv-tile__cab"><a href="<?php echo esc_url( mural17_link_servico( 'farmacia', $cidade ) ); ?>">Farmácia de plantão</a><?php echo $plantao ? '<span class="sv-tile__agora">Aberta agora</span>' : ''; ?></p>
			<?php if ( $plantao ) : ?>
				<b class="sv-tile__grande"><?php echo esc_html( get_the_title( $plantao ) ); ?></b>
				<span class="sv-tile__linha"><?php echo esc_html( trim( mural17_campo( $plantao->ID, 'plantao_endereco' ) . ' · até ' . mural17_hora( mural17_data( mural17_campo( $plantao->ID, 'plantao_fim' ) ) ), ' ·' ) ); ?></span>
				<span class="sv-tile__acoes">
					<?php if ( mural17_tel( mural17_campo( $plantao->ID, 'plantao_telefone' ) ) ) : ?>
						<a class="botao botao--pilula botao--mini" href="<?php echo esc_url( mural17_tel( mural17_campo( $plantao->ID, 'plantao_telefone' ) ) ); ?>">Ligar</a>
					<?php endif; ?>
					<a class="botao botao--pilula botao--mini botao--contorno-escuro" href="<?php echo esc_url( mural17_mapa( mural17_campo( $plantao->ID, 'plantao_endereco' ) . ', ' . get_the_title( $plantao ), $cidade ) ); ?>" target="_blank" rel="noopener">Como chegar</a>
				</span>
			<?php else : ?>
				<span class="sv-tile__linha">Ainda sem a escala de <?php echo esc_html( $curto ); ?>.</span>
				<a class="sv-tile__mais" href="<?php echo esc_url( mural17_link_servico( 'farmacia', $cidade ) ); ?>">Ver as cidades vizinhas</a>
			<?php endif; ?>
		</article>

		<article class="sv-tile sv-tile--gasolina">
			<p class="sv-tile__cab"><a href="<?php echo esc_url( mural17_link_servico( 'combustivel', $cidade ) ); ?>">Gasolina</a><span class="sv-tile__fonte">ANP · semana</span></p>
			<?php if ( $comb && isset( $comb['precos']['gasolina'] ) ) : ?>
				<?php
				$g   = $comb['precos']['gasolina'];
				$var = mural17_variacao_combustivel( $comb['cidade']->term_id, 'gasolina' );
				?>
				<b class="sv-tile__grande"><?php echo esc_html( mural17_preco( $g['media'] ) ); ?> <?php if ( $var ) : ?><small class="variacao variacao--<?php echo esc_attr( $var[0] ); ?>"><?php echo esc_html( $var[1] ); ?></small><?php endif; ?></b>
				<span class="sv-tile__linha">média em <?php echo esc_html( mural17_nome_curto( $comb['cidade'] ) ); ?> · mais barata <?php echo esc_html( mural17_preco( $g['min'] ) ); ?></span>
				<span class="sv-tile__linha sv-tile__linha--fraca">
					<?php
					$outros = array();
					if ( isset( $comb['precos']['etanol'] ) ) {
						$outros[] = 'Etanol ' . mural17_preco( $comb['precos']['etanol']['media'] );
					}
					if ( isset( $comb['precos']['diesel'] ) ) {
						$outros[] = 'Diesel ' . mural17_preco( $comb['precos']['diesel']['media'] );
					}
					echo esc_html( implode( ' · ', $outros ) );
					?>
				</span>
			<?php else : ?>
				<span class="sv-tile__linha">A pesquisa da ANP ainda não foi importada.</span>
			<?php endif; ?>
		</article>

		<article class="sv-tile sv-tile--agua">
			<p class="sv-tile__cab"><a href="<?php echo esc_url( mural17_link_servico( 'interrupcoes', $cidade ) ); ?>">Água e luz</a><?php echo $faltas['agora'] ? '<span class="sv-tile__agora sv-tile__agora--alerta">' . count( $faltas['agora'] ) . ' agora</span>' : ''; ?></p>
			<?php if ( $faltas['agora'] ) : ?>
				<?php $f = $faltas['agora'][0]; ?>
				<b class="sv-tile__medio"><?php echo esc_html( ( 'energia' === mural17_campo( $f->ID, 'int_tipo' ) ? 'Sem energia' : 'Sem água' ) . ': ' . wp_trim_words( mural17_campo( $f->ID, 'int_bairros' ), 6, '…' ) ); ?><?php
				$prev = mural17_data( mural17_campo( $f->ID, 'int_previsao' ) );
				echo $prev ? esc_html( ' até ' . ( 'Hoje' === mural17_dia( $prev ) ? '' : strtolower( mural17_dia( $prev ) ) . ', ' ) . mural17_hora( $prev ) ) : '';
				?></b>
				<span class="sv-tile__linha sv-tile__linha--fraca"><?php echo esc_html( trim( mural17_campo( $f->ID, 'int_motivo' ) . ' · fonte: ' . mural17_campo( $f->ID, 'int_fonte' ), ' ·' ) ); ?></span>
			<?php else : ?>
				<b class="sv-tile__medio">Nenhuma falta informada agora</b>
			<?php endif; ?>
			<?php if ( $faltas['programadas'] ) : ?>
				<?php $pg = $faltas['programadas'][0]; ?>
				<span class="sv-tile__linha"><?php echo esc_html( mural17_dia( mural17_data( mural17_campo( $pg->ID, 'int_inicio' ) ), true ) . ': desligamento de ' . ( 'energia' === mural17_campo( $pg->ID, 'int_tipo' ) ? 'energia' : 'água' ) . ' programado' ); ?></span>
			<?php endif; ?>
		</article>

		<article class="sv-tile sv-tile--falecimentos">
			<p class="sv-tile__cab"><a href="<?php echo esc_url( add_query_arg( 'cidade', $regiao ? $regiao->slug : '', get_post_type_archive_link( 'falecimento' ) ) ); ?>">Falecimentos</a><span class="sv-tile__fonte">Hoje</span></p>
			<?php if ( $fals->found_posts ) : ?>
				<?php
				$fp  = $fals->posts[0];
				$fc  = mural17_cidade( $fp->ID );
				$vel = mural17_data( mural17_campo( $fp->ID, 'fal_velorio_fim' ) );
				?>
				<b class="sv-tile__medio"><?php echo esc_html( sprintf( _n( '%s nota na região', '%s notas na região', $fals->found_posts, 'mural17' ), number_format_i18n( $fals->found_posts ) ) ); ?></b>
				<span class="sv-tile__linha"><?php echo esc_html( get_the_title( $fp ) . ( mural17_campo( $fp->ID, 'fal_idade' ) ? ', ' . mural17_campo( $fp->ID, 'fal_idade' ) . ' anos' : '' ) . ( $vel ? ' · velório até ' . mural17_hora( $vel ) : '' ) . ( $fc ? ' · ' . mural17_nome_curto( $fc ) : '' ) ); ?></span>
			<?php else : ?>
				<span class="sv-tile__linha">Nenhuma nota hoje na região.</span>
			<?php endif; ?>
		</article>

		<article class="sv-tile sv-tile--concursos">
			<p class="sv-tile__cab"><a href="<?php echo esc_url( add_query_arg( 'cidade', $regiao ? $regiao->slug : '', get_post_type_archive_link( 'concurso' ) ) ); ?>">Concursos</a><span class="sv-tile__fonte">Região</span></p>
			<b class="sv-tile__medio"><?php echo esc_html( sprintf( _n( '%s com inscrição aberta', '%s com inscrição aberta', $concs->found_posts, 'mural17' ), number_format_i18n( $concs->found_posts ) ) ); ?></b>
			<?php if ( $concs->posts ) : ?>
				<?php
				$cp    = $concs->posts[0];
				$prazo = mural17_prazo_concurso( $cp->ID );
				?>
				<span class="sv-tile__linha"><?php echo esc_html( $prazo[1] . ': ' . mural17_campo( $cp->ID, 'conc_orgao' ) . ( mural17_campo( $cp->ID, 'conc_salario' ) ? ', até ' . mural17_reais( mural17_campo( $cp->ID, 'conc_salario' ) ) : '' ) ); ?></span>
			<?php endif; ?>
		</article>

		<article class="sv-tile sv-tile--fala">
			<p class="sv-tile__cab"><a href="<?php echo esc_url( mural17_link_servico( 'fala', $cidade ) ); ?>">Fala, 17</a><span class="sv-tile__fonte">Reclamações</span></p>
			<b class="sv-tile__medio"><?php echo esc_html( $falas ? sprintf( _n( '%s respondida esta semana', '%s respondidas esta semana', $falas, 'mural17' ), number_format_i18n( $falas ) ) : 'Viu um problema no seu bairro?' ); ?></b>
			<a class="sv-tile__mais" href="<?php echo esc_url( mural17_link_servico( 'fala', $cidade ) . '#mandar' ); ?>">Mande aqui, com foto</a>
		</article>
	</div>
</section>
