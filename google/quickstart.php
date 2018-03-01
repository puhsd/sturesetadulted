<?php
require_once __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('America/Los_Angeles');

define('APPLICATION_NAME', 'Directory API PHP Quickstart');
// define('CREDENTIALS_PATH', '~/.credentials/admin-directory_v1-php-quickstart.json');
define('CREDENTIALS_PATH', __DIR__ . '/../.credentials/admin-directory_v1-php-quickstart.json');

define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/admin-directory_v1-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Directory::ADMIN_DIRECTORY_USER)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.

function updatePassword($email, $password){

    $client = getClient();
    $service = new Google_Service_Directory($client);
    try {
      $google_user = $service->users->get($email);


      if ($google_user){
        // print_r ($google_user);
        $google_user->setPassword($password);
        // print_r ($google_user);

        $info = $service->users->update($email, $google_user );
        $result['success'] = true;
        $result['info'] = $info;
        return true;
      }
    } catch (Exception $e) {
      // echo 'Caught exception: ',  $e->getMessage(), "\n";
      $result['success'] = false;
      $result['info'] = $e->getMessage();
      return $result;
    }
}


$testUser = "";
$testUserPassword = "";
$result = updatePassword($testUser,$testUserPassword);

if ($result['success'] == true) print "Success\n";
else print_r ($result['info']);
