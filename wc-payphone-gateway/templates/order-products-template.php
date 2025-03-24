<?php

$products = $order->get_items();
$shipping = $order->get_shipping_method();
$shippingTotal = $order->get_shipping_total();
$shippingTax = $order->get_shipping_tax();
$total = $order->get_total();
$subtotal = $order->get_subtotal();
$tax = $order->get_total_tax();
$discount = $order->get_total_discount();
$fee = $order->get_total_fees();

?>
<table class="products">
  <thead>
    <tr>
      <th>
        <?php echo __('Description', "payphone") ?>
      </th>
      <th style="width:100px;">
        <?php echo __('Quant.', "payphone") ?>
      </th>
      <th style="width:100px;">
        <?php echo __('Unit Price', "payphone") ?>
      </th>
      <th style="text-align: right;width:120px">
        <?php echo __('Total', "payphone") ?>
      </th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($products as $item_id => $product) {
      $item_data = $product->get_data();
      $unit_price = $item_data['subtotal'] / $item_data['quantity'];
      ?>
    <tr class="items">
      <td>
        <?php echo substr(trim(strip_tags($item_data['name'])), 0, 50) ?>
      </td>
      <td>
        <?php echo $item_data['quantity'] ?>
      </td>
      <td>
        <?php echo $dataTransaction->currency . ' ' . number_format($unit_price, 2) ?>
      </td>
      <td style="text-align: right;">
        <?php echo $dataTransaction->currency . ' ' . number_format($item_data['subtotal'], 2) ?>
      </td>
    </tr>
    <?php } ?>
    <tr>
      <td></td>
      <td class="totals" colspan="2">
        <?php echo __('Subtotal', "payphone") ?>
      </td>
      <td class="totals" style="text-align:right;">
        <?php echo $dataTransaction->currency . ' ' . number_format($subtotal, 2) ?>
      </td>
    </tr>
    <?php if ($fee) { ?>
    <tr>
      <td></td>
      <td class="totals" colspan="2">
        <?php echo __('Fee', "payphone") ?>
      </td>
      <td class="totals" style="text-align:right;">
        <?php echo $dataTransaction->currency . ' ' . number_format($fee, 2) ?>
      </td>
    </tr>
    <?php } ?>
    <?php if ($discount) { ?>
    <tr>
      <td></td>
      <td class="totals" colspan="2">
        <?php echo __('Discount', "payphone") ?>
      </td>
      <td class="totals" style="text-align:right;color:red;">
        -<?php echo $dataTransaction->currency . ' ' . number_format($discount, 2) ?>
      </td>
    </tr>
    <?php } ?>
    <?php if (!empty($shipping)) { ?>
    <tr>
      <td></td>
      <td class="totals" colspan="2">
        <?php echo __('Shipping', "payphone") ?>
      </td>
      <td class="totals" style="text-align:right;">
        <?php echo $dataTransaction->currency . ' ' . (empty($shipping) ? number_format(0, 2) : number_format($shippingTotal, 2)) ?>
      </td>
    </tr>
    <?php } ?>
    <?php if ($tax) { ?>
    <tr>
      <td></td>
      <td class="totals" colspan="2">
        <?php echo __('Tax', "payphone") ?>
      </td>
      <td class="totals" style="text-align:right;">
        <?php echo $dataTransaction->currency . ' ' . number_format($tax, 2) ?>
      </td>
    </tr>
    <?php } ?>
    <tr>
      <td></td>
      <td class="totals" colspan="2">
        <?php echo __('Total', "payphone") ?>
      </td>
      <td class="totals" style="text-align:right;">
        <?php echo $dataTransaction->currency . ' ' . number_format($total, 2) ?>
      </td>
    </tr>
  </tbody>
</table>