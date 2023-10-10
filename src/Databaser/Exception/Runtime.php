<?php

namespace SFW\Databaser\Exception;

class Runtime extends \SFW\Exception\Runtime implements \SFW\Databaser\Exception
{
    use SqlStateTrait;
}
