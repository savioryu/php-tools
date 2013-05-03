<?PHP
/**
 * @fileOverview demo for callback api
 * @author zemzheng@tencent.com
 */

date_default_timezone_set('Asia/Hong_Kong');
define('zCONF', __DIR__ . '/../tmp/zemConf.php');
define('zLog', __DIR__ . '/../tmp/log' . date('Ymd') . '.log');

if(!file_exists(zCONF)) exit('Please use your own conf file');

include_once(__DIR__ . '/../src/WechatSDK.php');
include_once(zCONF);


if(preg_replace('/(\d+\.\d+).*$/', '$1', phpversion()) * 1 >= 5.3){
  include(__DIR__ . '/api_gt_5.3.php');
} else {
  include(__DIR__ . '/api_lt_5.3.php');
}

$zWeChat = new WeChatSDK(
  $APPSECRET, 
  $APPID,
  $token,
  $handleReqFuncsFuncs
);
$zWeChat->accessDataPush();

