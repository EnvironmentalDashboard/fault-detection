<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../bos/db.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.css">
    <title>Leak Detection</title>
  </head>
  <body>
    <div class="container" style="margin-top: 20px">
      <div class="row">
        <div class="col-sm-8">
          <h2 class="text-muted text-center">No meters are being monitored; select from this list on the right</h2>
        </div>
        <div class="col-sm-4">
          <h4>Notified emails</h4>
          <p class="text-muted">No emails are being notified</p>
          <form class="form-inline" style="margin-bottom: 20px">
            <label class="sr-only" for="inlineFormInputName2">Add email to be notified</label>
            <input type="text" class="form-control mb-2 mr-sm-2" id="inlineFormInputName2" placeholder="Email">
            <button type="submit" class="btn btn-primary mb-2">Add</button>
          </form>
          <h4>Unmonitored Meters</h4>
          <form action="">
            <?php foreach ($db->query('SELECT bos_id, name FROM buildings WHERE bos_id IN (SELECT building_id FROM meters WHERE resource = \'Water\') ORDER BY name ASC') as $building) {
              echo "<h6>{$building['name']}</h6><ul class='list-group' style='margin-bottom:20px'>";
              foreach ($db->query("SELECT id, name FROM meters WHERE building_id = {$building['bos_id']} AND resource = 'Water' ORDER BY name ASC") as $meter) {
                echo "<li class='list-group-item'>{$meter['name']}</li>";
              }
              echo "</ul>";
            } ?>
          </form>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>
</html>