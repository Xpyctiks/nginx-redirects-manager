<?php
$emptyFile="";
global $pdo;
date_default_timezone_set('Europe/Kyiv');

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
  //loading variables from config files
  include $confFile;
  if (empty($dbHost) || empty($dbName) || empty($dbPassword) || empty($dbPort) || empty($dbUser) || empty($nginxFolder) || empty($nginxAddConfigsFolder)) {
    $message="ERROR: Some important variables are not set in the config file not found. Can not proceed...";
    error_log($message,0);
    echo($message);
    die();
  }
  //creating PDO object for future work with
  $pdo=new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword");
  $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
} else {
  $config='<?php
//Hash to be used for passwords in DB.Auto generated with this config file.
$pwdSalt=\'$2a$10$'.genRandomStr(22).'$\';
//DB connection
$dbHost="127.0.0.1";
$dbPort="3306";
$dbName="nginx_manager";
$dbUser="nginx_manager";
$dbPassword="";
//nginx root folder. With trailing slash
$nginxFolder="/etc/nginx/";
//Additional configs folder. With trailing slash
$nginxAddConfigsFolder="additional-configs/";';

  file_put_contents($confFile,$config);
  echo("New config file was generated. Please, fill in the variables, create MySQL DB using schema.sql and refresh this page!");
  die();
}

//Console function to generate password hash for mysql db
if (isset($argv[1]) && $argv[1] == "genpwd") {
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
if (isset($argv[1]) && ($argv[1] == "adduser" && !isset($argv[2]) || $argv[1] == "help")) {
  echo("Usage: index.php adduser <username> <user_password> <user_realname> <user_role>\n");
  echo("<username> - username for login page\n");
  echo("<password> - user password for login page\n");
  echo("<user_realname> - real name of the nw user\n");
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
    $result=$pdo->query("INSERT INTO users (`username`, `password`, `realname`, `role`) VALUES ('".$argv[2]."', '".crypt($argv[3],$pwdSalt)."', '".$argv[4]."', '".$role."')");
    if ($result) {
      echo("User added successfully!\n");
    } else {
      echo("Seems like some error happened during addition of the user. Check it manually!\n");
    }
    die();
  }
  else
  {
    echo("Error! Some of important parameters is not set. Try \"index.php help\" to get all information about.\n");
    die();
  }
} ?>

<?php
//-------------------HEADER HTML---------
include("templates/HeaderGeneral.html");
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
      header('Location: /');
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
      //have no idea how I found that
      unset($fileContent[$lineNumber]);
      unset($fileContent[$lineNumber+1]);
      unset($fileContent[$lineNumber+2]);
      // if ($fileContent[$lineNumber+3] == "\n") {
      //   unset($fileContent[$lineNumber+3]);
      // }
      file_put_contents($_COOKIE['path'], implode("", $fileContent));
      header('Location: /');
    }
    else
    {
      echo("Error opening file ".$_COOKIE['path']);
    }
  }
}

//processing Logout button
if (isset($_POST['logout'])) {
  session_unset();
  $result=$pdo->query("SELECT * FROM users WHERE session='".htmlspecialchars($_COOKIE['PHPSESSID'])."'");
  foreach ($result as $row) {
    $username=$row['username'];
    $password=$row['password'];
    $uid=$row['id'];
  }
  //clearing Session field in DB
  $result=$pdo->query("UPDATE users SET session='' WHERE id='".$uid."'");
  //clearing cookies
  setcookie("PHPSESSID","",time()-3600);
  setcookie("realname","",time()-3600);
  setcookie("domain","",time()-3600);
  setcookie("path","",time()-3600);
  setcookie("type","",time()-3600);
  unset($_COOKIE['PHPSESSID']);
  header('Location: /');
}

