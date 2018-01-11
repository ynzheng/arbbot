<?php

require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../CCXTAdapter.php';

class Binance extends CCXTAdapter {

  public function __construct() {
    parent::__construct( 4, 'Binance', 'binance' );
  }

  public function isMarketActive( $market ) {
    return $market[ 'info' ][ 'status' ] == 'TRADING';
  }

  public function checkAPIReturnValue( $result ) {
    if ( isset( $result[ 'info' ][ 'code' ] ) ) {
      return false;
    }
    return $result[ 'info' ][ 'success' ] === true;
  }

  private $lastStuckReportTime = [ ];

  public function detectStuckTransfers() {

    // TODO
    $history = $this->queryDepositsAndWithdrawals();
    foreach ( $history as $key => $block ) {
      foreach ( $block as $entry ) {
        $timestamp = $entry[ 'timestamp' ];
        if ( key_exists( $key, $this->lastStuckReportTime ) && $timestamp < $this->lastStuckReportTime[ $key ] ) {
          continue;
        }
        $status = strtoupper( $entry[ 'status' ] );

        if ( $timestamp < time() - 12 * 3600 && (substr( $status, 0, 8 ) != 'COMPLETE' || strpos( $status, 'ERROR' ) !== false) ) {
          alert( 'stuck-transfer', $this->prefix() . "Stuck $key! Please investigate and open support ticket if neccessary!\n\n" . print_r( $entry, true ), true );
          $this->lastStuckReportTime[ $key ] = $timestamp;
        }
      }
    }

  }

  public function getWalletsConsideringPendingDeposits() {

    // TODO
    $result = [ ];
    foreach ( $this->wallets as $coin => $balance ) {
      $result[ $coin ] = $balance;
    }
    $history = $this->queryDepositsAndWithdrawals();

    foreach ( $history[ 'deposits' ] as $entry ) {

      $status = strtoupper( $entry[ 'status' ] );
      if ($status != 'PENDING') {
        continue;
      }

      $coin = strtoupper( $entry[ 'currency' ] );
      $amount = $entry[ 'amount' ];
      $result[ $coin ] += $amount;

    }

    return $result;

  }

};
