<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\LensSepaBundle\Adapter\AdapterInterface;
use Lens\Bundle\LensSepaBundle\Adapter\FilesystemAdapter;
use Lens\Bundle\LensSepaBundle\Generator;
use Lens\Bundle\LensSepaBundle\Sepa;
use Symfony\Component\Filesystem\Filesystem;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(Sepa::class)
        ->args([
            service(AdapterInterface::class),
        ])

        ->set(Generator::class)
        ->args([
            param('lens_sepa.requested_collection_date'),
            param('lens_sepa.creditor.id'),
            param('lens_sepa.creditor.name'),
            param('lens_sepa.creditor.iban'),
        ])

        ->set(FilesystemAdapter::class)
        ->args([
            service(Filesystem::class),
            service(Generator::class),
            param('lens_sepa.save_path'),
            param('kernel.debug'),
        ])
        ->alias('lens_sepa.adapter.filesystem', FilesystemAdapter::class)
    ;
};
