<?php
namespace ETNA\ConversationProxy\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

Class ConversationUtils
{
    private $container;

    public function __construct(ContainerInterface $container = null)
    {
        if (null === $container) {
            throw new \Exception("ConversationUtils requires $container to be set");
        }

        $this->container = $container;
    }

    public function fireRequest(Request $req, $method, $uri, $body = [])
    {
        $method = strtoupper($method);

        if (false === in_array($method, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
            return $this->container->abort(405, "ConversationProxy can not fire request of method : {$method}");
        }

        $domain = getenv("TRUSTED_DOMAIN");
        $jar    = CookieJar::fromArray(["authenticator" => $req->cookies->get('authenticator')], $domain);

        $client = new Client([
            "base_uri" => getenv("CONVERSATION_API_URL")
        ]);

        try {
            $response = $client->request($method, $uri, [
                "cookies" => $jar,
                "json"    => $body
            ]);
        } catch (\Exception $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }

        return json_decode($response->getBody(), true);
    }
}