//processing Login requst
if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
  $result=$pdo->query("SELECT * FROM users WHERE username='".htmlspecialchars($_POST['username'])."'");
  foreach ($result as $row) {
    $username=$row['username'];
    $password=$row['password'];
    $uid=$row['id'];
    $realname=$row['realname'];
  }
  //password is ok
  if (password_verify($_POST['password'],$password)) {
    session_start([
      'cookie_lifetime' => 28800,
    ]);
    $result=$pdo->query("UPDATE users SET session='".session_id()."' WHERE id='".$uid."'");
    setcookie("realname",$realname);
    $_COOKIE['PHPSESSID']='';
    header('Location: /');
  } else {
    //set variable which will show the alert (processed in the end of the code)
    $wrongLoginPwd="1";
  }
}

//MAIN LOGIN PAGE
//if there is no PHPSESSID cookie set, show the login dialog
if (!isset($_COOKIE['PHPSESSID'])) {
  //-------------------LOGIN PAGE HTML---------
  include("templates/LoginWindow.html");
  //-----------FOOTER GENERAL HTML--------------
  include("templates/FooterGeneral.html");
  die();
} else {
  //check session ID from cookie and DB. IF they are different - logout this user.
  $result=$pdo->query("SELECT * FROM users WHERE session='".htmlspecialchars($_COOKIE['PHPSESSID'])."'");
  foreach ($result as $row) {
    $session=$row['session'];
  } 
  //if no session from users table found, set variable as empty  
  if(!isset($session)) {
    $session="";
  }
  if ($_COOKIE['PHPSESSID'] != $session) {
    session_unset();
    setcookie("PHPSESSID","",time()-3600);
    setcookie("realname","",time()-3600);
    setcookie("domain","",time()-3600);
    setcookie("path","",time()-3600);
    setcookie("type","",time()-3600);
    unset($_COOKIE['PHPSESSID']);
    //echo("Session expired or wrong. Please, log in again.");
    header('Location: /');
    die();
  }
}

//process changing of Domain to work with from header menu of the page
if (isset($_GET['setdomain']) && !empty($_GET['setdomain'])) {
  $result=$pdo->query("SELECT path FROM domains WHERE domain='".htmlspecialchars($_GET['setdomain'])."' AND type='".htmlspecialchars($_COOKIE['type'])."'");
  foreach ($result as $row) {
    $path=$row['path'];
  }
  setcookie("path",$row['path']);
  setcookie("domain",$_GET['setdomain']);  
  header('Location: /');
}

//process changing of Redirect type from header menu of the page
if (isset($_GET['settype']) && !empty($_GET['settype'])) {
  $result=$pdo->query("SELECT path FROM domains WHERE domain='".htmlspecialchars($_COOKIE['domain'])."' AND type='".htmlspecialchars($_GET['settype'])."'");
  foreach ($result as $row) {
    $path=$row['path'];
  }
  setcookie("path",$path);
  setcookie("type",$_GET['settype']);
  header('Location: /');
} 

//processing errors while adding new records
if (isset($_COOKIE['addrecordsError'])) {
  setcookie("addrecordsError","",time()-3600);
  $_POST['addnew']="addnew";
}

//processing addnew function with all data fields, but no file
if (isset($_POST['addnewSubmit']) && !isset($_POST['fileUpload'])) {
  //if all variables are set and not empty, moving on
  if (!empty($_POST['RedirectFromField']) && !empty($_POST['RedirectToField']) && !empty($_POST['templateField'])) {
    //filling up a template variable
    $result=$pdo->query("SELECT template FROM templates WHERE name='".htmlspecialchars($_POST['templateField'])."'");
    foreach ($result as $row) {
      $template=explode(PHP_EOL,$row['template']);
    }
    //preparing full text
    if (isset($_COOKIE['path']) && !empty($_COOKIE['path'])) {
      $line1=str_replace("%1",trim($_POST['RedirectFromField']),preg_replace('~(*BSR_ANYCRLF)\R~', "\n",$template[0]));
      $line2=rtrim(str_replace("%2","https://".$_COOKIE['domain'].trim($_POST['RedirectToField']),preg_replace('~(*BSR_ANYCRLF)\R~', "\n",$template[1])));
      $line3="\n".$template[2]."\n";
      if (file_put_contents($_COOKIE['path'], $line1.$line2.$line3, FILE_APPEND) == false) {
        setcookie("addrecordsError","Error writing to config file! No changes was saved. Check file and folder permissions.");
        error_log("Error writing to config file! Check permissions and correct path!",0);
      }
      header('Location: /');
    }
  } else {
    //some variables are not set or empty. Show an error.
    setcookie("addrecordsError","Some fields are empty!");
    error_log("Error writing to config file! Check permissions and correct path!",0);
    header('Location: /');
  }
  die();
}

