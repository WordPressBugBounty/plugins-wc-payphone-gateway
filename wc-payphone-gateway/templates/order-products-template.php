<h4 style="text-transform: uppercase;">
  <?php echo __('Order Details', 'payphone') ?>
</h4>
<table <?php echo $styleTable ?>>
  <thead class='thead-dark'>
    <tr>
      <th <?php echo $styleCabezera ?>>
        <?php echo __('Product', 'payphone') ?>
      </th>
      <th <?php echo $styleCabezera ?>>
        <?php echo __('Amount', 'payphone') ?>
      </th>
      <th <?php echo $styleCabezera ?>>
        <?php echo __('Price', 'payphone') . ' ' . $dataTransaction->currency ?>
      </th>
    </tr>
  </thead>
  <?php foreach ($products as $product) {
    $item_data = $product->get_data();
    ?>
    <tr>
      <td style="padding:8px 16px;">
        <?php echo substr(trim(strip_tags($item_data['name'])), 0, 50) ?>
      </td>
      <td style="width: 200px;text-align: center;padding:8px 16px;">x
        <?php echo $item_data['quantity'] ?>
      </td>
      <td style="width: 200px;text-align: right;padding:8px 16px;">
        <?php echo round(round(($item_data['total'] + $item_data['total_tax']), 2), 2) ?>
      </td>
    </tr>
  <?php } ?>
  <?php if (!empty($shipping)) { ?>
    <tr>
      <td colspan="2" style="text-align: right;">
        <b>
          <?php echo substr(trim(strip_tags($shipping)), 0, 50) ?>
        </b>
      </td>
      <td style="width: 200px;text-align: right;padding:8px 16px;">
        <?php echo round(round(($shippingTotal + $shippingTax), 2), 2) ?>
      </td>
    </tr>
  <?php } ?>

</table><br><br>