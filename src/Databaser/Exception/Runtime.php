<?php

declare(strict_types=1);

namespace SFW\Databaser\Exception;

class Runtime extends \SFW\Exception\Runtime implements \SFW\Databaser\Exception
{
    use SqlStateTrait;
}
