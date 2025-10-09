<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Server Under Maintenance</title>
  <link rel="icon" href="../images/logo1.jpeg">
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", sans-serif;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow: hidden;
      text-align: center;
    }

    h1 {
      font-size: 3em;
      margin-bottom: 10px;
    }

    p {
      font-size: 1.2em;
      margin: 5px 0 20px;
    }

    .illustration {
      position: relative;
      width: 200px;
      height: 200px;
      margin: 20px auto;
    }

    /* Gears */
    .gear {
      width: 80px;
      height: 80px;
      border: 8px solid #fff;
      border-radius: 50%;
      position: absolute;
      animation: spin 8s linear infinite;
    }

    .gear:before,
    .gear:after {
      content: '';
      position: absolute;
      top: -12px;
      left: 50%;
      width: 16px;
      height: 16px;
      background: #fff;
      border-radius: 4px;
      transform: translateX(-50%);
    }

    .gear:after {
      top: auto;
      bottom: -12px;
    }

    .gear.small {
      width: 50px;
      height: 50px;
      top: 100px;
      left: 120px;
      animation-duration: 6s;
    }

    @keyframes spin {
      100% {
        transform: rotate(360deg);
      }
    }

    /* Progress dots */
    .dots {
      margin-top: 30px;
    }

    .dot {
      display: inline-block;
      width: 12px;
      height: 12px;
      margin: 0 5px;
      background: #fff;
      border-radius: 50%;
      opacity: 0.3;
      animation: blink 1.5s infinite;
    }

    .dot:nth-child(2) {
      animation-delay: 0.3s;
    }

    .dot:nth-child(3) {
      animation-delay: 0.6s;
    }

    @keyframes blink {
      0%, 80%, 100% { opacity: 0.3; }
      40% { opacity: 1; }
    }

    footer {
      position: absolute;
      bottom: 15px;
      font-size: 0.9em;
      opacity: 0.8;
    }
  </style>
</head>
<body>
  <h1>We’ll Be Back Soon!</h1>
  <p>Our servers are undergoing scheduled maintenance.<br>
  Please check back later.</p>

  <div class="illustration">
    <div class="gear"></div>
    <div class="gear small"></div>
  </div>

  <div class="dots">
    <span class="dot"></span>
    <span class="dot"></span>
    <span class="dot"></span>
  </div>

  <footer>© Copyright 2024 KKRTC | All Rights Reserved</footer>
</body>
</html>
