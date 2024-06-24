<?php
$emptyFile="";

function genRandomStr($length) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  $length=22;
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[random_int(0, $charactersLength - 1)];
  }
  return $randomString;
}
//getting variables from config file
$confFile="./nginx-manager.conf";
if (file_exists($confFile)) {
  include $confFile;
  if (empty($dbHost) || empty($dbName) || empty($dbPassword) || empty($dbPort) || empty($dbUser)) {
    $message="ERROR: Some important variables are not set in the config file not found. Can not proceed...";
    error_log($message,0);
    echo($message);
    die();
  }
} else {
  $config='<?php
//Hash to be used for passwords in DB.Auto generated with this config file.
$pwdSalt=\'$2a$10$'.genRandomStr(22).'$\';
//DB connection
$dbHost="127.0.0.1";
$dbPort="3306";
$dbName="nginx_manager";
$dbUser="nginx_manager";
$dbPassword="";';
  file_put_contents($confFile,$config);
  echo("New config file was generated. Please, fill in the variables, create MySQL DB using schema.sql and refresh this page!");
  die();
}

//Console function to generate password hash for mysql db
if (isset($argv[1]) && ($argv[1] == "genpwd")) {
  if (isset($argv[2])) {
    echo(crypt($argv[2],$pwdSalt));
    echo("\n");
  } else {
    echo('"password" parameter is not set'."\n");
  }
  die();
}
//Console function to add user
//Show help function
if (isset($argv[1]) && (($argv[1] == "adduser") && (!isset($argv[2]))) || ($argv[1] == "help")) {
  echo("Usage: index.php adduser <username> <user_password> <user_realname> <user_role>\n");
  echo("<username> - username for login page\n");
  echo("<password> - user password for login page\n");
  echo("<useer_realname> - real name of the nw user\n");
  echo("<user_role> - \"1\" - default User role. \"2\" - Admin role\n");
  die();
}
//add user function
if (isset($argv[1]) && ($argv[1] == "adduser") && isset($argv[2])) {
  //checking all necessary parameters are set
  if (isset($argv[2]) && isset($argv[3]) && isset($argv[4])) {
    //checking if Role parameter set. If not, using default vaule 1.
    if (isset($argv[5])) {
      $role=$argv[5];
    } else {
      $role="1";
    }
    //processing request
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $result=$pdo->query("INSERT INTO users (`username`, `password`, `realname`, `role`) VALUES ('".$argv[2]."', '".crypt($argv[3],$pwdSalt)."', '".$argv[4]."', '".$role."')");
    if ($result) {
      echo("User added successfully!\n");
    } else {
      echo("Seems like some error happened during addition of the user. Check it manually!\n");
    }
    unset($pdo);
    die();
  }
  else
  {
    echo("Error! Some of important parameters is not set. Try \"index.php help\" to get all information about.\n");
    die();
  }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
  <title>Nginx Redirects Manager</title>
  <link rel="icon" href="favicon.ico" type='image/x-icon' sizes="16x16" />
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <script src="js/bootstrap.bundle.min.js"></script> 
</head>
<body>

<?php
//PROCESSING "DELETE" OPERATIONS
//Multiple delete request
if (isset($_POST['delete']) && (count($_POST)>=2)) {
  if (isset($_COOKIE['path']) && !empty($_COOKIE['path'])) {
    if ($fileContent=file($_COOKIE['path'])) {
      foreach($_POST['checkbox'] as $lineNumber){
        unset($fileContent[$lineNumber]);
        unset($fileContent[$lineNumber+1]);
        unset($fileContent[$lineNumber+2]);
        if ($fileContent[$lineNumber+3] == "\n") {
          unset($fileContent[$lineNumber+3]);
        } 
      }
      file_put_contents($_COOKIE['path'], implode("", $fileContent));
      header('Location: index.php');
    }
    else
    {
      echo("Error opening file ".$_COOKIE['path']);
    }
  }
} 
//Delete single request - by pressing Delete button with no checkbox selected anywhere
if (isset($_POST['delete']) && (count($_POST)==1)) {
  if (isset($_COOKIE['path']) && !empty($_COOKIE['path'])) {
    if ($fileContent=file($_COOKIE['path'])) {
      $lineNumber=$_POST['delete'];
      unset($fileContent[$lineNumber]);
      unset($fileContent[$lineNumber+1]);
      unset($fileContent[$lineNumber+2]);
      if ($fileContent[$lineNumber+3] == "\n") {
        unset($fileContent[$lineNumber+3]);
      }
      file_put_contents($_COOKIE['path'], implode("", $fileContent));
      header('Location: index.php');
    }
    else
    {
      echo("Error opening file ".$_COOKIE['path']);
    }
  }
  //die();
}

if (isset($_POST['logout'])) {
  session_unset();
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT * FROM users WHERE session='".htmlspecialchars($_COOKIE['PHPSESSID'])."'");
  foreach ($result as $row) {
    $username=$row['username'];
    $password=$row['password'];
    $uid=$row['id'];
  }
  $result=$pdo->query("UPDATE users SET session='' WHERE id='".$uid."'");
  unset($pdo);
  setcookie("PHPSESSID","",time()-3600);
  setcookie("realname","",time()-3600);
  setcookie("domain","",time()-3600);
  setcookie("path","",time()-3600);
  setcookie("type","",time()-3600);
  unset($_COOKIE['PHPSESSID']);
  header('Location: index.php');
}

if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT * FROM users WHERE username='".htmlspecialchars($_POST['username'])."'");
  unset($pdo);
  foreach ($result as $row) {
    $username=$row['username'];
    $password=$row['password'];
    $uid=$row['id'];
    $realname=$row['realname'];
  }
  if (password_verify($_POST['password'],$password)) {
    session_start();
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $result=$pdo->query("UPDATE users SET session='".session_id()."' WHERE id='".$uid."'");
    unset($pdo);
    setcookie("realname",$realname);
    $_COOKIE['PHPSESSID']='';
    header('Location: index.php');
  } else {
    $wrongLoginPwd="1";
  }
}

