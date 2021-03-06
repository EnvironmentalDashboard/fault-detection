<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../db.php';
if (isset($_COOKIE['user_id'])) {
  $user_id = $_COOKIE['user_id'];
} else {
  header('Location: /?err=not_logged_in'); exit();
}
$added_email = false;
if (isset($_POST['add-email'])) {
  $stmt = $db->prepare('REPLACE INTO notified_emails (user_id, email) VALUES (?, ?)');
  $stmt->execute([$user_id, $_POST['add-email']]);
  $added_email = true;
}

$stmt = $db->prepare('SELECT id, name, last_leak FROM meters WHERE id IN (SELECT meter_id FROM active_meters WHERE user_id = ?)');
$stmt->execute([$user_id]);
$active_meters = $stmt->fetchAll();
$active_meters_ids = array_column($active_meters, 'id');
$num_active_meters = count($active_meters);
if ($num_active_meters === 0) {
  $zero = true;
} else {
  $zero = false;
}
$looks_good = '<div style="display:inline-block;margin-right:5px;height:10px;width:10px;border-radius:100%;background:green"></div><span style="font-size:70%">Looks good</span>';
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
    <div class="container" style="margin-top: 20px">
      <div class="row">
        <div class="col-12">
          <?php if ($added_email) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Success!</strong> <?php echo $_POST['add-email'] ?> will be emailed when leaks are detected.
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php } ?>
          <a href="logout.php" class="btn btn-primary btn-outline-primary float-right">Log out</a>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-8">
          <h1>Monitored meters</h1>
          <h2 class="text-muted text-center mt-5" id="no-meters-msg" <?php echo ($zero) ? '' : 'style="display:none"' ?>>No meters are being monitored; select from this list on the right</h2>
          <table class="table table-striped table-dark" id="table" <?php echo ($zero) ? 'style="display:none"' : '' ?>>
            <thead>
              <tr>
                <th scope="col">Meter</th>
                <th scope="col">Status</th>
                <th scope="col">Remove</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <?php foreach ($active_meters as $meter) {
                $status = $looks_good;
                if ($meter['last_leak'] != 0) {
                  $date = date('F j, g:i a', $meter['last_leak']);
                  $status = "<div style='display:inline-block;margin-right:5px;height:10px;width:10px;border-radius:100%;background:red'></div><span style='font-size:70%'>Leak detected on {$date}</span><p><a href='#' class='btn btn-sm btn-outline-primary resolved-btn' data-meterid='{$meter['id']}' style='padding:4px;font-size:70%;color:#fff'>Mark as resolved</a></p>";
                }
                echo "<tr id='meter{$meter['id']}'><td>{$meter['name']}</td><td>{$status}</td><td><a href='#' class='close btn remove-btn float-left' data-meterid='{$meter['id']}'><span style='color:#fff'>&times;</span></a></td></tr>";
              } ?>
            </tbody>
          </table>
        </div>
        <div class="col-sm-4">
          <h4>Notified emails</h4>
          <?php
          $stmt = $db->prepare('SELECT email FROM notified_emails WHERE user_id = ?');
          $stmt->execute([$user_id]);
          if ($stmt->rowCount() > 0) {
            foreach ($stmt->fetchAll() as $row) {
              echo "<p>{$row['email']}</p>";
            }
          } else {
            echo '<p class="text-muted">No emails are being notified</p>';
          }
          ?>
          <form class="form-inline needs-validation" style="margin-bottom: 20px" action="" method="POST" novalidate>
            <label class="sr-only" for="inlineFormInputName2">Add email to be notified</label>
            <input type="email" class="form-control mb-2 mr-sm-2" id="inlineFormInputName2" name="add-email" placeholder="Email" required>
            <button type="submit" class="btn btn-primary mb-2">Add</button>
            <div class="invalid-feedback">
              Please provide a valid email.
            </div>
          </form>
          <h4>Unmonitored meters</h4>
          <?php foreach ($db->query("SELECT bos_id, name FROM buildings WHERE bos_id IN (SELECT building_id FROM meters WHERE resource = 'Water') ORDER BY name ASC") as $building) {
            echo "<h6>{$building['name']}</h6><ul class='list-group list-group-flush' style='margin-bottom:20px'>";
            foreach ($db->query("SELECT id, name FROM meters WHERE building_id = {$building['bos_id']} AND resource = 'Water' ORDER BY name ASC") as $meter) {
              if (in_array($meter['id'], $active_meters_ids)) {
                echo "<li class='list-group-item' data-name='{$building['name']} {$meter['name']}' data-id='{$meter['id']}' id='unselected{$meter['id']}' style='cursor:pointer;display:none'>{$meter['name']}</li>";
              } else {
                echo "<li class='list-group-item' data-name='{$building['name']} {$meter['name']}' data-id='{$meter['id']}' id='unselected{$meter['id']}' style='cursor:pointer'>{$meter['name']}</li>";
              }
            }
            echo "</ul>";
          } ?>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script>
    (function(){
      // https://stackoverflow.com/a/5639455/2624391
      var cookies;
      function readCookie(name,c,C,i){
        if(cookies){ return cookies[name]; }
        c = document.cookie.split('; ');
        cookies = {};
        for(i=c.length-1; i>=0; i--){
          C = c[i].split('=');
          cookies[C[0]] = C[1];
        }
        return cookies[name];
      }
      window.readCookie = readCookie; // or expose it however you want
    })();
    var uid = readCookie('user_id');
    var num_active_meters = <?php echo $num_active_meters; ?>;
    $('.list-group-item').on('click', function(e) {
      e.preventDefault();
      if (num_active_meters == 0) {
        $('#table').css('display', '');
        $('#no-meters-msg').css('display', 'none');
      }
      var id = $(this).data('id'), name = $(this).data('name');
      $('#tbody').append('<tr id="meter'+id+'"><td>'+name+'</td><td><?php echo $looks_good ?></td><td><a href="#" class="close btn remove-btn float-left" data-meterid="'+id+'"><span style="color:#fff">&times;</span></a></td></tr>');
      $(this).css('display', 'none');
      var http = new XMLHttpRequest();
      var url = "includes/active_meters.php";
      var params = "meterid="+id+"&add=1&uid=" + uid;
      http.open("POST", url, true);
      http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      http.send(params);
      num_active_meters++;
    });
    $(document).on('click', '.remove-btn', function(e) { // https://stackoverflow.com/a/1207393/2624391
      e.preventDefault();
      var id = $(this).data('meterid');
      $('#meter'+id).remove();
      $('#unselected'+id).css('display', 'initial');
      var http = new XMLHttpRequest();
      var url = "includes/active_meters.php";
      var params = "meterid="+id+"&remove=1&uid=" + uid;
      http.open("POST", url, true);
      http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      http.send(params);
      num_active_meters--;
      if (num_active_meters == 0) {
        $('#table').css('display', 'none');
        $('#no-meters-msg').css('display', '');
      }
    });
    $('.resolved-btn').on('click', function(e) {
      e.preventDefault();
      var http = new XMLHttpRequest();
      var url = "includes/no_more_leaks.php";
      var params = "meterid="+($(this).data('meterid'));
      http.open("POST", url, true);
      http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      http.send(params);
      var td = $(this).parents()[1];
      $(td).html('<?php echo $looks_good ?>');
    });

    (function() {
      'use strict';
      window.addEventListener('load', function() {
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.getElementsByClassName('needs-validation');
        // Loop over them and prevent submission
        var validation = Array.prototype.filter.call(forms, function(form) {
          form.addEventListener('submit', function(event) {
            if (form.checkValidity() === false) {
              event.preventDefault();
              event.stopPropagation();
            }
            form.classList.add('was-validated');
          }, false);
        });
      }, false);
    })();
    </script>
  </body>
</html>