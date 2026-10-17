<?php
/**
 * Cabeçalho.
 *
 * @package Mural17
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>

<div class="topo-barra">
	<div class="wrap topo-barra__in">
		<span><?php echo esc_html( ucfirst( date_i18n( 'l, j \d\e F \d\e Y' ) ) ); ?></span>
		<?php
		if ( has_nav_menu( 'topo' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'topo',
					'container'      => false,
					'menu_class'     => 'topo-barra__menu',
					'depth'          => 1,
				)
			);
		}
		?>
	</div>
</div>

<header class="cabecalho">
	<div class="wrap cabecalho__in">
		<button type="button" class="botao-icone menu-abrir" aria-controls="nav-principal" aria-expanded="false" aria-label="Abrir menu">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
		</button>

		<div class="marca">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="marca__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img src="<?php echo esc_url( MURAL17_URI . '/assets/marca/mural17-logo.svg' ); ?>" width="361" height="118" alt="Mural 17"></a>
			<?php endif; ?>
			<?php if ( mural17_opt( 'mural17_slogan' ) ) : ?>
				<p class="marca__slogan"><?php echo esc_html( mural17_opt( 'mural17_slogan' ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="cabecalho__acoes">
			<?php get_search_form(); ?>
			<?php
			$wa = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ), 'Olá, Mural 17! Tenho uma notícia/sugestão:' );
			if ( $wa ) :
				?>
				<a class="botao" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Envie sua notícia</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<nav id="nav-principal" class="nav" aria-label="Seções">
	<div class="wrap">
		<div class="nav__busca"><?php get_search_form(); ?></div>
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
	</div>
</nav>

<?php
if ( mural17_opt( 'mural17_ultimahora' ) ) :
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

<?php mural17_anuncio( 'anuncio-topo' ); ?>

<main id="conteudo" class="wrap principal">
