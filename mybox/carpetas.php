<?php
session_start();

if ($_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit(); //fin del script
}

// Directorio actual
if (isset($_GET['path'])) {
    $ruta = getenv('HOME_PATH') . '/' . $_SESSION["usuario"] . '/' . $_GET['path'];
} else {
    $ruta = getenv('HOME_PATH') . '/' . $_SESSION["usuario"];
}

// Verifica que la ruta sea válida y segura
if (!file_exists($ruta) || strpos(realpath($ruta), getenv('HOME_PATH') . '/' . $_SESSION["usuario"]) !== 0) {
    echo "<script>alert('Ruta no válida');</script>";
    $ruta = getenv('HOME_PATH') . '/' . $_SESSION["usuario"];
}

// Función para eliminar carpetas de manera recursiva
function eliminarCarpeta($carpeta) {
    if (is_dir($carpeta)) {
        $items = array_diff(scandir($carpeta), ['.', '..']);
        foreach ($items as $item) {
            $rutaItem = $carpeta . '/' . $item;
            is_dir($rutaItem) ? eliminarCarpeta($rutaItem) : unlink($rutaItem);
        }
        return rmdir($carpeta);
    }
    return false;
}

// Función para crear una nueva carpeta
if (isset($_POST["nombreCarpeta"])) {
    $nombreCarpeta = trim($_POST["nombreCarpeta"]);
    $nuevaRuta = $ruta . '/' . $nombreCarpeta;

    if (!file_exists($nuevaRuta)) {
        if (mkdir($nuevaRuta, 0700)) {
            echo "<script>alert('Carpeta creada exitosamente.');</script>";
        } else {
            echo "<script>alert('Error al crear la carpeta.');</script>";
        }
    } else {
        echo "<script>alert('La carpeta ya existe.');</script>";
    }
}

// Función para subir un archivo
if (isset($_FILES['archivo'])) {
    $nombreArchivo = $_FILES['archivo']['name'];
    $rutaArchivo = $ruta . '/' . basename($nombreArchivo);

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaArchivo)) {
        echo "<script>alert('Archivo subido exitosamente.');</script>";
    } else {
        echo "<script>alert('Error al subir el archivo.');</script>";
    }
}

// Función para mover un archivo a una carpeta
if (isset($_POST['archivo']) && isset($_POST['destino'])) {
    $archivo = $_POST['archivo'];
    $destino = $_POST['destino'];
    
    $rutaArchivo = $ruta . '/' . $archivo;
    $rutaDestino = $ruta . '/' . $destino . '/' . $archivo;

    if (file_exists($rutaArchivo) && is_dir($ruta . '/' . $destino)) {
        if (rename($rutaArchivo, $rutaDestino)) {
            echo "<script>alert('Archivo movido exitosamente.');</script>";
        } else {
            echo "<script>alert('Error al mover el archivo.');</script>";
        }
    } else {
        echo "<script>alert('El archivo o la carpeta de destino no existen.');</script>";
    }
}

// Función para borrar un archivo o una carpeta
if (isset($_GET['borrar'])) {
    $elementoABorrar = $ruta . '/' . $_GET['borrar'];

    if (is_file($elementoABorrar)) {
        if (unlink($elementoABorrar)) {
            echo "<script>alert('Archivo borrado exitosamente.');</script>";
        } else {
            echo "<script>alert('Error al borrar el archivo.');</script>";
        }
    } elseif (is_dir($elementoABorrar)) {
        if (eliminarCarpeta($elementoABorrar)) {
            echo "<script>alert('Carpeta borrada exitosamente.');</script>";
        } else {
            echo "<script>alert('Error al borrar la carpeta.');</script>";
        }
    } else {
        echo "<script>alert('El elemento no existe o no es válido.');</script>";
    }
}

// Obtener carpetas disponibles
$carpetas = array_filter(glob($ruta . '/*'), 'is_dir');
?>
<!doctype html>
<html lang="en">
<head>
    <?php
    include_once('sections/head.inc');
    ?>
    <title>Ingreso al Sitio</title>
</head>
<body class="container-fluid">
<header class="row">
    <div class="row">
        <?php include_once('sections/header.inc'); ?>
    </div>
