<?php

declare(strict_types=1);

namespace Lens\Bundle\LensSepaBundle\DependencyInjection;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Lens\Bundle\LensSepaBundle\Adapter\AdapterInterface;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LensSepaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $config = $container->resolveEnvPlaceholders($config, true);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $this->validateAndSetRequestedCollectionDate($container, $config);

        $container->setParameter(
            'lens_sepa.save_path',
            $config['save_path'] ?? $container->getParameter('kernel.project_dir').'/var/sepa',
        );

        $container->setParameter('lens_sepa.creditor.id', $config['creditor']['id']);
        $container->setParameter('lens_sepa.creditor.name', $config['creditor']['name']);
        $container->setParameter('lens_sepa.creditor.iban', $config['creditor']['iban']);

        $container->setAlias(AdapterInterface::class, $config['adapter']);
    }

    private function validateAndSetRequestedCollectionDate(ContainerBuilder $container, array $config): void
    {
        $requestedCollectionDate = $config['requested_collection_date'];
        try {
            if (!$requestedCollectionDate) {
                throw new RuntimeException();
            }

            // Testing the format (exception on invalid).
            new DateTimeImmutable($requestedCollectionDate);

            $container->setParameter('lens_sepa.requested_collection_date', $requestedCollectionDate);
        } catch (Exception) {
            throw new RuntimeException(sprintf(
                'Due date is missing or not a valid "%s" format.',
                DateTimeInterface::class,
            ));
        }
    }
}
