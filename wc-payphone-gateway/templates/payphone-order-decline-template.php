<?php
/**
 * Template Name: Pagina Virtual del error al pagar con payphone
 */

// Incluir la cabecera principal
get_header();
$urlImagen = get_site_url() . '/wp-content/plugins/wc-payphone-gateway/assets/img/Payphone-pestania.png';
?>
<div style="display: flex;padding:16px;">
  <div style="margin:auto; width: 850px;min-height: 500px;font-size: 24px;">
    <a href="https://www.payphone.app/" target="_blank" style="display: inline-block;">
      <img src='<?php echo $urlImagen ?>'>
    </a>
    <br />
    <br />
    <?php
    $message = filter_input(INPUT_GET, 'error');
    if ($message) {
      echo $message . '<br/>';
    }

    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);

    if (array_key_exists("order", $queries)) {
      $message = get_post_meta($queries['order'], "mesaggeError", true);
      if ($message) {
        echo $message;
      }
    }

    ?>
  </div>
</div>
<?php
// Incluir el pie de pagina
get_footer();