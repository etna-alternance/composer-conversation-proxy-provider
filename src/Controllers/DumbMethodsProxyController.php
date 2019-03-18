<?php
namespace ETNA\ConversationProxy\Controllers;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class DumbMethodsProxyController extends AbstractController
{
    /**
     * @Route("/conversations/search", methods={"GET"}, name="conversation_search")
     */
    public function conversationSearch(Request $req)
    {
        return $this->fireRequest($req, "/conversations");
    }

    /**
     * @Route("/conversations/stats", methods={"GET"}, name="conversation_stats")
     */
    public function conversationStats(Request $req)
    {
        return $this->fireRequest($req, "/conversations");
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}", methods={"GET"}, name="load_message")
     */
    public function loadMessage(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages", methods={"GET"}, name="load_conversation_messages")
     */
    public function loadConversationMessages(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}/update_metas", methods={"PUT"}, name="update_message_metas")
     */
    public function updateMessageMetas(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages", methods={"POST", "OPTIONS"}, name="write_message")
     */
    public function writeMessage(Request $req)
    {
        if (null === $req->request->get("content", null)) {
            throw new HttpException(400, "Missing content field for message creation");
        }

        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}/views", methods={"GET"}, name="load_views")
     */
    public function loadViews(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}/likes", methods={"GET"}, name="load_likes")
     */
    public function loadLikes(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}/view", methods={"POST", "PUT", "OPTIONS"}, name="write_message_view")
     */
    public function writeMessageView(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/views", methods={"POST", "PUT", "OPTIONS"}, name="write_conversation_view")
     */
    public function writeConversationViews(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}/like", methods={"POST", "PUT", "OPTIONS"}, name="add_like")
     */
    public function addLike(Request $req)
    {
        return $this->fireRequest($req);
    }

    /**
     * @Route("/conversations/{conversation}/messages/{message}/like", methods={"POST", "PUT", "OPTIONS"}, name="remove_like")
     */
    public function removeLike(Request $req)
    {
        return $this->fireRequest($req);
    }

    private function fireRequest(Request $req, $remove_prefix = "")
    {
        $method = $req->getMethod();

        if (false === in_array($method, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
            throw new HttpException(405, "ConversationProxy can not fire request of method : {$method}");
        }

        $path_info = str_replace($remove_prefix, "", $req->getPathInfo());
        $domain    = getenv("TRUSTED_DOMAIN");
        $jar       = CookieJar::fromArray(["authenticator" => $req->cookies->get('authenticator')], $domain);
        $client    = new Client([
            "base_uri" => getenv("CONVERSATION_API_URL")
        ]);

        try {
            $response = $client->request($method, "{$path_info}?{$req->getQueryString()}",
                [
                    "cookies" => $jar,
                    "json"    => $req->request->all()
                ]
            );

            return $this->json(json_decode($response->getBody()->getContents(), true));
        } catch (\Exception $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }
    }
}
