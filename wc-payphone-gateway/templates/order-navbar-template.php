<?php
if ( !defined( 'ABSPATH' ) ) {
  exit;
}
if ( wp_is_block_theme() ) {
  // Tema basado en bloques (FSE)
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks() outputs safe, sanitized block content.
  echo do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->' );
  
  // Cargar estilos
  wp_enqueue_style( 'global-styles' );
  wp_head();
} else {
  // Tema clásico
  get_header();
}
