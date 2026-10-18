<?php
/**
 * Cabeçalho.
 *
 * @package Mural17
 */

$mural17_wa    = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), 'Olá, Mural 17! Tenho uma notícia:' );
$mural17_mural = is_front_page() || is_tax( 'cidade' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>

<header class="cab">
	<div class="wrap cab__in">
		<button type="button" class="botao-icone menu-abrir" aria-controls="nav-principal" aria-expanded="false" aria-label="Abrir menu">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
		</button>

		<?php if ( has_custom_logo() ) : ?>
			<div class="cab__logo"><?php the_custom_logo(); ?></div>
		<?php else : ?>
			<a class="cab__logo marca__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img src="<?php echo esc_url( MURAL17_URI . '/assets/marca/mural17-logo.svg' ); ?>" width="503" height="83" alt="Mural 17"></a>
		<?php endif; ?>

		<button type="button" class="cidade-botao" data-abre-cidades aria-haspopup="dialog">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
			<span class="cidade-botao__nome" data-minha-cidade>Escolha sua cidade</span>
		</button>

		<nav id="nav-principal" class="nav" aria-label="Seções">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'principal',
					'container'      => false,
					'menu_class'     => 'nav__lista',
					'depth'          => 1,
					'fallback_cb'    => 'mural17_menu_padrao',
				)
			);
			?>
			<div class="nav__busca"><?php get_search_form(); ?></div>
		</nav>

		<div class="cab__acoes">
			<button type="button" class="botao-icone botao-icone--claro" data-abre-busca aria-expanded="false" aria-controls="busca-painel" aria-label="Buscar">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			</button>
			<?php if ( $mural17_wa ) : ?>
				<a class="botao botao--pilula cab__enviar" href="<?php echo esc_url( $mural17_wa ); ?>" target="_blank" rel="noopener">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>
					Enviar notícia
				</a>
			<?php endif; ?>
		</div>
	</div>
	<div class="busca-painel" id="busca-painel" hidden>
		<div class="wrap"><?php get_search_form(); ?></div>
	</div>
</header>

<dialog class="cidades" id="cidades" aria-labelledby="cidades-titulo">
	<div class="cidades__topo">
		<h2 class="cidades__titulo" id="cidades-titulo">Escolha sua cidade</h2>
		<button type="button" class="botao-icone" data-fecha-cidades aria-label="Fechar">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
		</button>
	</div>
	<label class="screen-reader-text" for="cidades-busca">Buscar cidade</label>
	<input type="search" id="cidades-busca" class="cidades__busca" placeholder="Digite o nome da cidade" autocomplete="off">
	<p class="cidades__dica">Sua escolha fica guardada neste aparelho. A home continua mostrando todo o 17.</p>
	<div class="cidades__lista">
		<?php foreach ( mural17_lista_cidades() as $grupo ) : ?>
			<section class="cidades__grupo">
				<h3 class="cidades__regiao"><a href="<?php echo esc_url( get_term_link( $grupo['regiao'] ) ); ?>" data-cidade="<?php echo esc_attr( $grupo['regiao']->name ); ?>"><?php echo esc_html( $grupo['regiao']->name ); ?></a></h3>
				<ul>
					<?php foreach ( $grupo['cidades'] as $c ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>" data-cidade="<?php echo esc_attr( $c->name ); ?>"><?php echo esc_html( $c->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
		<p class="cidades__nada" hidden>Nenhuma cidade do DDD 17 com esse nome.</p>
	</div>
</dialog>

<?php
if ( ! $mural17_mural && mural17_opt( 'mural17_ultimahora' ) ) :
	$ultimas = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 4,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'date_query'          => array( array( 'after' => '2 days ago' ) ),
		)
	);
	if ( $ultimas->have_posts() ) :
		?>
		<div class="ultima-hora">
			<div class="wrap ultima-hora__in">
				<span class="ultima-hora__selo">Última hora</span>
				<ul class="ultima-hora__lista">
					<?php
					while ( $ultimas->have_posts() ) :
						$ultimas->the_post();
						?>
						<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php endwhile; ?>
				</ul>
			</div>
		</div>
		<?php
	endif;
	wp_reset_postdata();
endif;
?>

<?php
if ( ! $mural17_mural ) {
	mural17_anuncio( 'anuncio-topo' );
}
?>

<main id="conteudo" class="<?php echo $mural17_mural ? 'principal principal--mural' : 'wrap principal'; ?>">
