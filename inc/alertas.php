<?php
/**
 * Alertas: avisos meteorológicos do INMET para as cidades do 17, alerta
 * manual da redação (Defesa Civil, por exemplo), previsão do tempo e
 * estoque de sangue do hemocentro.
 *
 * Os avisos do INMET são lidos a cada 30 minutos pelo agendador do
 * WordPress e guardados no banco; as páginas só leem o que está guardado.
 *
 * @package Mural17
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Níveis de alerta, na ordem de gravidade.
 *
 * @return array<string, array{nome:string,rotulo:string}>
 */
function mural17_niveis_alerta() {
	return array(
		'amarelo'  => array(
			'nome'   => 'Perigo potencial',
			'rotulo' => 'Atenção',
		),
		'laranja'  => array(
			'nome'   => 'Perigo',
			'rotulo' => 'Perigo',
		),
		'vermelho' => array(
			'nome'   => 'Grande perigo',
			'rotulo' => 'Grande perigo',
		),
	);
}

/**
 * Texto comparável: sem acento, minúsculo, só letras e números.
 *
 * @param string $s Texto.
 * @return string
 */
function mural17_normaliza( $s ) {
	$s = strtolower( remove_accents( (string) $s ) );
	return trim( preg_replace( '/[^a-z0-9]+/', ' ', $s ) );
}

/**
 * Mapa nome normalizado => ID das 117 cidades.
 *
 * @return array<string, int>
 */
function mural17_mapa_nomes_cidades() {
	static $mapa = null;
	if ( null === $mapa ) {
		$mapa = array();
		foreach ( mural17_lista_cidades() as $grupo ) {
			foreach ( $grupo['cidades'] as $c ) {
				$mapa[ mural17_normaliza( $c->name ) ] = (int) $c->term_id;
			}
		}
	}
	return $mapa;
}

/**
 * Lê os avisos ativos do INMET e guarda os que pegam cidades do 17.
 *
 * @return bool Conseguiu ler.
 */
function mural17_inmet_atualiza() {
	$url = apply_filters( 'mural17_inmet_url', 'https://apiprevmet3.inmet.gov.br/avisos/ativos' );
	$r   = wp_remote_get(
		$url,
		array(
			'timeout' => 12,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
		update_option( 'mural17_inmet_erro', is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $r ), false );
		return false;
	}
	$dados = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( ! is_array( $dados ) ) {
		update_option( 'mural17_inmet_erro', 'Resposta sem JSON', false );
		return false;
	}
	$lista = array();
	foreach ( array( 'hoje', 'futuro' ) as $grupo ) {
		foreach ( isset( $dados[ $grupo ] ) && is_array( $dados[ $grupo ] ) ? $dados[ $grupo ] : array() as $a ) {
			$aviso = mural17_inmet_le_aviso( $a );
			if ( $aviso ) {
				$lista[ $aviso['id'] ] = $aviso;
			}
		}
	}
	update_option(
		'mural17_inmet',
		array(
			'avisos' => array_values( $lista ),
			'em'     => time(),
		),
		false
	);
	delete_option( 'mural17_inmet_erro' );
	mural17_avisa_alertas_novos( array_values( $lista ) );
	return true;
}

/**
 * Um aviso do INMET no formato do site, ou null se não pega o 17.
 *
 * @param array $a Aviso como vem do INMET.
 * @return array|null
 */
