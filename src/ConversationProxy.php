<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use GuzzleHttp\Client;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ConversationProxy implements ServiceProviderInterface
{
    private $controller_instance = null;

    public function __construct($controller_instance = null)
    {
        if (null === $controller_instance) {
            $controller_instance = new DumbMethodsProxy();
        }

        if (false === is_subclass_of($controller_instance, "ETNA\Silex\Provider\ConversationProxy\DumbMethodsProxy")) {
            throw new \Exception("Controller given to ConversationProxyProvider have to inherit from DumbMethodsProxy");
        }

        $this->controller_instance = $controller_instance;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app["conversation_proxy"] = function ($app) {
            $conversation_api_url = getenv("CONVERSATION_API_URL");
            if (false === $conversation_api_url) {
                throw new \Exception("ConversationProxyProvider needs env var CONVERSATION_API_URL");
            }
            if (false === getenv("TRUSTED_DOMAIN")) {
                throw new \Exception("ConversationProxyProvider needs env var TRUSTED_DOMAIN");
            }

            return new Client([
                "base_uri" => $conversation_api_url
            ]);
        };

        $app["conversations"] = function ($app) {
            return new ConversationManager($app);
        };

        $app->mount("/", $this->controller_instance);
    }
}
