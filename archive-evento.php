<?php
/**
 * Agenda do 17: o que fazer hoje, no fim de semana, na semana ou no mês,
 * por cidade, categoria e entrada gratuita.
 *
 * @package Mural17
 */

get_header();

$mural17_escopo = mural17_escopo_empregos();
$mural17_quando = isset( $_GET['quando'] ) ? sanitize_text_field( wp_unslash( $_GET['quando'] ) ) : 'semana'; // phpcs:ignore WordPress.Security.NonceVerification
$mural17_quando = preg_match( '/^(hoje|fds|semana|mes|\d{4}-\d{2}-\d{2})$/', $mural17_quando ) ? $mural17_quando : 'semana';
$mural17_cat    = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$mural17_cats   = mural17_categorias_evento();
$mural17_cat    = isset( $mural17_cats[ $mural17_cat ] ) ? $mural17_cat : '';
$mural17_gratis = ! empty( $_GET['gratis'] ); // phpcs:ignore WordPress.Security.NonceVerification
$mural17_base   = get_post_type_archive_link( 'evento' );
$mural17_url    = function ( $mudar ) use ( $mural17_base, $mural17_escopo, $mural17_quando, $mural17_cat, $mural17_gratis ) {
	$a = array_merge(
		array(
			'cidade' => $mural17_escopo ? $mural17_escopo->slug : '0',
			'quando' => 'semana' === $mural17_quando ? '' : $mural17_quando,
			'cat'    => $mural17_cat,
			'gratis' => $mural17_gratis ? '1' : '',
		),
		$mudar
	);
	return add_query_arg( array_filter( $a, 'strlen' ), $mural17_base );
};
list( $mural17_ini, $mural17_fim ) = mural17_intervalo_agenda( $mural17_quando );
$mural17_itens = mural17_eventos_entre( $mural17_ini, $mural17_fim, $mural17_escopo, $mural17_cat, $mural17_gratis );
list( $mural17_sab, $mural17_dom ) = mural17_intervalo_agenda( 'fds' );
$mural17_tz    = wp_timezone();
$mural17_hoje  = new DateTimeImmutable( 'today', $mural17_tz );
$mural17_onde  = $mural17_escopo ? mural17_nome_curto( $mural17_escopo ) : '';
$mural17_wa    = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), 'Olá, Mural 17! Quero divulgar um evento:' );

