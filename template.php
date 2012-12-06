<?PHP
/**
 * @author zemzheng@gmail.com
 * @description a template tools
 */

abstract class template{
  /**
   * @public 
   * @static
   * @description 判断一个文件里面是不是有PHP标签。 tell if an file with PHP tag.
   * @function isPHP
   * @param {string} $path 文件路径。 file path
   * @return {boolen} 
   */
  static function isPHP($path){ 
    $result = false;
    if(file_exists($path)){
      $result = preg_match(
        '/<\?.*\?>/m',
        self::readFile($path)
      ) ? true : false;
    }
    return $result;
  }

  /**
   * @public 
   * @static 
   * @description 传入参数给模板，生成页面。
   * @function includeTmpl
   * @param {string} $path 导入的模板文件路径。file path
   * @param {array} $params 传给模板的参数。The params you want to pass to the template
   * @return {string} 模板运行完后的字符串结果。The content complie form template
   */
  static function includeTmpl($path, $params = array()){
    if(!file_exists($path)){
      return '';
    }
    foreach($params as $key => $val){
      $$key = $val;
    }
    ob_start();
    include($path);
    $result = ob_get_contents();
    ob_end_clean();
    echo "\n[S][includeTmpl] $path\n";
    return $result;
  }
}
