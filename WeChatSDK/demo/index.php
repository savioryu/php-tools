<!DOCTYPE HTML>
<html>
<head>
  <meta charset="UTF-8">
  <title></title>
  <link rel="stylesheet" href="./css/bootstrap.css" />
  <link rel="stylesheet" href="./css/aui-artDialog/blue.css" />
  <link rel="stylesheet" href="./css/menu.css" />
  <script type="text/javascript" src="./js/jquery-1.8.2.min.js"></script>
  <script type="text/javascript" src="./js/artDialog.min.js"></script>
</head>
<body>
  <div class="container-fluid">

    <div class="navbar" id="header">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="brand" href="#">Demo for WeChat SDK</a>
        </div>
      </div>
    </div>

    <?PHP

      if(isset($_GET['quit'])){
        $appid = $appsecret = '';
      } else {
        session_start();
        $appid     = $_SESSION['appid'] = isset($_POST['appid'])     
          ? $_POST['appid']     
          : ( isset($_SESSION['appid']) ? $_SESSION['appid'] : '');
        $appsecret = $_SESSION['appsecret'] = isset($_POST['appsecret']) 
          ? $_POST['appsecret'] 
          : ( isset($_SESSION['appsecret']) ? $_SESSION['appsecret'] : '');
      }

      if ($appid && $appsecret):         
    ?>

    <div class="row-fluid">
      <div class="span5">
        <div class="pannel-row">
          <textarea id="menu-json" class="show"></textarea>
        </div>
        <div class="pannel-row">
          <a id="get-menu" class="btn btn-success">Get Current Menu</a>
          &nbsp;||&nbsp;
          <a id="set-menu" class="btn btn-primary">Set Menu</a>
          &nbsp;||&nbsp;
          <a id="del-menu" class="btn btn-danger">Delete Current Menu</a>
        </div>
      </div>
      <div class="span7">
        <table class="table table-hover table-bordered">
          <tr>
            <td>APPID :</td>
            <td><?= $appid; ?></td>
          </tr>
          <tr>
            <td>APPSECRET :</td>
            <td><?= $appsecret; ?></td>
          </tr>
          <tr>
            <td colspan="2">
              <a class="  " href="/?quit">Quit</a>
            </td>
        </table>
      </div>
    </div>

    <?PHP else: ?>
    <h3>Custom Menu APPID/APPSECRET</h3>
    <form class="form-horizontal" method="post" action="/">
      <div class="control-group">
        <label class="control-label" for="form_appid">APPID</label>
        <div class="controls controls-row">
          <input type="text" name="appid" id="form_appid" value="<?= $appid; ?>"/>
        </div>
      </div>
      <div class="control-group">
        <label class="control-label" for="form_appsecret">APPSECRET</label>
        <div class="controls controls-row">
          <input type="text" name="appsecret" id="form_appsecret" value="<?= $appsecret; ?>"/>
        </div>
      </div>
      <div class="control-group">
        <div class="controls controls-row">
          <input type="submit" value="Go"/>
        </div>
      </div>
    </form>
    <?PHP endif; ?>
  </div>
  </body>
</html>
<script type="text/javascript" src="./js/menu.js"></script>
