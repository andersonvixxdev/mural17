<?php
/**
 * Topo dos arquivos de agenda, empregos e classificados, com chamada para divulgar.
 *
 * Args: titulo, texto, acao (texto do botão), mensagem (texto inicial do WhatsApp).
 *
 * @package Mural17
 */

$wa = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), $args['mensagem'] );
?>
<header class="arquivo__topo arquivo__topo--cta">
	<div>
		<h1 class="arquivo__titulo"><?php echo esc_html( $args['titulo'] ); ?></h1>
		<p class="arquivo__desc"><?php echo esc_html( $args['texto'] ); ?></p>
	</div>
	<?php if ( $wa ) : ?>
		<a class="botao" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $args['acao'] ); ?></a>
	<?php endif; ?>
</header>

<?php
$cidades = get_terms(
	array(
		'taxonomy'   => 'cidade',
		'hide_empty' => false,
		'parent'     => 0,
	)
);
if ( ! is_wp_error( $cidades ) && $cidades ) :
	$base  = get_post_type_archive_link( get_query_var( 'post_type' ) );
	$atual = isset( $_GET['cidade'] ) ? sanitize_title( wp_unslash( $_GET['cidade'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<nav class="filtro" aria-label="Filtrar por cidade">
		<a class="filtro__item<?php echo '' === $atual ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( $base ); ?>">Todas</a>
		<?php foreach ( $cidades as $c ) : ?>
			<a class="filtro__item<?php echo $atual === $c->slug ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'cidade', $c->slug, $base ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>
