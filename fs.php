<?PHP
/**
 * @desciption file system tools
 * @author zemzheng@gmail.com
 */

abstract class fs{
  /**
   * @public 
   * @static
   * @description 根据路径读取文件。会echo读取情况。
   * 			        Read the file to string. Will echo the result. 
   * @function readFile
   * @param {string} $file_path 文件路径 
   * @return {string} 文件内容 File Content  
   */
  static function readFile($file_path){
    if (!file_exists($file_path)){
      echo "\n[F][readFile] $file_path\n";
      return false;
    } else {
      echo "\n[S][readFile] $file_path\n";
    }
    $content = file_get_contents($file_path);
    return $content;
  }

  /**
   * @public
   * @static
   * @description 写文件。
   * @function writeFile
   * @param {string} $file_path 文件路径
   * @param {string} $content 要写入的内容.Content to write.
   * @param {boolen} $clean 要不要先删除文件?Need to unlink the file if exists?
   * @return {int} 写入内容的长度。The length writed to file.
   */
  static function writeFile($file_path, $content, $clean=1){
    if ($clean && file_exists($file_path)){
      echo "\n[S][writeFile][rm]\n  $file_path\n";
      unlink($file_path);
    }
    $f = fopen($file_path, 'a');
    $result = fwrite($f, $content);
    fclose($f);
    echo "\n", $result ?  '[S]' : '[F]', "[writeFile][write] $file_path\n";
    return $result;
  }

  /**
   * @public 
   * @static 
   * @description 得到从 $from 到 $to 的相对路径
   * @function getRelativePath
   * @param {string} $from 起始路径。 Start path
   * @param {string} $to 目标路径。Target path
   * @return {string} 返回相对路径。 The relative path
   */
  static function getRelativePath($from, $to){
    $arr_from = explode(DIRECTORY_SEPARATOR,  realpath($from));
    $arr_to   = explode(DIRECTORY_SEPARATOR,  realpath($to)); 

    do{
      $current_from = array_shift($arr_from);
      $current_to   = array_shift($arr_to);
    }while($current_from == $current_to && count($current_from));
    array_unshift($arr_to, $current_to);

    $result = array();//'.');
    $ii = count($arr_from) + (is_dir($from)?1:0);

    while($ii--){
      array_push($result, '..');
    }

    $result = 
      call_user_func_array(
        'self::joinPath',
        array_merge($result, $arr_to)
        //$result
      );

    return $result;
  }

  /**
   * @public 
   * @static
   * @description 查看目标文件夹是否存在，不存在则创建
   * @function createDir
   * @param {string} $path 目标路径。Target path
   */
  static function createDir($path){
    print_r($path);
    $path = explode(DIRECTORY_SEPARATOR, $path);
    $i = 0;
    $t = null;
    while(isset($path[$i])){
      if(null !== $t){
        $t = self::joinPath($t, $path[$i++]);
      }else{
        $t = $path[$i++];
      }
      if($t && !is_dir($t)){
        echo "zem create $t\n";
        mkdir($t);
      }
    }
  }

  /**
   * @public
   * @static
   * @description 把输入的内容按照系统路径格式（斜杠OR反斜杠）组合起来
   * @function joinPath
   * @param {string} 可以多个参数，路径。 multi-params
   * @return {string} 组合后的路径
   */
  static function joinPath(){
    return implode(DIRECTORY_SEPARATOR,func_get_args());  
  }

  /**
   * @static
   * @public
   * @description 遍历一个路径。 walk a path and visit every thing
   * @function filesWalker
   * @param {string} $path 指定的路径
   * @param {function} $funcForFile 如果是文件，处理的函数。Function for handle file. $funcForFile($file_path);
   * @param {function} $funcForFolder 如果是文件夹，处理的函数。 Function for handle folder. $funcForFolder($folder_path);
   * @return
   */
  static function filesWalker($path, $funcForFile='', $funcForFolder=''){
    if(!file_exists($path)){
      return false;
    }
    if(is_dir($path)){
      $path = preg_replace('/\/+$/', '', $path) . '/';
      if (is_callable($funcForFolder)){
        $funcForFolder($path);
      }
      
      $dp = dir($path);
      while($file = $dp->read()){
        if($file!='.' && $file!='..' && $file!='.svn'){
          self::filesWalker($path . "$file", $funcForFile, $funcForFolder);
        }
      }

    }else{
      if (is_callable($funcForFile)){
        $funcForFile($path);
      }
    }
  }
} 
