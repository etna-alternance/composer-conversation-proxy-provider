<?php
/**
 * PHP version 7.1
 *
 * @author BLU <dev@etna-alternance.net>
 */

declare(strict_types=1);

namespace ETNA\ConversationProxy\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tree_builder = new TreeBuilder();
        $root_node    = $tree_builder->root('conversation');

        $root_node
            ->children()
                ->scalarNode('conversation_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('api_path')
                    ->defaultValue('^/?')
                ->end()
                ->scalarNode('cookie_expiration')
                    ->defaultFalse()
                ->end()
            ->end();

        return $tree_builder;
    }
}

