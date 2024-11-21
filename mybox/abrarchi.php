<?php
    $archivo = $_GET['arch'];

    session_start();

    if ($_SESSION["autenticado"] != "SI") {
        header("Location: index.php");
        exit(); //fin del script
    }

    $ruta = getenv('HOME_PATH').'/'.$_SESSION["usuario"].'/'.$archivo;

    $file = fopen($ruta,"r");
    $contenido = fread($file, filesize($ruta));
    $mime = mime_content_type($ruta);

    if($mime == 'application/pdf'){
        header("Content-type: ". $mime);
        echo $contenido;
    }else{
        header("Content-Disposition: attachment; filename=".$archivo);
        header("Content-type: ". $mime);
        header("Content-length: ".filesize($ruta));
        readfile($ruta);
    }
?>
