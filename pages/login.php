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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        /* Importing Google font - Open Sans */
        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap");

        * {
            margin: 10;
            padding: 10;
            box-sizing: border-box;
            font-family: "Open Sans", sans-serif;
        }

        body {
            height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #E5E7E9;
        }

        .form-container {
            display: flex;
            width: 100%;
            max-width: 800px;
            background: #fff;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container .form-image {
            display: none;
            width: 50%;
            background-size: cover;
            background-position: center;
        }

        .form-container .form-content {
            width: 100%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-content h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
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

        @media (min-width: 768px) {
            .form-container {
                max-width: 700px;
            }

            .form-container .form-image {
                display: block;
                height: 350px;
                /* Set the desired height */
                width: 50%;
                /* Set the desired width */
            }

            .form-container .form-content {
                width: 50%;
            }
        }

        /* Custom styles for the overlay panel */
        .overlay-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 10px;
            background-color: #85C1E9;
            /* Light grey background */
        }

        .overlay-panel img {
            margin-bottom: 30px;
            margin-top: 20px;
        }

        .overlay-panel h3 {
            font-size: 1.2rem;
            color: #333;
            margin: 0;
            font-weight: 600;
        }

        .overlay-panel h4 {
            font-size: 1rem;
            color: #333;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-image">
            <div class="overlay-panel overlay-right">
                <img src="../images/kkrtclogo.png" alt="Logo" width="80" height="80">
                <h3>ಕಲ್ಯಾಣ ಕರ್ನಾಟಕ ರಸ್ತೆ ಸಾರಿಗೆ ನಿಗಮ</h3><br>
                <h4 style="color: #5D6D7E;">ದೈನಂದಿನ ವಾಹನದ ಸ್ದಿತಿಗತಿ<br>Daily Vehicle Position</h4><br><br><br><br>
            </div>
        </div>
        <div class="form-content">
            <h2>LOGIN</h2>
            <form class="user" role="form" action="processlogin.php" method="post">
                <div class="form-group">
                    <input class="form-control form-control-user" placeholder="Username" name="user" type="text"
                        required>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <input class="form-control form-control-user" placeholder="Password" name="password"
                            type="password" value="" id="passwordInput" required>
                        <div class="input-group-append">
                            <span class="input-group-text" id="togglePassword"
                                style="border-radius: 0 1.5rem 1.5rem 0;">
                                <i class="fas fa-eye-slash" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary btn-user btn-block" type="submit" name="btnlogin">Login</button>
            </form>
        </div>
    </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>