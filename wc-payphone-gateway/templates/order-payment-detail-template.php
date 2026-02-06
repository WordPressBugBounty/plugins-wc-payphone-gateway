<?php
  if ( !defined( 'ABSPATH' ) ) {
    exit;
  }
?>
<div class="payment-title">
  <?php echo esc_html__('Detail Payment', 'wc-payphone-gateway'); ?>
</div>

<div class="payment-info">
  <div>
    <div>
      <?php echo esc_html__('Payment Method', 'wc-payphone-gateway'); ?>:
      <?php echo esc_html( sanitize_text_field( $dataTransaction->cardBrand ) ); ?>
    </div>
  </div>

  <div>
    <div>
      <?php echo esc_html__('Transaction number', 'wc-payphone-gateway'); ?>:
      <?php echo esc_html( sanitize_text_field( $dataTransaction->transactionId ) ); ?>
    </div>
  </div>

  <div style="text-transform: capitalize;">
    <div>
      <?php echo esc_html( $dataTransaction->optionalParameter4 ? __('Names', 'wc-payphone-gateway') : __('Client', 'wc-payphone-gateway') ); ?>:
      <?php
      if ( !empty( $dataTransaction->optionalParameter4 ) ) {
          echo esc_html( strtolower( sanitize_text_field( $dataTransaction->optionalParameter4 ) ) );
      } else {
          echo esc_html( sanitize_text_field( $dataTransaction->phoneNumber ) );
      }
      ?>
    </div>
  </div>

  <div>
    <div>
      <?php echo esc_html__('Reference', 'wc-payphone-gateway'); ?>:
      <?php echo esc_html( sanitize_text_field( $dataTransaction->reference ) ); ?>
    </div>
  </div>
</div>