// Destaques do fim de semana: com foto primeiro.
$mural17_dest = array();
if ( in_array( $mural17_quando, array( 'semana', 'fds' ), true ) ) {
	$mural17_fds = mural17_eventos_entre( $mural17_sab, $mural17_dom, $mural17_escopo, $mural17_cat, $mural17_gratis );
	usort(
		$mural17_fds,
		function ( $a, $b ) {
			return (int) ! $a['img'] <=> (int) ! $b['img'];
		}
	);
	$mural17_dest = array_slice( $mural17_fds, 0, 3 );
}
// Evento de mais de 4 dias (exposição, mostra, temporada) vai para "Em cartaz"
// e só aparece no dia a dia no dia em que começa.
$mural17_longo  = function ( $e ) {
	return strtotime( $e['fim'] . ' 12:00' ) - strtotime( $e['data'] . ' 12:00' ) > 4 * DAY_IN_SECONDS;
};
$mural17_cartaz = array_values(
	array_filter(
		$mural17_itens,
		function ( $e ) use ( $mural17_longo, $mural17_ini ) {
			return $mural17_longo( $e ) && $e['data'] <= $mural17_ini;
		}
	)
);
$mural17_hora = function ( $h ) {
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', (string) $h, $m ) ) {
		return '';
	}
	return (int) $m[1] . 'h' . ( '00' === $m[2] ? '' : $m[2] );
};
?>
<div class="ag" data-servico-cidade="<?php echo esc_attr( $mural17_escopo && ! mural17_e_regiao( $mural17_escopo ) ? $mural17_escopo->term_id : '' ); ?>" data-servico-auto="<?php echo mural17_cidade_na_url() ? '0' : '1'; ?>">
	<section class="ag-hero">
		<h1 class="ag-hero__titulo">O que fazer <?php echo $mural17_escopo ? 'em <em>' . esc_html( $mural17_onde ) . '</em>' : 'no <em>17</em>'; ?></h1>
		<p class="ag-hero__texto">Shows, festas, teatro, feiras e esporte nas 117 cidades. Da prefeitura, dos produtores e de quem vende ingresso.</p>
		<nav class="ag-dias" aria-label="Dias">
			<?php for ( $mural17_i = 0; $mural17_i < 7; $mural17_i++ ) : ?>
				<?php
				$mural17_d   = $mural17_hoje->modify( '+' . $mural17_i . ' days' );
				$mural17_ymd = $mural17_d->format( 'Y-m-d' );
				$mural17_on  = $mural17_quando === $mural17_ymd || ( 0 === $mural17_i && 'hoje' === $mural17_quando );
				$mural17_fs  = in_array( (int) $mural17_d->format( 'N' ), array( 6, 7 ), true );
				?>
				<a class="<?php echo esc_attr( trim( ( $mural17_on ? 'is-on ' : '' ) . ( $mural17_fs ? 'is-fds' : '' ) ) ); ?>" href="<?php echo esc_url( $mural17_url( array( 'quando' => 0 === $mural17_i ? 'hoje' : $mural17_ymd ) ) ); ?>">
					<span><?php echo esc_html( 0 === $mural17_i ? 'Hoje' : ucfirst( wp_date( 'D', $mural17_d->getTimestamp() ) ) ); ?></span>
					<b><?php echo esc_html( $mural17_d->format( 'j' ) ); ?></b>
					<span><?php echo esc_html( $mural17_fs ? 'fim de semana' : wp_date( 'l', $mural17_d->getTimestamp() ) ); ?></span>
				</a>
			<?php endfor; ?>
			<a class="<?php echo 'fds' === $mural17_quando ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'quando' => 'fds' ) ) ); ?>"><span>Só o</span><b>FDS</b><span>sáb e dom</span></a>
			<a class="<?php echo 'mes' === $mural17_quando ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'quando' => 'mes' ) ) ); ?>"><span>Próximos</span><b>30</b><span>dias</span></a>
		</nav>
	</section>

	<div class="ag-chips">
		<a class="<?php echo ! $mural17_cat && ! $mural17_gratis ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'cat' => '', 'gratis' => '' ) ) ); ?>">Tudo</a>
		<a class="<?php echo $mural17_gratis ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'gratis' => $mural17_gratis ? '' : '1' ) ) ); ?>">Grátis</a>
		<?php foreach ( $mural17_cats as $mural17_slug => $mural17_nome ) : ?>
			<a class="<?php echo $mural17_cat === $mural17_slug ? 'is-on' : ''; ?>" href="<?php echo esc_url( $mural17_url( array( 'cat' => $mural17_cat === $mural17_slug ? '' : $mural17_slug ) ) ); ?>"><?php echo esc_html( $mural17_nome ); ?></a>
		<?php endforeach; ?>
		<form class="ag-cidade" method="get" action="<?php echo esc_url( $mural17_base ); ?>">
			<?php foreach ( array( 'quando' => 'semana' === $mural17_quando ? '' : $mural17_quando, 'cat' => $mural17_cat, 'gratis' => $mural17_gratis ? '1' : '' ) as $mural17_k => $mural17_v ) : ?>
				<?php if ( '' !== $mural17_v ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $mural17_k ); ?>" value="<?php echo esc_attr( $mural17_v ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<label class="screen-reader-text" for="ag-cidade">Cidade</label>
			<select id="ag-cidade" name="cidade" data-envia>
				<option value="0">📍 Todo o 17</option>
				<?php foreach ( mural17_lista_cidades() as $mural17_g ) : ?>
					<optgroup label="<?php echo esc_attr( $mural17_g['regiao']->name ); ?>">
						<option value="<?php echo esc_attr( $mural17_g['regiao']->slug ); ?>" <?php selected( $mural17_escopo && $mural17_escopo->term_id === $mural17_g['regiao']->term_id ); ?>><?php echo esc_html( 'Toda a ' . $mural17_g['regiao']->name ); ?></option>
						<?php foreach ( $mural17_g['cidades'] as $mural17_c ) : ?>
							<option value="<?php echo esc_attr( $mural17_c->slug ); ?>" <?php selected( $mural17_escopo && $mural17_escopo->term_id === $mural17_c->term_id ); ?>><?php echo esc_html( $mural17_c->name ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
		</form>
	</div>

	<div class="ag-grade">
		<div class="ag-principal">
			<?php if ( $mural17_dest ) : ?>
				<section class="ag-caixa">
					<h2 class="ag-caixa__titulo">Destaques do fim de semana</h2>
					<div class="ag-dest">
						<?php foreach ( $mural17_dest as $mural17_e ) : ?>
							<a class="ag-ev" href="<?php echo esc_url( $mural17_e['link'] ); ?>"<?php echo 'post' === $mural17_e['tipo'] ? '' : ' target="_blank" rel="noopener"'; ?>>
								<span class="ag-ev__img ag-ev__img--<?php echo esc_attr( sanitize_title( $mural17_e['cat'] ? $mural17_e['cat'] : 'outros' ) ); ?>"<?php echo $mural17_e['img'] ? ' style="background-image:url(' . esc_url( $mural17_e['img'] ) . ')"' : ''; ?>></span>
								<span class="ag-ev__in">
									<span class="ag-ev__quando"><?php echo esc_html( wp_date( 'D, j', strtotime( max( $mural17_e['data'], $mural17_sab ) . ' 12:00' ) ) . ( $mural17_e['hora'] ? ' · ' . $mural17_hora( $mural17_e['hora'] ) : '' ) ); ?></span>
									<span class="ag-ev__titulo"><?php echo esc_html( $mural17_e['titulo'] ); ?></span>
									<span class="ag-ev__onde"><?php echo esc_html( implode( ' · ', array_filter( array( $mural17_e['local'], $mural17_e['cidade'] ? $mural17_e['cidade']->name : '' ) ) ) ); ?></span>
									<?php echo mural17_selo_evento( $mural17_e ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $mural17_cartaz ) : ?>
				<section class="ag-caixa">
					<h2 class="ag-caixa__titulo">Em cartaz</h2>
					<ul class="ag-lista">
						<?php foreach ( $mural17_cartaz as $mural17_e ) : ?>
							<li>
								<span class="ag-lista__hora">até <?php echo esc_html( wp_date( 'j/m', strtotime( $mural17_e['fim'] . ' 12:00' ) ) ); ?></span>
								<span class="ag-lista__info">
									<a class="ag-lista__titulo" href="<?php echo esc_url( $mural17_e['link'] ); ?>"<?php echo 'post' === $mural17_e['tipo'] ? '' : ' target="_blank" rel="noopener"'; ?>><?php echo esc_html( $mural17_e['titulo'] ); ?></a>
									<span class="ag-lista__onde"><?php echo esc_html( implode( ' · ', array_filter( array( $mural17_e['local'], $mural17_e['cidade'] ? $mural17_e['cidade']->name : '' ) ) ) ); ?> <?php echo mural17_selo_evento( $mural17_e ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</span>
								<span class="ag-lista__valor<?php echo mural17_evento_gratis( $mural17_e['valor'] ) ? ' is-gratis' : ''; ?>"><?php echo esc_html( mural17_evento_gratis( $mural17_e['valor'] ) ? 'Grátis' : $mural17_e['valor'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<section class="ag-caixa">
				<div class="ag-caixa__cab">
					<h2 class="ag-caixa__titulo"><?php echo 'mes' === $mural17_quando ? 'Próximos 30 dias' : ( 'fds' === $mural17_quando ? 'Fim de semana' : 'Dia a dia' ); ?></h2>
					<?php if ( 'mes' !== $mural17_quando ) : ?>
						<a href="<?php echo esc_url( $mural17_url( array( 'quando' => 'mes' ) ) ); ?>">Ver o mês →</a>
					<?php endif; ?>
				</div>
				<?php
				$mural17_teve = false;
				for ( $mural17_d = new DateTimeImmutable( $mural17_ini, $mural17_tz ); $mural17_d->format( 'Y-m-d' ) <= $mural17_fim; $mural17_d = $mural17_d->modify( '+1 day' ) ) :
					$mural17_ymd = $mural17_d->format( 'Y-m-d' );
					$mural17_dia = array_filter(
						$mural17_itens,
						function ( $e ) use ( $mural17_ymd, $mural17_longo ) {
							return $mural17_longo( $e ) ? $e['data'] === $mural17_ymd : ( $e['data'] <= $mural17_ymd && $e['fim'] >= $mural17_ymd );
						}
					);
					if ( ! $mural17_dia ) {
						continue;
					}
					$mural17_teve = true;
					$mural17_rot  = $mural17_ymd === $mural17_hoje->format( 'Y-m-d' ) ? 'Hoje, ' . wp_date( 'l j', $mural17_d->getTimestamp() ) : ( $mural17_ymd === $mural17_hoje->modify( '+1 day' )->format( 'Y-m-d' ) ? 'Amanhã, ' . wp_date( 'l j', $mural17_d->getTimestamp() ) : wp_date( 'l, j \d\e F', $mural17_d->getTimestamp() ) );
					?>
					<h3 class="ag-dia"><?php echo esc_html( mb_strtoupper( mb_substr( $mural17_rot, 0, 1 ) ) . mb_substr( $mural17_rot, 1 ) ); ?></h3>
					<ul class="ag-lista">
						<?php foreach ( $mural17_dia as $mural17_e ) : ?>
							<?php $mural17_g = mural17_evento_gratis( $mural17_e['valor'] ); ?>
							<li>
								<span class="ag-lista__hora"><?php echo esc_html( $mural17_e['data'] < $mural17_ymd ? 'até ' . wp_date( 'j/m', strtotime( $mural17_e['fim'] . ' 12:00' ) ) : $mural17_hora( $mural17_e['hora'] ) ); ?></span>
								<span class="ag-lista__info">
									<a class="ag-lista__titulo" href="<?php echo esc_url( $mural17_e['link'] ); ?>"<?php echo 'post' === $mural17_e['tipo'] ? '' : ' target="_blank" rel="noopener"'; ?>><?php echo esc_html( $mural17_e['titulo'] ); ?></a>
									<span class="ag-lista__onde"><?php echo esc_html( implode( ' · ', array_filter( array( $mural17_e['local'], $mural17_e['cidade'] ? $mural17_e['cidade']->name : '' ) ) ) ); ?> <?php echo mural17_selo_evento( $mural17_e ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</span>
								<span class="ag-lista__valor<?php echo $mural17_g ? ' is-gratis' : ''; ?>"><?php echo esc_html( $mural17_g ? 'Grátis' : $mural17_e['valor'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endfor; ?>
				<?php if ( ! $mural17_teve && ! $mural17_cartaz ) : ?>
					<p class="vazio">Nenhum evento <?php echo $mural17_escopo ? 'em ' . esc_html( $mural17_onde ) : 'no 17'; ?> nesse período<?php echo $mural17_cat || $mural17_gratis ? ' com esse filtro' : ''; ?>. <a href="<?php echo esc_url( $mural17_url( array( 'quando' => 'mes', 'cat' => '', 'gratis' => '' ) ) ); ?>">Veja os próximos 30 dias</a><?php echo $mural17_wa ? ' ou <a href="' . esc_url( $mural17_wa ) . '" target="_blank" rel="noopener">divulgue o seu</a>' : ''; ?>.</p>
				<?php endif; ?>
			</section>
		</div>

		<aside class="ag-lado">
			<div class="ag-cta">
				<h2>Vai fazer um evento?</h2>
				<p>Divulgue grátis na agenda do Mural 17. Prefeituras e câmaras publicam direto pelo painel do órgão.</p>
				<?php if ( $mural17_wa ) : ?>
					<a class="botao botao--pilula" href="<?php echo esc_url( $mural17_wa ); ?>" target="_blank" rel="noopener">Divulgar pelo WhatsApp</a>
				<?php endif; ?>
			</div>
			<?php
			$mural17_sy = array_filter(
				mural17_eventos_sympla( $mural17_escopo ),
				function ( $e ) {
					return $e['fim'] >= wp_date( 'Y-m-d' );
				}
			);
			usort(
				$mural17_sy,
				function ( $a, $b ) {
					return strcmp( $a['data'], $b['data'] );
				}
			);
			?>
			<?php if ( $mural17_sy ) : ?>
				<section class="ag-caixa ag-ingressos">
					<h2>Ingressos à venda no <?php echo $mural17_escopo ? esc_html( $mural17_onde ) : '17'; ?></h2>
					<ul>
						<?php foreach ( array_slice( $mural17_sy, 0, 6 ) as $mural17_e ) : ?>
							<li><a href="<?php echo esc_url( $mural17_e['link'] ); ?>" target="_blank" rel="noopener"><b><?php echo esc_html( $mural17_e['titulo'] ); ?></b><span><?php echo esc_html( implode( ' · ', array_filter( array( $mural17_e['cidade'] ? $mural17_e['cidade']->name : '', wp_date( 'd/m', strtotime( $mural17_e['data'] . ' 12:00' ) ) . ( $mural17_e['fim'] > $mural17_e['data'] ? ' a ' . wp_date( 'd/m', strtotime( $mural17_e['fim'] . ' 12:00' ) ) : '' ), 'Sympla' ) ) ) ); ?></span></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
			<?php
			mural17_form_avisos(
				array(
					'temas'  => array( 'agenda' ),
					'titulo' => 'Agenda do fim de semana',
					'texto'  => 'Toda quinta, por e-mail, o que fazer na sua cidade no fim de semana.',
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
