<?php
/**
 * Formulário de busca.
 *
 * @package Mural17
 */

$mural17_busca_id = wp_unique_id( 'busca-' );
?>
<form role="search" method="get" class="busca" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $mural17_busca_id ); ?>" class="screen-reader-text">Buscar no Mural 17</label>
	<input type="search" id="<?php echo esc_attr( $mural17_busca_id ); ?>" class="busca__campo" placeholder="Buscar notícias, vagas, eventos" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	<button type="submit" class="busca__botao" aria-label="Buscar">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
	</button>
</form>
