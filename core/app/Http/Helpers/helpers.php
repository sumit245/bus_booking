<?php

use App\Lib\GoogleAuthenticator;
use App\Lib\SendSms;
use App\Models\EmailTemplate;
use App\Models\Extension;
use App\Models\Frontend;
use App\Models\GeneralSetting;
use App\Models\SmsTemplate;
use App\Models\EmailLog;
use App\Models\Counter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\DomCrawler\Crawler;


function sidebarVariation()
{

    /// for sidebar
    $variation['sidebar'] = 'bg_img';

    //for selector
    $variation['selector'] = 'capsule--rounded';

    //for overlay
    $variation['overlay'] = 'overlay--indigo';

    //Opacity
    $variation['opacity'] = 'overlay--opacity-8'; // 1-10

    return $variation;
}

function systemDetails()
{
    $system['name'] = 'Ghumantoo';
    $system['version'] = '1.0';
    return $system;
}

function getLatestVersion()
{
    $param['purchasecode'] = env("PURCHASECODE");
    $param['website'] = @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . ' - ' . env("APP_URL");
    $url = 'https://license.viserlab.com/updates/version/' . systemDetails()['name'];
    $result = curlPostContent($url, $param);
    if ($result) {
        return $result;
    } else {
        return null;
    }
}


function slug($string)
{
    return Illuminate\Support\Str::slug($string);
}


function shortDescription($string, $length = 120)
{
    return Illuminate\Support\Str::limit($string, $length);
}


function shortCodeReplacer($shortCode, $replace_with, $template_string)
{
    return str_replace($shortCode, $replace_with, $template_string);
}


function verificationCode($length)
{
    if ($length == 0) return 0;
    $min = pow(10, $length - 1);
    $max = 0;
    while ($length > 0 && $length--) {
        $max = ($max * 10) + 9;
    }
    return random_int($min, $max);
}

function getNumber($length = 8)
{
    $characters = '1234567890';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}



//moveable
function uploadImage($file, $location, $size = null, $old = null, $thumb = null)
{
    $path = makeDirectory($location);
    if (!$path) throw new Exception('File could not been created.');

    if ($old) {
        removeFile($location . '/' . $old);
        removeFile($location . '/thumb_' . $old);
    }
    $filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();
    $image = Image::make($file);
    if ($size) {
        $size = explode('x', strtolower($size));
        $image->resize($size[0], $size[1]);
    }
    $image->save($location . '/' . $filename);

    if ($thumb) {
        $thumb = explode('x', $thumb);
        Image::make($file)->resize($thumb[0], $thumb[1])->save($location . '/thumb_' . $filename);
    }

    return $filename;
}

function uploadFile($file, $location, $size = null, $old = null)
{
    $path = makeDirectory($location);
    if (!$path) throw new Exception('File could not been created.');

    if ($old) {
        removeFile($location . '/' . $old);
    }

    $filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();
    $file->move($location, $filename);
    return $filename;
}

function makeDirectory($path)
{
    if (file_exists($path)) return true;
    return mkdir($path, 0755, true);
}


function removeFile($path)
{
    return file_exists($path) && is_file($path) ? @unlink($path) : false;
}


function activeTemplate($asset = false)
{
    $general = GeneralSetting::first(['active_template']);
    $template = $general->active_template;
    $sess = session()->get('template');
    if (trim($sess)) {
        $template = $sess;
    }
    if ($asset) return 'assets/templates/' . $template . '/';
    return 'templates.' . $template . '.';
}

function activeTemplateName()
{
    $general = GeneralSetting::first(['active_template']);
    $template = $general->active_template;
    $sess = session()->get('template');
    if (trim($sess)) {
        $template = $sess;
    }
    return $template;
}


function loadReCaptcha()
{
    $reCaptcha = Extension::where('act', 'google-recaptcha2')->where('status', 1)->first();
    return $reCaptcha ? $reCaptcha->generateScript() : '';
}


function loadAnalytics()
{
    $analytics = Extension::where('act', 'google-analytics')->where('status', 1)->first();
    return $analytics ? $analytics->generateScript() : '';
}

function loadTawkto()
{
    $tawkto = Extension::where('act', 'tawk-chat')->where('status', 1)->first();
    return $tawkto ? $tawkto->generateScript() : '';
}


function loadFbComment()
{
    $comment = Extension::where('act', 'fb-comment')->where('status', 1)->first();
    return  $comment ? $comment->generateScript() : '';
}

