<?php
/**
 * Template Name: Pagina Virtual del resultado de la transaccion de Payphone
 */

// Incluir la cabecera principal
get_header();


// Obtener el ID de la order
// Obtener los datos completos de la orden
$order_id = get_query_var('order-id');
$order = new WC_Order($order_id);

$showTransactionPayphone = $order->get_meta('showTransactionPayphone');

if ($showTransactionPayphone) {
  echo "<script>location.href = '" . get_site_url() . "'</script>";
}

?>
<div style="display: flex;padding:16px;">
  <div style="margin:auto; width: 850px;">
    <?php

    //Validar si la order fallo
    if ($order->has_status('failed')) {
      wc_get_template('templates/order-failed-template.php', array('order' => $order), '', PAYPHONE_PATH);
    } else {
      //Obtener los datos adicionales de la orden
      $dataTransaction = $order->get_meta('DataPayphone');

      if ($dataTransaction != "") {
        $dataTransaction = json_decode($dataTransaction);
        $styleCabezera = "style='background-color: #ff7300 !important;box-shadow: 0 4px 15px #c85b02;text-transform: uppercase;height: 40px;color:#000;text-align:center;font-weight: bold;padding:0'";
        $styleTable = "style='border-spacing: 0;width: 100%;border-collapse: separate;'";

        //Template del detalle general de la orden
        wc_get_template(
          'templates/order-detail-template.php',
          array(
            'dataTransaction' => $dataTransaction,
            'styleCabezera' => $styleCabezera,
            'styleTable' => $styleTable
          ),
          '',
          PAYPHONE_PATH
        );

        //Template del detalle del pago
        wc_get_template(
          'templates/order-payment-detail-template.php',
          array('dataTransaction' => $dataTransaction),
          '',
          PAYPHONE_PATH
        );

        //Template de los productos
        wc_get_template(
          'templates/order-products-template.php',
          array(
            'dataTransaction' => $dataTransaction,
            'styleCabezera' => $styleCabezera,
            'styleTable' => $styleTable,
            'products' => $order->get_items(),
            "shipping" => $order->get_shipping_method(),
            "shippingTotal" => $order->get_shipping_total(),
            "shippingTax" => $order->get_shipping_tax()

          ),
          '',
          PAYPHONE_PATH
        );
        $order->update_meta_data('showTransactionPayphone', 'ready');
        $order->save();
      } else {
        echo "<script>location.href = '" . get_site_url() . "'</script>";
      }
    }
    ?>
  </div>
</div>
<?php
// Incluir el pie de pagina
get_footer();
?>