<div class="payment-title">
  <?php echo esc_html( __('Detail Payment', "payphone") ); ?>
</div>

<div class="payment-info">
  <div>
    <div>
      <?php echo esc_html( __('Payment Method', "payphone") ); ?>:
      <?php echo esc_html( sanitize_text_field( $dataTransaction->cardBrand ) ); ?>
    </div>
  </div>

  <div>
    <div>
      <?php echo esc_html( __('Transaction number', "payphone") ); ?>:
      <?php echo esc_html( sanitize_text_field( $dataTransaction->transactionId ) ); ?>
    </div>
  </div>

  <div style="text-transform: capitalize;">
    <div>
      <?php echo esc_html( $dataTransaction->optionalParameter4 ? __('Names', "payphone") : __('Client', "payphone") ); ?>:
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
      <?php echo esc_html( __('Reference', "payphone") ); ?>:
      <?php echo esc_html( sanitize_text_field( $dataTransaction->reference ) ); ?>
    </div>
  </div>
</div>
