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

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
