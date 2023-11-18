<?php

class Core_Logger {
  static $logFile = '/app/data/log';

  /**
   * @param $message
   */
  public static function debug($message) {
    // Core_Logger::_message($message, 'DEBUG');
  }

  /**
   * @param $message
   */
  public static function error($message) {
    Core_Logger::_message($message . "\n\n", 'ERROR');
    // die;
  }

  /**
   * @param $message
   */
  public static function info($message) {
    // Core_Logger::_message($message, 'INFO');
  }

  /**
   * @param $message
   */
  public static function warn($message) {
    // Core_Logger::_message($message . "\n", 'WARN');
  }

  /**
   * @param $message
   * @param $type
   */
  private static function _message($message, $type) {
    // if ($type == 'ERROR') {
    //   $cmd = "mosquitto_pub -h mqtt -t 'core-logger/" . $type . "' -m '" . str_replace("'", "", $message) . "'";
    //   ob_start();
    //   system($cmd . ' 2>&1 &', $retval);
    //   ob_end_clean();
    // }

    // $finalMessage = "\n[" . date('Y-m-d H:i:s') . '] ' . $type . ' ' . exec('whoami') . ': ' . $message;
    // echo $finalMessage;
    // file_put_contents(Core_Logger::$logFile, $finalMessage, FILE_APPEND);
  }
}
