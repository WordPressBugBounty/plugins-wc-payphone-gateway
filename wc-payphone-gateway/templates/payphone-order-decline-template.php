<?php
/**
 * Template Name: Pagina Virtual del error al pagar con payphone
 */

// Incluir la cabecera principal
get_header();
$urlImagen = esc_url( get_site_url() . '/wp-content/plugins/wc-payphone-gateway/assets/img/Payphone-pestania.png' );
?>
<div style="display: flex;padding:16px;">
  <div style="margin:auto; width: 850px;min-height: 500px;font-size: 24px;">
    <a href="https://www.payphone.app/" target="_blank" style="display: inline-block;">
      <img src='<?php echo $urlImagen; ?>' alt="Payphone Logo">
    </a>
    <br />
    <br />
    <?php
    // Sanitizar mensaje de error en GET
    $message = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_STRING);
    if ( ! empty($message) ) {
      echo esc_html( $message ) . '<br/>';
    }

    // Sanitizar query string manual
    $queries = array();
    parse_str(sanitize_text_field($_SERVER['QUERY_STRING']), $queries);

    if ( ! empty($queries['order']) ) {
      $order_id = intval( $queries['order'] ); // Asegurarnos que es un ID numérico
      $message = get_post_meta( $order_id, "mesaggeError", true );

      if ( ! empty($message) ) {
        echo esc_html( $message );
      }
    }
    ?>
  </div>
</div>
<?php
// Incluir el pie de pagina
get_footer();
?>
