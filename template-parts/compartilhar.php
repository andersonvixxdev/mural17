<?php
/**
 * Botões de compartilhamento (sem scripts de terceiros).
 *
 * @package Mural17
 */

$url    = rawurlencode( get_permalink() );
$titulo = rawurlencode( get_the_title() );
?>
<div class="compartilhar">
	<span class="compartilhar__rotulo">Compartilhe</span>
	<a class="compartilhar__botao compartilhar__botao--wa" href="https://wa.me/?text=<?php echo esc_attr( $titulo . '%20' . $url ); ?>" target="_blank" rel="noopener">WhatsApp</a>
	<a class="compartilhar__botao" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $url ); ?>" target="_blank" rel="noopener">Facebook</a>
	<a class="compartilhar__botao" href="https://twitter.com/intent/tweet?text=<?php echo esc_attr( $titulo ); ?>&amp;url=<?php echo esc_attr( $url ); ?>" target="_blank" rel="noopener">X</a>
</div>
