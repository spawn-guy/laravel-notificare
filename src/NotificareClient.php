<?php

namespace Notificare\Notificare;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class NotificareClient
{
    const API_URL = 'https://push.notifica.re';

    const ENDPOINT_NOTIFY_DEVICE = '/device/';
    const ENDPOINT_NOTIFY_SYSTEM_DEVICE = '/notification/system/device/';

    const ENDPOINT_NOTIFY_ALL = '/notification/broadcast';
    const ENDPOINT_NOTIFY_TAGS = '/notification/tags';
    const ENDPOINT_NOTIFY_SEGMENTS = '/notification/segments';
    const ENDPOINT_NOTIFY_CRITERIA = '/notification/criteria';
    const ENDPOINT_NOTIFY_SYSTEM = '/notification/system';

    const ENDPOINT_NOTIFY_SCHEDULE = '/notification/schedule';

    protected $config;
    protected $client;
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

    /**
     * NotificareClient constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->client = new Client([
            'base_uri' => self::API_URL,
        ]);
        $this->additionalParams = [];
    }

    public function testCredentials()
    {
        return 'config: ' . json_encode($this->config);
    }

    private function configGet($field, $default = null)
    {
        return array_get($this->config, $field, $default);
    }

    /**
     * @param array $options
     * @return array
     */
    private function requiresAuthWithAppKeyMasterSecret($options = [])
    {
        $options[RequestOptions::AUTH] = [
            $this->configGet('applicationKey'),
            $this->configGet('masterSecret'),
        ];

        return $options;
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

    /**
     * @param array $notification
     * @param string $deviceId
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationToDevice($notification, $deviceId)
    {
        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_DEVICE . (string)$deviceId);
    }

    /**
     * @param array $notification
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationToAll($notification)
    {
        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_ALL);
    }

    /**
     * @param array $notification
     * @param string|array $tags
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationToTags($notification, $tags)
    {
        $notification['tags'] = (array)$tags;

        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_TAGS);
    }

    /**
     * @param array $notification
     * @param string|array $segments
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationToSegments($notification, $segments)
    {
        $notification['segments'] = (array)$segments;

        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_SEGMENTS);
    }

    /**
     * @param array $notification
     * @param array $criteria
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationToCriteria($notification, $criteria)
    {
        $notification['criteria'] = $criteria;

        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_CRITERIA);
    }

    /**
     * Send a notification with custom parameters
     * @param array $payload
     * @param string|Carbon $when
     * @param boolean $local
     * @param string $uri
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationScheduled($payload, $when = 'now', $local = false, $uri)
    {
        $result = $this->sendNotificationRaw($payload, $uri);

        $resultData = self::getResponseData($result);
        if (!empty($resultData['_id'])) {
            $this->scheduleNotification($resultData['_id'], $when, $local);
        }

        return $result;
    }

    /**
     * Send a notification with custom parameters
     * @param array $payload
     * @param string $uri
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendNotificationRaw($payload, $uri)
    {
        $request = $this->requiresAuthWithAppKeyMasterSecret();

        $request = array_merge($request, $this->additionalParams);

        $request[RequestOptions::JSON] = $payload;

        return $this->post($uri, $request);
    }

    /**
     * @param $endPoint
     * @param $request
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function post($endPoint, $request)
    {
        if ($this->requestAsync === true) {
            $promise = $this->client->postAsync($endPoint, $request);
            return (is_callable($this->requestCallback) ? $promise->then($this->requestCallback) : $promise);
        }
        return $this->client->post($endPoint, $request);
    }

    /**
     * @param $endPoint
     * @param $request
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function put($endPoint, $request)
    {
        if ($this->requestAsync === true) {
            $promise = $this->client->putAsync($endPoint, $request);
            return (is_callable($this->requestCallback) ? $promise->then($this->requestCallback) : $promise);
        }
        return $this->client->put($endPoint, $request);
    }

    /**
     * @param $endPoint
     * @param $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($endPoint, $request)
    {
        return $this->client->get($endPoint, $request);
    }

    /**
     * @param string $notification_id
     * @param string $when
     * @param bool $local
     * @return bool|\GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function scheduleNotification($notification_id, $when, $local = false)
    {
        $notification_id = (string)$notification_id;

        if (!empty($notification_id)) {
            $payloadSchedule = [
                'notification' => $notification_id,
                'time' => Carbon::parse($when)->format('Y-m-d H:i'),
                'local' => $local,
            ];
            return $this->sendNotificationRaw($payloadSchedule, self::ENDPOINT_NOTIFY_SCHEDULE);
        }

        return false;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $result
     * @return mixed
     */
    public static function getResponseData($result)
    {
        return json_decode($result->getBody()->getContents(), true);
    }

    /**
     * @param array $notification
     * @param string $deviceId
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotificationToDevice($notification, $deviceId)
    {
        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_SYSTEM_DEVICE . (string)$deviceId);
    }

    /**
     * @param array $notification
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotification($notification)
    {
        $notification['type'] = 'com.domain.system.notification';

        return $this->sendNotificationRaw($notification, self::ENDPOINT_NOTIFY_SYSTEM);
    }

    /**
     * @param array $notification
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotificationToAll($notification)
    {
        return $this->sendSystemNotification($notification);
    }

    /**
     * @param array $notification
     * @param string|array $tags
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotificationToTags($notification, $tags)
    {
        $notification['tags'] = (array)$tags;

        return $this->sendSystemNotification($notification);
    }

    /**
     * @param array $notification
     * @param string|array $segments
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotificationToSegments($notification, $segments)
    {
        $notification['segments'] = (array)$segments;

        return $this->sendSystemNotification($notification);
    }

    /**
     * @param array $notification
     * @param array $criteria
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotificationToCriteria($notification, $criteria)
    {
        $notification['criteria'] = $criteria;

        return $this->sendSystemNotification($notification);
    }

    /**
     * @param array $notification
     * @param array $location
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function sendSystemNotificationToLocation($notification, $location)
    {
        $notification['location'] = $location;

        return $this->sendSystemNotification($notification);
    }
}
