<?php
class Jackhole {
  public $redir = 'https://www.google.com/';
  public $hash;
  public $marker;
  public $source;
  public $requests = [];

  function __construct() {
    if ($this->load()) {
      $pass = '';
      if (isset($_COOKIE['pass'])) {
        $pass = $_COOKIE['pass'];
      } else if (isset($_REQUEST['pass'])) {
        $pass = $_REQUEST['pass'];
      }
      
      if (sha1($pass) === $this->hash) {
        $this->pass = $pass;
        $this->admin();
      } else {
        array_unshift($this->requests, $this->info());
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        //header("Location: $this->redir", true);
        echo "<script>document.location='$this->redir';</script>";
      }
    } else {
      setcookie('pass', $this->pass);
      header('Location: ' . $_SERVER['PHP_SELF']);
    }

    $this->save();
    exit();
  }

  public function info() {
    $headers = getallheaders();
    $cookies = [];
    if (isset($headers['Cookie'])) {
      foreach(explode(';', $headers['Cookie']) as $cookie) {
        list($key, $value) = explode('=', trim($cookie));
        $cookies[$key] = $value;
      }
      unset($headers['Cookie']);
    }
    $request = [
      'client' => [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'port' => $_SERVER['REMOTE_PORT']
      ],
      'uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
      'method' => $_SERVER['REQUEST_METHOD'],
      'headers' => $headers,
      'cookies' => $cookies,
      'params' => $_GET,
      'body' => array_merge($_POST, $_FILES),
      'time' => $_SERVER['REQUEST_TIME_FLOAT']
    ];

    if (!count($request['body'])) {
      $request['body'] = file_get_contents('php://input');
    }

    return $request;
  }

  public function render($data, $output = 'json', $pretty = true) {
    if ($output == 'json') {
      $rendered = json_encode(json_decode($data), JSON_PRETTY_PRINT);
    } else {
      ob_start();
      var_export($data);
      $rendered = ob_get_clean();
    }

    $rendered = '<code>' . htmlentities($rendered) . '</code>';
  
    if ($pretty) {
      $rendered = '<pre>' . $rendered . '</pre>';
    }
  
    return $rendered;
  }