function mural17_inmet_le_aviso( $a ) {
	if ( ! is_array( $a ) ) {
		return null;
	}
	$municipios = isset( $a['municipios'] ) ? $a['municipios'] : '';
	$municipios = is_array( $municipios ) ? implode( ', ', $municipios ) : (string) $municipios;
	$mapa       = mural17_mapa_nomes_cidades();
	$cidades    = array();
	// "Mirassol - SP (3530300), Jaci - SP (3524907), ..."
	if ( preg_match_all( '/([^,;()]+?)\s*-\s*SP\b/i', $municipios, $m ) ) {
		foreach ( $m[1] as $nome ) {
			$n = mural17_normaliza( $nome );
			if ( isset( $mapa[ $n ] ) ) {
				$cidades[] = $mapa[ $n ];
			}
		}
	}
	$cidades = array_values( array_unique( $cidades ) );
	if ( ! $cidades ) {
		return null;
	}

	$sev   = mural17_normaliza( isset( $a['severidade'] ) ? $a['severidade'] : '' );
	$nivel = false !== strpos( $sev, 'grande' ) ? 'vermelho' : ( false !== strpos( $sev, 'potencial' ) ? 'amarelo' : 'laranja' );

	$quando = function ( $iso, $data, $hora ) use ( $a ) {
		if ( ! empty( $a[ $iso ] ) ) {
			$d = date_create( (string) $a[ $iso ] );
			if ( $d ) {
				return $d->setTimezone( wp_timezone() )->format( 'Y-m-d H:i' );
			}
		}
		if ( ! empty( $a[ $data ] ) ) {
			$d = mural17_data( $a[ $data ] . ' ' . ( ! empty( $a[ $hora ] ) ? substr( (string) $a[ $hora ], 0, 5 ) : '00:00' ) );
			return $d ? $d->format( 'Y-m-d H:i' ) : '';
		}
		return '';
	};
	$lista  = function ( $v ) {
		if ( is_array( $v ) ) {
			return array_values( array_filter( array_map( 'sanitize_text_field', $v ) ) );
		}
		$v = trim( (string) $v );
		return '' === $v ? array() : array_values( array_filter( array_map( 'trim', preg_split( '/\R|;\s+|\.\s+(?=[A-ZÀ-Ú])/u', sanitize_textarea_field( $v ) ) ) ) );
	};

	$inicio = $quando( 'inicio', 'data_inicio', 'hora_inicio' );
	$fim    = $quando( 'fim', 'data_fim', 'hora_fim' );
	return array(
		'id'         => 'inmet-' . ( isset( $a['id'] ) ? sanitize_key( (string) $a['id'] ) : md5( wp_json_encode( $a ) ) ),
		'fonte'      => 'INMET',
		'evento'     => sanitize_text_field( isset( $a['descricao'] ) ? $a['descricao'] : 'Aviso meteorológico' ),
		'nivel'      => $nivel,
		'inicio'     => $inicio,
		'fim'        => $fim,
		'riscos'     => $lista( isset( $a['riscos'] ) ? $a['riscos'] : '' ),
		'instrucoes' => $lista( isset( $a['instrucoes'] ) ? $a['instrucoes'] : '' ),
		'cidades'    => $cidades,
	);
}

/**
 * Alerta da redação (Defesa Civil, falta de energia geral...), guardado
 * em Serviços › Alertas e sangue.
 *
 * @return array|null
 */
function mural17_alerta_manual() {
	$a = get_option( 'mural17_alerta_manual' );
	if ( ! is_array( $a ) || empty( $a['ativo'] ) || empty( $a['evento'] ) ) {
		return null;
	}
	if ( ! empty( $a['fim'] ) && $a['fim'] <= mural17_agora() ) {
		return null;
	}
	$cidades = array_map( 'intval', isset( $a['cidades'] ) ? (array) $a['cidades'] : array() );
	if ( ! $cidades ) {
		$cidades = array_values( mural17_mapa_nomes_cidades() );
	}
	return array(
		'id'         => 'manual-' . ( isset( $a['versao'] ) ? (int) $a['versao'] : 1 ),
		'fonte'      => ! empty( $a['fonte'] ) ? $a['fonte'] : 'Redação do Mural 17',
		'evento'     => $a['evento'],
		'nivel'      => isset( mural17_niveis_alerta()[ $a['nivel'] ?? '' ] ) ? $a['nivel'] : 'laranja',
		'inicio'     => isset( $a['inicio'] ) ? $a['inicio'] : '',
		'fim'        => isset( $a['fim'] ) ? $a['fim'] : '',
		'riscos'     => array_filter( array( isset( $a['texto'] ) ? $a['texto'] : '' ) ),
		'instrucoes' => array_values( array_filter( array_map( 'trim', preg_split( '/\R/', isset( $a['instrucoes'] ) ? (string) $a['instrucoes'] : '' ) ) ) ),
		'cidades'    => $cidades,
	);
}

