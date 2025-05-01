<?php
$client = sanitize_text_field( $order->get_billing_first_name() ) . ' ' . sanitize_text_field( $order->get_billing_last_name() );
$phone = sanitize_text_field( $order->get_billing_phone() );
$email = sanitize_email( $order->get_billing_email() );
$status = sanitize_text_field( $dataTransaction->transactionStatus );

switch ($dataTransaction->statusCode) {
  case 2:
    $status = __('Canceled', "payphone");
    break;
  case 3:
    $status = __('Approved', "payphone");
    break;
  default:
    break;
}

?>
<div class="header">
  <a href="https://www.payphone.app/" target="_blank" style="display: inline-block;">
    <img style="width:181px" alt="logo Payphone" src="<?php echo esc_url( WC_PAYPHONE_PLUGIN_URL . '/assets/img/logo.png' ); ?>">
  </a>
  <div class="title">
    <?php echo esc_html( sanitize_text_field( $dataTransaction->storeName ) ); ?>
  </div>
</div>

<div class="order-info <?php echo esc_attr( $dataTransaction->statusCode === 2 ? 'canceled' : '' ); ?>">
  <div>
    <span style='text-transform: uppercase;'>
      <?php echo esc_html( __('Pay', "payphone") ); ?>
    </span>:
    <?php echo esc_html( $status ); ?>
  </div>
  <div>
    <span style='text-transform: uppercase;'>
      <?php echo esc_html( __('Order', "payphone") ); ?>
    </span>#
    <?php echo esc_html( $order->get_id() ); ?>
  </div>
  <?php if ( !empty($dataTransaction->message) ) { ?>
  <span style="width:100%">
    <?php echo esc_html( sanitize_text_field( $dataTransaction->message ) ); ?>
  </span>
  <?php } ?>
</div>

<div class="order-client">
  <div>
    <div>
      <?php echo esc_html( __('Date', "payphone") ); ?>:
      <?php 
      $date = strtotime($dataTransaction->date);
      echo $date ? esc_html( date("d-m-Y H:i", $date) ) : '';
      ?>
    </div>
    <div style="text-transform: capitalize;">
      <?php echo esc_html( __('Client', "payphone") ); ?>:
      <?php echo esc_html( $client ); ?>
    </div>
  </div>
  <div>
    <div>
      <?php if ( !empty($dataTransaction->authorizationCode) ) { ?>
        <?php echo esc_html( __('Authorization', "payphone") ); ?>:
        <?php echo esc_html( sanitize_text_field( $dataTransaction->authorizationCode ) ); ?>
      <?php } ?>
    </div>
    <div>
      <?php echo esc_html( $phone ? __('Phone', "payphone") : __('Email', "payphone") ); ?>:
      <?php echo esc_html( $phone ? $phone : $email ); ?>
    </div>
  </div>
  <div style="justify-content:end;">
    <div>
      <?php echo $phone ? esc_html( __('Email', "payphone") . ':' ) : ''; ?>
      <?php echo $phone ? esc_html( $email ) : ''; ?>
    </div>
  </div>
</div>