if (!isset($_COOKIE['PHPSESSID'])) {
  ?>
  <div id="loginContainer" style="width: 400px; height: 400px; margin-left: 40vw; margin-top: 25vh;">
  <p class="display-6" style="text-align: center; margin-left: 1px;">Nginx Redirects Manager</p>
  <span class="badge rounded-pill bg-primary" style="font-size: 14px; margin-bottom: 15px; margin-left: 100px;">Authorization is needed here:</span>
    <form action="" method="POST">
    <div class="input-group mb-3">
      <span class="input-group-text">Username</span>
      <input type="text" class="form-control" id="username" name="username">
    </div>
    <div class="input-group mb-3">
      <span class="input-group-text">Password&nbsp;</span>
      <input type="password" class="form-control" id="password" name="password">
    </div>
    <button style="margin-left: 170px;" type="submit" id="login" name="login" class="btn btn-primary" onclick="showLoading()">Log In</button>
    <script>
      function showLoading() {
        document.getElementById("spinner").style.visibility = "visible";
      }
    </script>
    </form>
    <div class="spinner-border text-primary" role="status" style="margin-left: 190px; margin-top: 15px; visibility: hidden;" id="spinner">
      <span class="visually-hidden">Loading...</span>
    </div>
    <?php
    if ($wrongLoginPwd == "1") { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top: 15px;">
      Wrong username or password!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
    }    
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $result=$pdo->query("SELECT COUNT(*) FROM users;");
    unset($pdo);
    foreach ($result as $row) {
      if ($row['COUNT(*)'] == 0){
        ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top: 15px;">
          Three is no users set in database! You need to add at least one.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php
      }
    } 
    ?>
  </div>
  <?php
  die();
} 
else {
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT * FROM users WHERE session='".htmlspecialchars($_COOKIE['PHPSESSID'])."'");
  unset($pdo);
  foreach ($result as $row) {
    $session=$row['session'];
  }  
  if ($_COOKIE['PHPSESSID'] != $session) {
    session_unset();
    setcookie("PHPSESSID","",time()-3600);
    unset($_COOKIE['PHPSESSID']);
    echo("Session expired or wrong. Please, log in again.");
    die();
  }
}