function loadCustomCaptcha($height = 46, $width = '100%', $bgcolor = '#003', $textcolor = '#abc')
{
    $textcolor = '#' . GeneralSetting::first()->base_color;
    $captcha = Extension::where('act', 'custom-captcha')->where('status', 1)->first();
    if (!$captcha) {
        return 0;
    }
    $code = rand(100000, 999999);
    $char = str_split($code);
    $ret = '<link href="https://fonts.googleapis.com/css?family=Henny+Penny&display=swap" rel="stylesheet">';
    $ret .= '<div style="height: ' . $height . 'px; line-height: ' . $height . 'px; width:' . $width . '; text-align: center; background-color: ' . $bgcolor . '; color: ' . $textcolor . '; font-size: ' . ($height - 20) . 'px; font-weight: bold; letter-spacing: 20px; font-family: \'Henny Penny\', cursive;  -webkit-user-select: none; -moz-user-select: none;-ms-user-select: none;user-select: none;  display: flex; justify-content: center;">';
    foreach ($char as $value) {
        $ret .= '<span style="    float:left;     -webkit-transform: rotate(' . rand(-60, 60) . 'deg);">' . $value . '</span>';
    }
    $ret .= '</div>';
    $captchaSecret = hash_hmac('sha256', $code, $captcha->shortcode->random_key->value);
    $ret .= '<input type="hidden" name="captcha_secret" value="' . $captchaSecret . '">';
    return $ret;
}


function captchaVerify($code, $secret)
{
    $captcha = Extension::where('act', 'custom-captcha')->where('status', 1)->first();
    $captchaSecret = hash_hmac('sha256', $code, $captcha->shortcode->random_key->value);
    if ($captchaSecret == $secret) {
        return true;
    }
    return false;
}

function getTrx($length = 12)
{
    $characters = 'ABCDEFGHJKMNOPQRSTUVWXYZ123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getAmount($amount, $length = 2)
{
    $amount = round($amount, $length);
    return $amount + 0;
}

function seatLayoutToArray($layoutString)
{
    return $seat_layout = explode('x', str_replace(' ', '', $layoutString));
}

function showAmount($amount, $decimal = 2, $separate = true, $exceptZeros = false)
{
    $separator = '';
    if ($separate) {
        $separator = ',';
    }
    $printAmount = number_format($amount, $decimal, '.', $separator);
    if ($exceptZeros) {
        $exp = explode('.', $printAmount);
        if ($exp[1] * 1 == 0) {
            $printAmount = $exp[0];
        }
    }
    return $printAmount;
}


function removeElement($array, $value)
{
    return array_diff($array, (is_array($value) ? $value : array($value)));
}

function cryptoQR($wallet)
{

    return "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$wallet&choe=UTF-8";
}

//moveable
function curlContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

//moveable
function curlPostContent($url, $arr = null)
{
    if ($arr) {
        $params = http_build_query($arr);
    } else {
        $params = '';
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


function inputTitle($text)
{
    return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
}


function titleToKey($text)
{
    return strtolower(str_replace(' ', '_', $text));
}


function str_limit($title = null, $length = 10)
{
    return \Illuminate\Support\Str::limit($title, $length);
}

//moveable
function getIpInfo()
{
    $ip = $_SERVER["REMOTE_ADDR"];

    //Deep detect ip
    if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }


    $xml = @simplexml_load_file("http://www.geoplugin.net/xml.gp?ip=" . $ip);


    $country = @$xml->geoplugin_countryName;
    $city = @$xml->geoplugin_city;
    $area = @$xml->geoplugin_areaCode;
    $code = @$xml->geoplugin_countryCode;
    $long = @$xml->geoplugin_longitude;
    $lat = @$xml->geoplugin_latitude;

    $data['country'] = $country;
    $data['city'] = $city;
    $data['area'] = $area;
    $data['code'] = $code;
    $data['long'] = $long;
    $data['lat'] = $lat;
    $data['ip'] = request()->ip();
    $data['time'] = date('d-m-Y h:i:s A');


    return $data;
}

//moveable
function osBrowser()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $osPlatform = "Unknown OS Platform";
    $osArray = array(
        '/windows nt 10/i' => 'Windows 10',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/windows nt 6.0/i' => 'Windows Vista',
        '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i' => 'Windows XP',
        '/windows xp/i' => 'Windows XP',
        '/windows nt 5.0/i' => 'Windows 2000',
        '/windows me/i' => 'Windows ME',
        '/win98/i' => 'Windows 98',
        '/win95/i' => 'Windows 95',
        '/win16/i' => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i' => 'Mac OS 9',
        '/linux/i' => 'Linux',
        '/ubuntu/i' => 'Ubuntu',
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile'
    );
    foreach ($osArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $osPlatform = $value;
        }
    }
    $browser = "Unknown Browser";
    $browserArray = array(
        '/msie/i' => 'Internet Explorer',
        '/firefox/i' => 'Firefox',
        '/safari/i' => 'Safari',
        '/chrome/i' => 'Chrome',
        '/edge/i' => 'Edge',
        '/opera/i' => 'Opera',
        '/netscape/i' => 'Netscape',
        '/maxthon/i' => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/mobile/i' => 'Handheld Browser'
    );
    foreach ($browserArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $browser = $value;
        }
    }

    $data['os_platform'] = $osPlatform;
    $data['browser'] = $browser;

    return $data;
}