  public function admin() {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
    
    if (isset($_REQUEST['action'])) {
      $id = null;
      if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
        $id = intval($_REQUEST['id']);
      }
      switch ($_REQUEST['action']) {
        case 'dump':
          if (count($this->requests) == 0) break;
          header('Content-Type: application/json');
          header("Content-Transfer-Encoding: Binary"); 
          header('Content-disposition: attachment; filename="' . date('Y.m.d_h.i.s') . '-http_bin.json"');

          if (is_null($id)) {
            header('Content-disposition: attachment; filename="' . date('Y.m.d_h.i.s') . '-http_bin.json"');
            echo json_encode($this->requests, JSON_PRETTY_PRINT);
          } else {
            header('Content-disposition: attachment; filename="' . date('Y.m.d_h.i.s') . '-' . $id . 'http_bin.json"');
            echo json_encode($this->requests[$id], JSON_PRETTY_PRINT);
          }
          exit();
        break;

        case 'delete':
          if (!is_null($id)) {
            array_splice($this->requests, intval($id), 1);
          }
        break;

        case 'clear':
          $this->init();
          $this->requests = [];
          setcookie('pass', $this->pass);
        break;

        case 'exit':
          header('Location: /');
          unlink(__FILE__);
          exit();
        break;
      }

      header('Location: ' . $url);

      return;
    }
?>
<html>
<head>
    <title>Jackhole</title>
    <link rel="stylesheet" href="https://unpkg.com/chota@latest">
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48dGV4dCB5PSIuOWVtIiBmb250LXNpemU9IjkwIj7wn5WzPC90ZXh0Pjwvc3ZnPg==">
    <style>
      .content,input.toggle{display:none}.request{font-size:1.4rem;}.content{background:#222;border-bottom:1px solid var(--border-color);overflow-x:auto}.content table tbody th{padding-left:2rem;width:15rem}input.toggle:checked+.request>.row{background:#444}input.toggle:checked+.request+.content{display:block}.text-ellipsis{text-overflow:ellipsis;overflow:hidden;white-space:nowrap}body.dark{--bg-color:#222;--bg-secondary-color:#131316;--font-color:#f5f5f5;--color-grey:#ccc;--color-darkGrey:#777}label.request>.row{padding-top:.6rem;cursor:pointer;transition:background .3s}label.request>.row:hover{background:#333}tbody.small th,tbody.small td{padding:.6rem .2rem}.bd-orange{border-color:#f5b042}.bd-blue{border-color:#3bb8ed}.bd-purple{border-color:#a62dd6}.button{padding:.5rem}.actions .button{margin-left:.5rem}.small{font-size:.8em;}pre code{white-space: pre-wrap;}
    </style>
</head>
<body class="dark">
<div class="container">
<nav class="nav">
  <div class="nav-left">
    <a class="brand" href="<?= $url ?>">🕳&nbsp;Jackhole</a>
  </div>
  <div class="nav-right">
    <a class="button success" href="<?= $url . "?action=dump" ?>">💾 Dump</a>
    <a class="button outline" href="<?= $url . "?action=clear" ?>">↻ Clear</a>
    <a class="button error"
      onclick="return confirm('You sure ? This file will be deleted.')"
      href="<?= $url . "?action=exit" ?>">🌩 Exit</a>
  </div>
</nav>
<div class="row">
  <div class="col">
  <p>
    <input type="text" value="<?= $url ?>" onfocus="this.select()">
  </p>
  </div>
</div>

<div class="row">
  <div class="col">
    <h2>Requests</h2>
      <div id="requests">
<?php
    if (count($this->requests) == 0) {
      echo 'No requests ...';
    } else {
      foreach ($this->requests as $i => $request) {
        $cls = '';
        $colors = [
          'POST' => 'bd-success',
          'PUT' => 'bd-success',
          'GET' => 'bd-orange',
          'OPTIONS' => 'bd-purple',
          'TRACE' => 'bd-purple',
          'DELETE' => 'bd-error',
        ];
        if (key_exists($request['method'], $colors)) {
          $cls = ' ' . $colors[$request['method']];
        }
?>
        <input type="checkbox" class="toggle" id="req_<?= $i ?>">
        <label class="request" for="req_<?= $i ?>">
          <div class="row">
            <div class="col-3 text-ellipsis">
              <span class="small"><?= date('m/d h:i:s', intval($request['time'])) ?></span>
              –
              <?= $request['client']['ip'] ?>
            </div>
            <div class="col-7 text-ellipsis">
              <span class="tag <?= $cls ?>">
                <?= $request['method'] ?>
              </span>
              &nbsp;
              <?= $request['uri'] ?>
            </div>
            <div class="col-1 actions">
              <a href="<?= $url . '?action=delete&id=' . $i ?>" class="button outline bd-error pull-right text-error">❌</a>
              <a href="<?= $url . '?action=dump&id=' . $i ?>" class="button outline bd-success pull-right text-success">💾</a>
            </div>
          </div>
        </label>
        <div class="content">
          <table>
          <tbody class="small">
            <tr>
              <th colspan="100%" class="text-center bd-dark">Headers</th>
            </tr>
<?php
        foreach ($request['headers'] as $key => $value)
          echo "<tr><th>$key</th><td><code>$value</code></td></tr>";
        
        $args = ['cookies', 'params', 'body'];
        foreach ($args as $arg) {
          if (count($request[$arg])) {
            echo '<tr>'.
              '<th colspan="100%" class="text-center bd-dark">'.
                ucfirst($arg).
              '</th>'.
            '</tr>';
            if (is_array($request[$arg])) {
              foreach ($request[$arg] as $key => $value) {
                echo '<tr>'.
                  "<th>$key</th>".
                  '<td>' . $this->render($value) . '</td>'.
                "</tr>";
              }
            } else {
              echo '<tr><td colspan="2">'.
                $this->render($request[$arg]).
              '</td></tr>';
            }
          }
        }
?>
          </tbody>
          </table>
        </div>
<?php
      }
?>
      </div>
<?php
    }
?>
  </div>
</div>
</div>
<script>
  document.body.querySelector("#requests").addEventListener("change",e=>{let t=document.body.querySelectorAll('input[type="checkbox"]');Array.prototype.forEach.call(t,t=>{t!=e.target&&(t.checked=!1)})});
</script>
</body>
</html>
<?php
  }

  public function load() {
    $this->marker = base64_decode('PT09PT0K');
    $this->source = file_get_contents(__FILE__);

    $pos = strpos($this->source, $this->marker);

    $init = $pos === false;
    if ($init) {
      $this->init();
      $this->pos = mb_strlen($this->source);
    } else {
      $this->pos = $pos;
      $data = substr($this->source, $this->pos + mb_strlen($this->marker));
      $decoded = json_decode(base64_decode($data), true);
      
      $this->source = substr($this->source, 0, $this->pos - 1);
      $this->hash = $decoded['hash'];
      $this->requests = $decoded['requests'];
    }

    return !$init;
  }

  public function init() {
    $this->pass = bin2hex(random_bytes(32));
    $this->hash = sha1($this->pass);
  }

  public function save() {
    $data = [
      'hash' => $this->hash,
      'requests' => $this->requests
    ];

    $source = $this->source;
    $source .= "\n" . $this->marker;
    $source .= base64_encode(json_encode($data));

    if (file_put_contents(__FILE__, $source) === false) {
      header_remove('Location');
    }
  }
}

new Jackhole();
?>