//process changing of Domain to work with from header menu of the page
if (isset($_GET['setdomain']) && !empty($_GET['setdomain'])) {
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT path FROM domains WHERE domain='".htmlspecialchars($_GET['setdomain'])."' AND type='".htmlspecialchars($_COOKIE['type'])."'");
  unset($pdo);
  foreach ($result as $row) {
    $path=$row['path'];
  }
  setcookie("path",$row['path']);
  setcookie("domain",$_GET['setdomain']);  
  header('Location: index.php');
}

//process changing of Redirect type from header menu of the page
if (isset($_GET['settype']) && !empty($_GET['settype'])) {
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT path FROM domains WHERE domain='".htmlspecialchars($_COOKIE['domain'])."' AND type='".htmlspecialchars($_GET['settype'])."'");
  unset($pdo);
  foreach ($result as $row) {
    $path=$row['path'];
  }
  setcookie("path",$path);
  setcookie("type",$_GET['settype']);
  header('Location: index.php');
} 

//processing addnew function with all data fields, but no file
if (isset($_POST['addnewSubmit']) && !empty($_POST['RedirectFromField']) && !empty($_POST['RedirectToField']) && !empty($_POST['templateField']) && !isset($_POST['fileUpload'])) {
  //filling up a template variable
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT template FROM templates WHERE name='".htmlspecialchars($_POST['templateField'])."'");
  unset($pdo);
  foreach ($result as $row) {
    $template=explode(PHP_EOL,$row['template']);
  }
  //preparing full text
  if (isset($_COOKIE['path']) && !empty($_COOKIE['path'])) {
    $line1=str_replace("%1",trim($_POST['RedirectFromField']),preg_replace('~(*BSR_ANYCRLF)\R~', "\n",$template[0]));
    $line2=rtrim(str_replace("%2","https://".$_COOKIE['domain'].trim($_POST['RedirectToField']),preg_replace('~(*BSR_ANYCRLF)\R~', "\n",$template[1])));
    $line3="\n".$template[2]."\n";
    file_put_contents($_COOKIE['path'], $line1.$line2.$line3, FILE_APPEND);
    header('Location: index.php');
  }
}

//processing addnew function with file upload
if (isset($_POST['addnewSubmit']) & !empty($_POST['templateField']) && (isset($_FILES['fileUpload']['name']))) {
  $uploaddir='/tmp/';
  $uploadfile=$uploaddir.basename("nginx-manager.csv");
  move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadfile);
  //filling up a template variable
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
  $result=$pdo->query("SELECT template FROM templates WHERE name='".htmlspecialchars($_POST['templateField'])."'");
  unset($pdo);
  foreach ($result as $row) {
    $template=explode(PHP_EOL,$row['template']);
  }
  //preparing full text
  if (isset($_COOKIE['path']) && !empty($_COOKIE['path'])) {
    $row = 1;
    if (($handle = fopen($uploadfile, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (empty($data[0])) { 
          continue;
        }
        $line1=str_replace("%1",$data[0],preg_replace('~(*BSR_ANYCRLF)\R~', "\n",$template[0]));
        $line2=rtrim(str_replace("%2","https://".$_COOKIE['domain'].$data[1],preg_replace('~(*BSR_ANYCRLF)\R~', "\n",$template[1])));
        $line3="\n".$template[2]."\n";
        file_put_contents($_COOKIE['path'], $line1.$line2.$line3, FILE_APPEND);
      }
      fclose($handle);
      unlink($uploadfile);
      header('Location: index.php');
    }
  }
}