function siteName()
{
    $general = GeneralSetting::first();
    $sitname = str_word_count($general->sitename);
    $sitnameArr = explode(' ', $general->sitename);
    if ($sitname > 1) {
        $title = "<span>$sitnameArr[0] </span> " . str_replace($sitnameArr[0], '', $general->sitename);
    } else {
        $title = "<span>$general->sitename</span>";
    }

    return $title;
}

//moveable
function getTemplates()
{
    $param['purchasecode'] = env("PURCHASECODE");
    $param['website'] = @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . ' - ' . env("APP_URL");
    $url = 'https://license.viserlab.com/updates/templates/' . systemDetails()['name'];
    $result = curlPostContent($url, $param);
    if ($result) {
        return $result;
    } else {
        return null;
    }
}


function getPageSections($arr = false)
{

    $jsonUrl = resource_path('views/') . str_replace('.', '/', activeTemplate()) . 'sections.json';
    $sections = json_decode(file_get_contents($jsonUrl));
    if ($arr) {
        $sections = json_decode(file_get_contents($jsonUrl), true);
        ksort($sections);
    }
    return $sections;
}

function getImage($image, $size = null)
{
    $clean = '';
    if (file_exists($image) && is_file($image)) {
        return asset($image) . $clean;
    }
    if ($size) {
        return route('placeholder.image', $size);
    }
    return asset('assets/images/default.png');
}

function notify($user, $type, $shortCodes = null)
{
    sendEmail($user, $type, $shortCodes);
    sendSms($user, $type, $shortCodes);
}

function sendSms($user, $type, $shortCodes = [])
{
    $general = GeneralSetting::first();
    $smsTemplate = SmsTemplate::where('act', $type)->where('sms_status', 1)->first();
    $gateway = $general->sms_config->name;
    $sendSms = new SendSms;
    if ($general->sn == 1 && $smsTemplate) {
        $template = $smsTemplate->sms_body;
        foreach ($shortCodes as $code => $value) {
            $template = shortCodeReplacer('{{' . $code . '}}', $value, $template);
        }
        $message = shortCodeReplacer("{{message}}", $template, $general->sms_api);
        $message = shortCodeReplacer("{{name}}", $user->username, $message);
        $sendSms->$gateway($user->mobile, $general->sitename, $message, $general->sms_config);
    }
}

function sendEmail($user, $type = null, $shortCodes = [])
{
    $general = GeneralSetting::first();

    $emailTemplate = EmailTemplate::where('act', $type)->where('email_status', 1)->first();
    if ($general->en != 1 || !$emailTemplate) {
        return;
    }


    $message = shortCodeReplacer("{{fullname}}", $user->fullname, $general->email_template);
    $message = shortCodeReplacer("{{username}}", $user->username, $message);
    $message = shortCodeReplacer("{{message}}", $emailTemplate->email_body, $message);

    if (empty($message)) {
        $message = $emailTemplate->email_body;
    }

    foreach ($shortCodes as $code => $value) {
        $message = shortCodeReplacer('{{' . $code . '}}', $value, $message);
    }

    $config = $general->mail_config;

    $emailLog = new EmailLog();
    $emailLog->user_id = $user->id;
    $emailLog->mail_sender = $config->name;
    $emailLog->email_from = $general->sitename . ' ' . $general->email_from;
    $emailLog->email_to = $user->email;
    $emailLog->subject = $emailTemplate->subj;
    $emailLog->message = $message;
    $emailLog->save();


    if ($config->name == 'php') {
        sendPhpMail($user->email, $user->username, $emailTemplate->subj, $message, $general);
    } else if ($config->name == 'smtp') {
        sendSmtpMail($config, $user->email, $user->username, $emailTemplate->subj, $message, $general);
    } else if ($config->name == 'sendgrid') {
        sendSendGridMail($config, $user->email, $user->username, $emailTemplate->subj, $message, $general);
    } else if ($config->name == 'mailjet') {
        sendMailjetMail($config, $user->email, $user->username, $emailTemplate->subj, $message, $general);
    }
}