//processing addnew function with file upload
if (isset($_POST['addnewSubmit']) & !empty($_POST['templateField']) && (isset($_FILES['fileUpload']['name']))) {
  $uploaddir='/tmp/';
  $uploadfile=$uploaddir.basename("nginx-manager.csv");
  move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadfile);
  //filling up a template variable
  $result=$pdo->query("SELECT template FROM templates WHERE name='".htmlspecialchars($_POST['templateField'])."'");
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
        if (file_put_contents($_COOKIE['path'], $line1.$line2.$line3, FILE_APPEND) == false) {
          setcookie("addrecordsError","Error writing to config file! No changes was saved. Check file and folder permissions.");
          error_log("Error writing to config file! Check permissions and correct path!",0);
        }
      }
      fclose($handle);
      unlink($uploadfile);
      header('Location: /');
    }
  }
}
//processing Add new records dialog
if (isset($_POST['addnew'])) {
  if (!isset($_COOKIE['domain']) && (!isset($_COOKIE['type']))) {
    //-----------HEADER PANEL HTML--------------
    include("templates/HeaderPanel.html");?>
    <div class="alert alert-warning alert-dismissible fade show" role="info" style="margin-top: 75px;">
      First you need to choose a domain and redirect type to acces Add Records page
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php //-----------FOOTER GENERAL HTML--------------
    include("templates/FooterGeneral.html");
    die();
  }
  //check if we have a cookie which tells us there is an error while last operation - show the alert window
  if (isset($_COOKIE['addrecordsError'])) {
    setcookie("addrecordsError","",time()-3600);
    //-----------HEADER PANEL HTML--------------
    include("templates/HeaderPanel.html");
    //-----------ADD NEW PAGE HTML--------------
    include("templates/AddNewPage.html");
    //-----------ADD RECORDS ERROR MODAL HTML--------------
    include("templates/AddRecordsErrorModal.html"); 
    echo("</div>");
    //-----------FOOTER GENERAL HTML--------------
    include("templates/FooterGeneral.html");
  //check if we have a cookie which tells us there is an error while last operation - show the alert window
  } else {
    //-----------HEADER PANEL HTML--------------
    include("templates/HeaderPanel.html");
    //-----------ADD NEW PAGE HTML--------------
    include("templates/AddNewPage.html");
    echo("</div>");
    //-----------FOOTER GENERAL HTML--------------
    include("templates/FooterGeneral.html");
  }
  die();
} 

//processing Rollback operation
if (isset($_POST['rollback'])) {
  $hdr=date("Y-m-d H:i:s")." Rollback to commit \"".trim(substr(file_get_contents($nginxFolder.$nginxAddConfigsFolder.".git/COMMIT_EDITMSG"),0,39))."\" by ".$_COOKIE['realname'];
  ob_start();
  passthru("./rollback.sh \"".$nginxFolder."\" \"".$nginxAddConfigsFolder."\"");
  $res=ob_get_contents();
  ob_end_clean();
  file_put_contents("rollback.log", $hdr." Result: ".$res, FILE_APPEND);
  header('Location: /');
}