/**
 * Alertas valendo agora (ou que começam nas próximas 24h), do mais grave
 * para o mais leve. Com cidade, só os que pegam a cidade.
 *
 * @param WP_Term|null $cidade Cidade.
 * @return array[]
 */
function mural17_alertas_ativos( $cidade = null ) {
	$guardado = get_option( 'mural17_inmet' );
	$lista    = is_array( $guardado ) && isset( $guardado['avisos'] ) ? $guardado['avisos'] : array();
	$manual   = mural17_alerta_manual();
	if ( $manual ) {
		array_unshift( $lista, $manual );
	}
	$agora  = mural17_agora();
	$limite = mural17_agora( '+24 hours' );
	$ordem  = array_flip( array_keys( mural17_niveis_alerta() ) );
	$lista  = array_values(
		array_filter(
			$lista,
			function ( $a ) use ( $agora, $limite, $cidade ) {
				if ( ! empty( $a['fim'] ) && $a['fim'] <= $agora ) {
					return false;
				}
				if ( ! empty( $a['inicio'] ) && $a['inicio'] > $limite ) {
					return false;
				}
				return ! $cidade || in_array( (int) $cidade->term_id, array_map( 'intval', $a['cidades'] ), true );
			}
		)
	);
	usort(
		$lista,
		function ( $a, $b ) use ( $ordem ) {
			return array( $ordem[ $b['nivel'] ] ?? 0, $a['fim'] ) <=> array( $ordem[ $a['nivel'] ] ?? 0, $b['fim'] );
		}
	);
	return $lista;
}

/**
 * Período do alerta em texto: "até 22h", "de 14h de hoje até 12h de amanhã".
 *
 * @param array $a Alerta.
 * @return string
 */
function mural17_periodo_alerta( $a ) {
	$ini = mural17_data( $a['inicio'] );
	$fim = mural17_data( $a['fim'] );
	if ( ! $fim ) {
		return '';
	}
	$dia_fim = mural17_dia( $fim, true );
	$txt_fim = ( 'Hoje' === $dia_fim ? '' : strtolower( $dia_fim ) . ', ' ) . mural17_hora( $fim );
	if ( $ini && $ini->format( 'Y-m-d H:i' ) > mural17_agora() ) {
		$dia_ini = mural17_dia( $ini, true );
		return 'de ' . ( 'Hoje' === $dia_ini ? 'hoje' : strtolower( $dia_ini ) ) . ', ' . mural17_hora( $ini ) . ', até ' . $txt_fim;
	}
	return 'até ' . $txt_fim;
}

/**
 * Faixa de alerta no topo de todas as páginas (o mais grave do 17). O
 * navegador troca "em 24 cidades" pela cidade escolhida, se ela estiver
 * no alerta.
 */
