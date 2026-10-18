<?php
/**
 * Páginas em /servicos/<nome>/ (farmácia de plantão, guia, alertas, Fala 17,
 * combustível e falta de água e luz). Cada uma está em template-parts/servicos/.
 *
 * @package Mural17
 */

get_header();

$mural17_slug = mural17_servico_atual();
$mural17_arq  = mural17_rotas_servicos()[ $mural17_slug ];
$mural17_cid  = mural17_cidade_servico();
?>
<div class="sv-pagina" data-servico-cidade="<?php echo esc_attr( $mural17_cid ? $mural17_cid->term_id : '' ); ?>" data-servico-auto="<?php echo mural17_cidade_na_url() ? '0' : '1'; ?>">
	<?php get_template_part( 'template-parts/servicos/' . $mural17_arq, null, array( 'cidade' => $mural17_cid ) ); ?>
</div>
<?php
get_footer();