//processing Commit operation
if (isset($_POST['commit'])) {
  $hdr=date("Y-m-d H:i:s")." Commiting changes from commit \"".trim(substr(file_get_contents($nginxFolder.$nginxAddConfigsFolder.".git/COMMIT_EDITMSG"),0,39))."\" to the new one by ".$_COOKIE['realname'];
  ob_start();
  passthru("./commit.sh \"".$_COOKIE['realname']."\" \"".$nginxFolder."\" \"".$nginxAddConfigsFolder."\"");
  $res=ob_get_contents();
  file_put_contents("commit.log", $hdr." Result: ".$res, FILE_APPEND);
  ob_end_clean();
  setcookie("commitMessage",$res);
  header('Location: /');
}

//if we have a cookie with text after commit, get text from cookie and delete it.Then show message during futher code execution
if (isset($_COOKIE['commitMessage'])) {
  $commitMessage=$_COOKIE['commitMessage'];
  setcookie("commitMessage","",time()-3600);
}

  //-----------HEADER PANEL HTML--------------
  include("templates/HeaderPanel.html");
  include("templates/TableHeader.html");
  //loading raw data from text config file
  if (!isset($_COOKIE['path'])) { ?>
    <div class="alert alert-warning alert-dismissible fade show" role="info" style="margin-top: 15px;">
    Please choose a domain and redirect type first
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <script>
    document.getElementById("spinnerLoading").style.visibility = "hidden";
  </script>
  <?php die(); 
  } else {
  $dataPre=file_get_contents($_COOKIE['path']);
  //explode the data to an array
  $data=explode(PHP_EOL,$dataPre);
  //ID will be user for the first column of table - number of record in table.
  $id=0;
  //parsing array.Filling up a table by variables from the array
  for ($i=0;$i<count($data);$i++) {
    //check does the current string has "location" key - that means we start counting from this string, then +1 for redirect rule and +2 for "}" symbol
    if (strpos($data[$i],"location") !== false) {
      echo("\n\t<tr>\n");
      echo("\t\t<th scope=\"row\">$id</th>\n");
      //Generate RedirectFROM
      echo("\t\t<td>");
      $redirectFromEnd=strpos($data[$i],"{");
      $redirectFrom=substr($data[$i],11,-2);
      if ($dataPre==false) {
        echo("No records in file.");
      } else {
        echo($redirectFrom);
      }
      echo("</td>\n");
      echo("\t\t<td>");
      echo("<input class=\"form-check-input is-invalid\" type=\"checkbox\" name=\"checkbox[]\" value=\"".$i."\" id=\"checkbox\">");
      echo("</td>\n");
      //generate RedirectTO
      echo("\t\t<td>");
      $redirectToStart=strpos($data[$i+1],"$");                
      $redirectTo=substr($data[$i+1],$redirectToStart+2,-10);
      echo($redirectTo);
      echo("</td>\n");
      //generate type of redirect
      echo("\t\t<td>");
      $typeEnd=strlen($data[$i])-10;
      $typeRedir=substr($data[$i],8,-$typeEnd);
      echo($typeRedir);
      echo("</td>\n"); ?>
      <td>
        <button type="submit" class="btn btn-danger" name="delete" value="<?php echo($i);?>" onclick="deleteLoading()">Delete</button>
      </td>
      <?php
      //prints the current "location" string number inside the file
      echo("<td>");
      echo($i);
      echo("</td>\n");
      echo("\t</tr>\n");
      $id++;
      }
    if (count($data) <= 1)
    {
      $emptyFile="1";
    }
  }
} ?>
  </form>
  <script>
    document.getElementById("spinnerLoading").style.visibility = "hidden";
    <?php if (isset($data)) { echo("document.getElementById(\"totalLines\").textContent=\"Total lines loaded: ".count($data)."\";"); };
    if (isset($id)) { echo("document.getElementById(\"totalRecords\").textContent=\"Total records: ".$id."\";\n"); } ?>
  </script>
</tbody>
</table>
<?php 
if (isset($commitMessage)) {
  include("templates/CommitInfoModal.html");
}
//-----------FOOTER GENERAL HTML--------------
include("templates/FooterGeneral.html");
