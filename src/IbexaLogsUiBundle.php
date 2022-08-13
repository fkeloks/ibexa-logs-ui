<?php

namespace IbexaLogsUi\Bundle;

use IbexaLogsUi\Bundle\DependencyInjection\IbexaLogsUiExtension;
use IbexaLogsUi\Bundle\Security\LogsUiProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class IbexaLogsUiBundle extends Bundle
{
    protected $name = 'IbexaLogsUiBundle';

    public function getContainerExtension(): IbexaLogsUiExtension
    {
        return new IbexaLogsUiExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $eZExtension = $container->getExtension('ezpublish');
        $eZExtension->addPolicyProvider(new LogsUiProvider);
    }
}
