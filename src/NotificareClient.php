<?php

namespace Notificare\Notificare;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class NotificareClient
{
    const API_URL = 'https://push.notifica.re';

    const ENDPOINT_NOTIFICATIONS = '/notifications';
    const ENDPOINT_PLAYERS = '/players';

    protected $client;
    protected $options;
    protected $applicationKey;
    protected $masterSecret;
    protected $additionalParams;

    /**
     * @var bool
     */
    public $requestAsync = false;

    /**
     * @var Callable
     */
    private $requestCallback;

    /**
     * Turn on, turn off async requests
     *
     * @param bool $on
     * @return $this
     */
    public function async($on = true)
    {
        $this->requestAsync = $on;
        return $this;
    }

    /**
     * Callback to execute after Notificare returns the response
     * @param Callable $requestCallback
     * @return $this
     */
    public function callback(Callable $requestCallback)
    {
        $this->requestCallback = $requestCallback;
        return $this;
    }

    public function __construct($applicationKey, $masterSecret)
    {
        $this->applicationKey = $applicationKey;
        $this->masterSecret = $masterSecret;

        $this->client = new Client([
            'base_uri' => self::API_URL,
        ]);
        $this->options = [];
        $this->additionalParams = [];
    }

    public function testCredentials()
    {
        return 'APP KEY: ' . $this->applicationKey . ' SECRET: ' . $this->masterSecret;
    }

    private function requiresAuth()
    {
        $this->options[RequestOptions::AUTH] = [
            $this->applicationKey,
            $this->masterSecret,
        ];
    }

    public function setAdditionalParams($params = [])
    {
        $this->additionalParams = $params;
        return $this;
    }

    public function setAdditionalParam($key, $value)
    {
        $this->additionalParams[$key] = $value;
        return $this;
    }

    public function sendNotificationToUser($message, $userId, $url = null, $data = null, $buttons = null, $schedule = null)
    {
        $contents = array(
            'en' => $message
        );

        $params = array(
            'app_id' => $this->applicationKey,
            'contents' => $contents,
            'include_player_ids' => is_array($userId) ? $userId : array($userId)
        );

        if (isset($url)) {
            $params['url'] = $url;
        }

        if (isset($data)) {
            $params['data'] = $data;
        }

        if (isset($buttons)) {
            $params['buttons'] = $buttons;
        }

        if (isset($schedule)) {
            $params['send_after'] = $schedule;
        }

        $this->sendNotificationCustom($params);
    }

    public function sendNotificationUsingTags($message, $tags, $url = null, $data = null, $buttons = null, $schedule = null)
    {
        $contents = array(
            'en' => $message
        );

        $params = array(
            'app_id' => $this->applicationKey,
            'contents' => $contents,
            'filters' => $tags,
        );

        if (isset($url)) {
            $params['url'] = $url;
        }

        if (isset($data)) {
            $params['data'] = $data;
        }

        if (isset($buttons)) {
            $params['buttons'] = $buttons;
        }

        if (isset($schedule)) {
            $params['send_after'] = $schedule;
        }

        $this->sendNotificationCustom($params);
    }

    public function sendNotificationToAll($message, $url = null, $data = null, $buttons = null, $schedule = null)
    {
        $contents = array(
            'en' => $message
        );

        $params = array(
            'app_id' => $this->applicationKey,
            'contents' => $contents,
            'included_segments' => array('All')
        );

        if (isset($url)) {
            $params['url'] = $url;
        }

        if (isset($data)) {
            $params['data'] = $data;
        }

        if (isset($buttons)) {
            $params['buttons'] = $buttons;
        }

        if (isset($schedule)) {
            $params['send_after'] = $schedule;
        }

        $this->sendNotificationCustom($params);
    }

    public function sendNotificationToSegment($message, $segment, $url = null, $data = null, $buttons = null, $schedule = null)
    {
        $contents = array(
            'en' => $message
        );

        $params = array(
            'app_id' => $this->applicationKey,
            'contents' => $contents,
            'included_segments' => [$segment]
        );

        if (isset($url)) {
            $params['url'] = $url;
        }

        if (isset($data)) {
            $params['data'] = $data;
        }

        if (isset($buttons)) {
            $params['buttons'] = $buttons;
        }

        if (isset($schedule)) {
            $params['send_after'] = $schedule;
        }

        $this->sendNotificationCustom($params);
    }

    /**
     * Send a notification with custom parameters defined in
     * https://documentation.notificare.com/reference#section-example-code-create-notification
     * @param array $parameters
     * @return mixed
     */
    public function sendNotificationCustom($parameters = [])
    {
        $this->requiresAuth();

        if (isset($parameters['api_key'])) {
            $this->options['headers']['Authorization'] = 'Basic ' . $parameters['api_key'];
        }

        // Make sure to use app_id
        if (!isset($parameters['app_id'])) {
            $parameters['app_id'] = $this->applicationKey;
        }

        // Make sure to use included_segments
        if (empty($parameters['included_segments']) && empty($parameters['include_player_ids'])) {
            $parameters['included_segments'] = ['all'];
        }

        $parameters = array_merge($parameters, $this->additionalParams);

        $this->options[RequestOptions::JSON] = $parameters;
        //$this->options['buttons'] = json_encode($parameters);//FIXME: ????
        //$this->options[RequestOptions::VERIFY] = false;//FIXME: ???

        return $this->post(self::ENDPOINT_NOTIFICATIONS);
    }

    public function getNotification($notification_id, $app_id = null)
    {
        $this->requiresAuth();

        if (!$app_id) {
            $app_id = $this->applicationKey;
        }

        return $this->get(self::ENDPOINT_NOTIFICATIONS . '/' . $notification_id . '?app_id=' . $app_id);
    }

    /**
     * Creates a user/player
     *
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function createPlayer(Array $parameters)
    {
        if (!isset($parameters['device_type']) or !is_numeric($parameters['device_type'])) {
            throw new \Exception('The `device_type` param is required as integer to create a player(device)');
        }
        return $this->sendPlayer($parameters, 'POST', self::ENDPOINT_PLAYERS);
    }

    /**
     * Edit a user/player
     *
     * @param array $parameters
     * @return mixed
     */
    public function editPlayer(Array $parameters)
    {
        return $this->sendPlayer($parameters, 'PUT', self::ENDPOINT_PLAYERS . '/' . $parameters['id']);
    }

    /**
     * Create or update a by $method value
     *
     * @param array $parameters
     * @param $method
     * @param $endpoint
     * @return mixed
     */
    private function sendPlayer(Array $parameters, $method, $endpoint)
    {
        $this->requiresAuth();

        $parameters['app_id'] = $this->applicationKey;
        $this->options[RequestOptions::JSON] = $parameters;

        $method = strtolower($method);
        return $this->{$method}($endpoint);
    }

    public function post($endPoint)
    {
        if ($this->requestAsync === true) {
            $promise = $this->client->postAsync($endPoint, $this->options);
            return (is_callable($this->requestCallback) ? $promise->then($this->requestCallback) : $promise);
        }
        return $this->client->post($endPoint, $this->options);
    }

    public function put($endPoint)
    {
        if ($this->requestAsync === true) {
            $promise = $this->client->putAsync($endPoint, $this->options);
            return (is_callable($this->requestCallback) ? $promise->then($this->requestCallback) : $promise);
        }
        return $this->client->put($endPoint, $this->options);
    }

    public function get($endPoint)
    {
        return $this->client->get($endPoint, $this->options);
    }
}
