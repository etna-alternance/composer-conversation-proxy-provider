<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use Guzzle\Http\Message\Request as GuzzleRequest;

use GuzzleHttp\Cookie\CookieJar;
use Silex\Application;

use Symfony\Component\HttpFoundation\Request;

class ConversationManager
{
    private $app;

    public function __construct(Application $app = null)
    {
        if (null === $app) {
            throw new \Exception("ConversationManager requires $app to be set");
        }
        $this->app = $app;
    }

    public function findByQueryString($query, $from = 0, $size = 99999)
    {
        $query    = urlencode($query);
        $response = $this->fireRequest("GET", "/search?q={$query}&from={$from}&size={$size}");

        $response["hits"] = array_map(
            function ($hit) {
                $conversation = new Conversation();
                $conversation->fromArray($hit);
                return $conversation;
            },
            $response["hits"]
        );

        return $response;
    }

    public function findUnreadByQueryString($query, $from = 0, $size = 99999)
    {
        $query  = urlencode($query);
        $unread = $this->fireRequest("GET", "/fetch_unread?q={$query}&from={$from}&size={$size}");

        return $unread;
    }

    public function findOneByQueryString($query)
    {
        $matching = $this->findByQueryString($query, 0, 1);
        if (0 === count($matching["hits"])) {
            return null;
        }
        return $matching["hits"][0];
    }

    public function findStatsByQueryString($query, $from = 0, $size = 99999)
    {
        $query    = urlencode($query);
        $response = $this->fireRequest("GET", "/stats?q={$query}&from={$from}&size={$size}");

        return $stats;
    }

    public function save(Conversation $conversation)
    {
        $actions  = $conversation->getSaveActions();
        $response = null;

        if ($actions === [["method" => "post", "route" => "/conversations"]]) {
            $body = $conversation->toArray();
            if (false === isset($body["messages"][0])) {
                return $this->app->abort(400, "Need content to create conversation");
            }

            $body["metas"] = json_encode($body["metas"]);
            $response      = $this->fireRequest("POST", "/conversations", $body);
        } else {
            foreach ($actions as $action) {
                $route  = $action["route"];
                $method = $action["method"];
                unset($action["route"]);
                unset($action["method"]);

                $response = $this->fireRequest($method, $route, $action);
            }
        }
        return $response;
    }

    public function toJsonResponse(array $conversations)
    {
        foreach ($conversations as $index => $conversation) {
            $conversations[$index] = $conversation->toArray();
        }
        return $conversations;
    }

    private function fireRequest($method, $uri, $body = [])
    {
        $method = strtoupper($method);

        if (false === in_array($method, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
            return $this->app->abort(405, "ConversationProxy can not fire request of method : {$method}");
        }

        $domain = getenv("TRUSTED_DOMAIN");
        $jar    = CookieJar::fromArray(["authenticator" => $this->app["cookies.authenticator"]], $domain);

        try {
            $response = $this->app["conversation_proxy"]->request($method, $uri, [
                "cookies" => $jar,
                "json"    => $body
            ]);
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $client_error) {
            return $app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
    }
}
