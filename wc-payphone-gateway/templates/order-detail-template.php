<?php
$styleCuerpo = "vertical-align: middle;text-align: center;padding: 1px;margin: 5px;";
$urlImagen = get_site_url() . '/wp-content/plugins/wc-payphone-gateway/assets/img/Payphone-pestania.png';
?>
<a href="https://www.payphone.app/" target="_blank" style="display: inline-block;">
  <img src='<?php echo $urlImagen ?>'>
</a>
<br /><br />
<div style='font-size: 20px;'>
  <span style='text-transform: uppercase;'>
    <?php echo __('Store', 'payphone') ?>
  </span>:
  <strong>
    <?php echo $dataTransaction->storeName ?>
  </strong>
</div>
<div style='font-size: 20px;'>
  <span style='text-transform: uppercase;'>
    <?php echo __('Pay', 'payphone') ?>
  </span>:
  <strong style='font-size: 25px;color: green;text-shadow: 2px 2px #caf389;'>
    <?php echo $dataTransaction->transactionStatus ?>
  </strong>
</div>

<br /><br />
<table <?php echo $styleTable ?>>
  <thead>
    <tr>
      <th <?php echo $styleCabezera ?>>
        <?php echo __('Order Number', 'payphone') ?>
      </th>
      <th <?php echo $styleCabezera ?>>
        <?php echo __('Date', 'payphone') ?>
      </th>
      <th <?php echo $styleCabezera ?>>
        <?php echo __('Total', 'payphone') ?>
      </th>
    </tr>
  </thead>
  <tr>
    <td style='<?php echo $styleCuerpo ?>'>
      <strong>
        <?php echo $dataTransaction->clientTransactionId ?>
      </strong>
    </td>
    <td style='<?php echo $styleCuerpo ?>border: 1px solid #000000'>
      <strong>
        <?php echo date("d/m/Y", strtotime($dataTransaction->date)) ?>
      </strong>
    </td>
    <td style='<?php echo $styleCuerpo ?>'>
      <?php echo $dataTransaction->currency ?>
      <strong style='font-size: 30px;'>
        <?php echo round(($dataTransaction->amount / 100), 2) ?>
      </strong>
    </td>
  </tr>
</table>
<br>