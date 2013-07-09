<?PHP
/**
 * @author zemzheng@tencent.com / zemzheng@gmail.com
 * @fileOverview WeChat Callback SDK
 * @version 0.5
 */

/**
 * @class WeChatSDK
 * @description WeChat Callback API SDK
 */
class WeChatSDK{
  # {{{ SETTING ==========================================================
  private static $_api_url_root = 'https://api.weixin.qq.com';
  private static $_errList = array(
    ## en_US
    ## http://admin.wechat.com/wiki/index.php?title=Return_Codes
    '-1' => 'System busy', '0' => 'Request succeeded', '40001' => 'Verification failed', '40002' => 'Invalid certificate type', '40003' => 'Invalid OPEN ID', '40004' => 'Invalid media file type', '40005' => 'Invalid file type', '40006' => 'Invalid file size', '40007' => 'Invalid media file ID', '40008' => 'Invalid message type', '40009' => 'Invalid image file size', '40010' => 'Invalid audio file size', '40011' => 'Invalid video file size', '40012' => 'Invalid thumbnail file size', '40013' => 'Invalid APP ID', '41001' => 'Parameter missing: access_token', '41002' => 'Parameter missing: appid', '41003' => 'Parameter missing: refresh_token', '41004' => 'Parameter missing: secret', '41005' => 'Multimedia file data missing', '41006' => 'Parameter missing: media_id', '42001' => 'access_token timed out', '43001' => 'GET request required', '43002' => 'POST request required', '43003' => 'HTTPS request required', '44001' => 'Multimedia file is empty', '44002' => 'POST package is empty', '44003' => 'Rich media message is empty', '45001' => 'Error source: multimedia file size', '45002' => 'Message contents too long', '45003' => 'Title too long', '45004' => 'Description too long', '45005' => 'URL too long', '45006' => 'Image URL too long', '45007' => 'Audio play time over limit', '45008' => 'Rich media messages over limit', '45009' => 'Error source: interface call', '46001' => 'Media data missing', '47001' => 'Error while extracting JSON/XML contents'
  );
  # / SETTING }}} ========================================================

  # {{{ METHODS ==========================================================

  /**
   *
   * @method __construct
   * @param {String} $APPSECRET           : APPSECRET
   * @param {String} $APPID               : APPID
   * @param {String} $_token              : The Token set by YOU
   * @param {Array}  $_handleReqFuncs      : [Selectable] Functions, for handle Request
   *  <pre>
   *    array(
   *      'common'      => function($postObj){},  # Handler for all data/event push request. [handleReqFuncs::common]
   *
   *        'text'        => function($postObj){},  # Handler for text           push request. [handleReqFuncs::text]
   *        'image'       => function($postObj){},  # Handler for image          push request. [handleReqFuncs::image]
   *        'location'    => function($postObj){},  # Handler for location       push request. [handleReqFuncs::location]
   *        'link'        => function($postObj){},  # Handler for link           push request. [handleReqFuncs::link]
   *        'event'       => function($postObj){},  # Handler for event          push request. [handleReqFuncs::event]
   *
   *      'accessCheck' => function(){},          # Handler for CallBack API Verification [handleReqFuncs::accessCheck]
   *      '404'         => function(){},          # Handler for other case                [handleReqFuncs::404]
   *    )
   *  </pre>
   * @param {Array}  $_actionHookFuncs     : [Selectable] Common Functions for each instance
   * <pre>
   *    array(
   *      'beforeSend' => function($msg){} # @see event actionHook::beforeSend
   *    )
   * </pre>
   * @example
   * <pre>
   * // Your codes here...
   * $wechat = new WeChatSDK(
   *   $APPSECRET, $APPID, $_token,
   *   $_handleReqFuncs     ,
   *   $_actionHookFuncs
   * );
   * </pre>
   */
  public function __construct(
    $APPSECRET, $APPID, $_token, 
    $_handleReqFuncs      = array(),
    $_actionHookFuncs = array()
  ){
    $this->APPSECRET    = $APPSECRET;
    $this->APPID        = $APPID;

    $this->_token           = $_token;
    $this->_handleReqFuncs  = $_handleReqFuncs;
    self::$_actionHookFuncs = $_actionHookFuncs;
  }

