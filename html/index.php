<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.css">
    <title>Leak Detection</title>
  </head>
  <body style="background: #e9ecef">
    <div class="jumbotron">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <button type="button" class="btn btn-primary btn-outline-primary float-right" data-toggle="modal" data-target="#logInModal">Log in</button>
          </div>
        </div>
        <?php if (isset($_GET['err'])) { ?>
        <div class="row">
          <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php switch ($_GET['err']) {
                case 'no_token':
                  echo "Failed to retrieve API token; try creating a new API client or changing the BuildingOS account entered.";
                  break;
                case 'no_data':
                  echo "Error: no data returned from API.";
                  break;
                case 'no_orgs':
                  echo "This BuildingOS account has access 0 organizations.";
                  break;
                case 'no_logged_in':
                  echo "Please log in";
                  break;
              } ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        </div>
        <?php } ?>
        <div class="row">
          <div class="col-sm-6">
            <h1 class="display-4">Get notified when leaks are detected in your water meters</h1>
            <p class="lead">Have a BuildingOS account? Sign in to create an account</p>
          </div>
          <div class="col-sm-6">
            <form action="continue.php" method="POST" class="needs-validation" novalidate>
              <div class="form-group">
                <label for="email">BuildingOS Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">
                  Please provide a valid email.
                </div>
              </div>
              <div class="form-group">
                <label for="password">BuildingOS Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="invalid-feedback">
                  Please do not leave this field blank.
                </div>
              </div>
              <div class="form-group">
                <label for="client_id">Client ID</label>
                <input type="text" class="form-control" id="client_id" name="client_id" required>
                <div class="invalid-feedback">
                  Please do not leave this field blank.
                </div>
              </div>
              <div class="form-group">
                <label for="client_secret">Client Secret</label>
                <input type="text" class="form-control" id="client_secret" name="client_secret" required>
                <div class="invalid-feedback">
                  Please do not leave this field blank.
                </div>
              </div>
              <div class="form-group">
                <button type="submit" class="btn btn-primary">Continue</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>


    <div class="modal fade" id="logInModal" tabindex="-1" role="dialog" aria-labelledby="logInModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form id="login_form" action="login.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-header">
              <h5 class="modal-title" id="logInModalLabel">Log in</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="login_email">Email address</label>
                <input type="email" class="form-control" id="login_email" name="login_email" aria-describedby="emailHelp" placeholder="Enter email" required>
                <div class="invalid-feedback" id="email-feedback">
                  Please provide a valid email.
                </div>
              </div>
              <div class="form-group">
                <label for="login_password">Password</label>
                <input type="password" class="form-control" id="login_password" name="login_password" placeholder="Password" required>
                <div class="invalid-feedback" id="password-feedback">
                  Please do not leave this field blank.
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Log in</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script>
      $("#login_form").submit(function(e){
        e.preventDefault();
        var email = $('#login_email').val(), pass = $('#login_password').val();
        if (email.length > 0 && pass.length > 0) {
          // https://stackoverflow.com/a/9713078/2624391
          var http = new XMLHttpRequest();
          var url = "login.php";
          var params = "email=" + encodeURIComponent(email) + "&password=" + encodeURIComponent(pass);
          http.open("POST", url, true);
          http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          http.onreadystatechange = function() {//Call a function when the state changes.
            if (http.readyState == 4 && http.status == 200) {
              var resp = http.responseText;
              if (resp == 'NO EMAIL') {
                $('#login_email').addClass('is-invalid');
                $('#email-feedback').text("'"+email+"' does not exist, please create an account");
              } else if (resp == 'BAD PASS') {
                $('#login_password').addClass('is-invalid');
                $('#password-feedback').text('Incorrect password');
              } else {
                setCookie('user_id', resp, 360);
                window.location.href = 'home.php';
              }
            }
          }
          http.send(params);
        }
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
      function setCookie(c_name, value, exdays) { // https://stackoverflow.com/a/14573665/2624391
        var exdate = new Date();
        exdate.setDate(exdate.getDate() + exdays);
        var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
        document.cookie = c_name + "=" + c_value;
      }
    </script>
  </body>
</html>