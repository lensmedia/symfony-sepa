<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\DependencyInjection;

use Iban\Validation\Iban;
use Iban\Validation\Validator;
use InvalidArgumentException;
use Lens\Bundle\LensSepaBundle\Adapter\FilesystemAdapter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lens_sepa');
        $rootNode = $treeBuilder->getRootNode();

        $this->addSepaSection($rootNode);
        $this->addCreditorSection($rootNode);

        return $treeBuilder;
    }

    private function addSepaSection(NodeDefinition|ArrayNodeDefinition $rootNode): void
    {
        /** @noinspection NullPointerExceptionInspection */
        $rootNode->children()
            ->scalarNode('adapter')->defaultValue(FilesystemAdapter::class)->end()
            ->scalarNode('save_path')->defaultNull()->end()
            ->scalarNode('requested_collection_date')->isRequired()->end()
        ->end();
    }

    private function addCreditorSection(NodeDefinition|ArrayNodeDefinition $rootNode): void
    {
        /** @noinspection NullPointerExceptionInspection */
        $rootNode->children()
            ->arrayNode('creditor')
                ->isRequired()
                ->children()
                    ->scalarNode('id')->isRequired()->end()
                    ->scalarNode('name')->isRequired()->end()
                    ->scalarNode('iban')
                        ->isRequired()
                        ->validate()
                            ->always(function (string $value): string {
                                $validator = new Validator();
                                $iban = new Iban($value);

                                if (!$validator->validate($iban)) {
                                    throw new InvalidArgumentException(implode(
                                        ' ',
                                        $validator->getViolations(),
                                    ));
                                }

                                return $iban->format(Iban::FORMAT_ELECTRONIC);
                            })
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
