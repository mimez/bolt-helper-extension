<?php

namespace Bolt\Extension\MichaelMezger\Helper;

use Bolt\Extension\SimpleExtension;
use Bolt\Extension\MichaelMezger\Helper\Twig\Helper;

class HelperExtension extends SimpleExtension
{

    public function registerTwigFunctions()
    {
        $helper = new Helper($this->container);

        return [
            'filtercontent' => [[$helper, 'getContentIdsByFilter'], ['is_safe' => ['html']]],
            'implode' => [[$helper, 'implode'], ['is_safe' => ['html']]],
            'filegetcontents' => [[$helper, 'filegetcontents'], ['is_safe' => ['html']]],
            'activemenuidentifier' => [[$helper, 'getActiveMenuIdentifier'], ['is_safe' => ['html']]],
        ];
    }
}
