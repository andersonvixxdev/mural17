<?php
/**
 * Comentários.
 *
 * @package Mural17
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comentarios" class="comentarios">
	<?php if ( have_comments() ) : ?>
		<h2 class="secao__titulo">
			<?php
			$n = get_comments_number();
			/* translators: %s: número de comentários. */
			printf( esc_html( _n( '%s comentário', '%s comentários', $n, 'mural17' ) ), esc_html( number_format_i18n( $n ) ) );
			?>
		</h2>
		<ol class="comentarios__lista">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'  => 'Deixe seu comentário',
			'label_submit' => 'Comentar',
		)
	);
	?>
</section>
