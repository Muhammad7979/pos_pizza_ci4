<?php

namespace App\Libraries;
use App\Libraries\qrcode\QRcode;
// use App\Libraries\QRcode\QRcode;
use App\Libraries\QRcode\QRimage;
use CodeIgniter\Files\File;

class Ciqrcode
{
    protected $cacheable = true;
    protected $cachedir = WRITEPATH . 'cache/';
    protected $errorlog = WRITEPATH . 'logs/';
    protected $quality = true;
    protected $size = 1024;

    public function __construct($config = [])
    {
        $this->initialize($config);
    }

    public function initialize($config = [])
    {
        $this->cacheable = isset($config['cacheable']) ? $config['cacheable'] : $this->cacheable;
        $this->cachedir = isset($config['cachedir']) ? $config['cachedir'] : $this->cachedir;
        $this->errorlog = isset($config['errorlog']) ? $config['errorlog'] : $this->errorlog;
        $this->quality = isset($config['quality']) ? $config['quality'] : $this->quality;
        $this->size = isset($config['size']) ? $config['size'] : $this->size;

        define('QR_CACHEABLE', $this->cacheable);
        define('QR_CACHE_DIR', $this->cachedir);
        define('QR_LOG_DIR', $this->errorlog);

        if ($this->quality) {
            define('QR_FIND_BEST_MASK', true);
        } else {
            define('QR_FIND_BEST_MASK', false);
            define('QR_DEFAULT_MASK', $this->quality);
        }

        define('QR_FIND_FROM_RANDOM', false);
        define('QR_PNG_MAXIMUM_SIZE', $this->size);

        include_once APPPATH . 'Libraries/qrcode/qrconst.php';
        include_once APPPATH . 'Libraries/qrcode/qrtools.php';
        include_once APPPATH . 'Libraries/qrcode/qrspec.php';
        include_once APPPATH . 'Libraries/qrcode/qrimage.php';
        include_once APPPATH . 'Libraries/qrcode/qrinput.php';
        include_once APPPATH . 'Libraries/qrcode/qrbitstream.php';
        include_once APPPATH . 'Libraries/qrcode/qrsplit.php';
        include_once APPPATH . 'Libraries/qrcode/qrrscode.php';
        include_once APPPATH . 'Libraries/qrcode/qrmask.php';
        include_once APPPATH . 'Libraries/qrcode/qrencode.php';
    }

    public function generate($params = [])
    {
        if (isset($params['black']) && is_array($params['black']) && count($params['black']) == 3 && array_filter($params['black'], 'is_int') === $params['black']) {
            QRimage::$black = $params['black'];
        }

        if (isset($params['white']) && is_array($params['white']) && count($params['white']) == 3 && array_filter($params['white'], 'is_int') === $params['white']) {
            QRimage::$white = $params['white'];
        }

        $params['data'] = isset($params['data']) ? $params['data'] : 'QR Code Library';

        if (isset($params['savename'])) {
            $level = 'L';
            if (isset($params['level']) && in_array($params['level'], ['L', 'M', 'Q', 'H'])) {
                $level = $params['level'];
            }

            $size = 4;
            if (isset($params['size'])) {
                $size = min(max((int)$params['size'], 1), 10);
            }

            QRcode::png($params['data'], $params['savename'], $level, $size, 2);

            return $params['savename'];
        } else {
            $level = 'L';
            if (isset($params['level']) && in_array($params['level'], ['L', 'M', 'Q', 'H'])) {
                $level = $params['level'];
            }

            $size = 4;
            if (isset($params['size'])) {
                $size = min(max((int)$params['size'], 1), 10);
            }

            QRcode::png($params['data'], null, $level, $size, 2);
        }
    }
}