  /**
   * @method accessDataPush
   * @description Start to handle current HTTP Request.
   * @example
   * <pre>
   * // Your codes here ...
   * $wechat = new WeChatSDK($APPSECRET, $APPID, $_token,
   *  $_handleReqFuncs     ,
   *   $_actionHookFuncs
   * );
   * $wechat->accessDataPush();
   * </pre>
   */
  public function accessDataPush(){
    if(isset($GLOBALS["HTTP_RAW_POST_DATA"])){
      if(!$this->_checkSignature()){
        return;
      }
      $postObj = simplexml_load_string(
        $GLOBALS["HTTP_RAW_POST_DATA"],
        'SimpleXMLElement', 
        LIBXML_NOCDATA
      );
      $postObj = $this->_handlePostObj($postObj);

      /**
       * @event handleReqFuncs::commonBefore
       * @description Trigger when receive push data. Before all other handle hook
       * <br/> It can be handleReqFuncs::[text/image/location/link/event]
       * @example
       * <pre>
       * // How to set it?
       * new WeChatSDK(
       *    $APPSECRET, 
       *    $APPID,
       *    $token,
       *    array(
       *      'commonBefore' => 
       *    ),
       *    $actionHookFuncs
       * );
       * </pre>
        */
      $this->_runHandleReqFuncByType('commonBefore', $postObj);

      // Call Special Request Handle Function 
      $this->_runHandleReqFuncByType($postObj['type'], $postObj);
      // Call Common Request Handle Function
      /**
       * @event handleReqFuncs::common
       * @description Trigger when receive push data. 
       * <br/> It can be handleReqFuncs::[text/image/location/link/event]
       * @example
       * <pre>
       * // How to set it?
       * new WeChatSDK(
       *    $APPSECRET, 
       *    $APPID,
       *    $token,
       *    array(
       *      'common' => 
       *    ),
       *    $actionHookFuncs
       * );
       * </pre>
        */

      /**
       * @event handleReqFuncs::text
       * @description Trigger when receive text data push
       * @param {Array} Text Msg Data
       * <pre>
       *    array(
       *      # common
       *      'from' => # {String} : id, who send this msg
       *      'to'   => # {String} : id, who received this msg
       *      'time' => # {Int}    : When is the msg created?
       *      'type' => # {String} : msg type
       *      'id'   => # {String} : msg id
       *      # special
       *      'text' => # {String} : Msg string
       *    )
       * </pre>
       */

      /**
       * @event handleReqFuncs::image
       * @description Trigger when receive image data push
       * @param {Array} image Msg Data
       * <pre>
       *    array(
       *      # common
       *      'from' => # {String} : id, who send this msg
       *      'to'   => # {String} : id, who received this msg
       *      'time' => # {Int}    : When is the msg created?
       *      'type' => # {String} : msg type
       *      'id'   => # {String} : msg id
       *      # special
       *      'url'  => # {String} image url
       *    )
       * </pre>
       */

      /**
       * @event handleReqFuncs::location
       * @description Trigger when receive image data push
       * @param {Array} location Msg Data
       * <pre>
       *    array(
       *      # common
       *      'from' => # {String} : id, who send this msg
       *      'to'   => # {String} : id, who received this msg
       *      'time' => # {Int}    : When is the msg created?
       *      'type' => # {String} : msg type
       *      'id'   => # {String} : msg id
       *      # special
       *      'X' => # {float}  : Latitude
       *      'Y' => # {float}  : Longitude
       *      'S' => # {float}  : Scale
       *      'I' => # {String} : Location info
       *    )
       * </pre>
       */

      /**
       * @event handleReqFuncs::link
       * @description Trigger when receive link data push
       * @param {Array} link Msg Data
       * <pre>
       *    array(
       *      # common
       *      'from' => # {String} : id, who send this msg
       *      'to'   => # {String} : id, who received this msg
       *      'time' => # {Int}    : When is the msg created?
       *      'type' => # {String} : msg type
       *      'id'   => # {String} : msg id
       *      # special
       *      'title' => # {String} : Link title
       *      'desc' =>  # {String} : Link description
       *      'url' =>   # {String} : Link url
       *    )
       * </pre>
       */

      /**
       * @event handleReqFuncs::event
       * @description Trigger when receive event data push
       * @param {Array} event Msg Data
       * <pre>
       *    array(
       *      # common
       *      'from' => # {String} : id, who send this msg
       *      'to'   => # {String} : id, who received this msg
       *      'time' => # {Int}    : When is the msg created?
       *      'type' => # {String} : msg type
       *      'id'   => # {String} : msg id
       *      # special
       *      'event' => # {String} : event type
       *      'key'   => # {String} : event key
       *    )
       * </pre>
       */
      $this->_runHandleReqFuncByType('common', $postObj);

    } else if(isset($_GET['echostr'])) {
      /**
       * @event handleReqFuncs::accessCheck
       * @description Trigger when request CallBack API Verification 
       */
      $this->_runHandleReqFuncByType('accessCheck');
      if($this->_checkSignature()){
        /**
         * @event handleReqFuncs::accessCheckSuccess
         * @description Trigger when check signature success. Before send 'echostr'
         * @example
         * <pre>
         * // How to set it?
         * new WeChatSDK(
         *    $APPSECRET, 
         *    $APPID,
         *    $token,
         *    array(
         *      'accessCheckSuccess' => 
         *    ),
         *    $actionHookFuncs
         * );
         * </pre>
        */
        $this->_runHandleReqFuncByType('accessCheckSuccess');
        // avoid of xss
        if (!headers_sent()){
          header('Content-Type: text/plain');
        }
        echo preg_replace('/[^a-z0-9]/i', '', $_GET['echostr']);
      }
    } else {
      /**
       * @event handleReqFuncs::404
       * @description Unknow Request Handler.              
       */
      if( !$this->_runHandleReqFuncByType('404') ){
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
      }
    }
  }

