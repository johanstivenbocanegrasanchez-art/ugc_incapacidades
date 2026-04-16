<?php

declare(strict_types=1);

namespace Core;

use Config\Oracle;

abstract class Model
{
    protected Oracle $db;

    public function __construct()
    {
        $this->db = Oracle::getInstance();
    }
}
