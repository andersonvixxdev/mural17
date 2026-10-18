<?php
/**
 * Fala, 17: reclamações dos moradores, a resposta do órgão e o formulário.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$escopo   = mural17_escopo_servico();
$cidade   = $escopo && $escopo->parent ? $escopo : null;
$tipos    = mural17_tipos_fala();
$situacao = isset( $_GET['situacao'] ) ? sanitize_key( wp_unslash( $_GET['situacao'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$tipo     = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$pagina   = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$situacao = in_array( $situacao, array( 'aberta', 'respondida', 'resolvida' ), true ) ? $situacao : '';
$tipo     = isset( $tipos[ $tipo ] ) ? $tipo : '';
$base     = $escopo ? add_query_arg( 'cidade', $escopo->slug, mural17_servicos()['fala']['url'] ) : add_query_arg( 'cidade', '', mural17_servicos()['fala']['url'] );
$meta     = array();
if ( $situacao ) {
	$meta[] = array(
		'key'   => '_mural17_fala_situacao',
		'value' => $situacao,
	);
}
if ( $tipo ) {
	$meta[] = array(
		'key'   => '_mural17_fala_tipo',
		'value' => $tipo,
	);
}
$q       = new WP_Query(
	array(
		'post_type'      => 'fala',
		'posts_per_page' => 10,
		'paged'          => $pagina,
		'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
		'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
	)
);
$contar  = function ( $s ) use ( $escopo ) {
	$c = new WP_Query(
		array(
			'post_type'      => 'fala',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => '_mural17_fala_situacao',
					'value' => $s,
				),
			),
			'tax_query'      => mural17_tax_cidade( $escopo ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) $c->found_posts;
};
$nums    = array(
	'aberta'     => $contar( 'aberta' ),
	'respondida' => $contar( 'respondida' ),
	'resolvida'  => $contar( 'resolvida' ),
);
$wa      = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), 'Olá, Mural 17! Quero fazer uma reclamação para o Fala 17:' );
$codigo  = isset( $_GET['m17'] ) ? sanitize_key( wp_unslash( $_GET['m17'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$msgs    = mural17_mensagens_servicos();
$pedido  = isset( $_GET['tipo'] ) ? $tipo : ''; // phpcs:ignore WordPress.Security.NonceVerification
$rotulos = array(
	'aberta'     => 'abertas',
	'respondida' => 'respondidas',
	'resolvida'  => 'resolvidas',
);

ob_start();
?>
<div class="sv-numeros">
	<?php foreach ( $nums as $k => $n ) : ?>
		<span class="sv-numeros__item sv-numeros__item--<?php echo esc_attr( $k ); ?>"><b><?php echo esc_html( number_format_i18n( $n ) ); ?></b><?php echo esc_html( $rotulos[ $k ] ); ?></span>
	<?php endforeach; ?>
</div>
<?php
$extra = ob_get_clean();

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo'  => 'Fala, 17',
		'texto'   => 'Buraco, lâmpada queimada, mato alto, vazamento. Mande a foto, a gente publica e cobra a prefeitura. A resposta dela aparece aqui.',
		'cidade'  => $escopo,
		'regioes' => true,
		'extra'   => $extra,
	)
);
?>
<div class="sv-pagina__grade">
	<div class="sv-pagina__principal">
		<nav class="filtro" aria-label="Filtrar reclamações">
			<a class="filtro__item<?php echo '' === $situacao ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tipo', $tipo ? $tipo : false, $base ) ); ?>">Todas</a>
			<?php foreach ( array( 'aberta' => 'Abertas', 'respondida' => 'Respondidas', 'resolvida' => 'Resolvidas' ) as $k => $nome ) : ?>
				<a class="filtro__item<?php echo $situacao === $k ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'situacao' => $k, 'tipo' => $tipo ? $tipo : false ), $base ) ); ?>"><?php echo esc_html( $nome ); ?></a>
			<?php endforeach; ?>
			<span class="filtro__sep" aria-hidden="true"></span>
			<?php foreach ( $tipos as $k => $nome ) : ?>
				<a class="filtro__item filtro__item--leve<?php echo $tipo === $k ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tipo' => $tipo === $k ? false : $k, 'situacao' => $situacao ? $situacao : false ), $base ) ); ?>"><?php echo esc_html( $nome ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( $q->have_posts() ) : ?>
			<?php
			foreach ( $q->posts as $p ) :
				$sit   = mural17_campo( $p->ID, 'fala_situacao' );
				$sit   = $sit ? $sit : 'aberta';
				$resp  = mural17_campo( $p->ID, 'fala_resposta' );
				$de    = mural17_campo( $p->ID, 'fala_resposta_de' );
				$em    = mural17_data( mural17_campo( $p->ID, 'fala_resposta_em' ) );
				$av    = mural17_data( mural17_campo( $p->ID, 'fala_avisado_em' ) );
				$eu    = (int) get_post_meta( $p->ID, '_mural17_eutambem', true );
				$c     = mural17_cidade( $p->ID );
				$dias  = (int) floor( ( time() - get_post_time( 'U', true, $p ) ) / DAY_IN_SECONDS );
				$ja_eu = ! empty( $_COOKIE[ 'm17_et_' . $p->ID ] );
				?>
				<article class="fala<?php echo has_post_thumbnail( $p ) ? '' : ' fala--sem-foto'; ?>" id="fala-<?php echo (int) $p->ID; ?>">
					<?php if ( has_post_thumbnail( $p ) ) : ?>
						<div class="fala__foto"><?php echo get_the_post_thumbnail( $p, 'mural17-card', array( 'alt' => '' ) ); ?></div>
					<?php endif; ?>
					<div class="fala__texto">
						<p class="fala__rotulos">
							<span class="situacao situacao--<?php echo esc_attr( 'resolvida' === $sit ? 'ok' : ( 'respondida' === $sit ? 'oficial' : 'erro' ) ); ?>"><?php echo esc_html( 'aberta' === $sit ? 'Aberta · ' . ( $dias ? $dias . ( 1 === $dias ? ' dia' : ' dias' ) : 'hoje' ) : ucfirst( $sit ) ); ?></span>
							<span><?php echo esc_html( ( $tipos[ mural17_campo( $p->ID, 'fala_tipo' ) ] ?? 'Outro' ) . ' · ' . mural17_campo( $p->ID, 'fala_onde' ) . ( $c && ( ! $escopo || ! $escopo->parent ) ? ' · ' . mural17_nome_curto( $c ) : '' ) ); ?></span>
						</p>
						<h2 class="fala__titulo"><?php echo esc_html( get_the_title( $p ) ); ?></h2>
						<?php if ( wp_trim_words( $p->post_content, 14, '…' ) !== $p->post_title ) : // O título automático já é o começo do relato. ?>
							<div class="fala__relato"><?php echo wp_kses_post( wpautop( esc_html( $p->post_content ) ) ); ?></div>
						<?php endif; ?>
						<p class="fala__meta">Enviada há <?php echo esc_html( human_time_diff( get_post_time( 'U', true, $p ) ) ); ?><?php echo $eu ? esc_html( sprintf( _n( ' · %s morador disse “eu também”', ' · %s moradores disseram “eu também”', $eu, 'mural17' ), number_format_i18n( $eu ) ) ) : ''; ?></p>
						<?php if ( $resp ) : ?>
							<div class="fala__resposta">
								<p class="fala__de"><span class="brasao brasao--mini" aria-hidden="true"><?php echo esc_html( mural17_iniciais( $de ) ); ?></span> Respondido por <?php echo esc_html( $de ? $de : 'órgão responsável' ); ?><?php echo $em ? ' · ' . esc_html( 'há ' . human_time_diff( $em->getTimestamp() ) ) : ''; ?></p>
								<?php echo wp_kses_post( wpautop( esc_html( $resp ) ) ); ?>
							</div>
						<?php else : ?>
							<p class="fala__espera"><?php echo $av ? 'Prefeitura avisada em ' . esc_html( $av->format( 'd/m' ) ) . ' · ainda sem resposta' : 'Aguardando resposta'; ?></p>
						<?php endif; ?>
						<?php if ( 'resolvida' !== $sit ) : ?>
							<form class="fala__eu" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-eutambem>
								<input type="hidden" name="action" value="mural17_eutambem">
								<input type="hidden" name="id" value="<?php echo (int) $p->ID; ?>">
								<input type="hidden" name="voltar" value="<?php echo esc_url( $base ); ?>">
								<button type="submit" class="botao botao--pilula botao--contorno-escuro botao--mini" <?php echo $ja_eu ? 'disabled' : ''; ?>><?php echo $ja_eu ? 'Você disse “eu também”' : '+ Eu também'; ?> · <span data-eutambem-qtd><?php echo (int) $eu; ?></span></button>
							</form>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>

			<?php if ( $q->max_num_pages > 1 ) : ?>
				<nav class="paginacao" aria-label="Páginas">
					<?php if ( $pagina > 1 ) : ?>
						<a class="botao botao--pilula botao--contorno-escuro" href="<?php echo esc_url( add_query_arg( array( 'pg' => $pagina - 1, 'situacao' => $situacao ? $situacao : false, 'tipo' => $tipo ? $tipo : false ), $base ) ); ?>">Mais novas</a>
					<?php endif; ?>
					<?php if ( $pagina < $q->max_num_pages ) : ?>
						<a class="botao botao--pilula botao--contorno-escuro" href="<?php echo esc_url( add_query_arg( array( 'pg' => $pagina + 1, 'situacao' => $situacao ? $situacao : false, 'tipo' => $tipo ? $tipo : false ), $base ) ); ?>">Mais antigas</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<div class="caixa"><p class="vazio">Nenhuma reclamação <?php echo esc_html( $situacao ? $rotulos[ $situacao ] . ' ' : '' ); ?><?php echo esc_html( mural17_em_escopo( $escopo ) ); ?> por enquanto. Viu um problema? Mande pelo formulário.</p></div>
		<?php endif; ?>
	</div>

	<aside class="sv-pagina__lateral">
		<form class="formulario fala-form" id="mandar" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Mandar uma reclamação</h2>
			<?php if ( in_array( $codigo, array( 'fala-ok', 'erro-fala', 'erro-fala-limite', 'erro-nonce' ), true ) ) : ?>
				<?php $msg = 'erro-nonce' === $codigo ? array( 'erro', 'A página ficou aberta por muito tempo. Recarregue e tente de novo.' ) : $msgs[ $codigo ]; ?>
				<p class="aviso aviso--<?php echo esc_attr( $msg[0] ); ?>" role="status"><?php echo esc_html( $msg[1] ); ?></p>
			<?php endif; ?>
			<input type="hidden" name="action" value="mural17_fala">
			<input type="hidden" name="voltar" value="<?php echo esc_url( $base ); ?>">
			<?php wp_nonce_field( 'mural17_fala', 'mural17_nonce' ); ?>
			<p class="formulario__mel" aria-hidden="true"><label>Não preencha<input type="text" name="mural17_site_confirma" tabindex="-1" autocomplete="off"></label></p>
			<p class="campo">
				<label for="fala-foto">Foto do problema</label>
				<input id="fala-foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
				<span class="campo__dica">Sem rosto de pessoas e sem placa de carro. JPG, PNG ou WebP até 8 MB.</span>
			</p>
			<p class="campo">
				<label for="fala-tipo">Tipo</label>
				<select id="fala-tipo" name="fala_tipo" required>
					<?php foreach ( $tipos as $k => $nome ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $pedido, $k ); ?>><?php echo esc_html( $nome ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="campo">
				<label for="fala-cidade">Cidade</label>
				<select id="fala-cidade" name="cidade" required>
					<?php mural17_opcoes_cidades( $cidade ? $cidade->term_id : 0 ); ?>
				</select>
			</p>
			<p class="campo">
				<label for="fala-onde">Onde (rua e ponto de referência)</label>
				<input id="fala-onde" name="fala_onde" required maxlength="140">
			</p>
			<p class="campo">
				<label for="fala-texto">O que está acontecendo</label>
				<textarea id="fala-texto" name="texto" rows="4" required maxlength="1500"></textarea>
			</p>
			<div class="formulario__dupla">
				<p class="campo"><label for="fala-nome">Seu nome</label><input id="fala-nome" name="fala_nome" maxlength="80" autocomplete="name"></p>
				<p class="campo"><label for="fala-zap">Seu WhatsApp</label><input id="fala-zap" name="fala_contato" maxlength="20" inputmode="tel" autocomplete="tel"></p>
			</div>
			<p class="campo campo--check">
				<input id="fala-regras" type="checkbox" required>
				<label for="fala-regras">Li as regras: sem acusação a pessoas e sem política partidária.</label>
			</p>
			<p class="formulario__acoes">
				<button type="submit" class="botao botao--pilula">Enviar para a redação</button>
				<?php if ( $wa ) : ?>
					<a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">ou mande pelo WhatsApp</a>
				<?php endif; ?>
			</p>
		</form>
		<section class="caixa">
			<h2 class="caixa__titulo">Regras</h2>
			<p>A redação confere antes de publicar.</p>
			<p>Sem acusação a pessoas, sem política partidária.</p>
			<p>Seu nome e telefone não aparecem no site.</p>
		</section>
	</aside>
</div>
