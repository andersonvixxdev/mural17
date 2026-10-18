<?php
/**
 * Empregos no 17: vagas anunciadas no portal, lista do PAT da cidade e vagas
 * de outros sites, com busca, cidade e filtros.
 *
 * @package Mural17
 */

get_header();

$mural17_escopo = mural17_escopo_empregos();
$mural17_busca  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$mural17_tipo   = isset( $_GET['tipo'] ) ? sanitize_text_field( wp_unslash( $_GET['tipo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$mural17_fonte  = isset( $_GET['fonte'] ) ? sanitize_key( wp_unslash( $_GET['fonte'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$mural17_tipos  = array( 'CLT', 'Temporário', 'Estágio', 'Jovem aprendiz', 'PJ' );
$mural17_tipo   = in_array( $mural17_tipo, $mural17_tipos, true ) ? $mural17_tipo : '';
$mural17_fonte  = in_array( $mural17_fonte, array( 'mural', 'pat', 'sites' ), true ) ? $mural17_fonte : '';
$mural17_base   = get_post_type_archive_link( 'vaga' );
$mural17_url    = function ( $mudar ) use ( $mural17_base, $mural17_escopo, $mural17_busca, $mural17_tipo, $mural17_fonte ) {
	$a = array_merge(
		array(
			'cidade' => $mural17_escopo ? $mural17_escopo->slug : '0',
			'q'      => $mural17_busca,
			'tipo'   => $mural17_tipo,
			'fonte'  => $mural17_fonte,
		),
		$mudar
	);
	return add_query_arg( array_filter( $a, 'strlen' ), $mural17_base );
};
$mural17_contagem = mural17_contagem_vagas();
$mural17_no_17    = array_sum( $mural17_contagem );
$mural17_aqui     = 0;
if ( $mural17_escopo ) {
	foreach ( mural17_ids_escopo( $mural17_escopo ) as $mural17_id ) {
		$mural17_aqui += $mural17_contagem[ $mural17_id ] ?? 0;
	}
}
$mural17_listas = get_posts(
	array(
		'post_type'      => 'listapat',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'date_query'     => array( array( 'after' => '4 days ago' ) ),
	)
);
$mural17_pats = array();
foreach ( $mural17_listas as $mural17_l ) {
	$mural17_c = mural17_cidade( $mural17_l->ID );
	if ( $mural17_c && ! isset( $mural17_pats[ $mural17_c->term_id ] ) ) {
		$mural17_pats[ $mural17_c->term_id ] = $mural17_c;
	}
}
$mural17_wa   = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), 'Olá, Mural 17! Quero anunciar uma vaga:' );
$mural17_onde = $mural17_escopo ? mural17_nome_curto( $mural17_escopo ) : '';
?>
<div class="emp" data-servico-cidade="<?php echo esc_attr( $mural17_escopo && ! mural17_e_regiao( $mural17_escopo ) ? $mural17_escopo->term_id : '' ); ?>" data-servico-auto="<?php echo mural17_cidade_na_url() ? '0' : '1'; ?>">
	<section class="emp-hero">
		<h1 class="emp-hero__titulo">Empregos <?php echo $mural17_escopo ? 'em <em>' . esc_html( $mural17_onde ) . '</em>' : 'no <em>17</em>'; ?></h1>
		<p class="emp-hero__texto">Vagas das empresas da região, dos PATs das prefeituras e dos principais sites, num lugar só.</p>
		<form class="emp-busca" method="get" action="<?php echo esc_url( $mural17_base ); ?>" role="search">
			<label class="screen-reader-text" for="emp-q">Cargo ou empresa</label>
			<input id="emp-q" type="search" name="q" value="<?php echo esc_attr( $mural17_busca ); ?>" placeholder="Cargo ou empresa (ex.: auxiliar de produção, motorista)">
			<label class="screen-reader-text" for="emp-cidade">Cidade</label>
			<select id="emp-cidade" name="cidade">
				<option value="0">Todo o 17</option>
				<?php foreach ( mural17_lista_cidades() as $mural17_g ) : ?>
					<optgroup label="<?php echo esc_attr( $mural17_g['regiao']->name ); ?>">
						<option value="<?php echo esc_attr( $mural17_g['regiao']->slug ); ?>" <?php selected( $mural17_escopo && $mural17_escopo->term_id === $mural17_g['regiao']->term_id ); ?>><?php echo esc_html( 'Toda a ' . $mural17_g['regiao']->name ); ?></option>
						<?php foreach ( $mural17_g['cidades'] as $mural17_c ) : ?>
							<option value="<?php echo esc_attr( $mural17_c->slug ); ?>" <?php selected( $mural17_escopo && $mural17_escopo->term_id === $mural17_c->term_id ); ?>><?php echo esc_html( $mural17_c->name ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
			<?php if ( $mural17_tipo ) : ?>
				<input type="hidden" name="tipo" value="<?php echo esc_attr( $mural17_tipo ); ?>">
			<?php endif; ?>
			<button type="submit">Buscar</button>
		</form>
		<div class="emp-nums">
			<p><b><?php echo esc_html( number_format_i18n( $mural17_no_17 ) ); ?></b>vagas abertas no 17</p>
			<?php if ( $mural17_escopo ) : ?>
				<p><b><?php echo esc_html( number_format_i18n( $mural17_aqui ) ); ?></b>em <?php echo esc_html( $mural17_onde ); ?></p>
			<?php endif; ?>
			<p><b><?php echo count( $mural17_pats ); ?></b>PATs com lista recente</p>
		</div>
	</section>

	<nav class="emp-chips" aria-label="Filtros">
		<a class="<?php echo ! $mural17_fonte && ! $mural17_tipo ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'fonte' => '', 'tipo' => '' ) ) ); ?>">Todas</a>
		<a class="<?php echo 'mural' === $mural17_fonte ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'fonte' => 'mural' ) ) ); ?>">Anunciadas no Mural 17</a>
		<a class="<?php echo 'pat' === $mural17_fonte ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'fonte' => 'pat' ) ) ); ?>">PAT da prefeitura</a>
		<a class="<?php echo 'sites' === $mural17_fonte ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'fonte' => 'sites' ) ) ); ?>">Outros sites</a>
		<span class="emp-chips__sep" aria-hidden="true"></span>
		<?php foreach ( $mural17_tipos as $mural17_t ) : ?>
			<a class="<?php echo $mural17_tipo === $mural17_t ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'tipo' => $mural17_tipo === $mural17_t ? '' : $mural17_t ) ) ); ?>"><?php echo esc_html( $mural17_t ); ?></a>
		<?php endforeach; ?>
	</nav>

	<div class="emp-grade">
		<div class="emp-principal">
			<?php if ( ! $mural17_fonte || 'mural' === $mural17_fonte ) : ?>
				<?php $mural17_q = mural17_vagas_portal( $mural17_escopo, $mural17_busca, $mural17_tipo, 'mural' === $mural17_fonte ? -1 : 6 ); ?>
				<section class="emp-caixa">
					<div class="emp-caixa__cab">
						<h2>Anunciadas no Mural 17</h2>
						<?php if ( ! $mural17_fonte && $mural17_q->found_posts > 6 ) : ?>
							<a href="<?php echo esc_url( $mural17_url( array( 'fonte' => 'mural' ) ) ); ?>">Todas (<?php echo (int) $mural17_q->found_posts; ?>) →</a>
						<?php endif; ?>
					</div>
					<?php if ( $mural17_q->have_posts() ) : ?>
						<div class="emp-destaques">
							<?php
							while ( $mural17_q->have_posts() ) :
								$mural17_q->the_post();
								get_template_part( 'template-parts/card-vaga', null, array( 'destaque' => true ) );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					<?php else : ?>
						<p class="vazio">Nenhuma vaga anunciada <?php echo $mural17_escopo ? 'em ' . esc_html( $mural17_onde ) : 'no portal'; ?><?php echo $mural17_busca || $mural17_tipo ? ' com esse filtro' : ''; ?> agora. <?php echo $mural17_wa ? '<a href="' . esc_url( $mural17_wa ) . '" target="_blank" rel="noopener">Anuncie a sua, grátis</a>.' : ''; ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ( ! $mural17_fonte || 'pat' === $mural17_fonte ) && ! $mural17_tipo ) : ?>
				<?php
				$mural17_pat_cid = $mural17_escopo && ! mural17_e_regiao( $mural17_escopo ) ? $mural17_escopo : null;
				$mural17_pat     = $mural17_pat_cid ? mural17_lista_pat( $mural17_pat_cid ) : null;
				?>
				<?php if ( $mural17_pat ) : ?>
					<?php
					$mural17_info   = mural17_info_pat( $mural17_pat_cid );
					$mural17_itens  = $mural17_pat['itens'];
					$mural17_balcao = MURAL17_BALCAO_FONTE === $mural17_pat['fonte'];
					if ( $mural17_balcao && ! $mural17_info['link'] ) {
						$mural17_info['link'] = mural17_balcao_url();
					}
					if ( $mural17_busca ) {
						$mural17_itens = array_values(
							array_filter(
								$mural17_itens,
								function ( $i ) use ( $mural17_busca ) {
									return false !== strpos( mural17_normaliza( $i['cargo'] ), mural17_normaliza( $mural17_busca ) );
								}
							)
						);
					}
					?>
					<section class="emp-caixa">
						<div class="emp-caixa__cab">
							<h2><?php echo esc_html( $mural17_balcao ? 'Balcão de Empregos' . ( 'rio-preto' === $mural17_pat_cid->slug ? ' de Rio Preto' : ' · vagas em ' . $mural17_onde ) : 'PAT de ' . $mural17_onde ); ?> · <?php echo esc_html( strtolower( mural17_dia( mural17_data( wp_date( 'Y-m-d H:i', $mural17_pat['quando'] ) ) ) ) ); ?></h2>
							<?php if ( $mural17_info['link'] ) : ?>
								<a href="<?php echo esc_url( $mural17_info['link'] ); ?>" target="_blank" rel="noopener">Lista oficial →</a>
							<?php endif; ?>
						</div>
						<p class="emp-pat__info"><b>Atualizada <?php echo esc_html( mb_strtolower( mural17_quando_servico( wp_date( 'Y-m-d H:i', $mural17_pat['quando'] ) ) ) ); ?></b><?php echo esc_html( implode( '', array_map( function ( $x ) { return ' · ' . $x; }, array_filter( array( $mural17_info['endereco'], $mural17_info['horario'], $mural17_info['documentos'] ? 'leve ' . $mural17_info['documentos'] : '', $mural17_info['telefone'] ) ) ) ) ); ?></p>
						<?php if ( $mural17_itens ) : ?>
							<div class="tabela-rolagem">
								<table class="emp-pat">
									<thead><tr><th scope="col">Vagas</th><th scope="col">Cargo</th><th scope="col"><?php echo $mural17_balcao ? 'Detalhes' : 'Requisito'; ?></th></tr></thead>
									<tbody>
										<?php foreach ( $mural17_itens as $mural17_n => $mural17_i ) : ?>
											<tr<?php echo $mural17_n >= 40 ? ' hidden data-pat-mais' : ''; ?>><td class="emp-pat__n"><?php echo $mural17_i['qtd'] ? (int) $mural17_i['qtd'] : '—'; ?></td><th scope="row"><?php echo esc_html( $mural17_i['cargo'] ); ?></th><td><?php echo esc_html( $mural17_i['req'] ); ?></td></tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<?php if ( count( $mural17_itens ) > 40 ) : ?>
								<button type="button" class="botao botao--pilula botao--contorno-escuro emp-mais" onclick="document.querySelectorAll('[data-pat-mais]').forEach(function(e){e.hidden=false});this.remove();">Ver mais <?php echo (int) ( count( $mural17_itens ) - 40 ); ?> cargos</button>
							<?php endif; ?>
						<?php else : ?>
							<p class="vazio">Nenhum cargo da lista de hoje com “<?php echo esc_html( $mural17_busca ); ?>”.</p>
						<?php endif; ?>
						<?php if ( $mural17_balcao ) : ?>
							<p class="emp-fonte">Lista lida automaticamente do Balcão de Empregos da Prefeitura de Rio Preto. Para se candidatar, faça o cadastro e envie o currículo pela <a href="<?php echo esc_url( mural17_balcao_url() ); ?>" target="_blank" rel="noopener">página oficial</a>.</p>
						<?php else : ?>
							<p class="emp-fonte">As vagas do PAT são preenchidas no próprio posto, por ordem de chegada. Confirme antes de ir.</p>
						<?php endif; ?>
					</section>
				<?php elseif ( $mural17_pats && ( ! $mural17_pat_cid || 'pat' === $mural17_fonte ) ) : ?>
					<section class="emp-caixa">
						<div class="emp-caixa__cab"><h2>Listas dos PATs</h2></div>
						<?php if ( $mural17_pat_cid ) : ?>
							<p class="vazio">O PAT de <?php echo esc_html( $mural17_onde ); ?> não publicou lista nos últimos dias. Veja as cidades vizinhas:</p>
						<?php endif; ?>
						<ul class="emp-pats">
							<?php foreach ( $mural17_pats as $mural17_c ) : ?>
								<?php
								if ( $mural17_escopo && mural17_e_regiao( $mural17_escopo ) && (int) $mural17_c->parent !== (int) $mural17_escopo->term_id ) {
									continue;
								}
								$mural17_l = mural17_lista_pat( $mural17_c );
								?>
								<li><a href="<?php echo esc_url( $mural17_url( array( 'cidade' => $mural17_c->slug ) ) ); ?>"><b><?php echo esc_html( $mural17_c->name ); ?></b><span><?php echo esc_html( ( $mural17_l['total'] ? $mural17_l['total'] . ' vagas · ' : '' ) . implode( ', ', array_slice( wp_list_pluck( $mural17_l['itens'], 'cargo' ), 0, 3 ) ) ); ?></span></a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( ! $mural17_fonte || 'sites' === $mural17_fonte ) : ?>
				<?php $mural17_ext = mural17_vagas_externas( $mural17_escopo, $mural17_busca, $mural17_tipo ); ?>
				<?php if ( $mural17_ext || 'sites' === $mural17_fonte ) : ?>
					<section class="emp-caixa">
						<div class="emp-caixa__cab"><h2>Mais vagas na região</h2><span class="emp-caixa__qtd"><?php echo esc_html( sprintf( _n( '%s vaga', '%s vagas', count( $mural17_ext ), 'mural17' ), number_format_i18n( count( $mural17_ext ) ) ) ); ?></span></div>
						<p class="emp-caixa__sub">De sites de emprego. O link leva para a vaga no site original.</p>
						<?php if ( $mural17_ext ) : ?>
							<ul class="emp-ext">
								<?php foreach ( $mural17_ext as $mural17_n => $mural17_v ) : ?>
									<?php $mural17_vc = get_term( (int) $mural17_v['cidade'], 'cidade' ); ?>
									<li<?php echo $mural17_n >= 25 ? ' hidden data-emp-mais' : ''; ?>>
										<a href="<?php echo esc_url( $mural17_v['link'] ); ?>" target="_blank" rel="noopener nofollow">
											<span class="emp-ext__t"><?php echo esc_html( $mural17_v['titulo'] ); ?></span>
											<span class="emp-ext__e"><?php echo esc_html( implode( ' · ', array_filter( array( $mural17_vc && ! is_wp_error( $mural17_vc ) ? $mural17_vc->name : '', $mural17_v['empresa'], $mural17_v['tipo'], $mural17_v['salario'] ) ) ) ); ?></span>
											<span class="emp-ext__via"><b>via Jooble</b><?php echo esc_html( strtolower( mural17_dia( mural17_data( wp_date( 'Y-m-d H:i', $mural17_v['data'] ) ) ) ) ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php if ( count( $mural17_ext ) > 25 ) : ?>
								<button type="button" class="botao botao--pilula botao--contorno-escuro emp-mais" onclick="document.querySelectorAll('[data-emp-mais]').forEach(function(e){e.hidden=false});this.remove();">Ver mais <?php echo (int) ( count( $mural17_ext ) - 25 ); ?> vagas</button>
							<?php endif; ?>
						<?php else : ?>
							<p class="vazio">Nenhuma vaga de outros sites <?php echo $mural17_escopo ? 'em ' . esc_html( $mural17_onde ) : 'agora'; ?><?php echo $mural17_busca ? ' com “' . esc_html( $mural17_busca ) . '”' : ''; ?>.</p>
						<?php endif; ?>
					</section>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<aside class="emp-lado">
			<div class="emp-cta">
				<h2>Tem vaga na sua empresa?</h2>
				<p>Anuncie grátis no Mural 17. A vaga sai na sua cidade e nas redes do portal.</p>
				<?php if ( $mural17_wa ) : ?>
					<a class="botao botao--pilula" href="<?php echo esc_url( $mural17_wa ); ?>" target="_blank" rel="noopener">Anunciar vaga pelo WhatsApp</a>
				<?php endif; ?>
			</div>
			<?php if ( $mural17_contagem ) : ?>
				<section class="emp-caixa emp-cidades">
					<h2>Vagas por cidade</h2>
					<ul>
						<?php foreach ( array_slice( $mural17_contagem, 0, 10, true ) as $mural17_id => $mural17_n ) : ?>
							<?php $mural17_c = get_term( (int) $mural17_id, 'cidade' ); ?>
							<?php if ( $mural17_c && ! is_wp_error( $mural17_c ) ) : ?>
								<li><a href="<?php echo esc_url( $mural17_url( array( 'cidade' => $mural17_c->slug ) ) ); ?>"><?php echo esc_html( $mural17_c->name ); ?></a><b><?php echo esc_html( number_format_i18n( $mural17_n ) ); ?></b></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
			<?php
			mural17_form_avisos(
				array(
					'temas'  => array( 'vagas' ),
					'titulo' => 'Receba as vagas da sua cidade',
					'texto'  => 'Um e-mail por dia com as vagas novas, só quando houver. Cancele com um clique.',
					'botao'  => 'Quero receber',
					'escuro' => false,
					'cidade' => $mural17_escopo,
				)
			);
			?>
		</aside>
	</div>
</div>
<?php
get_footer();
