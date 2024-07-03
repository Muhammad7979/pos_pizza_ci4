<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class Biometric
{
    private $CloudABIS_API_URL = 'https://bioplugin.cloudabis.com/v12/';
    private $CloudABISAppKey = '58a9fa2fa73c43219fa5fba624fe02c4';
    private $CloudABISSecretKey = '640611549E9D4D34B2E068DA29C4208F';
    private $ENGINE_NAME = 'FingerPrint';
    private $FORMAT = 'ISO';

    public function generateToken()
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->CloudABIS_API_URL . "api/Authorizations/Token",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\r\n  \"clientAPIKey\": \"$this->CloudABISAppKey\", \r\n  \"clientKey\": \"$this->CloudABISSecretKey\"\r\n}",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "postman-token: 6f57f414-8466-926e-03e0-38a76c201598"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($err) {
                throw new Exception("cURL Error #:" . $err);
            } else {
                $response = json_decode($response);
                return isset($response->responseData) ? $response->responseData : null;
            }
        } catch (Exception $e) {
            throw new Exception("Experiencing technical difficulties!");
        }
    }

    public function isRegistered($biometricRequest, $token)
    {
        $registrationid = $biometricRequest->username;
        $engineName = $this->ENGINE_NAME;
        $customerKey = $this->CloudABISSecretKey;

        $curl = curl_init();

        $data = json_encode([
            'ClientKey' => $customerKey,
            'RegistrationID' => $registrationid,
        ]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->CloudABIS_API_URL . "api/Biometrics/IsRegistered",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $token",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: f33d9566-866e-d6f9-5b85-bb5eabd25da5"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            return array($httpcode, $err);
        } else {
            return array($httpcode, json_decode($response));
        }
    }

    public function register($biometricRequest, $token)
    {
        $customerKey = $this->CloudABISSecretKey;

        $registrationid = $biometricRequest->username;

        $curl = curl_init();

        $data = json_encode([
            'ClientKey' => $customerKey,
            'RegistrationID' => $registrationid,
            'Images' => [
                'Fingerprint' => [
                    [
                        'Position' => 1,
                        'Base64Image' => $biometricRequest->templateXML
                    ]
                ]
            ]
        ]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->CloudABIS_API_URL . "api/Biometrics/Register",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $token",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 2f03c3f1-3cb4-796f-096f-fdf87126e8c8",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($err) {
            return array($httpcode, $err);
        } else {
            return array($httpcode, json_decode($response));
        }
    }

    public function update($biometricRequest, $token)
    {
        $registrationid = $biometricRequest->username;
        $customerKey = $this->CloudABISSecretKey;

        $curl = curl_init();

        $data = json_encode([
            'ClientKey' => $customerKey,
            'RegistrationID' => $registrationid,
            'Images' => [
                'Fingerprint' => [
                    [
                        'Position' => 1,
                        'Base64Image' => $biometricRequest->templateXML
                    ]
                ]
            ]
        ]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->CloudABIS_API_URL . "api/Biometrics/Update",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $token",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 9f9fa4b2-f9b0-a245-9c8b-158ef833e918"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            return array($httpcode, $err);
        } else {
            return array($httpcode, json_decode($response));
        }
    }

    public function identify($biometricRequest, $token)
    {
        $customerKey = $this->CloudABISSecretKey;

        $curl = curl_init();

        $data = json_encode([
            'ClientKey' => $customerKey,
            'Images' => [
                'Fingerprint' => [
                    [
                        'Position' => 1,
                        'Base64Image' => $biometricRequest->templateXML
                    ]
                ]
            ]
        ]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->CloudABIS_API_URL . "api/Biometrics/Identify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $token",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 1d325ed7-879f-1544-4aca-3b3a91d8c071"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            return array($httpcode, $err);
        } else {
            return array($httpcode, json_decode($response));
        }
    }
}
