<?php

namespace App\Libraries;

class SmsLib
{
    private $CI;

    public function __construct()
    {
        $this->CI = service('request')->CI;
    }

    /*
     * SMS sending function
     * Example of use: $response = sendSMS('4477777777', 'My test message');
     */
    public function sendSMS($phone, $message)
    {
        $username = $this->CI->config->item('msg_uid');
        $password = $this->CI->config->item('msg_pwd');
        $originator = $this->CI->config->item('msg_src');

        $response = false;

        // if any of the parameters is empty return with a FALSE
        if (empty($username) || empty($password) || empty($phone) || empty($message) || empty($originator)) {
            //echo $username . ' ' . $password . ' ' . $phone . ' ' . $message . ' ' . $originator;
        } else {
            $response = true;

            // make sure passed string is URL encoded
            $message = rawurlencode($message);

            // add call to send a message via 3rd party API here
            // Some examples

            /*
            $url = "http://xxx.xxx.xxx.xxx/send_sms?username=$username&password=$password&src=$originator&dst=$phone&msg=$message&dr=1";

            $c = curl_init();
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_URL, $url);
            $response = curl_exec($c);
            curl_close($c);
            */

            // This is a textmarketer.co.uk API call, see: http://wiki.textmarketer.co.uk/display/DevDoc/Text+Marketer+Developer+Documentation+-+Wiki+Home
            /*
            $url = 'https://api.textmarketer.co.uk/gateway/'."?username=$username&password=$password&option=xml";
            $url .= "&to=$phone&message=".urlencode($message).'&orig='.urlencode($originator);
            $fp = fopen($url, 'r');
            $response = fread($fp, 1024);
            */
        }

        return $response;
    }
}
