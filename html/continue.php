<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../db.php';
if (isset($_POST['create_account'])) {
  require '../bos/class.bos.php';
  $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
  $stmt->execute([$_POST['account_email'], password_hash($_POST['account_password'], PASSWORD_DEFAULT)]);
  $user_id = $db->lastInsertId();
  $stmt = $db->prepare('INSERT INTO api (user_id, client_id, client_secret, username, password) VALUES (?, ?, ?, ?, ?)');
  $stmt->execute([$user_id, $_POST['client_id'], $_POST['client_secret'], $_POST['email'], $_POST['password']]);
  $api_id = $db->lastInsertId();
  $org_urls = [];
  foreach ($_POST['org'] as $org) {
    $split = explode('$SEP$', $org);
    $stmt = $db->prepare('INSERT INTO orgs (api_id, name, url) VALUES (?, ?, ?)');
    $stmt->execute([$api_id, $split[1], $split[0]]);
    $org_id = $db->lastInsertId();
    $stmt = $db->prepare('INSERT INTO users_orgs_map (user_id, org_id) VALUES (?, ?)');
    $stmt->execute([$user_id, $org_id]);
    $org_urls[] = $split[0];
  }
  setcookie('user_id', $user_id, time()+31104000);
  ob_start(); // TODO: make this happen async in diff script
  $bos = new BuildingOS($db, [$_POST['client_id'], $_POST['client_secret'], $_POST['email'], $_POST['password']]);
  foreach ($org_urls as $org_url) {
    $bos->syncBuildings($org_url, true);
  }
  ob_clean();
  header('Location: home.php'); exit();
}
// Get list of orgs for form
$data = array(
  'client_id' => $_POST['client_id'],
  'client_secret' => $_POST['client_secret'],
  'username' => $_POST['email'],
  'password' => $_POST['password'],
  'grant_type' => 'password'
  );
$options = array(
  'http' => array(
    'method'  => 'POST',
    'content' => http_build_query($data)
    )
);
$context = stream_context_create($options);
$result = @file_get_contents('https://api.buildingos.com/o/token/', false, $context);
if (!$result) {
  var_dump($result);exit;
  header('Location: index.php/?err=no_token'); exit(); // incorrect bos credentials, try using different account
}
$json = json_decode($result, true);
$token = $json['access_token'];
$options = array(
  'http' => array(
    'method' => 'GET',
    'header' => 'Authorization: Bearer ' . $token
    )
);
$context = stream_context_create($options);
$request = json_decode(file_get_contents('https://api.buildingos.com/organizations', false, $context), true);
if (!$request) {
  header('Location: index.php/?err=no_data'); exit();
}
$orgs = array();
foreach ($request['data'] as $organization) {
  $orgs[$organization['name']] = $organization['url'];
}
if (empty($orgs)) {
  header('Location: index.php/?err=no_orgs'); exit();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.css">
    <title>Leak Detection</title>
  </head>
  <body style="background: #e9ecef">
    <div class="jumbotron" style="padding-bottom: 0px;margin-bottom: 0px">
      <div class="container">
        <div class="row">
          <div class="col-sm-6">
            <h1 class="display-4">You're almost done</h1>
            <p class="lead">Please enter a login email and password and select organizations to import</p>
          </div>
          <div class="col-sm-6">
            <form action="" method="POST">
              <div class="form-group">
                <label for="account_email">Account Email</label>
                <input type="account_email" class="form-control" id="account_email" name="account_email">
              </div>
              <div class="form-group">
                <label for="account_password">Account Password</label>
                <input type="password" class="form-control" id="account_password" name="account_password">
              </div>
              <input type="hidden" name="email" value="<?php echo $_POST['email']; ?>">
              <input type="hidden" name="password" value="<?php echo $_POST['password']; ?>">
              <input type="hidden" name="client_id" value="<?php echo $_POST['client_id']; ?>">
              <input type="hidden" name="client_secret" value="<?php echo $_POST['client_secret']; ?>">
              <div class="form-group">
                <?php $once = false;
                  foreach ($orgs as $name => $url) {
                    $stmt = $db->prepare('SELECT COUNT(*) FROM orgs WHERE url = ?');
                    $stmt->execute(array($url));
                    if ($stmt->fetchColumn() === '0') {
                      $already_in_use = false;
                    } else {
                      $already_in_use = true;
                      $once = true;
                    }
                    $val = "{$url}\$SEP\${$name}";
                  ?>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="<?php echo $val ?>" value="<?php echo $val ?>" name="org[]">
                    <label class="custom-control-label" for="<?php echo $val ?>"><?php echo ($already_in_use) ? "{$name}*" : $name; ?></label>
                  </div>
                  <?php } ?>
                  <?php if ($once) { ?><p><small>Organizations marked with an asterisk are used by other Dashboard accounts. When adding organizations already synced with another Dashboard account, the API credentials you provide will not be saved as they are not needed to access that organization.</small></p><?php } ?>
              </div>
              <div class="form-group">
                <input type="submit" class="btn btn-primary" name="create_account" value="Create account">
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>
</html>