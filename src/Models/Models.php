<?php

namespace Models;

use Config\Database;
use PDO;

class Models
{
    protected PDO $conn;

    public function __construct(private Database $db = new Database())
    {
        $this->conn = $db->get_connect();
    }
}
