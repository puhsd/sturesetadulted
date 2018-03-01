<?php
  // error_reporting(0);
  define('LDAP_SERVER', 'ldaps://ldap.example.com:636');
  define('LDAP_BASE_OU', "OU=Accounts,DC=EXAMPLE,DC=COM");
  define('LDAP_DOMAIN', "example.com"); //required for to update audlt school domain
  define('VERIFY_DOMAIN', "example01.com"); //domain name that will be replaced to connect to Active Directory


  $bootstrap = ["Success" => "alert-success", "Failed" => "alert-danger", "Info" => "alert-info", "Warning" => "alert-warning"];

  $message = "";
?>
<html>
<head>
  <style>
    .tab-content {
      border-left: 1px solid #ddd;
      border-right: 1px solid #ddd;
      border-bottom: 1px solid #ddd;
      padding: 10px;
    }

    .nav-tabs {
        margin-bottom: 0;
    }
  </style>
<title>Password Reset</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

<link rel='stylesheet' type='text/css' href='//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css'>
</head>
<body>
<div class="container ">
  <div class="col-xs-2 col-xs-offset-5 ">
    <img class=" m-x-auto d-block img-responsive" src="https://d1h00kd22lgwsm.cloudfront.net/api/file/HvEL26J8TXqhzWXK4sPI" alt="PUHSD Logo">

  </div>
</div>

<br/>
<?php

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}




if (!empty($_POST)) {

  function connect_AD($username, $password)
  {
    try {

        $ad = ldap_connect(LDAP_SERVER) ;

        ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3) ;

        $bound = ldap_bind($ad, $username, $password);

        if ($bound) {
            $result['status'] = 'Success';
            $result['info'] = $ad;

        }
        else {
            $result['status'] = 'Failed';
            $result['info'] = ldap_error($ad);

        }

    } catch (Exception $e) {

      $result['status'] = 'Failed';
      $result['info'] = $e->getMessage();

    }
    return $result ;
  }

  // function getDN(){
  //   $ad = connect_AD();
  //
  //   $s = ldap_search($ad, "OU=Student,OU=Accounts,DC=PUHSD,DC=ORG", "mail=".$_POST['studentEmail']);
  //   // echo(ldap_count_entries($ad, $s)."\n");
  //   $info = ldap_get_entries($ad, $s);
  //   return $info[0]['distinguishedname'][0];
  // }

  function resetPassword($staffEmail, $staffPassword, $studentEmail){
    $password = generateRandomString(8);

    //encode password
    $pwdtxt = "$password";
    $newPassword = '"' . $pwdtxt . '"';
    $newPass = iconv( 'UTF-8', 'UTF-16LE', $newPassword );
    //$ldaprecord["unicodepwd"] = $newPassw;
    $new["unicodepwd"] = $newPass;


    $result = connect_AD($staffEmail, $staffPassword);
    if ($result['status'] == 'Success'){
        $ad = $result['info'];

        $search_result = ldap_search($ad, LDAP_BASE_OU, "mail=".$studentEmail);

        $info = ldap_get_entries($ad, $search_result);


        if ($info['count'] != 0){
          $dn = $info[0]['distinguishedname'][0];
          if (ldap_modify($ad, $dn, $new) === false) {
            $result['info'] = ldap_error($ad)."<BR/>".ldap_errno($ad);
            $result['status'] = "Failed";
          } else  {
            $result['info'] = ldap_error($ad) == 'Success' ? "New temporary password for ".$studentEmail." is ".$password : ldap_error($ad);
            $result['status'] = ldap_error($ad) ? "Warning" : "Success";
          }
        } else {
          $result['info'] = "Email address ".$studentEmail." was not found.";
          $result['status'] = "Failed";
        }
    }

    return $result;

  }

  function setNewPassword($useremail,$currentpassword,$newpassword){
    // echo $useremail.",".$currentpassword.",".$newpassword;
    if ( explode("@",$useremail)[1] == VERIFY_DOMAIN){
      $ldap_user = explode("@",$useremail)[0].'@'.LDAP_DOMAIN;
      $result = connect_AD($ldap_user, $currentpassword);


      if ($result['status'] == 'Success'){
        try {
          $ad = $result['info'];

          // Getting Distinguished Name
          $search_result = ldap_search($ad, LDAP_BASE_OU, "samaccountname=".explode("@",$useremail)[0]);
          $info = ldap_get_entries($ad, $search_result);
          // print_r($info);
          $dn = $info[0]['distinguishedname'][0];


          //encode password
          $pwdtxt = "$newpassword";
          $newPassword = '"' . $pwdtxt . '"';
          $newPass = iconv( 'UTF-8', 'UTF-16LE', $newPassword );
          //$ldaprecord["unicodepwd"] = $newPassw;
          $new["unicodepwd"] = $newPass;

          // $new["userPassword"] = '{md5}' . base64_encode(pack('H*', md5($newpassword)));
          // $new["userPassword"] = "{SHA}" . base64_encode( pack( "H*", sha1( $newpassword ) ) );

          ldap_modify($ad, $dn, $new);

          $result['info'] = ldap_error($ad) ? ldap_error($ad) : "Loggin password has been set.";
          $result['status'] = ldap_error($ad) ? "Warning" : "Success";

          // Updating Google password
          require_once __DIR__.'/../google/googleReset.php';
          $google_result = updateGooglePassword($useremail,$newpassword);

          if ($google_result['success'] == true){
            $result['info'] .= "\nGoogle password has been set.";
          } else {
            $result['info'] .= "\n".$google_result['info'];
            $result['status'] = "Warning";
          }


        } catch (Exception $e) {
          // echo 'Caught exception: ',  $e->getMessage(), "\n";
          $result['status'] = 'Failed';
          $result['info'] = $e->getMessage();
        }


      }
    } else {
      $result['status'] = 'Warning';
      $result['info'] = 'Unsupported Domain';
    }

      return $result;
      // return ["status" => "Info", "info" => "Under Construction"];

  }



  if (isset($_POST['staffEmail']))
  {
      $message = resetPassword($_POST['staffEmail'],$_POST['staffPassword'],$_POST['studentEmail']);
  }
  elseif (isset($_POST['userEmail']))
  {
    if ($_POST['newPassword'] != "" && strlen($_POST['newPassword']) >= 8 && $_POST['newPassword'] == $_POST['confirmPassword'])
      $message = setNewPassword($_POST['userEmail'],$_POST['currentPassword'],$_POST['newPassword']);
    else
      $message = ["info" => "The password does not meet the minimum requirements, or the new password and confirm password do not match", "status" => "Warning"];
  }



}