?>
  <div>
  <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="/">Nginx Redirects Manager v1.0</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav me-auto mb-2 mb-md-0">
        <li class="nav-item">
            <div class="dropdown">
              <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
              <?php 
              if (isset($_COOKIE['domain']) && !empty($_COOKIE['domain'])) {
                echo($_COOKIE['domain']);
              } else {
                echo("Current domain:");
              }
              ?>
              </button>
              <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <li><h6 class="dropdown-header">Available domains:</h6></li>
              <?php
                $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $result=$pdo->query("SELECT DISTINCT domain FROM domains ORDER BY domain");
                unset($pdo);
                foreach ($result as $row) {
                  echo("<li><a class=\"dropdown-item\" href=\"?setdomain=".$row['domain']."\">".$row['domain']."</a></li>");
                }
              ?>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <div class="dropdown">
              <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
              <?php 
              if (isset($_COOKIE['type']) && !empty($_COOKIE['type'])) {
                echo($_COOKIE['type']);
              } else {
                echo("Redirect type:");
              }
              ?>
              </button>
              <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <li><h6 class="dropdown-header">Redirect type:</h6></li>
              <?php
                $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $result=$pdo->query("SELECT DISTINCT type FROM domains WHERE domain='".htmlspecialchars($_COOKIE['domain'])."' ORDER BY type");
                unset($pdo);
                foreach ($result as $row) {
                  echo("<li><a class=\"dropdown-item\" href=\"?settype=".$row['type']."\">".$row['type']."</a></li>");
                }
              ?>
              </ul>
            </div>
          </li>
          <li class="nav-item" style="margin-left: 5px;">
            <form class="d-flex" action="/" method="POST">
              <button type="submit" class="btn btn-info" name="addnew" id="addnew" value="addnew">Add new records</button>
              </form>
          </li>
          <li class="nav-item">
            <a class="nav-link active" id="totalLines" style="color: #ffc107;"></a>
          </li>
          <li>
            <a class="nav-link active" id="totalRecords" style="color: #ffc107;"></a>
          </li>
          <span class="spinner-border text-warning" role="status" id="spinnerLoading" style="margin-left: 5px; margin-top: 5px; visibility: hidden;"></span>
        </ul>
        <form class="d-flex" action="/" method="POST">
          <button class="btn btn-outline-warning" type="submit" id="logout" name="logout"><?php echo($_COOKIE['realname']);?>&nbsp;Logout</button>
        </form>
      </div>
    </div>
  </nav></div>
  <?php
  //processing Add new records dialog
  if (isset($_POST['addnew'])) {
    ?>
    <div class="card" style="margin-top: 15vh; margin-left: 30vw; width: 80vh; height: 30vw;">
    <div class="card-header">
      <?php echo("Add new <strong>".$_COOKIE['type']."</strong> redirects for domain <strong>".$_COOKIE['domain']."</strong> :");?>
    </div>
    <div class="card-body">
      <form action="/" method="POST" enctype="multipart/form-data">
      <h5 class="card-title">Step 1:</h5>
      <p class="card-text">You can upload a CSV file with your redirects.</p>
      <input type="file" class="form-control form-control-sm" name="fileUpload" id="fileUpload"><br>
      <p class="card-text">or add single record right here:</p>
      <div class="input-group mb-3">
        <span class="input-group-text" id="RedirectFromField">Redirect from:</span>
        <input type="text" class="form-control" placeholder="/page/subpage/oldpage/" name="RedirectFromField">
      </div>
      <div class="input-group mb-3">
        <span class="input-group-text" id="RedirectToField" >Redirect to:&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <input type="text" class="form-control" placeholder="/page/newpage/" name="RedirectToField">
      </div>
      <p class="card-text">If you selected a file, this file will be submitted.Data from text forms will be ignored.<br>If you didn't selected a file, text froms data will be submitted.</p>
      <h5 class="card-title">Step 2:</h5>
      <div class="mb-3">
      <span class="badge rounded-pill bg-info text-dark">Select a template for the redirect:</span>
        <select class="form-select form-select-sm" name="templateField">
        <?php
          $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
          $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
          $result=$pdo->query("SELECT * FROM templates WHERE type='".htmlspecialchars($_COOKIE['type'])."' ORDER BY name");
          unset($pdo);
          foreach ($result as $row) {
            echo("<option value=\"".$row['name']."\">".$row['name']." (".$row['hint'].")</option>");
          }
          ?>
        </select>
        </div>
        <button type="submit" class="btn btn-info" name="addnewSubmit" value="addnewSubmit">Submit</button>
      </form>
    </div>
  </div>
    <?php
    die();
  }
  ?>
  <main style="">
  <div style="margin-top: 7vh;max-width: 95%; margin-left: 50px;">
    <div>
    <table class="table table-striped">
      <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Redirect from:</th>
        <th scope="col">Select:&nbsp;<input class="form-check-input is-invalid" type="checkbox" onclick="checkAll(this)" id="checkbox"></th>
        <th scope="col">Redirect to:</th>
        <th scope="col">Type:</th>
        <th scope="col">Action:</th>
        <th scope="col">Line#:</th>
      </tr>
      </thead>
      <tbody>
      <script>
        document.getElementById("spinnerLoading").style.visibility = "visible";
        function checkAll(bx) {
          var cbs = document.getElementsByTagName('input');
          for(var i=0; i < cbs.length; i++) {
            if(cbs[i].type == 'checkbox') {
              cbs[i].checked = bx.checked;
            }
          }
        }
      </script>
      <form action="/" method="POST">
      <?php
      if (file_exists($_COOKIE['path'])==false) {
        ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top: 15px;">
        Config file <?php echo($_COOKIE['path']);?> not found or unable to open it!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
          document.getElementById("spinnerLoading").style.visibility = "hidden";
        </script>
        <?php
      } 
      else 
      {
        $dataPre=file_get_contents($_COOKIE['path']);
        $data=explode(PHP_EOL,$dataPre);
        $id=0;
        for ($i=0;$i<count($data);$i++) {
          if (strpos($data[$i],"location") !== false) {
            echo("<tr>");
              echo("<th scope=\"row\">$id</th>");
              //Generate RedirectFROM
              echo("<td>");
              $redirectFromEnd=strpos($data[$i],"{");
              $redirectFrom=substr($data[$i],11,-2);
              if (($dataPre)==false) {
                echo("No records in file.");
              } else {
              echo($redirectFrom);
              }
              echo("</td>");
              echo("<td>");
              echo("<input class=\"form-check-input is-invalid\" type=\"checkbox\" name=\"checkbox[]\" value=\"".$i."\" id=\"checkbox\">");
              echo("</td>");
              //generate RedirectTO
              echo("<td>");
              $redirectToStart=strpos($data[$i+1],"$");                
              $redirectTo=substr($data[$i+1],$redirectToStart+2,-10);
              echo($redirectTo);
              echo("</td>");
              //generate type of redirect
              echo("<td>");
              $typeEnd=strlen($data[$i])-10;
              $typeRedir=substr($data[$i],8,-$typeEnd);
              echo($typeRedir);
              echo("</td>");
              ?>
              <td>
                <button type="submit" class="btn btn-danger" name="delete" value="<?php echo($i);?>" onclick="showLoading()">Delete</button>
                <script>
                  function showLoading() {
                    document.getElementById("spinnerLoading").className = "spinner-border text-danger";
                    document.getElementById("spinnerLoading").style.visibility = "visible";
                    }
                </script>
              </td>
              <?php
              echo("<td>");          
              echo($i);
              echo("</td>");
            echo("</tr>");
            $id++;
          }
          if (count($data) <= 1)
          {
            $emptyFile="1";
          }
        }
      }
      ?>
      </form>
      <script>
        document.getElementById("spinnerLoading").style.visibility = "hidden";
        <?php
        echo("document.getElementById(\"totalLines\").textContent=\"Total lines loaded: ".count($data)."\";");
        echo("document.getElementById(\"totalRecords\").textContent=\"Total records: ".$id."\";");
        ?>
      </script>
    </tbody>
    </table>
    <?php
    if ($emptyFile=="1") { ?>
    <div class="alert alert-info alert-dismissible fade show" role="info" style="margin-top: 15px;">
      This config file has no records yet...
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
    }
    ?>
    </div>
  </main>
</body>
</html>
