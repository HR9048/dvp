<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fullscreen Notification</title>
    <style>
        /* Fullscreen overlay */
        #notificationOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7); /* Dark transparent background */
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease-in-out, visibility 0.5s;
        }

        /* Notification box */
        .notification-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.5s ease-in-out;
        }

        /* Slide down animation */
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* OK button */
        .close-btn {
            margin-top: 15px;
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .close-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>

    <!-- Fullscreen Notification Overlay -->
    <div id="notificationOverlay">
        <div class="notification-box">
            <h2>🔔 New Notification</h2>
            <p>You have new updates. Check your dashboard for details.</p>
            <button class="close-btn" onclick="closeNotification()">OK</button>
        </div>
    </div>

    <script>
        // Simulating a dynamic count
        var notificationCount = 3; // Change this value dynamically in PHP

        // Show the notification if count > 0
        window.onload = function () {
            if (notificationCount > 0) {
                document.getElementById("notificationOverlay").style.opacity = "1";
                document.getElementById("notificationOverlay").style.visibility = "visible";
            }
        };

        // Close the notification
        function closeNotification() {
            document.getElementById("notificationOverlay").style.opacity = "0";
            document.getElementById("notificationOverlay").style.visibility = "hidden";
        }
    </script>

</body>
</html>
