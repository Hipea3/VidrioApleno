<?php
// Conexión a la base de datos
include('C:\xampp\htdocs\VidrioApleno\db.php');
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Inicializar mensajes de éxito y error
$success_message = "";
$error_message = "";

// Obtener tipos de vidrios
$sql_tipos = "SELECT * FROM tiposdevidrios";
$result_tipos = $conn->query($sql_tipos);

// Verificar si hay resultados
if (!$result_tipos) {
    echo "Error en la consulta: " . $conn->error;
}

// Comprobar si se ha enviado un formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

// Comprobar si la clave "action" existe en $_POST
    if (isset($_POST['action'])) {
        // Agregar tipo de vidrio
        if ($_POST['action'] == 'add_tipo_vidrio') {
            $tipoVidrio = $_POST['TiposDeVidrios'];
            
            // Verificar si ya existe
            $check_sql = "SELECT * FROM tiposdevidrios WHERE TiposDeVidrios = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param('s', $tipoVidrio);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Este tipo de vidrio ya existe.";
            } else {
                $sql = "INSERT INTO tiposdevidrios (TiposDeVidrios) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $tipoVidrio);
                if ($stmt->execute()) {
                    $success_message = "Tipo de vidrio agregado correctamente.";
                } else {
                    $error_message = "Error al agregar el tipo de vidrio.";
                }
            }
        }

        // Editar tipo de vidrio
        elseif ($_POST['action'] == 'edit_tipo_vidrio') {
            $idTipoVidrio = $_POST['idTiposDeVidrios'];
            $tipoVidrio = $_POST['TiposDeVidrios'];
            
            $sql = "UPDATE tiposdevidrios SET TiposDeVidrios = ? WHERE idTiposDeVidrios = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $tipoVidrio, $idTipoVidrio);
            if ($stmt->execute()) {
                $success_message = "Tipo de vidrio actualizado correctamente.";
            } else {
                $error_message = "Error al actualizar el tipo de vidrio.";
            }
        }

        // Eliminar tipo de vidrio
        elseif ($_POST['action'] == 'delete_tipo_vidrio') {
            $idTipoVidrio = $_POST['idTiposDeVidrios'];
            
            // Verificar si hay vidrios asociados
            $check_sql = "SELECT COUNT(*) as count FROM vidrios WHERE idTiposdevidrio = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param('i', $idTipoVidrio);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "No se puede eliminar este tipo de vidrio porque hay vidrios asociados.";
            } else {
                $sql = "DELETE FROM tiposdevidrios WHERE idTiposDeVidrios = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $idTipoVidrio);
                if ($stmt->execute()) {
                    $success_message = "Tipo de vidrio eliminado correctamente.";
                } else {
                    $error_message = "Error al eliminar el tipo de vidrio.";
                }
            }
        }

        // Agregar vidrio
        elseif ($_POST['action'] == 'add_vidrio') {
            $idTiposdevidrio = $_POST['idTiposdevidrio'];
            $cantidad = $_POST['cantidad'];
            $fechaIngreso = $_POST['fechaIngreso'];

            if (!validarCantidad($cantidad)) {
                $error_message = "La cantidad debe ser un número positivo.";
                return;
            }

            if (!validarFecha($fechaIngreso)) {
                $error_message = "La fecha no puede ser futura.";
                return;
            }

            // No es necesario verificar si existe el mismo tipo, ya que puede haber múltiples registros del mismo tipo
            $sql = "INSERT INTO vidrios (idTiposdevidrio, cantidad, fechaIngreso) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $idTiposdevidrio, $cantidad, $fechaIngreso);
            if ($stmt->execute()) {
                $success_message = "Vidrio añadido correctamente.";
            } else {
                $error_message = "Error al añadir el vidrio.";
            }
        }

        // Editar vidrio
        elseif ($_POST['action'] == 'edit_vidrio') {
            $idVidrios = $_POST['idVidrios'];
            $idTiposdevidrio = $_POST['idTiposdevidrio'];
            $cantidad = $_POST['cantidad'];
            $fechaIngreso = $_POST['fechaIngreso'];

            $sql = "UPDATE vidrios SET idTiposdevidrio = ?, cantidad = ?, fechaIngreso = ? WHERE idVidrios = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issi', $idTiposdevidrio, $cantidad, $fechaIngreso, $idVidrios);
            if ($stmt->execute()) {
                $success_message = "Vidrio actualizado correctamente.";
            } else {
                $error_message = "Error al actualizar el vidrio.";
            }
        }

        // Eliminar vidrio
        elseif ($_POST['action'] == 'delete_vidrio') {
            $idVidrios = $_POST['idVidrios'];
            $sql = "DELETE FROM vidrios WHERE idVidrios = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idVidrios);
            if ($stmt->execute()) {
                $success_message = "Vidrio eliminado correctamente.";
            } else {
                $error_message = "Error al eliminar el vidrio.";
            }
        }

        // Eliminar tipo de vidrio
        elseif ($_POST['action'] == 'delete_tipo_vidrio') {
            $idTiposDeVidrios = $_POST['idTiposDeVidrios'];
            
            $conn->begin_transaction();
            try {
                // Primero verificar si hay vidrios asociados
                $check_sql = "SELECT COUNT(*) as count FROM vidrios WHERE idTiposdevidrio = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param('i', $idTiposDeVidrios);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];

                if ($count > 0) {
                    throw new Exception("No se puede eliminar el tipo de vidrio porque hay vidrios asociados.");
                }

                // Si no hay vidrios asociados, proceder con la eliminación
                $sql = "DELETE FROM tiposdevidrios WHERE idTiposDeVidrios = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $idTiposDeVidrios);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al eliminar el tipo de vidrio.");
                }

                $conn->commit();
                $success_message = "Tipo de vidrio eliminado correctamente.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    } else {
        $error_message = "No se ha especificado ninguna acción.";
    }
}

