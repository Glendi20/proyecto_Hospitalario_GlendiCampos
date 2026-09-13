# Matriz de Trazabilidad — Requisito → Diagrama → Elemento

**Módulo:** Pacientes: registro, edición, búsqueda y detalle
**Proceso:** Registro, búsqueda y actualización segura de un paciente

| Req. | Requisito | Caso de Uso | Actividad | Secuencia |
|---|---|---|---|---|
| RF-01 | Registrar un paciente con datos válidos. | UC-01 Registrar Paciente | "Validar formato y campos obligatorios" / "Guardar nuevo paciente" | `registrarPaciente(datos)`; `validarDatos(datos)`; `guardar(nuevoPaciente)` |
| RF-02 | Impedir el registro de pacientes duplicados (mismo DPI). | UC-01 incluye UC-05 | "Verificar duplicidad por DPI" / Excepción E2 | `existeDuplicado(dpi)`; `yaExiste`; `mostrarError("DPI ya registrado")` |
| RF-03 | Buscar un paciente por criterio (DPI o nombre). | UC-02 Buscar Paciente | "Ingresar criterio de búsqueda" / "Buscar paciente en base de datos" | `ingresarCriterioBusqueda(...)`; `buscarPaciente(criterio)`; `findByCriterio(criterio)` |
| RF-04 | Mostrar el detalle del paciente encontrado. | UC-02 Buscar Paciente | "Mostrar detalle del paciente" | `mostrarDetalle(paciente)` |
| RF-05 | Permitir actualizar solo a usuarios con permiso autorizado (RBAC). | UC-03 incluye UC-04 | "Validar permiso de edición (RBAC)" / Excepción E3 | `validarPermiso(usuario, ...)`; `denegado`; `mostrarError("Acceso denegado")` |
| RF-06 | Validar los datos antes de guardar cualquier actualización. | UC-03 Actualizar Datos del Paciente | "Validar datos actualizados" / Excepción E1 | `validarDatos(cambios)`; `errores[]`; `mostrarErroresValidacion(errores)` |
| RF-07 | Registrar en bitácora cada alta, actualización o intento fallido. | UC-06 incluido por UC-01 y UC-03 | "Registrar evento..." (Auditoría) en las tres ramas | `registrarEvento(...)`; `registrarIntentoFallido(usuario, idPaciente)` |
| RF-08 | Informar al usuario el resultado de la operación (éxito o error). | UC-01, UC-02, UC-03 | "Mostrar confirmación..." / "Mostrar error..." | `mostrarConfirmacion()`; `mostrarConfirmacionRegistro()`; mensajes de error |

> Esta matriz también está incluida en la sección 2.5 de `docs/Documento_Final_GlendiCampos.docx`.
> Fuentes editables de los diagramas: `docs/diagramas/01_caso_de_uso.puml`, `02_actividad.puml`,
> `03_secuencia.puml`.
