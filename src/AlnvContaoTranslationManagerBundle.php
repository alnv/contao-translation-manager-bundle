<?php

namespace Alnv\ContaoTranslationManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AlnvContaoTranslationManagerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}