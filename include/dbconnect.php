
<?php

const dbname = "pcfyemen_PCFsystem";
$dns = "mysql:host=localhost;dbname=" . dbname;
$user = "pcfyemen_PCFsystem";
$pass = ')k~3V~a]}sVp';
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
    $con = new PDO($dns, $user, $pass, $options);
    // $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // // Set SQL mode
    $con->exec("SET SESSION sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
} catch (PDOException $e) {
    echo $e->getMessage();
}
