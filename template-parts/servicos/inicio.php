<?php
/**
 * /servicos/: a faixa do dia da cidade e os oito serviços.
 *
 * Args: cidade.
 *
 * @package Mural17
 */

$cidade = $args['cidade'];
$icones = array(
	'farmacia'     => '<path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7Z"/>',
	'interrupcoes' => '<path d="M12 3s6 7 6 11a6 6 0 0 1-12 0c0-4 6-11 6-11Z"/>',
	'alertas'      => '<path d="M12 3 2 20h20Z"/><path d="M12 10v4M12 17v.5"/>',
	'combustivel'  => '<rect x="4" y="3" width="10" height="18" rx="1.5"/><path d="M4 10h10M14 7l4 3v8a2 2 0 0 1-4 0v-3"/>',
	'concursos'    => '<path d="M5 3h10l4 4v14H5Z"/><path d="M9 12h6M9 16h6M9 8h3"/>',
	'falecimentos' => '<path d="M12 3v18M7 8h10"/>',
	'fala'         => '<path d="M4 5h16v11H9l-5 4Z"/>',
	'guia'         => '<path d="M4 5h6a2 2 0 0 1 2 2v13a2 2 0 0 0-2-2H4ZM20 5h-6a2 2 0 0 0-2 2v13a2 2 0 0 1 2-2h6Z"/>',
);

get_template_part(
	'template-parts/servicos/topo',
	null,
	array(
		'titulo' => 'Serviços',
		'texto'  => 'O que você precisa resolver hoje na sua cidade: farmácia aberta, falta de água, alerta de tempo, concursos, preço da gasolina.',
		'cidade' => $cidade,
	)
);
?>
<div class="sv-pagina__corpo">
	<?php get_template_part( 'template-parts/servicos/faixa', null, array( 'cidade' => $cidade ) ); ?>

	<ul class="sv-lista">
		<?php foreach ( mural17_servicos() as $k => $s ) : ?>
			<li>
				<a class="sv-card sv-card--<?php echo esc_attr( $k ); ?>" href="<?php echo esc_url( in_array( $k, array( 'concursos', 'falecimentos' ), true ) ? $s['url'] : mural17_link_servico( $k, $cidade ) ); ?>">
					<span class="sv-card__icone" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icones[ $k ]; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG fixo. ?></svg></span>
					<b><?php echo esc_html( $s['nome'] ); ?></b>
					<span><?php echo esc_html( $s['texto'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php
	mural17_form_avisos(
		array(
			'temas'  => array( 'alertas', 'agua-luz' ),
			'titulo' => 'Avisos no seu e-mail',
			'texto'  => 'Alerta de tempestade, falta de água e luz e concursos abertos na sua cidade. Só quando acontece, e dá para cancelar com um clique.',
			'cidade' => $cidade,
		)
	);
	?>
</div>
