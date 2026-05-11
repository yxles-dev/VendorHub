<?php
    $servername = "vendorhub-db.mysql.database.azure.com";
    $username = "vh_admin";
    $password = "Vendorhub-Pass";
    $dbname = "vendorhub";
    $port = 3306;

    $dsn = "mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4";

    $sslCaOption = defined('Pdo\\Mysql::ATTR_SSL_CA')
        ? constant('Pdo\\Mysql::ATTR_SSL_CA')
        : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : null);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($sslCaOption !== null) {
        $options[$sslCaOption] = dirname(__DIR__) . '/DigiCertGlobalRootG2.crt.pem';
    }

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Cannot Connect to the Database']);
        exit;
    }
?>