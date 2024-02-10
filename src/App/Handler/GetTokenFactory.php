<?php

declare(strict_types=1);

namespace App\Handler;

use AmoCRM\OAuth2\Client\Provider\AmoCRM;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use AmoCRM\Client\AmoCRMApiClient;

class GetTokenFactory
{
    public function __invoke(ContainerInterface $container): GetToken
    {
        return new GetToken(
            $container->get(TemplateRendererInterface::class),
            new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['REDIRECT_URI'])
        );
    }
}
