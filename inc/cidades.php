<?php
/**
 * Regiões e cidades do DDD 17 (117 municípios).

 * Regiões = regiões geográficas imediatas do IBGE. As cidades do DDD 17 que o
 * IBGE põe na região de Araçatuba ficam numa região própria. Chave = slug.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regiões com suas cidades.
 *
 * @return array<string, array{nome:string, cidades:array<string,string>}>
 */
function mural17_regioes() {
	return array(
		'regiao-rio-preto' => array(
			'nome'    => 'Região de Rio Preto',
			'cidades' => array(
				'adolfo'          => 'Adolfo',
				'altair'          => 'Altair',
				'bady-bassitt'    => 'Bady Bassitt',
				'balsamo'         => 'Bálsamo',
				'cedral'          => 'Cedral',
				'guapiacu'        => 'Guapiaçu',
				'ibira'           => 'Ibirá',
				'icem'            => 'Icém',
				'ipigua'          => 'Ipiguá',
				'irapua'          => 'Irapuã',
				'jaci'            => 'Jaci',
				'jose-bonifacio'  => 'José Bonifácio',
				'macaubal'        => 'Macaubal',
				'mendonca'        => 'Mendonça',
				'mirassol'        => 'Mirassol',
				'mirassolandia'   => 'Mirassolândia',
				'monte-aprazivel' => 'Monte Aprazível',
				'neves-paulista'  => 'Neves Paulista',
				'nipoa'           => 'Nipoã',
				'nova-alianca'    => 'Nova Aliança',
				'nova-granada'    => 'Nova Granada',
				'novo-horizonte'  => 'Novo Horizonte',
				'onda-verde'      => 'Onda Verde',
				'orindiuva'       => 'Orindiúva',
				'palestina'       => 'Palestina',
				'paulo-de-faria'  => 'Paulo de Faria',
				'poloni'          => 'Poloni',
				'potirendaba'     => 'Potirendaba',
				'sales'           => 'Sales',
				'rio-preto'       => 'São José do Rio Preto',
				'tanabi'          => 'Tanabi',
				'ubarana'         => 'Ubarana',
				'uchoa'           => 'Uchoa',
				'uniao-paulista'  => 'União Paulista',
				'urupes'          => 'Urupês',
			),
		),
		'regiao-catanduva' => array(
			'nome'    => 'Região de Catanduva',
			'cidades' => array(
				'ariranha'          => 'Ariranha',
				'catanduva'         => 'Catanduva',
				'catigua'           => 'Catiguá',
				'elisiario'         => 'Elisiário',
				'embauba'           => 'Embaúba',
				'itajobi'           => 'Itajobi',
				'marapoama'         => 'Marapoama',
				'novais'            => 'Novais',
				'palmares-paulista' => 'Palmares Paulista',
				'paraiso'           => 'Paraíso',
				'pindorama'         => 'Pindorama',
				'pirangi'           => 'Pirangi',
				'santa-adelia'      => 'Santa Adélia',
				'tabapua'           => 'Tabapuã',
			),
		),
		'regiao-votuporanga' => array(
			'nome'    => 'Região de Votuporanga',
			'cidades' => array(
				'americo-de-campos'      => 'Américo de Campos',
				'alvares-florence'       => 'Álvares Florence',
				'cardoso'                => 'Cardoso',
				'cosmorama'              => 'Cosmorama',
				'floreal'                => 'Floreal',
				'nhandeara'              => 'Nhandeara',
				'parisi'                 => 'Parisi',
				'pontes-gestal'          => 'Pontes Gestal',
				'riolandia'              => 'Riolândia',
				'sebastianopolis-do-sul' => 'Sebastianópolis do Sul',
				'valentim-gentil'        => 'Valentim Gentil',
				'votuporanga'            => 'Votuporanga',
			),
		),
		'regiao-fernandopolis' => array(
			'nome'    => 'Região de Fernandópolis',
			'cidades' => array(
				'estrela-d-oeste'          => 'Estrela d\'Oeste',
				'fernandopolis'            => 'Fernandópolis',
				'guarani-d-oeste'          => 'Guarani d\'Oeste',
				'indiapora'                => 'Indiaporã',
				'macedonia'                => 'Macedônia',
				'meridiano'                => 'Meridiano',
				'mira-estrela'             => 'Mira Estrela',
				'ouroeste'                 => 'Ouroeste',
				'pedranopolis'             => 'Pedranópolis',
				'sao-joao-das-duas-pontes' => 'São João das Duas Pontes',
				'sao-joao-de-iracema'      => 'São João de Iracema',
			),
		),
		'regiao-jales' => array(
			'nome'    => 'Região de Jales',
			'cidades' => array(
				'aparecida-d-oeste' => 'Aparecida d\'Oeste',
				'aspasia'           => 'Aspásia',
				'dirce-reis'        => 'Dirce Reis',
				'dolcinopolis'      => 'Dolcinópolis',
				'jales'             => 'Jales',
				'marinopolis'       => 'Marinópolis',
				'mesopolis'         => 'Mesópolis',
				'palmeira-d-oeste'  => 'Palmeira d\'Oeste',
				'paranapua'         => 'Paranapuã',
				'pontalinda'        => 'Pontalinda',
				'populina'          => 'Populina',
				'santa-albertina'   => 'Santa Albertina',
				'santa-salete'      => 'Santa Salete',
				'sao-francisco'     => 'São Francisco',
				'turmalina'         => 'Turmalina',
				'urania'            => 'Urânia',
				'vitoria-brasil'    => 'Vitória Brasil',
			),
		),
		'regiao-santa-fe-do-sul' => array(
			'nome'    => 'Região de Santa Fé do Sul',
			'cidades' => array(
				'nova-canaa-paulista'    => 'Nova Canaã Paulista',
				'rubineia'               => 'Rubinéia',
				'santa-clara-d-oeste'    => 'Santa Clara d\'Oeste',
				'santa-fe-do-sul'        => 'Santa Fé do Sul',
				'santa-rita-d-oeste'     => 'Santa Rita d\'Oeste',
				'santana-da-ponte-pensa' => 'Santana da Ponte Pensa',
				'tres-fronteiras'        => 'Três Fronteiras',
			),
		),
		'regiao-barretos' => array(
			'nome'    => 'Região de Barretos',
			'cidades' => array(
				'barretos'            => 'Barretos',
				'bebedouro'           => 'Bebedouro',
				'cajobi'              => 'Cajobi',
				'colina'              => 'Colina',
				'colombia'            => 'Colômbia',
				'guaira'              => 'Guaíra',
				'guaraci'             => 'Guaraci',
				'jaborandi'           => 'Jaborandi',
				'monte-azul-paulista' => 'Monte Azul Paulista',
				'olimpia'             => 'Olímpia',
				'severinia'           => 'Severínia',
				'terra-roxa'          => 'Terra Roxa',
				'viradouro'           => 'Viradouro',
			),
		),
		'regiao-aracatuba' => array(
			'nome'    => 'Região de Araçatuba',
			'cidades' => array(
				'auriflama'       => 'Auriflama',
				'gastao-vidigal'  => 'Gastão Vidigal',
				'general-salgado' => 'General Salgado',
				'guzolandia'      => 'Guzolândia',
				'magda'           => 'Magda',
				'moncoes'         => 'Monções',
				'nova-castilho'   => 'Nova Castilho',
				'nova-luzitania'  => 'Nova Luzitânia',
			),
		),
	);
}
