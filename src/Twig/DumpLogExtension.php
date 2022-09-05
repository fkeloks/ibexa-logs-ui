<?php

namespace IbexaLogsUi\Bundle\Twig;

use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Twig\TwigFunction;

class DumpLogExtension extends WebProfilerExtension
{
    public function getName(): string
    {
        return 'dump_log';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dump_log_message', [$this, 'dumpLog'], [
                'is_safe' => ['html'],
                'needs_environment' => true
            ]),
            new TwigFunction('dump_log_context', [$this, 'dumpData'], [
                'is_safe' => ['html'],
                'needs_environment' => true
            ]),
        ];
    }
}