  /**
   * @method sendRichMsg
   * @static
   * @param {String} $to   : id, who will get the msg
   * @param {String} $from : id, who sent the msg
   * @param {Array}  $list  : rich msg list. less then 10 items.
   * <strong>If you pass 10+ msg in, only 10 before will be send.</strong>
   * <pre>
   *          array(
   *            array(
   *              'title' => # {String} => sub msg title
   *              'desc'  => # {String} => sub msg description
   *              'image' => # {String} => sum msg image
   *              'url'   => # {String} => sub msg url
   *            ),
   *            ...
   *          )
   * </pre>
   * @param {bool} $flag   : Started this msg [default = false]
   * @example
   * <pre>
   * // Your codes here...
   * $handleText = function($postObj){
   *    WeChatSDK::sendRichMsg( $postObj['from'], $postObj['to'], array(
   *      array(
   *              'title' => 'title 1',
   *              'desc'  => 'desc 1',
   *              'image' => 'http://localhost/image1',
   *              'url'   => 'http://localhost/url1'
   *      ),
   *      array(
   *              'title' => 'title 2',
   *              'desc'  => 'desc 2',
   *              'image' => 'http://localhost/image2',
   *              'url'   => 'http://localhost/url2'
   *      )
   *    ) );
   * };
   * $wechat = WeChatSDK($APPSECRET, $APPID, $_token, array(
   *    'text' => $handleText
   * ));
   * </pre>
   */
  static public function sendRichMsg($to, $from, $list, $flag = false){
    $i  = 0;
    $ii = count($list);
    if($ii > 10){ $ii = 10; }
    $content = array();
    while($i < $ii){
      $item = $list[$i++];
      array_push(
        $content,
        sprintf(
          '<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
          </item>',
          $item['title'],
          $item['desc'],
          $item['image'],
          $item['url']
        )
      );
    }
    
    self::_send(sprintf( 
      '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <Content><![CDATA[]]></Content>
        <ArticleCount>%s</ArticleCount>
        <Articles>%s</Articles>
        <FuncFlag>%s</FuncFlag>
      </xml>',
      $to,
      $from,
      time(),
      $ii,
      implode('',$content),
      $flag
    ));
    
  }