function sendPhpMail($receiver_email, $receiver_name, $subject, $message, $general)
{
    $headers = "From: $general->sitename <$general->email_from> \r\n";
    $headers .= "Reply-To: $general->sitename <$general->email_from> \r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    @mail($receiver_email, $subject, $message, $headers);
}


function sendSmtpMail($config, $receiver_email, $receiver_name, $subject, $message, $general)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $config->host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $config->username;
        $mail->Password   = $config->password;
        if ($config->enc == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port       = $config->port;
        $mail->CharSet = 'UTF-8';
        //Recipients
        $mail->setFrom($general->email_from, $general->sitename);
        $mail->addAddress($receiver_email, $receiver_name);
        $mail->addReplyTo($general->email_from, $general->sitename);
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->send();
    } catch (Exception $e) {
        throw new Exception($e);
    }
}


function sendSendGridMail($config, $receiver_email, $receiver_name, $subject, $message, $general)
{
    $sendgridMail = new \SendGrid\Mail\Mail();
    $sendgridMail->setFrom($general->email_from, $general->sitename);
    $sendgridMail->setSubject($subject);
    $sendgridMail->addTo($receiver_email, $receiver_name);
    $sendgridMail->addContent("text/html", $message);
    $sendgrid = new \SendGrid($config->appkey);
    try {
        $response = $sendgrid->send($sendgridMail);
    } catch (Exception $e) {
        throw new Exception($e);
    }
}


function sendMailjetMail($config, $receiver_email, $receiver_name, $subject, $message, $general)
{
    $mj = new \Mailjet\Client($config->public_key, $config->secret_key, true, ['version' => 'v3.1']);
    $body = [
        'Messages' => [
            [
                'From' => [
                    'Email' => $general->email_from,
                    'Name' => $general->sitename,
                ],
                'To' => [
                    [
                        'Email' => $receiver_email,
                        'Name' => $receiver_name,
                    ]
                ],
                'Subject' => $subject,
                'TextPart' => "",
                'HTMLPart' => $message,
            ]
        ]
    ];
    $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
}


function getPaginate($paginate = 20)
{
    return $paginate;
}

function paginateLinks($data, $design = 'admin.partials.paginate')
{
    return $data->appends(request()->all())->links($design);
}


function menuActive($routeName, $type = null)
{
    if ($type == 3) {
        $class = 'side-menu--open';
    } elseif ($type == 2) {
        $class = 'sidebar-submenu__open';
    } else {
        $class = 'active';
    }
    if (is_array($routeName)) {
        foreach ($routeName as $key => $value) {
            if (request()->routeIs($value)) {
                return $class;
            }
        }
    } elseif (request()->routeIs($routeName)) {
        return $class;
    }
}


function imagePath()
{
    $data['gateway'] = [
        'path' => 'assets/images/gateway',
        'size' => '800x800',
    ];
    $data['verify'] = [
        'withdraw' => [
            'path' => 'assets/images/verify/withdraw'
        ],
        'deposit' => [
            'path' => 'assets/images/verify/deposit'
        ]
    ];
    $data['image'] = [
        'default' => 'assets/images/default.png',
    ];
    $data['withdraw'] = [
        'method' => [
            'path' => 'assets/images/withdraw/method',
            'size' => '800x800',
        ]
    ];
    $data['ticket'] = [
        'path' => 'assets/support',
    ];
    $data['language'] = [
        'path' => 'assets/images/lang',
        'size' => '64x64'
    ];
    $data['logoIcon'] = [
        'path' => 'assets/images/logoIcon',
    ];
    $data['favicon'] = [
        'size' => '128x128',
    ];
    $data['extensions'] = [
        'path' => 'assets/images/extensions',
        'size' => '36x36',
    ];
    $data['seo'] = [
        'path' => 'assets/images/seo',
        'size' => '600x315'
    ];
    $data['profile'] = [
        'user' => [
            'path' => 'assets/images/user/profile',
            'size' => '350x300'
        ],
        'admin' => [
            'path' => 'assets/admin/images/profile',
            'size' => '400x400'
        ]
    ];
    return $data;
}

