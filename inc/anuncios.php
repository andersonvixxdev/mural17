<?php
/**
 * Anúncios da Adsterra nos espaços de publicidade do tema.
 *
 * Só banners (iframe) e o banner nativo. Popunder, Social Bar e Smartlink
 * ficam de fora de propósito: abrem janela ou cobrem a tela, o que fere os
 * Better Ads Standards, faz o Chrome filtrar anúncios do site e conta contra
 * a aprovação no AdSense e na Pesquisa.
 *
 * Um widget posto em Aparência › Widgets na área do espaço continua valendo
 * mais que a Adsterra (para um anunciante direto, por exemplo).
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chaves dos blocos da Adsterra (painel Adsterra › Websites › mural17.com.br).
 * São públicas: aparecem no código de qualquer página com o anúncio.
 *
 * @return array<string, array{0:string,1:int,2:int}> formato => (chave, largura, altura).
 */
function mural17_adsterra_blocos() {
	return apply_filters(
		'mural17_adsterra_blocos',
		array(
			'728x90'  => array( '7d9329aba0e3cb29f4725c687b0d85de', 728, 90 ),
			'468x60'  => array( '1151f581c01d03869fd4620f065a2382', 468, 60 ),
			'320x50'  => array( '8f21e744a2b29d4578a808adaf169f3a', 320, 50 ),
			'300x250' => array( '570c93a7f92a12cc31efef463dc892c5', 300, 250 ),
			'160x600' => array( '1574122484d04a6492aaf8b931a43dc6', 160, 600 ),
			'160x300' => array( 'aaa099535057a45bb99b754c6b2ed550', 160, 300 ),
		)
	);
}

/**
 * Chave do banner nativo.
 *
 * @return string
 */
function mural17_adsterra_nativo() {
	return apply_filters( 'mural17_adsterra_nativo', 'e00b2b0f2c2fe6704ec3cf4fb1e7a51b' );
}

/**
 * Adsterra ligada? (Serviços › Publicidade; ligada por padrão.)
 *
 * @return bool
 */
function mural17_adsterra_ligada() {
	return '0' !== (string) get_option( 'mural17_adsterra', '1' );
}

/**
 * Página que não deve ter anúncio: institucional, formulário, painel do
 * órgão, busca, 404 e matéria sem texto.
 *
 * @return bool
 */
function mural17_sem_anuncio() {
	if ( is_search() || is_404() || is_preview() ) {
		return true;
	}
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$fora = array_merge( array_keys( mural17_paginas_institucionais() ), array( 'cadastro-de-orgao', 'painel-do-orgao' ) );
		if ( in_array( $slug, $fora, true ) || has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'mural17_painel_orgao' ) ) {
			return true;
		}
	}
	if ( is_singular( 'post' ) && str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', get_queried_object_id() ) ) ) < 60 ) {
		return true; // Matéria curtinha: anúncio maior que o conteúdo.
	}
	return (bool) apply_filters( 'mural17_sem_anuncio', false );
}

/**
 * Banner da Adsterra que escolhe o tamanho pela largura da tela.
 *
 * O código da Adsterra lê a variável global atOptions na hora em que o
 * script carrega, então cada banner precisa entrar logo depois da sua
 * atOptions, na ordem da página. O document.write faz isso, e só baixa o
 * tamanho que vai aparecer (esconder com CSS contaria impressão invisível).
 *
 * @param array $tamanhos Lista de pares (largura mínima da tela, formato),
 *                        do maior para o menor.
 */
function mural17_adsterra_banner( $tamanhos ) {
	$blocos = mural17_adsterra_blocos();
	$opcoes = array();
	foreach ( $tamanhos as $t ) {
		if ( isset( $blocos[ $t[1] ] ) ) {
			$b        = $blocos[ $t[1] ];
			$opcoes[] = array( (int) $t[0], $b[0], $b[1], $b[2] );
		}
	}
	if ( ! $opcoes ) {
		return;
	}
	?>
	<script data-cfasync="false">(function(){var o=<?php echo wp_json_encode( $opcoes ); ?>,w=window.innerWidth||document.documentElement.clientWidth,e=o[o.length-1];for(var i=0;i<o.length;i++){if(w>=o[i][0]){e=o[i];break;}}window.atOptions={key:e[1],format:'iframe',height:e[3],width:e[2],params:{}};document.write('<scr'+'ipt data-cfasync="false" src="https://bicea.org/22/'+e[1]+'"></scr'+'ipt>');})();</script>
	<?php
}

/**
 * Tamanhos de cada espaço do tema.
 *
 * @param string $area Espaço.
 * @return array
 */
function mural17_adsterra_tamanhos( $area ) {
	$mapa = array(
		'anuncio-topo' => array( array( 760, '728x90' ), array( 0, '320x50' ) ),
		'anuncio-meio' => array( array( 760, '728x90' ), array( 0, '300x250' ) ),
		'anuncio-post' => array( array( 760, '728x90' ), array( 500, '468x60' ), array( 0, '300x250' ) ),
		'anuncio-lado' => array( array( 0, '300x250' ) ),
	);
	return $mapa[ $area ] ?? array();
}

/**
 * Banner nativo (bloco de 4 anúncios com foto), no fim da matéria. Só um
 * por página: o código dele procura um contêiner com id fixo.
 */
function mural17_adsterra_nativo_bloco() {
	static $feito = false;
	if ( $feito || ! mural17_adsterra_ligada() || mural17_sem_anuncio() ) {
		return;
	}
	$feito = true;
	$k     = mural17_adsterra_nativo();
	?>
	<div class="anuncio anuncio--nativo" aria-label="Publicidade">
		<span class="anuncio__rotulo">Publicidade</span>
		<script async data-cfasync="false" src="https://bicea.org/21/<?php echo esc_attr( $k ); ?>"></script>
		<div id="container-<?php echo esc_attr( $k ); ?>"></div>
	</div>
	<?php
}

add_action(
	'admin_post_mural17_admin_publicidade',
	function () {
		mural17_confere_admin_servicos( 'mural17_admin_publicidade' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		update_option( 'mural17_adsterra', empty( $_POST['adsterra'] ) ? '0' : '1', false ); // phpcs:ignore WordPress.Security.NonceVerification
		mural17_volta_admin_servicos( 'publicidade', 'publicidade' );
	}
);

/**
 * Card "Publicidade" em Serviços.
 *
 * @param callable $form Abre o formulário da ação.
 */
function mural17_card_publicidade( $form ) {
	?>
	<div class="m17-card" id="publicidade">
		<h2>Publicidade (Adsterra)</h2>
		<p>Banners da Adsterra nos espaços do tema: topo (728×90, no celular 320×50), meio da capa, lateral (300×250) e dentro da matéria, mais o banner nativo no fim da matéria. Ficam fora das páginas institucionais, do cadastro e painel do órgão, da busca e de matérias muito curtas. Um widget posto no espaço em Aparência › Widgets vale mais que a Adsterra.</p>
		<p>Popunder, Social Bar e Smartlink não estão ligados: abrem janela ou cobrem a tela e prejudicam o site no Google e no AdSense.</p>
		<?php $form( 'mural17_admin_publicidade' ); ?>
			<p><label><input type="checkbox" name="adsterra" value="1" <?php checked( mural17_adsterra_ligada() ); ?>> Mostrar os anúncios da Adsterra</label></p>
			<?php submit_button( 'Salvar', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
