<?PHP
/**
 * @fileOverview For get/set/delete biz menu
 * @author zemzheng
 */

include_once(__DIR__ . '/../src/WechatSDK.php');


function checkList($target, $list){
  foreach($list as $keyname){
    if(!isset($target[$keyname])){
      return false;
    }
  }
  return true;
}
function output($msg = array('ok'=>1)){
  if(is_string($msg)){
    $msg = array('ok'=>0, 'msg'=>$msg);
  }
  echo preg_replace_callback(
    '/\\\\u([0-9a-f]{4})/i',
    create_function(
      '$matches',
      'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
    ),
    json_encode($msg)
  );
  
  exit();
}

session_start();
$appid = isset($_POST['appid']) 
  ? $_POST['appid'] 
  : isset($_SESSION['appid']) ? $_SESSION['appid'] : '';

$appsecret = isset($_POST['appsecret']) 
  ? $_POST['appsecret'] 
  : isset($_SESSION['appsecret']) ? $_SESSION['appsecret'] : '';

if(!($appid && $appsecret)){
  output('Lack of param(s)');
}

$log = function($data){
  $msg = print_r($data, true);
  $now = date('[Y-m-d H:i:s]');
  file_put_contents(__DIR__ .'/debug.log', "\n$now\t$msg", FILE_APPEND);
};



$wechat = new WeChatSDK( $appsecret, $appid, '',
  array(),
  array(
    'delMenuListResponse' => $log,
    'setMenuListResponse' => $log
  )
);

if(!isset($_POST['action'])){
  $_POST['action'] = 'get';
}


switch($_POST['action']){
  case 'get':
    output($wechat->getMenuList());
    break;
  case 'del':
    $result = $wechat->delMenuList();
    if( !(true === $result) ){
      if(!$result) $result = 'delete menu failure';
      output($result);
    }
    break;
  case 'set':
    if(!isset($_POST['json'])){
      output('Lack of param(s)');
    }
    $result = $wechat->setMenuByJson($_POST['json']);
    if( !(true === $result) ){
      if(!$result) $result = 'set menu failure';
      output($result);
    }
}

output();