</header>
<main class="row">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>Mi Cajón de Archivos</strong>
        </div>
        <div class="panel-body">

            <!-- FORMULARIO PARA CREAR UNA NUEVA CARPETA -->
            <form method="post" action="">
                <fieldset>
                    <legend><strong>Crear Carpeta</strong></legend>
                    <label for="nombreCarpeta">Nombre de la carpeta:</label>
                    <input type="text" id="nombreCarpeta" name="nombreCarpeta" placeholder="Nombre de la carpeta" required>
                    <button type="submit">Crear</button>
                </fieldset>
            </form>
            <br><br>

            <!-- FORMULARIO PARA SUBIR ARCHIVOS -->
            <form method="post" action="" enctype="multipart/form-data">
                <fieldset>
                    <legend><strong>Subir Archivo</strong></legend>
                    <label for="archivo">Seleccionar archivo:</label>
                    <input type="file" id="archivo" name="archivo" required>
                    <button type="submit">Subir</button>
                </fieldset>
            </form>
            <br><br>

            <!-- FORMULARIO PARA MOVER ARCHIVOS -->
            <form method="post" action="">
                <fieldset>
                    <legend><strong>Mover Archivo</strong></legend>
                    <label for="archivo">Archivo:</label>
                    <select id="archivo" name="archivo" required>
                        <?php
                        // Listar archivos
                        $archivos = array_filter(glob($ruta . '/*'), 'is_file');
                        foreach ($archivos as $archivo) {
                            echo '<option value="' . basename($archivo) . '">' . basename($archivo) . '</option>';
                        }
                        ?>
                    </select>

                    <label for="destino">Mover a carpeta:</label>
                    <select id="destino" name="destino" required>
                        <?php
                        // Listar carpetas
                        foreach ($carpetas as $carpeta) {
                            echo '<option value="' . basename($carpeta) . '">' . basename($carpeta) . '</option>';
                        }
                        ?>
                    </select>

                    <button type="submit">Mover</button>
                </fieldset>
            </form>
            <br><br>

            <!-- LISTADO DE ARCHIVOS Y CARPETAS -->
            <a href="carpetas.php">Volver al directorio raíz</a><br><br>
            <?php
            $conta = 0;
            $directorio = opendir($ruta);
            echo '<table class="table table-striped">';
            echo '<tr>';
            echo '<th>Nombre</th>';
            echo '<th>Tamaño</th>';
            echo '<th>Último acceso</th>';
            echo '<th>Archivo</th>';
            echo '<th>Directorio</th>';
            echo '<th>Lectura</th>';
            echo '<th>Escritura</th>';
            echo '<th>Ejecutable</th>';
            echo '<th>Acción</th>';
            echo '</tr>';
            while ($elem = readdir($directorio)) {
                if (($elem != '.') and ($elem != '..')) {
                    $isDir = is_dir($ruta . '/' . $elem);
                    echo '<tr>';
                    if ($isDir) {
                        echo '<th><a href=carpetas.php?path=' . urlencode((isset($_GET['path']) ? $_GET['path'] . '/' : '') . $elem) . '>' . $elem . '</a></th>';
                    } else {
                        echo '<th>' . $elem . '</th>';
                    }
                    echo '<th>' . (!$isDir ? filesize($ruta . '/' . $elem) . ' bytes' : '-') . '</th>';
                    echo '<th>' . (!$isDir ? date("d/m/y h:i:s", fileatime($ruta . '/' . $elem)) : '-') . '</th>';
                    echo '<th>' . !$isDir . '</th>';
                    echo '<th>' . $isDir . '</th>';
                    echo '<th>' . is_readable($ruta . '/' . $elem) . '</th>';
                    echo '<th>' . is_writable($ruta . '/' . $elem) . '</th>';
                    echo '<th>' . is_executable($ruta . '/' . $elem) . '</th>';
                    echo '<th><a href=carpetas.php?path=' . (isset($_GET['path']) ? $_GET['path'] . '&' : '') . 'borrar=' . urlencode($elem) . '>Borrar</a></th>';
                    echo '</tr>';
                    $conta++;
                }
            }
            echo '</table>';
            echo '<br><br>';
            closedir($directorio);
            if ($conta == 0)
                echo 'La carpeta del usuario se encuentra vacía';
            ?>
        </div>
    </div>
</main>
<footer class="row">
    <?php include_once('sections/foot.inc'); ?>
</footer>
</body>
</html>