// Obtener vidrios de la base de datos
$sql = "SELECT v.*, t.TiposDeVidrios FROM vidrios v INNER JOIN tiposdevidrios t ON v.idTiposdevidrio = t.idTiposDeVidrios";
$result_vidrios = $conn->query($sql);

// Verificar que la consulta se haya ejecutado correctamente
if (!$result_tipos) {
    die("Error al obtener los tipos de vidrio: " . $conn->error);
} else {
    if ($result_tipos->num_rows > 0) {
        // Hay tipos de vidrios disponibles
    } else {
        // No hay tipos de vidrios disponibles
        echo "No hay tipos de vidrios disponibles.";
    }
}

// Agregar este código después de la primera consulta
$tipos_array = [];
while ($row = $result_tipos->fetch_assoc()) {
    $tipos_array[] = $row;
}

// Actualizar la lista de tipos de vidrio
$sql_tipos = "SELECT * FROM tiposdevidrios ORDER BY idTiposDeVidrios";
$result_tipos = $conn->query($sql_tipos);
$tipos_array = [];
if ($result_tipos) {
    while ($row = $result_tipos->fetch_assoc()) {
        $tipos_array[] = $row;
    }
}

// Para debug temporal
echo "<!-- Número de tipos encontrados: " . count($tipos_array) . " -->";