  /**
   * @method sendMusic
   * @static
   * @param {String} $_to   : id, who will get the msg
   * @param {String} $_from : id, who sent the msg
   * @param {Array} $_music : Musci info list
   * <pre>
   *                    array(
   *                      'title'  => # {String} => music title
   *                      'desc'   => # {String} => music description
   *                      'url'    => # {String} => music url
   *                      'hq_url' => # {String} => high quality music url, default = $_music['url']
   *                    )
   * </pre>
   * @param {bool} $flag    : Started this msg [default = false]
   * @example 
   * <pre>
   * WeChatSDK::sendMusic( $postObj['from'], $postObj['to'], array(
   *    'title'  => 'music title',
   *    'desc'   => 'music description',
   *    'url'    => 'music url',
   *    'hq_url' => 'high quality music url'
   * ) );
   * </pre>
   */
  static public function sendMusic($_to, $_from, $_music, $flag = 0) {
    if(!isset($_music['hq_url'])){
      $_music['hq_url'] = $_music['url'];
    }
    self::_send(sprintf(
      '<xml>
         <ToUserName><![CDATA[%s]]></ToUserName>
         <FromUserName><![CDATA[%s]]></FromUserName>
         <CreateTime>%s</CreateTime>
         <MsgType><![CDATA[music]]></MsgType>
         <Music>
           <Title><![CDATA[%s]]></Title>
           <Description><![CDATA[%s]]></Description>
           <MusicUrl><![CDATA[%s]]></MusicUrl>
           <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
         </Music>
         <FuncFlag>0</FuncFlag>
       </xml>',
      $_to,
      $_from,
      time(),
      $_music['title'],
      $_music['desc'],
      $_music['url'],
      $_music['hq_url'],
      $flag
    ));
  }

  /**
   * @method sendText
   * @static
   * @param {String} $_to      : id, who will get the msg
   * @param {String} $_from    : id, who sent the msg
   * @param {String} $_content : msg content text
   * @param {bool}   $flag       : Started this msg [default = false]
   * @example
   * <pre>
   * WeChatSDK::sendText( $postObj['from'], $postObj['to'], 'Hello');
   * </pre>
   */
  static public function sendText($_to, $_from, $_content, $flag = 0){
    self::_send(sprintf(
      '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>%s</FuncFlag>
      </xml>',
      $_to,
      $_from,
      time(),
      $_content,
      $flag
    ));
  }

  # / METHODS }}} =====================

  private static function _send($msg){
    /**
     * @event actionHook::beforeSend
     * @description before send msg to our customer, you can log/stop it by this event.<br/>
     * @example
     * <pre>
     * // How to set it?
     * new WeChatSDK(
     *    $APPSECRET, 
     *    $APPID,
     *    $token,
     *    $handleReqFuncsFuncs,
     *    array(
     *      'beforeSend' => # {String|Function} the callable function or function name.
     *    )
     * );
     * </pre>
     */
    
    if (!headers_sent()){
      // avoid xss in ie
      header('Content-Type: text/xml');
    }
    self::_runActionHookFuncByType('beforeSend',$msg);
    echo $msg;
  }

  private function _runHandleReqFuncByType($type){
    $argvs = func_get_args();
    array_shift( $argvs );
    return isset($this->_handleReqFuncs[$type]) 
        && call_user_func_array($this->_handleReqFuncs[$type], $argvs);
        //&& $this->_handleReqFuncs[$type]($param);
  }

  private static function _runActionHookFuncByType($type){
    $argvs = func_get_args();
    array_shift( $argvs );
    return isset(self::$_actionHookFuncs[$type]) 
      && call_user_func_array(self::$_actionHookFuncs[$type], $argvs);
        // && self::$_actionHookFuncs[$type]($param, $param);
  }

  private function _checkSignature(){
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];	
        		
		$token = $this->_token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

  # {{ ERROR MSG ============================ 
  
  private static function getErrorCode($json){
    return $json['errcode'];
  }
  private static function getErrorMsg ($json){
    return $json['errmsg'];
  }
  private static function getErrorDesc($json){
    $errcode = self::getErrorCode($json);
    return isset( self::$_errList[ $errcode ] )
      ? self::$_errList[ $errcode ]
      : 'ERROR_DESC_NO_FOUND';
  }
  protected static function checkIsSuc($res){
    $result = isset($res['access_token']) 
      || (isset($res['errcode']) && ( 0 == (int)$res['errcode']));
    return $result; 
  }
  # / ERROR MSG }} ============================ 

  # {{ CUSTOM MENU SET/GET/DELETE ===============

