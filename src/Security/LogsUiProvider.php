<?php

namespace IbexaLogsUi\Bundle\Security;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

class LogsUiProvider extends YamlPolicyProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getFiles(): array
    {
        return [__DIR__ . '/../Resources/config/policies.yml'];
    }
}
