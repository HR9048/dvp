<?php
// ===========================
// STEP 1: Mobile Numbers Array
// ===========================
$busstandnamekannada = 'ಜರುಗಿಸಲಾಗಿದೆ';
$urlforview = '211';

require('../includes/connection.php'); // DB connection

$mobileNumbers = [];

// Query to fetch all mobile numbers from employee table (joined with users)
$query = "
    SELECT e.PHONE_NUMBER
    FROM users u
    JOIN employee e ON u.PF_ID = e.PF_ID
";

$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    $mobileNumbers['user'] = [];

    while ($row = $result->fetch_assoc()) {
        $mobileNumbers['user'][] = $row['PHONE_NUMBER']; // Add to 'user' array
    }
}


// ===========================
// STEP 2: SMS API Credentials
// ===========================
$username           = "Mobile_1-EKKRTC";
$password           = "ekkrtc@1234";
$senderid           = "EKKRTC";
$deptSecureKey      = "9f54bb99-fe06-416a-ab05-c3a6eb3c4183";
$encryp_password    = sha1(trim($password));
$templateid         = "1107175068056003109"; // Kannada review reply


// View URL for user message

$userMessage = "$busstandnamekannada ಸ್ವಚ್ಛತೆ ಕುರಿತಾದ ವಿಮರ್ಶೆಗೆ ಕ್ರಮ ಜರುಗಿಸಲಾಗಿದೆ. http://kkrtc.org/cfs/view.php?viewid=$urlforview .-EKKRTC";

// ===========================
// STEP 3: SMS Sending Function
// ===========================
function sendSingleUnicode($username, $encryp_password, $senderid, $message, $mobile, $deptSecureKey, $templateid)
{
    $finalmessage = string_to_finalmessage(trim($message));
    $key = hash('sha512', trim($username) . trim($senderid) . trim($finalmessage) . trim($deptSecureKey));

    $data = [
        "username"       => trim($username),
        "password"       => trim($encryp_password),
        "senderid"       => trim($senderid),
        "content"        => trim($finalmessage),
        "smsservicetype" => "unicodemsg",
        "mobileno"       => trim($mobile),
        "key"            => $key,
        "templateid"     => trim($templateid),
    ];

    return post_to_url_unicode("http://smsmobileone.karnataka.gov.in/index.php/sendmsg", $data);
}
// ===========================
// STEP 4: Begin Sending SMS
// ===========================
function post_to_url_unicode($url, $data)
{
    $fields = '';
    foreach ($data as $key => $value) {
        $fields .= $key . "=" . urlencode($value) . "&";
    }
    $fields = rtrim($fields, '&');

    $post = curl_init();
    curl_setopt($post, CURLOPT_SSLVERSION, 6);
    curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($post, CURLOPT_URL, $url);
    curl_setopt($post, CURLOPT_POST, count($data));
    curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($post, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
    curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($post);
    curl_close($post);
    return $result;
}

function string_to_finalmessage($message)
{
    $finalmessage = "";
    for ($i = 0; $i < mb_strlen($message, "UTF-8"); $i++) {
        $char = mb_substr($message, $i, 1, "UTF-8");
        $a = 0;
        $code = ordutf8($char, $a);
        $finalmessage .= "&#" . $code . ";";
    }
    return $finalmessage;
}

function ordutf8($string, &$offset)
{
    $code = ord(substr($string, $offset, 1));
    if ($code >= 128) {
        if ($code < 224) $bytesnumber = 2;
        else if ($code < 240) $bytesnumber = 3;
        else if ($code < 248) $bytesnumber = 4;

        $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
        for ($i = 2; $i <= $bytesnumber; $i++) {
            $offset++;
            $code2 = ord(substr($string, $offset, 1)) - 128;
            $codetemp = $codetemp * 64 + $code2;
        }
        $code = $codetemp;
    }
    return $code;
}





// ===========================
// STEP 5: Loop Through Numbers
// ===========================
foreach ($mobileNumbers as $designation => $mobileNumber) {
    if (!empty($mobileNumber) && preg_match("/^[0-9]{10}$/", $mobileNumber)) {
        $userResponse = sendSingleUnicode($username, $encryp_password, $senderid, $userMessage, $mobileNumber, $deptSecureKey, $templateid);
        $smsStatus    = ($userResponse === false) ? 'failed' : 'sent';
        $finalResponse = ($userResponse === false) ? 'Error' : $userResponse;


       
    }
}


