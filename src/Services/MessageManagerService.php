<?php
namespace ETNA\ConversationProxy\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ETNA\ConversationProxy\Utils\ConversationUtils;

class MessageManagerService
{
    private $container;
    private $utils;

    public function __construct(ContainerInterface $container = null, ConversationUtils $utils)
    {
        if (null === $container) {
            throw new \Exception("MessageManagerService requires $container to be set");
        }

        $this->container = $container;
        $this->utils     = $utils;
    }

    public function getConversationMessages(Request $req, $conversation_id)
    {
        $response = $this->utils->fireRequest($req, "GET", "/conversations/{$conversation_id}/messages");

        return $response;
    }

    public function searchMessages(Request $req, $query, $from = 0, $size = 10, $sort = "")
    {
        $query    = urlencode($query);
        $response = $this->utils->fireRequest($req, "GET", "/search_messages?q={$query}&from={$from}&size={$size}&sort={$sort}");

        return $response;
    }

    public function updateMessageMetas(Request $req, $conversation_id, $message_id, $metas)
    {
        $response = $this->utils->fireRequest($req, "PUT", "/conversations/{$conversation_id}/messages/{$message_id}/update_metas", $metas);

        return $response;
    }
}