  /**
   * @method getMenuList
   * @static 
   * @description Get the current custom menu list
   * @return {Array|null} return list array when success, or nothing for failure
   * @example
   * <pre>
   *       // Your codes here...
   *       $wechat = new WeChatSDK($APPSECRET, $APPID, $_token);
   *       print_r( $wechat->getMenuList() );
   *       // ...
   * </pre>
   */
  public function getMenuList(){

    $ACCESS_TOKEN = $this->_getAccessToken();
    $url = self::$_api_url_root;
    $url = "$url/cgi-bin/menu/get?access_token=$ACCESS_TOKEN";

    $json = self::get($url);
    $res  = json_decode($json, true);

    return $res;
  }

  /**
   * @method delMenuList
   * @static 
   * @return {boolen} Delete custom menu successfully?
   * @description Delete the custom menu
   * @example
   * <pre>
   *       // Your codes here...
   *       $wechat = new WeChatSDK($APPSECRET, $APPID, $_token);
   *       print_r( $wechat->delMenuList() ? 'Yes" : 'No' );
   *       // ...
   * </pre>
   */
  public function delMenuList(){
    $ACCESS_TOKEN = $this->_getAccessToken();

    $url = self::$_api_url_root;
    $url = "$url/cgi-bin/menu/delete?access_token=$ACCESS_TOKEN";

    $json = self::get($url);
    $res  = json_decode($json, true);

    $isSuc = self::checkIsSuc($res);

    /**
     * @event actionHook::delMenuListResponse
     * @description Trigger once it got the response after try to delete custom menu
     * @example
     * <pre>
     * // How to set it?
     * new WeChatSDK(
     *    $APPSECRET, 
     *    $APPID,
     *    $token,
     *    $handleReqFuncsFuncs,
     *    array(
     *      'delMenuListResponse' => # {String|Function} the callable function or function name.
     *    )
     * );
     * </pre>
     */
    self::_runActionHookFuncByType('delMenuListResponse', $res);
    $result = self::checkIsSuc($res);
    if(!$result){
      $res['msg'] = self::getErrorDesc($res);
      $result = $res;
    }

    return $result;
  }

  /**
   * @method setMenuByJson
   * @static
   * @param {string} $json JSON Format.
   * <pre>
   *    {
   *      "button":[
   *        {	
   *          "type":"click",
   *          "name":"Today",
   *          "key":"V1001_TODAY_MUSIC"
   *        },
   *        {
   *          "name":"More",
   *          "sub_button":[
   *            {
   *              "type":"click",
   *              "name":"Hello World",
   *              "key":"V1001_HELLO_WORLD"
   *            },
   *            {
   *              "type":"click",
   *              "name":"Good",
   *              "key":"V1001_GOOD"
   *            }
   *          ]
   *        }
   *      ]
   *    }
   * </pre>
   * @example
   * <pre>
   *       // Your codes here...
   *       $wechat = new WeChatSDK($APPSECRET, $APPID, $_token);
   *       $json_string = json_decode( file_get_contents($json_file), true);
   *       $wechat->setMenuByJson($json_string);
   *       // ...
   * </pre>
   * @description 
   */
  public function setMenuByJson($json = ''){
    //$json = json_encode( json_decode($json) );

    
    if( !is_string($json) ){ 
      return array(
        'msg' => 'Json String no found'
      ); 
    }

    ;
    if(!($ACCESS_TOKEN = $this->_getAccessToken())){
      return false;
    }

    $url = self::$_api_url_root;
    $url = "$url/cgi-bin/menu/create?access_token=$ACCESS_TOKEN";

    $opts = array(
      'http' => array(  
        'method' => 'POST',  
        'header' => "Connection: close\r\n".
                    "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($json)."\r\n",
        'content' => $json
      )
    );

    $res = self::post($url, $json);
    $res = $res ? json_decode(self::post($url, $json), true) : $res;

    /**
     * @event actionHook::setMenuListResponse
     * @description Trigger once it got the response after try to set custom menu
     * @example
     * <pre>
     * // How to set it?
     * new WeChatSDK(
     *    $APPSECRET, 
     *    $APPID,
     *    $token,
     *    $handleReqFuncsFuncs,
     *    array(
     *      'setMenuListResponse' => # {String|Function} the callable function or function name.
     *    )
     * );
     * </pre>
     */
    self::_runActionHookFuncByType('setMenuListResponse', $res);

    $result = self::checkIsSuc($res);

    if(!$result){
      $res['msg'] = self::getErrorDesc($res);
      $result = $res;
    }
    return $result;
  }

