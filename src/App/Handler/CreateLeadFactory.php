<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use AmoCRM\Client\AmoCRMApiClient;
use App\Service\CustomFieldsService;

class CreateLeadFactory
{
    public function __invoke(ContainerInterface $container): CreateLead
    {
        return new CreateLead(
            $container->get(TemplateRendererInterface::class),
            new AmoCRMApiClient(
                $_ENV['CLIENT_ID'],
                $_ENV['CLIENT_SECRET'],
                $_ENV['REDIRECT_URI']
            ),
            new CustomFieldsService()
        );
    }
}