function diffForHumans($date)
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->diffForHumans();
}

function showDateTime($date, $format = 'Y-m-d h:i A')
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->translatedFormat($format);
}

function showGender($val)
{
    switch ($val) {
        case $val == 0:
            $result = 'Others';
            break;
        case $val == 1:
            $result = 'Male';
            break;
        case $val == 2:
            $result = 'Female';
            break;
        default:
            $result = '';
            break;
    }
    return $result;
}

function showDayOff($val)
{
    $result = '';
    if (gettype($val) == 'array') {
        foreach ($val as $value) {
            $result .= getDay($value);
        }
    } else {
        $result = getDay($val);
    }
    return $result;
}

function getDay($val)
{
    switch ($val) {
        case $val == 0:
            $result = 'Sunday';
            break;
        case $val == 1:
            $result = 'Monday';
            break;
        case $val == 2:
            $result = 'Tuesday';
            break;
        case $val == 3:
            $result = 'Wednesday';
            break;
        case $val == 4:
            $result = 'Thursday';
            break;
        case $val == 5:
            $result = 'Friday';
            break;
        case $val == 6:
            $result = 'Saturday';
            break;
        default:
            $result = '';
            break;
    }
    return $result;
}

//moveable
function sendGeneralEmail($email, $subject, $message, $receiver_name = '')
{

    $general = GeneralSetting::first();


    if ($general->en != 1 || !$general->email_from) {
        return;
    }
    $message = shortCodeReplacer("{{message}}", $message, $general->email_template);
    $message = shortCodeReplacer("{{fullname}}", $receiver_name, $message);
    $message = shortCodeReplacer("{{username}}", $email, $message);

    $config = $general->mail_config;

    if ($config->name == 'php') {
        sendPhpMail($email, $receiver_name, $subject, $message, $general);
    } else if ($config->name == 'smtp') {
        sendSmtpMail($config, $email, $receiver_name, $subject, $message, $general);
    } else if ($config->name == 'sendgrid') {
        sendSendGridMail($config, $email, $receiver_name, $subject, $message, $general);
    } else if ($config->name == 'mailjet') {
        sendMailjetMail($config, $email, $receiver_name, $subject, $message, $general);
    }
}

function getContent($data_keys, $singleQuery = false, $limit = null, $orderById = false)
{
    if ($singleQuery) {
        $content = Frontend::where('data_keys', $data_keys)->orderBy('id', 'desc')->first();
    } else {
        $article = Frontend::query();
        $article->when($limit != null, function ($q) use ($limit) {
            return $q->limit($limit);
        });
        if ($orderById) {
            $content = $article->where('data_keys', $data_keys)->orderBy('id')->get();
        } else {
            $content = $article->where('data_keys', $data_keys)->orderBy('id', 'desc')->get();
        }
    }
    return $content;
}


function gatewayRedirectUrl($type = false)
{
    if ($type) {
        return 'user.ticket.history';
    } else {
        return 'ticket';
    }
}

function getStoppageInfo($stoppages)
{
    $data = Counter::routeStoppages($stoppages);
    return $data;
}

function stoppageCombination($numbers, $arraySize, $level = 1, $i = 0, $addThis = [])
{
    // If this is the last layer, use a different method to pass the number.
    if ($level == $arraySize) {
        $result = [];
        for (; $i < count($numbers); $i++) {
            $result[] = array_merge($addThis, array($numbers[$i]));
        }
        return $result;
    }

    $result = [];
    $nextLevel = $level + 1;
    for (; $i < count($numbers); $i++) {
        // Add the data given from upper level to current iterated number and pass
        // the new data to a deeper level.
        $newAdd = array_merge($addThis, array($numbers[$i]));
        $temp = stoppageCombination($numbers, $arraySize, $nextLevel, $i, $newAdd);


        $result = array_merge($result, $temp);
    }

    return $result;
}