function mural17_faixa_alerta() {
	$lista = mural17_alertas_ativos();
	if ( ! $lista ) {
		return;
	}
	$a     = $lista[0];
	$qtd   = count( $a['cidades'] );
	$nivel = mural17_niveis_alerta()[ $a['nivel'] ];
	?>
	<div class="faixa-alerta faixa-alerta--<?php echo esc_attr( $a['nivel'] ); ?>" role="region" aria-label="Alerta">
		<div class="wrap faixa-alerta__in">
			<span class="faixa-alerta__nivel"><?php echo esc_html( $nivel['rotulo'] ); ?></span>
			<p class="faixa-alerta__texto">
				<b><?php echo esc_html( $a['evento'] ); ?></b>
				<span data-alerta-cidades="<?php echo esc_attr( implode( ',', $a['cidades'] ) ); ?>" data-alerta-qtd="<?php echo (int) $qtd; ?>"><?php echo esc_html( 1 === $qtd ? 'em ' . get_term( $a['cidades'][0], 'cidade' )->name : 'em ' . $qtd . ' cidades do 17' ); ?></span>
				<?php echo esc_html( mural17_periodo_alerta( $a ) ); ?> · <?php echo esc_html( $a['fonte'] ); ?>
			</p>
			<a class="faixa-alerta__link" href="<?php echo esc_url( mural17_servicos()['alertas']['url'] ); ?>">O que fazer</a>
		</div>
	</div>
	<?php
}

/**
 * Manda e-mail dos alertas novos de perigo e grande perigo (uma vez por aviso).
 *
 * @param array[] $lista Alertas.
 */
function mural17_avisa_alertas_novos( $lista ) {
	$ja   = (array) get_option( 'mural17_alertas_avisados', array() );
	$novo = false;
	foreach ( $lista as $a ) {
		if ( in_array( $a['id'], $ja, true ) || 'amarelo' === $a['nivel'] || ( ! empty( $a['fim'] ) && $a['fim'] <= mural17_agora() ) ) {
			continue;
		}
		$ja[]  = $a['id'];
		$novo  = true;
		$texto = mural17_niveis_alerta()[ $a['nivel'] ]['nome'] . ': ' . $a['evento'] . ' ' . mural17_periodo_alerta( $a ) . '.';
		if ( $a['riscos'] ) {
			$texto .= "\n\n" . implode( "\n", $a['riscos'] );
		}
		if ( $a['instrucoes'] ) {
			$texto .= "\n\nO que fazer:\n- " . implode( "\n- ", array_slice( $a['instrucoes'], 0, 5 ) );
		}
		$texto .= "\n\nEmergência: Defesa Civil 199 · Bombeiros 193\nFonte: " . $a['fonte'];
		mural17_avisa_inscritos( 'alertas', $a['cidades'], 'Alerta: ' . $a['evento'], $texto, mural17_servicos()['alertas']['url'] );
	}
	if ( $novo ) {
		update_option( 'mural17_alertas_avisados', array_slice( $ja, -200 ), false );
	}
}

/**
 * Intervalo de 30 minutos no agendador.
 *
 * @param array $s Intervalos.
 * @return array
 */
function mural17_intervalos( $s ) {
	$s['mural17_30min'] = array(
		'interval' => 30 * MINUTE_IN_SECONDS,
		'display'  => 'A cada 30 minutos (Mural 17)',
	);
	return $s;
}
add_filter( 'cron_schedules', 'mural17_intervalos' );

/**
 * Agenda as leituras automáticas (INMET a cada 30 min, ANP 2x por dia).
 */
function mural17_agenda_leituras() {
	if ( ! wp_next_scheduled( 'mural17_le_inmet' ) ) {
		wp_schedule_event( time() + 60, 'mural17_30min', 'mural17_le_inmet' );
	}
	if ( ! wp_next_scheduled( 'mural17_le_anp' ) ) {
		wp_schedule_event( time() + 120, 'twicedaily', 'mural17_le_anp' );
	}
}
add_action( 'init', 'mural17_agenda_leituras' );
add_action( 'mural17_le_inmet', 'mural17_inmet_atualiza' );

/**
 * Tira as leituras do agendador quando o tema sai.
 */
function mural17_desagenda_leituras() {
	wp_clear_scheduled_hook( 'mural17_le_inmet' );
	wp_clear_scheduled_hook( 'mural17_le_anp' );
}
add_action( 'switch_theme', 'mural17_desagenda_leituras' );

/* -------------------------------------------------------------------------
 * Previsão do tempo (Open-Meteo, sem chave)
 * ---------------------------------------------------------------------- */

