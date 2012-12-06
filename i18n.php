<?PHP
/**
 * @author zemzheng@gmail.com
 * @description i18n tools
 */

abstract class i18n{

  /**
   * @public
   * @static
   * @description 根据数组内来正则替换文本。Replace an string by reg in array.
   * @function pregReplaceByArr
   * @param {string} $str 需要替换的文本。 String input.
   * @param {array} $arr 替换的正则数组。 Array with reg.
   *           <pre>array(
   *                  $reg => $reStr // preg_replace($reg, $reStr, $str); 
   *                  $reg => $reFun // preg_replace_callback($reg, $reFun, $str); 
   *                )
   *                $reFunc($strResult)  // $strResult 为每一个符合 $reg 正则匹配的结果。 $strResult is the result by filter $reg</pre>
   * @return {string} 替换后的结果。The content after filter
   */
  static function pregReplaceByArr($str, $arr){
    $result = $str;
    foreach($arr as $reg => $replace){
      if(is_callable($replace)){
        $result = preg_replace_callback(
          $reg, 
          function($str) use ($replace){
            return $replace($str[0]);
          },
          $result
        );
      } else {
        while(preg_match_all($reg, $result, $tmp)){
          $result = preg_replace(
            $reg,
            $replace,
            $result
          );
        }
      }
    }
    return $result;
  }

  /**
   * @public 
   * @static
   * @description 为中文字符包裹上gettext，为后续国际化做准备。 wrap Chinese with gettext method.
   * @function wrapChs
   * @param {string} $str 输入的文本。The content we want to have a wrap
   * @return {string} 输出的结果。 OK, you got it.
   */
  static function wrapChs($str, $mode='*'){
  
    $reg_pre_gettext = '<\?PHP\secho\s\_\(\'';
    $reg_aft_gettext = '\'\);\?>';
    $reg_wchs = "$reg_pre_gettext([^']+)$reg_aft_gettext";

    $reg_chs_mark = '，、。！？；“”‘：（）';
    $reg_cht_mark = ',\.\_a-zA-Z0-9\?\\\!\%\$\/~\-@';
    
    // 不知道是不是把中文包在这里会好一些
    // 不是
    //$reg_chinside_mark = ',\.\_a-zA-Z' . $reg_chs_mark . '0-9\?\\\!\%\$';


    $func_unwrap = function($str) use($reg_wchs) {        
      $str = preg_replace("/$reg_wchs/u", '$1', $str);      
      return $str;
    };


    // 注释是否要过滤
    $reg_note_filter = array();

    switch(strtolower($mode)){    
      case 'html':
      case 'htm' :
        $reg_note_filter = array(
          '/<!--.*?-->/u' => $func_unwrap,
          '/<%#.*?%>/u' => $func_unwrap

          ,// mmbiz 这边html里面的js注释也去掉
          // 单行注释
          "/(\/\/.*)$reg_wchs/u" => '$1$2',

          // 这个被下面的多行给包括了
          //"/(\/\*.*?)$reg_wchs(.*\*\/)/" => '$1$2$3',

          // 多行注释
          "/\/\*.*?\*\//su" => $func_unwrap

        );
        break;
      case '*':
      case 'javascript':
      case 'js':
      default:
        $reg_note_filter = array(
          // 单行注释
          "/(\/\/.*)$reg_wchs/u" => '$1$2',

          // 这个被下面的多行给包括了
          //"/(\/\*.*?)$reg_wchs(.*\*\/)/" => '$1$2$3',

          // 多行注释
          "/\/\*.*?\*\//su" => $func_unwrap
        );
    }

    // 全部过滤
    $result = preg_replace("/([\x{4e00}-\x{9fa5}]+)/u", "<?PHP echo _('$1');?>", $str);

    // 注释过滤
    $result = self::pregReplaceByArr(
      $result,
      $reg_note_filter
    );


    // 中间的单词之类
    $result = self::pregReplaceByArr(
      $result, 
      array(
        // 两个输出中间的
        // 后面要把每行超过2个php-gettext的内容并入一起
        "/$reg_aft_gettext([$reg_chs_mark$reg_cht_mark\s]+?)$reg_pre_gettext/u" => '$1',
        
        // 前面的
        "/([$reg_cht_mark$reg_chs_mark]+?\s*)($reg_pre_gettext)/u" => '$2$1',

        // 后面的
        // 注意， $reg_cht_mark 放在后面
        // 理由是：会出现匹配中文字符串的时候把\t\n等给匹配进来
        // 理由不成立呀孩子
        "/($reg_aft_gettext)(\s*[$reg_chs_mark$reg_cht_mark]+?)/u" => '$2$1'
      )
    );
    /* 下面这个是把一行里面多个gettext转成一个 */
    $result = preg_split('/[\n\r]/', $result);
    $ii = count($result);
    while($ii--){
      $str = trim($result[$ii]);
      $num = preg_match_all("/$reg_wchs/u", $str, $matches);
      if($num > 0){ /* 凡是一行有中文要处理的，全部拖出来看看呢 */
        $params = preg_split("/$reg_wchs/u", $str);
        /* 首尾不放入 *//*
        $before = array_shift($params);
        $after = array_pop($params);
        /**/

        array_walk(
          $params,
          function(&$item){
            // 反斜杠过滤
            $item = str_replace('\\','\\\\', $item);
            // 将单引号过滤
            $item = str_replace('\'','\\\'', $item);            
          }
        );

        // 将% 过滤成 %%
        // 为替换的位置打上顺序
        $matches_txt = '';
        $matches = $matches[1];
        $_i = 0;
        $_ii = count($matches);
        $_pindex = 1;
        while($_i < $_ii){
          $_i++;
          $matches_txt .= "%$_i\$s" . str_replace('%','%%', $matches[$_i-1]);
        }
        $_i++;
        $matches_txt .= "%$_i\$s";

        
        
        $result[$ii] =  
          /* 首尾放入 */
          "<?PHP \n\techo sprintf(\n\t\t_('$matches_txt'),\n\t\t"
            . '\''. implode("',\n\t\t'", $params). "');\n?>";
          /* 首尾不放入 *//*
          $before . '<?PHP echo sprintf('
            . '_(\'' . (implode('%s',$matches[1])) . '\'),'
            . '\''. implode('\',\'', $params). '\');?>' . $after;
          /**/
      }
    }
    $result = implode("\n", $result);
    
    return $result;
  }
}
