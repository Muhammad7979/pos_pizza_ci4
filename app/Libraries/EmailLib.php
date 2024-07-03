<?php

namespace App\Libraries;

use App\Models\Appconfig;
use CodeIgniter\Email\Email;

class EmailLib
{
    protected $email;
    protected $appData;

    public function __construct()
    {
        $this->appData = new Appconfig();
        $this->email = new Email();
        
        $config = [
            'mailType' => 'html',
            'userAgent' => 'OSPOS',
            'protocol' => $this->appData->get('protocol'),
            'mailPath' => $this->appData->get('mailpath'),
            'SMTPHost' => $this->appData->get('smtp_host'),
            'SMTPUser' => $this->appData->get('smtp_user'),
            'SMTPPass' => $this->appData->get('smtp_pass'),
            'SMTPPort' => $this->appData->get('smtp_port'),
            'SMTPTimeout' => $this->appData->get('smtp_timeout'),
            'SMTPCrypto' => $this->appData->get('smtp_crypto')
        ];
        
        $this->email->initialize($config);
    }

    /*
     * Email sending function
     * Example of use: $response = sendEmail('john@doe.com', 'Hello', 'This is a message', $filename);
     */
    public function sendEmail($to, $subject, $message, $attachment = null)
    {
        $email = $this->email;
        
        $email->setFrom($this->appData->get('email'), $this->appData->get('company'));
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);
        if (!empty($attachment)) {
            $email->attach($attachment);
        }

        $result = $email->send();
        
        if (!$result) {
            error_log($email->printDebugger());
        }
        
        return $result;
    }
}