function urlPath($routeName, $routeParam = null)
{
    if ($routeParam == null) {
        $url = route($routeName);
    } else {
        $url = route($routeName, $routeParam);
    }
    $basePath = route('home');
    $path = str_replace($basePath, '', $url);
    return $path;
}




function sendOtp($mobile, $userName = "Guest", $otp)
{
    $apiUrl = env('WHATSAPP_API_URL');
    $apiKey = env('WHATSAPP_API_KEY');
    // $otp    = (string) rand(100000, 999999);

    $payload = [
        "apiKey"              => $apiKey,
        "campaignName"        => "whatsapp_otp",
        "destination"         => "91{$mobile}",
        "userName"            => $userName,
        "templateParams"      => [$otp],
        "source"              => "new-landing-page form",
        "media"               => [],
        "buttons"             => [[
            "type"       => "button",
            "sub_type"   => "url",
            "index"      => 0,
            "parameters" => [
                [
                    "type" => "text",
                    "text" => $otp, // Replace with dynamic or fixed value if needed
                ],
            ],
        ]],
        "carouselCards"       => [],
        "location"            => [],
        "paramsFallbackValue" => ["FirstName" => "user"],
    ];

    $response = Http::post($apiUrl, $payload);
    Log::info($response);
    if ($response->successful()) {
        return $otp; // Return OTP if the API call succeeds
    } else {
        throw new \Exception("Failed to send OTP. Error: " . $response->body());
        return $response->body();
    }
}





function sendTicketDetailsWhatsApp(array $ticketDetails, $mobileNumber)
{
    $apiUrl = env('WHATSAPP_API_URL');
    $apiKey = env('WHATSAPP_API_KEY');

    // Prepare payload
    $payload = [
        'apiKey'              => $apiKey,
        'campaignName'        => 'ticket-booking',
        "destination"         => "91{$mobileNumber}",
        'userName'            => $ticketDetails['passenger_name'],
        'templateParams'      => [
            $ticketDetails['source_name'] ?? 'N/A',
            $ticketDetails['destination_name'] ?? 'N/A',
            $ticketDetails['date_of_journey'] ?? 'N/A',
            $ticketDetails['pnr'] ?? 'N/A',
            $ticketDetails['seats'] ?? 'N/A',
            $ticketDetails['boarding_details'], // Boarding Details
            $ticketDetails['drop_off_details'], // Drop-Off Details
        ],
        'source'              => 'new-landing-page form',
        'media'               => [], // No media provided
        'buttons'             => [], // No buttons provided
        'carouselCards'       => [], // No carousel cards provided
        'location'            => [], // No location provided
        'paramsFallbackValue' => [
            'FirstName' => 'user',
        ],
    ];

    $response = Http::post($apiUrl, $payload);

    if ($response->successful()) {
        return true; // Return OTP if the API call succeeds
    } else {
        throw new \Exception("Failed to send OTP. Error: " . $response->body());
    }
}

