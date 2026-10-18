<?php
/**
 * Rodapé e barra de abas do celular.
 *
 * @package Mural17
 */

$wa    = mural17_whatsapp_link( mural17_opt( 'mural17_whatsapp' ) );
$email = mural17_opt( 'mural17_email' );
$ig    = mural17_opt( 'mural17_instagram' );
$fb    = mural17_opt( 'mural17_facebook' );
?>
</main>

<footer class="rodape">
	<div class="wrap rodape__grade">
		<div class="rodape__marca">
			<a class="marca__logo marca__logo--rodape" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( MURAL17_URI . '/assets/marca/mural17-logo-negativo.svg' ); ?>" width="503" height="83" alt="Mural 17"></a>
			<p><?php echo esc_html( mural17_opt( 'mural17_slogan' ) ); ?>: notícias, vagas, classificados e agenda das 117 cidades do DDD 17.</p>
			<?php if ( $ig || $fb ) : ?>
				<div class="rodape__redes">
					<?php if ( $ig ) : ?>
						<a href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener" aria-label="Instagram do Mural 17">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
						</a>
					<?php endif; ?>
					<?php if ( $fb ) : ?>
						<a href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook do Mural 17">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V9H7v3.5h2V21h3.5v-8.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z"/></svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div>
			<h2 class="rodape__titulo">Regiões</h2>
			<ul class="rodape__lista">
				<?php
				$regioes = get_terms(
					array(
						'taxonomy'   => 'cidade',
						'hide_empty' => false,
						'parent'     => 0,
					)
				);
				if ( ! is_wp_error( $regioes ) ) {
					foreach ( $regioes as $r ) {
						echo '<li><a href="' . esc_url( get_term_link( $r ) ) . '">' . esc_html( $r->name ) . '</a></li>';
					}
				}
				?>
			</ul>
		</div>

		<div>
			<h2 class="rodape__titulo">Seções</h2>
			<ul class="rodape__lista">
				<?php
				foreach ( array( 'policia' => 'Polícia', 'transito' => 'Trânsito' ) as $slug => $nome ) {
					$link = mural17_cat_link( $slug );
					if ( $link ) {
						echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $nome ) . '</a></li>';
					}
				}
				?>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'vaga' ) ); ?>">Empregos</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'classificado' ) ); ?>">Classificados</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>">Agenda</a></li>
			</ul>
			<?php
			if ( has_nav_menu( 'rodape' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'rodape',
						'container'      => false,
						'menu_class'     => 'rodape__lista rodape__lista--extra',
						'depth'          => 1,
					)
				);
			}
			?>
		</div>

		<div>
			<h2 class="rodape__titulo">Fale com a redação</h2>
			<ul class="rodape__lista">
				<?php if ( $wa ) : ?>
					<li><a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( mural17_link_oficial( 'cadastro' ) ); ?>">Órgãos públicos: publiquem aqui</a></li>
				<?php if ( ! $wa && ! $email ) : ?>
					<li>Configure o WhatsApp e o e-mail em Aparência › Personalizar › Mural 17.</li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
	<div class="rodape__base">
		<div class="wrap">© <?php echo esc_html( gmdate( 'Y' ) ); ?> Mural 17 · Notícias de outros veículos são resumidas e linkam a fonte original.</div>
	</div>
</footer>

<nav class="abas" aria-label="Atalhos">
	<a class="abas__item<?php echo is_front_page() ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 12 4l9 7v9h-6v-6H9v6H3Z"/></svg>Início
	</a>
	<button type="button" class="abas__item<?php echo is_tax( 'cidade' ) ? ' is-ativo' : ''; ?>" data-abre-cidades>
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>Cidades
	</button>
	<a class="abas__item<?php echo is_post_type_archive( 'vaga' ) || is_singular( 'vaga' ) ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'vaga' ) ); ?>">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5h6v2"/></svg>Vagas
	</a>
	<a class="abas__item<?php echo is_post_type_archive( 'classificado' ) || is_singular( 'classificado' ) || is_tax( 'categoria-anuncio' ) ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'classificado' ) ); ?>">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h9l7 7-9 9-7-7Z"/><circle cx="8.5" cy="8.5" r="1.5"/></svg>Classif.
	</a>
	<a class="abas__item<?php echo is_post_type_archive( 'evento' ) || is_singular( 'evento' ) ? ' is-ativo' : ''; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>Agenda
	</a>
</nav>

<?php wp_footer(); ?>
</body>
</html>
