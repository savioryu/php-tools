<?PHP
  if('POST' == $_SERVER['REQUEST_METHOD']){
    echo json_encode($_POST);
  } else {
    echo json_encode(array('get' => 1));
  }
