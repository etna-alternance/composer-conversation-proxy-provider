<?php

namespace ETNA\ConversationProxy\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\Exception\HttpException;

use ETNA\ConversationProxy\Entities\Conversation;
use ETNA\ConversationProxy\Utils\ConversationUtils;

class ConversationManagerService
{
    private $container;
    private $utils;

    public function __construct(ContainerInterface $container = null, ConversationUtils $utils)
    {
        if (null === $container) {
            throw new \Exception("ConversationManagerService requires $container to be set");
        }

        $this->container = $container;
        $this->utils     = $utils;
    }

    public function findByQueryString(Request $req, $query, $from = 0, $size = 99999, $sort = "", $msg_query = "")
    {
        $query     = urlencode($query);
        $msg_query = urlencode($msg_query);
        $response  = $this->utils->fireRequest($req, "GET", "/search?q={$query}&messages_query={$msg_query}&from={$from}&size={$size}&sort={$sort}");

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

    public function findOneByQueryString(Request $req, $query)
    {
        $matching = $this->findByQueryString($req, $query, 0, 1);
        if (0 === count($matching["hits"])) {
            return null;
        }
        return $matching["hits"][0];
    }

    public function findUnreadByQueryString(Request $req, $query, $from = 0, $size = 99999)
    {
        $query  = urlencode($query);
        $unread = $this->utils->fireRequest($req, "GET", "/fetch_unread?q={$query}&from={$from}&size={$size}");

        return $unread;
    }

    public function findStatsByQueryString(Request $req, $query, $from = 0, $size = 99999)
    {
        $query = urlencode($query);
        $stats = $this->utils->fireRequest($req, "GET", "/stats?q={$query}&from={$from}&size={$size}");


        return $stats;
    }

    public function findAggsByQueryString(Request $req, $query, $aggs, $from = 0, $size = 0)
    {
        $query = urlencode($query);
        $aggs  = $this->utils->fireRequest($req, "POST", "/aggs?q={$query}&from={$from}&size={$size}", $aggs);

        return $aggs;
    }

    public function save(Request $req, Conversation $conversation)
    {
        $actions  = $conversation->getSaveActions();
        $response = null;

        if ($actions === [["method" => "post", "route" => "/conversations"]]) {
            $body = $conversation->toArray();
            if (false === isset($body["messages"][0])) {
                throw new HttpException(400, "Need content to create conversation");
            }

            $body["metas"] = json_encode($body["metas"]);
            $response      = $this->utils->fireRequest($req, "POST", "/conversations", $body);
        } else {
            foreach ($actions as $action) {
                $route  = $action["route"];
                $method = $action["method"];
                unset($action["route"]);
                unset($action["method"]);

                $response = $this->utils->fireRequest($req, $method, $route, $action);
            }
        }
        return $response;
    }
}
