<?php
/**
 * Template Name: Pagina Virtual del resultado de la transaccion de Payphone
 */

if ( !defined( 'ABSPATH' ) ) {
  exit;
}

(function() {

  // Cargar menu
  wc_get_template('templates/order-navbar-template.php', [], '', PAYPHONE_G_BTN_PLUGIN_PATH);

  $payphone_g_btn_redirectHome = "<script>location.href = '" . esc_url( get_site_url() ) . "'</script>";

  // Obtener el ID de la orden y sanitizarlo
  $order_id = absint( get_query_var('order-id') );
  if ( $order_id ) {
    try {
      $order = wc_get_order( $order_id );

      // Validar que realmente sea una orden
      if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $payphone_g_btn_redirectHome;
        exit;
      }

      $showTransactionPayphone = $order->get_meta('showTransactionPayphone', true);

      if ( !empty($showTransactionPayphone) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $payphone_g_btn_redirectHome;
        exit;
      }
    } catch ( \Throwable $th ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $payphone_g_btn_redirectHome;
      exit;
    }
  } else {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $payphone_g_btn_redirectHome;
    exit;
  }
  ?>

  <div class="wp-site-blocks ppbo-order">
    <main>
      <div class="content">
        <?php
        // Validar si la orden falló
        if ( $order->has_status('failed') ) {
          wc_get_template('templates/order-failed-template.php', array('order' => $order), '', PAYPHONE_G_BTN_PLUGIN_PATH);
        } else {
          // Obtener los datos adicionales de la orden
          $dataTransaction = $order->get_meta('DataPayphone', true);

          if ( ! empty($dataTransaction) ) {
            $dataTransaction = json_decode($dataTransaction);

            // Templates
            wc_get_template('templates/order-header-template.php', array('dataTransaction' => $dataTransaction, 'order' => $order), '', PAYPHONE_G_BTN_PLUGIN_PATH);
            wc_get_template('templates/order-products-template.php', array('dataTransaction' => $dataTransaction, 'order' => $order), '', PAYPHONE_G_BTN_PLUGIN_PATH);
            wc_get_template('templates/order-payment-detail-template.php', array('dataTransaction' => $dataTransaction), '', PAYPHONE_G_BTN_PLUGIN_PATH);
            wc_get_template('templates/order-footer-template.php', [], '', PAYPHONE_G_BTN_PLUGIN_PATH);

            // Marcar que fue visto el detalle de la transacción
            $order->update_meta_data('showTransactionPayphone', 'ready');
            $order->save();
          } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $payphone_g_btn_redirectHome;
            exit;
          }
        }
        ?>
      </div>
    </main>
  </div>

  <?php
  // Incluir el pie de pagina
  get_footer();

})();
