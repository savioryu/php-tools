<?PHP
/**
 * @author zemzheng@tencent.com
 * @fileOverview demo for php version < 5.3
 */
abstract class Handler{
  static public function text($postObj){
    WeChatSDK::sendText(
      $postObj['from'],
      $postObj['to'],
      'Hello, it works.'
    );
    self::common('receive text then send another');
  }
  static public function common(){
    $argvvs = func_get_args();
    error_log(print_r($argvvs, true));
  }
}

$handleReqFuncsFuncs = array(
  'common' => 'Handler::common',
  'text'   => 'Handler::text',
);

Handler::common('start with ' . phpversion());
