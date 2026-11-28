<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Topwire\Fluid\View\TopwireViewFactory;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container): void {
    $container->addCompilerPass(new class implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            foreach ($container->findTaggedServiceIds('topwire.controller') as $id => $tags) {
                $container->findDefinition($id)
                    ->removeMethodCall('injectViewFactory')
                    ->addMethodCall('injectViewFactory', [new Reference(TopwireViewFactory::class)]);
            }
        }
    }, PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
};
