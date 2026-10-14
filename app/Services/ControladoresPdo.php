<?php

namespace App\Services;

use PDO;

class ControladoresPdo
{
    public function disponibles(): array
    {
        return PDO::getAvailableDrivers();
    }
}
