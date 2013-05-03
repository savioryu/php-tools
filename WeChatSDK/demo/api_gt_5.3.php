<?PHP
/**
 * @author zemzheng@tencent.com
 * @fileOverview demo for php version >= 5.3
 */
$log = function($obj){
  $argvvs = func_get_args();
  error_log(print_r($argvvs, true));
};
$handleReqFuncsFuncs = array(
  'common' => $log,
  'text' => function($postObj){
    WeChatSDK::sendText(
      $postObj['from'],
      $postObj['to'],
      'Hello, it works.'
    );
    global $log;
    $log('receive text then send another');
  }
);

$log('start with ' . phpversion());
