<?php require ('session.php'); ?>
<?php if (logged_in()) { ?>
    <script type="text/javascript">
        window.location = "processlogin.php";
    </script>
<?php } ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KKRTC DVP</title>
    <link rel="icon" href="../images/logo1.jpeg">

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include Bootstrap JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

    <!-- Custom fonts for this template -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <style>
        body {
            background-color: pink;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border: 0;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control-user {
            border-radius: 25px;
        }

        .btn-user {
            border-radius: 25px;
            background-color: #4e73df;
            color: #fff;
        }

        .btn-user:hover {
            background-color: #2e59d9;
        }
    </style>

</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-6 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome to KKRTC</h1>
                                    </div>
                                    <form class="user" role="form" action="processlogin.php" method="post">
                                        <div class="form-group">
                                            <input class="form-control form-control-user" placeholder="Username"
                                                name="user" type="text" autofocus>
                                        </div>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <input class="form-control form-control-user" placeholder="Password"
                                                    name="password" type="password" value="" id="passwordInput">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" id="togglePassword"
                                                        style="border-radius: 0 1.5rem 1.5rem 0;">
                                                        <i class="fas fa-eye-slash" aria-hidden="true"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                                <label class="custom-control-label" for="customCheck">Remember
                                                    Me</label>
                                            </div>
                                        </div>
                                        <button class="btn btn-primary btn-user btn-block" type="submit"
                                            name="btnlogin">Login</button>

                                        <hr>
                                        <!-- <div class="text-center">
                                            <a class="small" href="register.php">Create an Account!</a>
                                        </div> -->
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Add this script at the end of your HTML body -->
    <script>
        $(document).ready(function () {
            $('form.user').submit(function (event) {
                // Prevent the form from submitting normally
                event.preventDefault();

                // Get form data
                var formData = $(this).serialize();


                // Send AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'processlogin.php',
                    data: formData,
                    dataType: 'json', // Treat the response as JSON
                    success: function (response) {

                        // Process response
                        if (response.status === 'success') {
                            alert(response.message);
                            window.location = response.redirect;
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        alert('An error occurred while processing your request.');
                        console.log("Error:", error); // Debugging
                    }
                });
            });
            // Get the password input and the eye icon
            var passwordInput = document.getElementById("passwordInput");
            var togglePassword = document.getElementById("togglePassword");

            // Toggle password visibility when the eye icon is clicked
            togglePassword.addEventListener("click", function () {
                // Toggle the type attribute of the password input
                var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
                passwordInput.setAttribute("type", type);

                // Change the eye icon accordingly
                if (type === "password") {
                    togglePassword.innerHTML = '<i class="fa fa-eye-slash" aria-hidden="true"></i>';
                } else {
                    togglePassword.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i>';
                }
            });
        });


    </script>



    <!-- Bootstrap core JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>