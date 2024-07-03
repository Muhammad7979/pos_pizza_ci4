<?php

namespace App\Libraries;

use Exception;
use Racecore\GATracking\GATracking;
use Racecore\GATracking\Tracking\Event;
use Racecore\GATracking\Tracking\Factory;

class TrackingLib
{
    private $CI;
    private $tracking;

    public function __construct()
    {
        $this->CI = service('request')->CI;

        $clientId = $this->CI->Appconfig->get('client_id');
        /**
         * Setup the class
         * optional
         */
        $options = [
            'client_create_random_id' => true, // create a random client id when the class can't fetch the current client id or none is provided by "client_id"
            'client_fallback_id' => 555, // fallback client id when cid was not found and random client id is off
            'client_id' => $clientId, // override client id
            'user_id' => getenv('SERVER_ADDR'), // determine current user id
            // adapter options
            'adapter' => [
                'async' => true, // requests to google are async - don't wait for google server response
                'ssl' => false // use ssl connection to google server
            ]
        ];

        $this->tracking = new GATracking('UA-28804498-4', $options);

        if (empty($clientId)) {
            $clientId = $this->tracking->getClientId();

            $this->CI->Appconfig->batch_save(['client_id' => $clientId]);
        }
    }

    /*
     * Track Event function
     */
    public function track_event($category, $action, $label = null, $value = null)
    {
        try {
            /** @var Event $event */
            $event = $this->tracking->createTracking('Event');
            $event->setAsNonInteractionHit(true);
            $event->setEventCategory($category);
            $event->setEventAction($action);
            $event->setEventLabel($label);
            $event->setEventValue($value);

            return $this->tracking->sendTracking($event);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /*
     * Track Page function
     */
    public function track_page($path, $title, $description = ' ')
    {
        try {
            /** @var Factory $event */
            $event = $this->tracking->createTracking('Factory', [
                'an' => 'OSPOS',
                'av' => $this->CI->config->item('application_version') . ' - ' . substr($this->CI->config->item('commit_sha1'), 5, 12),
                'ul' => $this->CI->config->item('language'),
                'dh' => getenv('SERVER_ADDR'),
                'dp' => $path,
                'dt' => $title,
                'cd' => $description
            ]);

            return $this->tracking->sendTracking($event);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
}
