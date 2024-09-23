<h4 style="text-transform: uppercase;">
  <?php echo __('Detail Payment', 'payphone') ?>
</h4>
<table>
  <tr>
    <td style="text-transform: uppercase;">
      <?php echo __('Payment Method', 'payphone') ?>:
    </td>
    <td style="padding-left:24px"><strong>
        <?php echo $dataTransaction->cardBrand ?>
      </strong></td>
  <tr>

  <tr>
    <td style="text-transform: uppercase;">
      <?php echo __('Transaction number', 'payphone') ?>:
    </td>
    <td style="padding-left:24px"><strong>
        <?php echo $dataTransaction->transactionId ?>
      </strong></td>
  <tr>
  <tr>
    <td style="text-transform: uppercase;">
      <?php echo __('Names', 'payphone') ?>:
    </td>
    <td style="padding-left:24px"><strong>
        <?php echo $dataTransaction->optionalParameter4 ?>
      </strong></td>
  <tr>

  <tr>
    <td style="text-transform: uppercase;">
      <?php echo __('Mail', 'payphone') ?>:
    </td>
    <td style="padding-left:24px"><strong>
        <?php echo $dataTransaction->optionalParameter2 ?>
      </strong></td>
  <tr>

  <tr>
    <td style="text-transform: uppercase;">
      <?php echo __('Reference', 'payphone') ?>:
    </td>
    <td style="padding-left:24px"><strong>
        <?php echo $dataTransaction->reference ?>
      </strong></td>
  <tr>
</table>