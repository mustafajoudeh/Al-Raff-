<?php
declare(strict_types=1);

final class DataBase
{
    public static function Connect(): ?PDO
    {
        $Conn = require __DIR__ . "/Config.php";

        $dsn = "mysql:host={$Conn['ServerName']};dbname={$Conn['dbname']};charset=utf8mb4";
        try {
            echo 'Suuuc';
            return new PDO(
                $dsn,
                $Conn['UserName'],
                $Conn['Password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
            error_log($e->getMessage());
            return null;
        }
    }
}