  # ------------------------------------------
  # / CUSTOM MENU SET/GET/DELETE }}} ===============
  private function _getAccessToken(){
    if ( time() > $this->EXPIRE_TIME ){
      $APPID     = $this->APPID;
      $APPSECRET = $this->APPSECRET;

      $url = self::$_api_url_root;
      $url  = "$url/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";

      $json = self::get($url);
      $res  = json_decode($json, true);
      
      if ( self::checkIsSuc($res) ){
        $this->ACCESS_TOKEN = $res['access_token'];
        $this->EXPIRE_TIME  = $res['expires_in'];
      } else {
        $this->ACCESS_TOKEN = $this->EXPIRE_TIME = 0;
      }
    }
    return $this->ACCESS_TOKEN;
  }

  private function _handlePostObj($postObj){
    $MsgType = strtolower((string)$postObj->MsgType);
    $result = array(
      'from'  => (string) htmlspecialchars( $postObj->FromUserName ),
      'to'    => (string) htmlspecialchars( $postObj->ToUserName ),
      'time'  => (int) $postObj->CreateTime,
      'type'  => (string) $MsgType
    );

    $this->_msgFrom = $result['from'];
    $this->_msgTo   = $result['to'];

    if( property_exists($postObj, 'MsgId') ){
      $result['id'] = $postObj->MsgId;
    }

    switch($result['type']){
      case 'text':
        $result['content'] = (string) $postObj->Content; // Content 消息内容
        break;

      case 'location':
        $result['X'] = (float) $postObj->Location_X; // Location_X 地理位置纬度
        $result['Y'] = (float) $postObj->Location_Y; // Location_Y 地理位置经度
        $result['S'] = (float) $postObj->Scale;      // Scale 地图缩放大小
        $result['I'] = (string) $postObj->Label;      // Label 地理位置信息
        break;

      case 'image':
        $result['url'] = (string) $postObj->PicUrl; // PicUrl 图片链接，开发者可以用HTTP GET获取
        break;

      case 'link':
        $result['title'] = (string) $postObj->Title;       // Content 消息标题
        $result['desc']  = (string) $postObj->Description; // Content 消息标题
        $result['url']   = (string) $postObj->Url;         // Content 消息标题
        break;
        
      case 'event':
        $result['event'] = strtolower((string) $postObj->Event);    // 事件类型，subscribe(订阅)、unsubscribe(取消订阅)、CLICK(自定义菜单点击事???
        $result['key']   = (string) $postObj->EventKey; // 事件KEY值，与自定义菜单接口中KEY值对???
        break;

    }

    return $result;
  }

  /**
   * @method post
   * @static
   * @param  {string}        $url URL to post data to
   * @param  {string|array}  $data Data to be post
   * @return {string|boolen} Response string or false for failure.
   */
  static public function post($url, $data){
    if(is_array($data)){
      $curlPost = array();
      foreach($data as $key => $val){
        $key = urlencode($key);
        $val = urlencode($val);
        array_push($curlPost, "$key=$val");
      }
      $curlPost = implode($curlPost, '&');
    } else if (is_string($data)){
      $curlPost = $data;
    } else {
      $curlPost = print_r($data, true);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    # curl_setopt($ch, CURLOPT_HEADER, 1);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);
    if(!$data) error_log( curl_error ( $ch ));
    curl_close($ch);
    return $data;
  }

  /**
   * @method get
   * @static
   * @param  {string}        $url URL to post data to
   * @param  {string|array}  $data Data to be post
   * @return {string|boolen} Response string or false for failure.
   */
  static public function get($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    # curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    if(!curl_exec($ch)){
      error_log( curl_error ( $ch ));
      $data = ''; 
    } else {
      $data = curl_multi_getcontent($ch);

    }
    curl_close($ch);
    return $data;
  }

  // 请求消息的来源及流向
  private $_msgFrom;
  private $_msgTo;

  private $_token;
  private $_handleReqFuncs;
  private static $_actionHookFuncs;

  private $APPSECRET;
  private $APPID;

  private $ACCESS_TOKEN;
  private $EXPIRE_TIME = 0;

}
