<?php
/**
 * Rodapé.
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
			<a class="marca__nome marca__nome--claro" href="<?php echo esc_url( home_url( '/' ) ); ?>">Mural<span>17</span></a>
			<p><?php echo esc_html( mural17_opt( 'mural17_slogan' ) ); ?>: notícias, anúncios, eventos e empregos de Neves Paulista, Mirassol, Jaci e Rio Preto.</p>
			<div class="rodape__redes">
				<?php if ( $ig ) : ?>
					<a href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener" aria-label="Instagram do Mural 17">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
					</a>
				<?php endif; ?>
				<?php if ( $fb ) : ?>
					<a href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook do Mural 17">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V9H7v3.5h2V21h3.5v-8.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z"/></svg>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div>
			<h2 class="rodape__titulo">Cidades</h2>
			<ul class="rodape__lista">
				<?php
				$cidades = get_terms(
					array(
						'taxonomy'   => 'cidade',
						'hide_empty' => false,
						'parent'     => 0,
					)
				);
				if ( ! is_wp_error( $cidades ) ) {
					foreach ( $cidades as $c ) {
						echo '<li><a href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a></li>';
					}
				}
				?>
			</ul>
		</div>

		<div>
			<h2 class="rodape__titulo">Serviços</h2>
			<ul class="rodape__lista">
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
						'menu_class'     => 'rodape__lista',
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
			</ul>
		</div>
	</div>
	<div class="rodape__base">
		<div class="wrap">© <?php echo esc_html( gmdate( 'Y' ) ); ?> Mural 17 · Notícias de outros veículos são resumidas e linkam a fonte original.</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
