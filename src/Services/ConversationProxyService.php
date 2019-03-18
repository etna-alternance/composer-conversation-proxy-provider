<?php
/**
 * PHP version 7.1
 *
 * @author BLU <dev@etna-alternance.net>
 */

declare(strict_types=1);

namespace ETNA\ConversationProxy\Services;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Cette classe est surtout un modèle de service permettant d'ajouter la logique du bundle.
 * Il faut créer un service dans l'application qui extends cette classe et le spécifier dans la configuration.
 *
 * Cela nous permet de pouvoir override la fonction test comme on le souhaite
 *
 * @abstract
 */
class ConversationProxyService implements EventSubscriberInterface
{
    /**
     * Retourne la liste des différents events sur lesquels cette classe va intervenir
     * En l'occurence, avant d'accéder à une des fonction d'un des controlleurs.
     *
     * @return array<string,array<string|integer>>
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        );
    }
}