/**
 * Descrição curta do código de tempo da OMM.
 *
 * @param int $c Código.
 * @return string
 */
function mural17_tempo_texto( $c ) {
	$c = (int) $c;
	if ( 0 === $c ) {
		return 'Céu limpo';
	}
	if ( $c <= 2 ) {
		return 'Poucas nuvens';
	}
	if ( 3 === $c ) {
		return 'Nublado';
	}
	if ( $c <= 48 ) {
		return 'Neblina';
	}
	if ( $c <= 57 ) {
		return 'Garoa';
	}
	if ( $c <= 67 || ( $c >= 80 && $c <= 82 ) ) {
		return 'Chuva';
	}
	if ( $c >= 95 ) {
		return 'Tempestade';
	}
	return 'Instável';
}

/**
 * Tempo agora e próximos 5 dias de uma cidade (guardado por 1 hora).
 *
 * @param WP_Term $cidade Cidade.
 * @return array|null
 */
function mural17_tempo( $cidade ) {
	$coords = mural17_coordenadas();
	if ( ! $cidade || ! isset( $coords[ $cidade->slug ] ) ) {
		return null;
	}
	$chave = 'mural17_tempo_' . $cidade->term_id;
	$cache = get_transient( $chave );
	if ( false !== $cache ) {
		return $cache ? $cache : null;
	}
	$url = add_query_arg(
		array(
			'latitude'      => $coords[ $cidade->slug ][0],
			'longitude'     => $coords[ $cidade->slug ][1],
			'current'       => 'temperature_2m,weather_code,relative_humidity_2m',
			'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
			'timezone'      => 'America/Sao_Paulo',
			'forecast_days' => 6,
		),
		'https://api.open-meteo.com/v1/forecast'
	);
	$r   = wp_remote_get( $url, array( 'timeout' => 5 ) );
	$d   = is_wp_error( $r ) ? null : json_decode( wp_remote_retrieve_body( $r ), true );
	if ( ! is_array( $d ) || empty( $d['current'] ) || empty( $d['daily']['time'] ) ) {
		set_transient( $chave, 0, 15 * MINUTE_IN_SECONDS );
		return null;
	}
	$dias = array();
	foreach ( $d['daily']['time'] as $i => $data ) {
		$dias[] = array(
			'data'  => $data,
			'max'   => (int) round( $d['daily']['temperature_2m_max'][ $i ] ),
			'min'   => (int) round( $d['daily']['temperature_2m_min'][ $i ] ),
			'chuva' => (int) ( $d['daily']['precipitation_probability_max'][ $i ] ?? 0 ),
			'cod'   => (int) $d['daily']['weather_code'][ $i ],
		);
	}
	$saida = array(
		'agora'   => (int) round( $d['current']['temperature_2m'] ),
		'umidade' => (int) ( $d['current']['relative_humidity_2m'] ?? 0 ),
		'cod'     => (int) $d['current']['weather_code'],
		'dias'    => $dias,
	);
	set_transient( $chave, $saida, HOUR_IN_SECONDS );
	return $saida;
}

/* -------------------------------------------------------------------------
 * Estoque de sangue
 * ---------------------------------------------------------------------- */

/**
 * Tipos sanguíneos na ordem do quadro.
 *
 * @return string[]
 */
function mural17_tipos_sangue() {
	return array( 'O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+' );
}

/**
 * Estados do estoque.
 *
 * @return array<string, string>
 */
function mural17_estados_sangue() {
	return array(
		'critico' => 'Crítico',
		'alerta'  => 'Alerta',
		'estavel' => 'Estável',
	);
}

/**
 * Estoque guardado: [tipos => estado, local, em, link], ou null.
 *
 * @return array|null
 */
function mural17_sangue() {
	$s = get_option( 'mural17_sangue' );
	if ( ! is_array( $s ) || empty( $s['tipos'] ) || ! array_filter( $s['tipos'] ) ) {
		return null;
	}
	return $s;
}
