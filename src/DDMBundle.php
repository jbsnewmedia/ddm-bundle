<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle;

use JBSNewMedia\DDMBundle\DependencyInjection\DDMExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DDMBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new DDMExtension();
        }

        if (false === $this->extension) {
            return null;
        }

        return $this->extension;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
