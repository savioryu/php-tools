<?PHP
/**
 * Lectura de archivos MO de GetText y simplificación de su uso con PHP
 * @class analyseMO
 * @see http://www.tierra0.com/page/3/
 */
abstract class analyseMO{
  static function mo2array($archivo = 'traduccion.mo'){
    $file      = file_get_contents($archivo, FILE_BINARY);

    $i['magic'] = self::deco(substr($file, 0, 4), true); // extraigo el numero magico para verificar si es valido
    if($i['magic'] != 'DE 12 04 95'){ return array('error'=>'Archivo no valido, no contiene datos validos de internacionalizacion.', 'magic'=>$i['magic']); }
    $f=4;   $ini=4;
    $i['version']         = self::deco(substr($file,$ini,4),'d');  $ini+=$f;
    $i['len']             = self::deco(substr($file,$ini,4),'d');  $ini+=$f; //total de frases traducidas
    $i['ini_original']    = self::deco(substr($file,$ini,4),'d'); $ini+=$f;
    $i['ini_traduccion']  = self::deco(substr($file,$ini,4),'d');   $ini+=$f;
    $i['len_hash']        = self::deco(substr($file,$ini,4),'d'); $ini+=$f;
    $i['ini_hash']        = self::deco(substr($file,$ini,4),'d');

    $o = str_split( substr($file, $i['ini_original'],   $i['ini_traduccion']), 4);
    $t = str_split( substr($file, $i['ini_traduccion'], $i['ini_hash']      ), 4);

    for($k=1; $k<=$i['len']; $k++){
      if($k%2!=0){
        #$i['table'][$k ]['o'] = substr( $file, self::deco($o[$k],'d'), self::deco($o[$k-1],'d') );
        #$i['table'][$k ]['t'] = substr( $file, self::deco($t[$k],'d'), self::deco($t[$k-1],'d') );
        $i['table'][substr( $file, self::deco($o[$k],'d'), self::deco($o[$k-1],'d') )] = substr( $file, self::deco($t[$k],'d'), self::deco($t[$k-1],'d') );
      }
    }
    return $i;
  }

  static function deco($data, $jumps=false){ //pasa el contenido binario a HEX y lo preformatea
    if($jumps==='h'){
      $o = explode(' ', wordwrap(strtoupper(bin2hex($data)), 2, " ", 1) );
      $o = $o[3].$o[2].$o[1].$o[0];
    } elseif($jumps==='d'){ // bin2dec
      $o = explode(' ', wordwrap(strtoupper(bin2hex($data)), 2, " ", 1) );
      $o = hexdec($o[3].$o[2].$o[1].$o[0]);
    }  elseif($jumps===true){
      $o = wordwrap(strtoupper(bin2hex($data)), 2, " ", 1);
    } else {
      $o = strtoupper(bin2hex($data));
    }
    return $o;
  }
}
