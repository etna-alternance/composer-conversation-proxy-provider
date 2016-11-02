<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use GuzzleHttp\Cookie\CookieJar;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;

class DumbMethodsProxy implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        if (!isset($app["conversation_proxy"])) {
            throw new \Exception("The guzzle client conversation_proxy is not set");
        }

        $controllers = $app["controllers_factory"];

        //Dumb proxy for stats and search
        $controllers->get("/conversations/search", [$this, "conversationSearch"]);
        $controllers->get("/conversations/stats", [$this, "conversationStats"]);

        //Dumb proxy for messages
        $controllers->get("/conversations/{conversation}/messages/{message}", [$this, "loadMessage"]);
        $controllers->get("/conversations/{conversation}/messages", [$this, "loadConversationMessages"]);
        $controllers->post("/conversations/{conversation}/messages", [$this, "writeMessage"]);

        //Dumb proxy for views
        $controllers->get("/conversations/{conversation}/messages/{message}/views", [$this, "loadViews"]);
        $controllers->match("/conversations/{conversation}/messages/{message}/view", [$this, "writeMessageView"])
            ->method("POST|PUT");
        $controllers->match("/conversations/{conversation}/views", [$this, "writeConversationViews"])
            ->method("POST|PUT");

        //Dumb proxy for likes
        $controllers->get("/conversations/{conversation}/messages/{message}/likes", [$this, "loadLikes"]);
        $controllers->match("/conversations/{conversation}/messages/{message}/like", [$this, "addLike"])
            ->method("POST|PUT");
        $controllers->delete("/conversations/{conversation}/messages/{message}/like", [$this, "removeLike"]);

        return $controllers;
    }

    public function conversationSearch(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req, "/conversations");
    }

    public function conversationStats(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req, "/conversations");
    }

    public function loadMessage(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function loadConversationMessages(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function writeMessage(Application $app, Request $req)
    {
        if (null === $req->request->get("content", null)) {
            return $app->abort(400, "Missing content field for message creation");
        }
        return $this->fireRequest($app, $req);
    }

    public function loadViews(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function writeMessageView(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function writeConversationViews(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function loadLikes(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function addLike(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function removeLike(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function fireRequest(Application $app, Request $req, $remove_prefix = "")
    {
        $method = $req->getMethod();
        if (false === in_array($method, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
            return $app->abort(405, "ConversationProxy can not fire request of method : {$method}");
        }

        $path_info = $req->getPathInfo();
        $path_info = str_replace($remove_prefix, "", $path_info);
        $domain    = getenv("TRUSTED_DOMAIN");

        try {
            $jar      = CookieJar::fromArray(["authenticator" => $req->cookies->get("authenticator")], $domain);
            $response = $app["conversation_proxy"]->request(
                $method,
                "{$path_info}?{$req->getQueryString()}",
                [
                    "cookies" => $jar,
                    "json"    => $req->request->all()
                ]
            );

            $headers = array_filter(
                $response->getHeaders(),
                function ($value, $name) {
                    return 0 === preg_match("/^.*-encoding|connection(?:-.*)*?/i", $name);
                },
                ARRAY_FILTER_USE_BOTH
            );

            return $app->json(json_decode($response->getBody()), 200, $headers);
        } catch (\GuzzleHttp\Exception\RequestException $client_error) {
            return $app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
    }
}
