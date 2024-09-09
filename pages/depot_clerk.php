<?php
require_once('session.php');
confirm_logged_in();

// Set default language to English if session not set
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
}

$language = $_SESSION['language'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KKRTC-DVP</title>

    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Google Translate API -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
        }

        // Automatically translate based on session language
        $(document).ready(function() {
            var language = "<?php echo $language; ?>";
            if (language === 'kannada') {
                setTimeout(function() {
                    triggerGoogleTranslate('kn');
                }, 1000); // delay to ensure Google Translate is ready
            }else{
              setTimeout(function() {
                    triggerGoogleTranslate('en');
                }, 1000); // delay to ensure Google Translate is ready
            }
        });

        // Function to trigger Google Translate for a specific language
        function triggerGoogleTranslate(languageCode) {
            var selectField = document.querySelector('select.goog-te-combo');
            if (selectField) {
                selectField.value = languageCode; // set the value to Kannada
                selectField.dispatchEvent(new Event('change')); // trigger the change event
            }
        }
    </script>

<style>
        /* Hide Google Translate bar */
        .goog-te-banner-frame {
            display: none !important;
        }

        /* Hide Google Translate menu frame */
        .goog-te-menu-frame {
            display: none !important;
        }

        /* Adjust page content to prevent shifting */
        body {
            top: 0px !important; 
        }

        /* Hide Google Translate toolbar */
        #google_translate_element {
            display: none;
        }

        .translate-btn {
            cursor: pointer;
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Language Switch Button -->
        <div class="translate-btn" style="padding: 10px; text-align: right;">
            <button class="btn btn-primary" id="switchLanguage">
                <?php echo ($language == 'english') ? 'Switch to Kannada' : 'Switch to English'; ?>
            </button>
        </div>

        <!-- Main Content -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content" style="max-width: 100%; overflow-x: hidden;">
                <nav class="navbar navbar-expand-md fixed-top" style="background-color: #bfc9ca;">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="../includes/depot_verify.php" style="color: black;">
                            <img src="../images/kkrtclogo.png" width="40" height="40" alt="KKRTC">
                        </a>
                    </div>
                </nav>

                <!-- Google Translate Element -->
                <div id="google_translate_element" style="display: none;"></div>

                <!-- Display content -->
                <div class="container mt-5">
                        <h1>Welcome to the KKRTC Portal</h1>
                        <p>This is the English version of the page content.</p>
                    
                </div>

                <h1>Hello</h1>
            </div> <!-- End of Main Content -->
        </div> <!-- End of Content Wrapper -->
    </div> <!-- End of Page Wrapper -->

    <!-- Google Translate API script -->
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <script type="text/javascript">
        $('#switchLanguage').click(function() {
            var currentLanguage = "<?php echo $language; ?>";
            var newLanguage = (currentLanguage === 'english') ? 'kannada' : 'english';

            // Send an AJAX request to update the session language
            $.ajax({
                url: '../includes/set_language.php',
                type: 'POST',
                data: {language: newLanguage},
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.status === 'success') {
                        // Reload the page to apply the language change
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                }
            });
        });
    </script>

</body>
</html>
