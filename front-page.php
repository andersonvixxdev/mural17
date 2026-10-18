<?php
/**
 * Home: o mural vivo do DDD 17 inteiro.
 *
 * Funciona com "Seus posts mais recentes" ou com uma página estática em
 * Configurações › Leitura.
 *
 * @package Mural17
 */

get_header();
get_template_part( 'template-parts/mural', null, array( 'escopo' => null ) );
get_footer();