function searchAPIBuses($source, $destination, $date, $userIp = "::1")
{
    try {
        $busUrl = env('LIVE_BUS_API') . '/busservice/rest/search';
        $busUser = env('LIVE_BUS_USERNAME');
        $busPass = env('LIVE_BUS_PASSWORD');
        $data = [
            'UserIp' => $userIp ?: "::1",
            'OriginId' => $source,
            'DestinationId' => $destination,
            'DateOfJourney' => $date,
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Username' => $busUser,
            'Password' => $busPass,
        ])->post($busUrl, $data);
        return $response->json();
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function getAPIBusSeats($resultIndex, $token, $userIp = "::1")
{
    try {
        $busUrl = env('LIVE_BUS_API') . '/busservice/rest/seatlayout';
        $busUser = env('LIVE_BUS_USERNAME');
        $busPass = env('LIVE_BUS_PASSWORD');

        $data = [
            'UserIp' => $userIp,
            'SearchTokenId' => $token,
            'ResultIndex' => $resultIndex
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Username' => $busUser,
            'Password' => $busPass,
        ])->post($busUrl, $data);

        return $response->json();
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function getBoardingPoints($SearchTokenID, $ResultIndex, $userIp = "::1")
{
    try {
        $busUrl = env('LIVE_BUS_API') . '/busservice/rest/boardingpoint';
        $busUser = env('LIVE_BUS_USERNAME');
        $busPass = env('LIVE_BUS_PASSWORD');

        $data = [
            'SearchTokenId' => $SearchTokenID,
            'ResultIndex' => $ResultIndex,
            'UserIp' => $userIp,
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Username' => $busUser,
            'Password' => $busPass,
        ])->post($busUrl, $data);
        if ($response->successful()) {
            return $response->json();
        }
        Log::error('Boarding points API error: ' . $response->body());
        return null;
    } catch (\Exception $e) {
        Log::error('Boarding points API exception: ' . $e->getMessage());
        return null;
    }
}

function blockSeatHelper($SearchTokenID, $ResultIndex, $boardingPointId, $droppingPointId, $passengers, $seats, $UserIp = "::1")
{
    try {
        $busUrl = env('LIVE_BUS_API') . '/busservice/rest/blockseat';
        $busUser = env('LIVE_BUS_USERNAME');
        $busPass = env('LIVE_BUS_PASSWORD');

        $data = [
            'UserIp' => $UserIp,
            'SearchTokenId' => $SearchTokenID,
            'ResultIndex' => $ResultIndex,
            'BoardingPointId' => (int)$boardingPointId,
            'DroppingPointId' => (int)$droppingPointId,
            'Passenger' => $passengers
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Username' => $busUser,
            'Password' => $busPass,
        ])->post($busUrl, $data);

        if ($response->successful()) {
            $json = $response->json();
            // Check if Result exists and has an Error with ErrorCode != 0
            if (isset($json['Error']) && isset($json['Error']['ErrorCode']) && $json['Error']['ErrorCode'] != 0) {
                return [
                    'success' => false,
                    'message' => $json['Error']['ErrorMessage'] ?? 'Unknown API error',
                    'code' => $json['Error']['ErrorCode'],
                    'error' => $json['Error'],
                ];
            }

            return [
                'success' => true,
                'Result' => $json['Result'] ?? $json
            ];
        }
        return [
            'success' => false,
            'message' => 'Failed to block seats',
            'error' => $response->body()
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception occurred while blocking seats',
            'error' => $e->getMessage()
        ];
    }
}


function bookAPITicket($userIp, $searchTokenId, $resultIndex, $boardingPointId, $droppingPointId, $passengers)
{
    try {
        $busUrl = env('LIVE_BUS_API') . '/busservice/rest/book';
        $busUser = env('LIVE_BUS_USERNAME');
        $busPass = env('LIVE_BUS_PASSWORD');

        $data = [
            'UserIp' => $userIp,
            'SearchTokenId' => $searchTokenId,
            'ResultIndex' => $resultIndex,
            'BoardingPointId' => (int)$boardingPointId,
            'DroppingPointId' => (int)$droppingPointId,
            'Passenger' => $passengers
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Username' => $busUser,
            'Password' => $busPass,
        ])->post($busUrl, $data);

        Log::info('Book ticket API response: ' . $response->body());
        return $response->json();
    } catch (\Exception $e) {
        Log::error('Book ticket API exception: ' . $e->getMessage());
        return [
            'Error' => [
                'ErrorCode' => 500,
                'ErrorMessage' => $e->getMessage()
            ]
        ];
    }
}


function cancelAPITicket($userIp, $searchTokenId, $bookingId, $seatId, $remarks)
{
    try {
        $busUrl = env('LIVE_BUS_API') . '/busservice/rest/cancelrequest';
        $busUser = env('LIVE_BUS_USERNAME');
        $busPass = env('LIVE_BUS_PASSWORD');

        $data = [
            'UserIp' => $userIp,
            'SearchTokenId' => $searchTokenId,
            'BookingId' => $bookingId,
            'SeatId' => $seatId,
            'Remarks' => $remarks
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Username' => $busUser,
            'Password' => $busPass,
        ];

        // 🔍 Log full request data
        Log::info('Sending cancel ticket API request', [
            'url' => $busUrl,
            'headers' => $headers,
            'body' => $data,
        ]);

        $response = Http::withHeaders($headers)->post($busUrl, $data);

        Log::info('Cancel ticket API response: ' . $response->body());

        return $response->json();
    } catch (\Exception $e) {
        Log::error('Cancel ticket API exception: ' . $e->getMessage());
        return [
            'Error' => [
                'ErrorCode' => 500,
                'ErrorMessage' => $e->getMessage()
            ]
        ];
    }
}