?>


<div class="container">

  <?php
    if ($message) {
      echo '<div class="alert '.$bootstrap[$message['status']].'">';
      echo $message['info'];
      echo '</div>';
    }
  ?>

  <ul class="nav nav-tabs" id="passwordTab" role="tablist">
    <li class="nav-item active col-sm-6 text-center">
      <a class="nav-link" id="passwordreset-tab " data-toggle="tab" href="#passwordreset" role="tab" aria-controls="passwordreset" aria-selected="true">Reset Password</a>
    </li>
    <li class="nav-item  col-sm-6 text-center">
      <a class="nav-link" id="passwordchange-tab" data-toggle="tab" href="#passwordchange" role="tab" aria-controls="passwordchange" aria-selected="false">Set Password</a>
    </li>
  </ul>


  <div class="tab-content" id="passwordTabContent">

    <div class="tab-pane fade  active in " id="passwordreset" role="tabpanel" aria-labelledby="passwordreset-tab">
      <div class="card" >
        <div class="card-body">
          <form action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> method="post" >
            <div class="form-group">
              <label for="userEmail">Email Address</label>
              <input type="email" class="form-control" id="userEmail" name="userEmail" aria-describedby="emailHelp" placeholder="Enter email">
              <!-- <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small> -->
            </div>
            <div class="form-group">
              <label for="currentPassword">Current Password</label>
              <input type="password" class="form-control" id="currentPassword" name="currentPassword" placeholder="Enter your current password">
            </div>
            <div class="form-group">
              <label for="newPassword">New Password</label>
              <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="New Password">
            </div>
            <div class="form-group">
              <label for="confirmPassword">Confirm New Password</label>
              <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm New Password">
            </div>
            <div class="text-center">
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="tab-pane fade " id="passwordchange" role="tabpanel" aria-labelledby="passwordchange-tab">
      <form action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> method="post">
        <div class="form-group">
          <label for="staffEmail">Staff Email Address</label>
          <input type="email" class="form-control" id="staffEmail" name="staffEmail" aria-describedby="emailHelp" placeholder="Enter email">
          <!-- <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small> -->
        </div>
        <div class="form-group">
          <label for="staffPassword">Staff Password</label>
          <input type="password" class="form-control" id="staffPassword" name="staffPassword" placeholder="Password">
        </div>
        <div class="form-group">
          <label for="studentEmail">Student Email Address</label>
          <input type="email" class="form-control" id="studentEmail" name="studentEmail" aria-describedby="emailHelp" placeholder="Enter student email">
        </div>
        <div class="text-center">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>
    </div>

  </div>
</div>

</body>
</html>
