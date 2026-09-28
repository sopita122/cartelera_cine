<?php
// host significa "servidor de base de datos" y que este igualado a localhost dice que actua con el servidor de XAMPP.
$host = "localhost";
// entre "" despues del igual significa el nombre EXACTO de la base de datos a la que queremos conectar.
$namedatabase = "cine";
// username significa "usuario de base de datos" y que este igualado a root dice que actua con el usuario root de XAMPP.
$username = "root";
// password significa "contraseña de base de datos" y que este igualado a "" dice que actua con la contraseña vacia de XAMPP.
$password = "";
// try significa intentar y catch significa capturar, es decir, intenta conectar a la base de datos y si no puede capturara el error
try{
    // PDO significa "PHP Data Objects" y es una clase de PHP que permite conectarse a diferentes tipos de bases de datos, en este caso MySQL. Tenemos que poner los datos de antes para asegurar que todo coincida.
    $conexion = new PDO("mysql:host=$host;dbname=$namedatabase;charset=utf8", $username, $password);
    // setAttribute significa "establecer atributo" y PDO::ATTR_ERRMODE significa "modo de error de PDO" y PDO::ERRMODE_EXCEPTION significa "modo de excepción de error", es decir, si hay un error en la conexión a la base de datos, se lanzará una excepción y se mostrará el mensaje de error.
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo significa "imprimir" y "Conexión exitosa a la base de datos" es el mensaje que se mostrará si la conexión a la base de datos es exitosa.
    echo "Conexión exitosa a la base de datos";
  // catch significa capturar y PDOException significa "excepción de PDO", es decir, si hay un error en la conexión a la base de datos, se capturará la excepción y se mostrará el mensaje de error.
} catch (PDOException $expresion) {
    echo "Error de conexión: " . $expresion->getMessage();
}
?>