// Verificación de la consulta SQL
$sql_tipos = "SELECT * FROM tiposdevidrios";
$result_tipos = $conn->query($sql_tipos);
if (!$result_tipos) {
    die("Error en la consulta: " . $conn->error);
}
echo "<!-- Consulta ejecutada correctamente -->";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vidrio A Pleno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Bienvenido a Vidrio A Pleno</h1>
        <p class="text-center">Gestiona tus tipos de vidrios y más.</p>

        <!-- Mensajes de éxito o error -->
        <?php if (!empty($success_message)): ?>
            <div id="success-message" class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div id="error-message" class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Botones dinámicos -->
        <div class="text-center mb-4">
            <button id="verVidriosBtn" class="btn btn-primary mx-2">Ver Vidrios</button>
            <button id="gestionarVidriosBtn" class="btn btn-warning mx-2">Gestionar Vidrios</button>
            <button id="gestionarTiposVidriosBtn" class="btn btn-success mx-2">Gestionar Tipos de Vidrios</button>
        </div>

        <!-- Sección Ver Vidrios -->
        <div id="verVidrios" style="display:none;">
            <h2 class="text-center my-4">Listado de Vidrios</h2>
            <?php if ($result_vidrios->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID Vidrio</th>
                            <th>Tipo de Vidrio</th>
                            <th>Cantidad</th>
                            <th>Fecha de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rowVidrios = $result_vidrios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $rowVidrios['idVidrios']; ?></td>
                            <td><?php echo htmlspecialchars($rowVidrios['TiposDeVidrios'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $rowVidrios['cantidad']; ?></td>
                            <td><?php echo $rowVidrios['fechaIngreso']; ?></td>
                            <td>
                                <!-- Botón para editar vidrio -->
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $rowVidrios['idVidrios']; ?>">Editar</button>
                                <!-- Modal para editar vidrio -->
                                <div class="modal fade" id="editModal-<?php echo $rowVidrios['idVidrios']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar Vidrio</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <input type="hidden" name="action" value="edit_vidrio">
                                                <input type="hidden" name="idVidrios" value="<?php echo $rowVidrios['idVidrios']; ?>">
                                                <div class="modal-body">
                                                    <div class="form-group mb-3">
                                                        <label for="idTiposdevidrio">Tipo de Vidrio</label>
                                                        <select name="idTiposdevidrio" class="form-control" required>
                                                            <?php foreach ($tipos_array as $tipo): ?>
                                                                <option value="<?php echo $tipo['idTiposDeVidrios']; ?>" 
                                                                        <?php echo ($rowVidrios['idTiposdevidrio'] == $tipo['idTiposDeVidrios']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($tipo['TiposDeVidrios']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label for="cantidad">Cantidad</label>
                                                        <input type="text" name="cantidad" class="form-control" value="<?php echo $rowVidrios['cantidad']; ?>" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label for="fechaIngreso">Fecha de Ingreso</label>
                                                        <input type="date" name="fechaIngreso" class="form-control" value="<?php echo $rowVidrios['fechaIngreso']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    <button type="submit" class="btn btn-primary">Actualizar Vidrio</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- Botón para eliminar vidrio -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="delete_vidrio">
                                    <input type="hidden" name="idVidrios" value="<?php echo $rowVidrios['idVidrios']; ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay vidrios registrados.</p>
            <?php endif; ?>
        </div>

            <!-- Sección Gestionar Vidrios -->
            <div id="gestionarVidrios" style="display:none;">
                <h2 class="text-center my-4">Gestionar Vidrios</h2>
                <form method="post" class="mb-4">
                    <input type="hidden" name="action" value="add_vidrio">
                    <div class="form-group mb-3">
                        <label for="idTiposdevidrio">Tipo de Vidrio</label>
                        <select name="idTiposdevidrio" class="form-control" required>
                            <?php foreach ($tipos_array as $tipo): ?>
                                <option value="<?php echo $tipo['idTiposDeVidrios']; ?>">
                                    <?php echo htmlspecialchars($tipo['TiposDeVidrios']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" required min="1">
                    </div>
                    <div class="form-group mb-3">
                        <label for="fechaIngreso">Fecha de Ingreso</label>
                        <input type="date" name="fechaIngreso" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Añadir Vidrio</button>
                </form>
            </div>

            <!-- Sección Ver Tipos de Vidrios -->
            <div id="verTiposVidrios" style="display:none;">
                <h2 class="text-center my-4">Listado de Tipos de Vidrios</h2>
                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addTipoModal">Agregar Tipo de Vidrio</button>
                
                <div class="modal fade" id="addTipoModal" tabindex="-1" aria-labelledby="addTipoModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Agregar Nuevo Tipo de Vidrio</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <input type="hidden" name="action" value="add_tipo_vidrio">
                                <div class="modal-body">
                                    <div class="form-group mb-3">
                                        <label for="TiposDeVidrios">Nombre del Tipo de Vidrio</label>
                                        <input type="text" name="TiposDeVidrios" class="form-control" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="submit" class="btn btn-primary">Agregar Tipo de Vidrio</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tabla de tipos de vidrios -->
                <?php if ($result_tipos->num_rows > 0): ?>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>ID Tipo de Vidrio</th>
                                <th>Tipo de Vidrio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rowTipos = $result_tipos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rowTipos['idTiposDeVidrios']); ?></td>
                                    <td><?php echo htmlspecialchars($rowTipos['TiposDeVidrios']); ?></td>
                                    <td>
                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editTipoModal-<?php echo $rowTipos['idTiposDeVidrios']; ?>">Editar</button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="delete_tipo_vidrio">
                                            <input type="hidden" name="idTiposDeVidrios" value="<?php echo $rowTipos['idTiposDeVidrios']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este tipo de vidrio?');">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay tipos de vidrios registrados.</p>
                <?php endif; ?>
            </div>

            <!-- Sección Gestionar Tipos de Vidrios -->
            <div id="verTiposVidrios" style="display:none;">
                <h2 class="text-center my-4">Gestionar Tipos de Vidrios</h2>
                
                <!-- Botón para agregar nuevo tipo -->
                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addTipoModal">
                    Agregar Tipo de Vidrio
                </button>

                <!-- Tabla de tipos de vidrios -->
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo de Vidrio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tipos_array as $tipo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tipo['idTiposDeVidrios']); ?></td>
                            <td><?php echo htmlspecialchars($tipo['TiposDeVidrios']); ?></td>
                            <td>
                                <!-- Botón Editar -->
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $tipo['idTiposDeVidrios']; ?>">
                                    Editar
                                </button>
                                
                                <!-- Modal -->
                                <div class="modal fade" id="editModal<?php echo $tipo['idTiposDeVidrios']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel">Editar Tipo de Vidrio</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit_tipo_vidrio">
                                                    <input type="hidden" name="idTiposDeVidrios" value="<?php echo $tipo['idTiposDeVidrios']; ?>">
                                                    <div class="mb-3">
                                                        <label for="TiposDeVidrios" class="form-label">Nombre del Tipo de Vidrio</label>
                                                        <input type="text" class="form-control" name="TiposDeVidrios" 
                                                               value="<?php echo htmlspecialchars($tipo['TiposDeVidrios']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botón Eliminar -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="delete_tipo_vidrio">
                                    <input type="hidden" name="idTiposDeVidrios" value="<?php echo $tipo['idTiposDeVidrios']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este tipo de vidrio?');">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Modal para agregar nuevo tipo -->
                <div class="modal fade" id="addTipoModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Agregar Nuevo Tipo de Vidrio</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="add_tipo_vidrio">
                                    <div class="form-group">
                                        <label for="TiposDeVidrios">Nombre del Tipo de Vidrio</label>
                                        <input type="text" class="form-control" name="TiposDeVidrios" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="submit" class="btn btn-primary">Agregar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
    // Función para ocultar todas las secciones
    function hideAllSections() {
        $('#verVidrios').hide();
        $('#gestionarVidrios').hide();
        $('#verTiposVidrios').hide();
    }

    // Manejador para el botón de ver vidrios
    $('#verVidriosBtn').click(function() {
        hideAllSections();
        $('#verVidrios').show();
    });

    // Manejador para el botón de gestionar vidrios
    $('#gestionarVidriosBtn').click(function() {
        hideAllSections();
        $('#gestionarVidrios').show();
    });

    // Manejador para el botón de gestionar tipos de vidrios
    $('#gestionarTiposVidriosBtn').click(function() {
        hideAllSections();
        $('#verTiposVidrios').show();
    });

    // Ocultar mensajes después de 3 segundos
    setTimeout(function() {
        $('#success-message').fadeOut('slow');
        $('#error-message').fadeOut('slow');
    }, 3000);

    // Agregar confirmación para eliminación
    $('.btn-danger').click(function(e) {
        if (!confirm('¿Está seguro de que desea eliminar este registro?')) {
            e.preventDefault();
        }
    });
});

            // Ocultar mensajes después de 3 segundos
            setTimeout(function() {
                $('#success-message').fadeOut('slow');
            }, 3000);
            setTimeout(function() {
                $('#error-message').fadeOut('slow');
            }, 3000);
            
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Para debug de los modales
        const editButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Modal target:', this.getAttribute('data-bs-target'));
            });
        });
    });
    </script>
</body>
</html>
