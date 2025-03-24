<div class="payment-title">
  <?php echo __('Detail Payment', "payphone") ?>
</div>
<div class="payment-info">
  <div>
    <div>
      <?php echo __('Payment Method', "payphone") ?>:
      <?php echo $dataTransaction->cardBrand ?>
    </div>
  </div>
  <div>
    <div>
      <?php echo __('Transaction number', "payphone") ?>:
      <?php echo $dataTransaction->transactionId ?>
    </div>
  </div>
  <div style="text-transform: capitalize;">
    <div>
      <?php echo $dataTransaction->optionalParameter4 ? __('Names', "payphone") : __('Client', "payphone") ?>:
      <?php echo $dataTransaction->optionalParameter4 ? strtolower($dataTransaction->optionalParameter4) : $dataTransaction->phoneNumber ?>
    </div>
  </div>
  <div>
    <div>
      <?php echo __('Reference', "payphone") ?>:
      <?php echo $dataTransaction->reference ?>
    </div>
  </div>
</div>