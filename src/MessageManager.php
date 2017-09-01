<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use Guzzle\Http\Message\Request as GuzzleRequest;

use GuzzleHttp\Cookie\CookieJar;
use Silex\Application;

use Symfony\Component\HttpFoundation\Request;

class MessageManager
{
    private $app;

    public function __construct(Application $app = null)
    {
        if (null === $app) {
            throw new \Exception("MessageManager requires $app to be set");
        }
        $this->app = $app;
    }

    public function getConversationMessages($conversation_id)
    {
        $response = $this->fireRequest("GET", "/conversations/{$conversation_id}/messages");

        return $response;
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
            return $this->app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
    }
}
