<?PHP
ini_set('error_log', null);
echo "\n\n";
include_once( __DIR__ . '/../src/WechatSDK.php');

$url = 'http://localhost/test/res.php';

echo "Try to get [$url] \n";
print_r(WechatSDK::get($url));
echo "\n------------------------------\n\n";

$data = array(
  'a' => 1, 
  'b' => 2
);
echo "Try to post to [$url] with: \n";
print_r($data);
print_r(WechatSDK::post($url, $data));
echo "\n------------------------------\n\n";




