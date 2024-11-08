<?php

    define("HOSTNAME", "localhost");
    define("USERNAME", "root");
    define("PASSWORD", "");
    define("DATABASE", "vidrioapleno");

    $conn = mysqli_connect(HOSTNAME,USERNAME,PASSWORD,DATABASE);

    if(!$conn){
        die("Conexion Fallida");
    }

?>