function parseSeatHtmlToJson($html)
{
    $crawler = new Crawler($html);
    $result = [
        'seat' => [
            'upper_deck' => ['rows' => []],
            'lower_deck' => ['rows' => []]
        ]
    ];

    $crawler->filter('.outerseat, .outerlowerseat')->each(function ($deckNode) use (&$result) {
        $deck = str_contains($deckNode->attr('class'), 'outerseat') ? 'upper_deck' : 'lower_deck';
        $rowNumber = 1;

        $deckNode->filter('.seatcontainer > div')->each(function ($seatNode) use (&$result, $deck, &$rowNumber) {
            $classes = explode(' ', $seatNode->attr('class') ?? '');
            $style = $seatNode->attr('style') ?? '';
            $onclick = $seatNode->attr('onclick') ?? '';
            $id = $seatNode->attr('id') ?? '';

            // Extract position
            preg_match('/top:\s*(\d+)px/', $style, $topMatch);
            $top = $topMatch[1] ?? 0;

            // Determine row (each 30px is a new row)
            $currentRow = floor($top / 30) + 1;
            if ($currentRow > $rowNumber) {
                $rowNumber = $currentRow;
            }

            // Initialize row if not exists
            if (!isset($result['seat'][$deck]['rows'][$rowNumber])) {
                $result['seat'][$deck]['rows'][$rowNumber] = [];
            }

            $seatType = $classes[0] ?? '';
            $isAvailable = false;
            $isSleeper = false;
            $price = 0;
            $seatId = $id;

            // Determine seat characteristics based on class
            if ($seatType === 'hseat') {
                $isSleeper = true;
                $isAvailable = true;
            } elseif ($seatType === 'bhseat') {
                $isSleeper = true;
                $isAvailable = false;
            } elseif ($seatType === 'nseat') {
                $isSleeper = false;
                $isAvailable = true;
            } elseif ($seatType === 'bseat') {
                $isSleeper = false;
                $isAvailable = false;
            }

            // Override from onclick if available
            if ($onclick && preg_match("/AddRemoveSeat\(.*?,'(.*?)','(.*?)'/", $onclick, $matches)) {
                $seatId = $matches[1];
                $price = (float)$matches[2];
                // Maintain type from class, only update availability
                $isAvailable = true;
            }

            $result['seat'][$deck]['rows'][$rowNumber][] = [
                'seat_id' => $seatId,
                'price' => $price,
                'is_sleeper' => $isSleeper,
                'type' => $seatType,
                'category' => $isSleeper ? 'sleeper' : 'seater',
                'position' => (int)$top,
                'is_available' => $isAvailable
            ];
        });
    });

    return $result;
}


if (!function_exists('formatCancelPolicy')) {
    function formatCancelPolicy(array $cancelPolicy)
    {
        $formatted = [];

        foreach ($cancelPolicy as $policy) {
            $charge = $policy['CancellationCharge'];
            $chargeType = $policy['CancellationChargeType'];
            $from = Carbon::parse($policy['FromDate']);
            $to = Carbon::parse($policy['ToDate']);

            // Format times for display
            $fromTime = $from->format('g:i A');
            $fromDate = $from->format('d M Y');
            $toTime = $to->format('g:i A');
            $toDate = $to->format('d M Y');

            if ($from->isSameDay($to)) {
                if ($from->eq($to)) {
                    $label = "After {$fromTime}, {$fromDate}";
                } elseif ($from->isMidnight()) {
                    $label = "Before {$toTime}, {$toDate}";
                } else {
                    $label = "Between {$fromTime} to {$toTime}, {$fromDate}";
                }
            } else {
                $label = "Between {$fromTime}, {$fromDate} to {$toTime}, {$toDate}";
            }

            // Decide charge string
            if ($chargeType == 2) {
                $chargeStr = "{$charge}% charge";
            } elseif ($chargeType == 1) {
                $chargeStr = "₹" . number_format($charge, 2) . " charge";
            } else {
                $chargeStr = "No refund";
            }

            // Special case: 100% = no refund
            if ($chargeType == 2 && $charge == 100) {
                $chargeStr = "No refund";
            }

            $formatted[] = "{$label} – {$chargeStr}";
        }

        return $formatted;
    }
}

// app/Helpers/helpers.php
if (!function_exists('renderSeatHTML')) {
    function renderSeatHTML($html)
    {
        return $html; // You could sanitize here if needed
    }
}
