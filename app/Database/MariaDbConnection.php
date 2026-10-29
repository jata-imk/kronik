<?php

namespace App\Database;

class MariaDbConnection extends \Illuminate\Database\MariaDbConnection
{
    use Concerns\DetectsMariaDbConcurrencyErrors;
}
