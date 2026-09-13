# Semana 2 — Proceso de diseño, RF/RNF y principio LSP

**Estudiante:** Glendi Patricia Campos Orellana (`Glendi20`)
**Módulo:** Pacientes: registro, edición, búsqueda y detalle
**Rama sugerida:** `feature/week-02-diseno` (desde `developer`)

**Consigna individual:** «Aplique LSP al diseño del flujo "registro, búsqueda y actualización segura de un
paciente". Defina RF/RNF y criterios de aceptación; muestre el diseño antes/después, justifique
responsabilidades y dependencias y aporte evidencia verificable.»

## 1. Requisitos Funcionales (RF)

| ID | Requisito funcional | Criterio de aceptación |
|---|---|---|
| RF-01 | El sistema debe permitir **registrar** un nuevo paciente capturando datos obligatorios (nombre completo, DPI, fecha de nacimiento, sexo, teléfono, dirección, tenant). | **Dado** un usuario con rol autorizado, **cuando** envía el formulario con todos los campos obligatorios válidos, **entonces** el sistema crea el paciente y responde con su identificador único (HTTP 201). |
| RF-02 | El sistema debe impedir el registro de un paciente cuyo DPI ya exista en el mismo tenant. | **Dado** un DPI ya registrado en el tenant, **cuando** se intenta registrar un paciente con ese mismo DPI, **entonces** el sistema rechaza la operación (HTTP 409) e informa "paciente duplicado". |
| RF-03 | El sistema debe permitir **buscar** pacientes por DPI, nombre parcial o número de expediente. | **Dado** un criterio de búsqueda válido, **cuando** el usuario ejecuta la búsqueda, **entonces** el sistema retorna la lista de coincidencias en menos de 2 segundos (ver RNF-01) o una lista vacía si no hay resultados. |
| RF-04 | El sistema debe permitir consultar el **detalle** completo de un paciente localizado. | **Dado** un paciente existente, **cuando** el usuario solicita su detalle, **entonces** el sistema muestra todos sus datos administrativos vigentes. |
| RF-05 | El sistema debe permitir **editar/actualizar** los datos de un paciente existente solo si el usuario tiene el permiso (rol) autorizado. | **Dado** un usuario sin el permiso `editar_paciente`, **cuando** intenta guardar una edición, **entonces** el sistema rechaza la operación (HTTP 403) y no persiste ningún cambio. |
| RF-06 | El sistema debe validar el formato de los datos antes de guardar cualquier alta o edición. | **Dado** un campo obligatorio vacío o con formato inválido (p. ej. DPI con letras), **cuando** se envía el formulario, **entonces** el sistema rechaza la operación y detalla el/los campo(s) inválido(s). |
| RF-07 | El sistema debe registrar en la bitácora de auditoría cada alta, edición e intento de acceso denegado sobre un paciente. | **Dado** cualquier alta, edición o intento fallido, **cuando** la operación finaliza, **entonces** existe un evento de auditoría con usuario, fecha/hora, tenant, acción y resultado. |
| RF-08 | El sistema debe informar al usuario el resultado de cada operación (éxito o error) de forma clara. | **Dado** cualquier operación del módulo, **cuando** esta finaliza, **entonces** la interfaz muestra un mensaje de confirmación o un mensaje de error específico (no genérico). |

## 2. Requisitos No Funcionales (RNF)

| ID | Requisito no funcional | Criterio de aceptación |
|---|---|---|
| RNF-01 | **Rendimiento:** la búsqueda de pacientes debe responder en menos de 2 segundos para catálogos de hasta 10,000 pacientes por tenant. | Prueba de carga con 10,000 registros ficticios muestra P95 &lt; 2s. |
| RNF-02 | **Seguridad:** toda comunicación con el módulo debe viajar cifrada (HTTPS/TLS) y las operaciones de edición deben pasar por control de acceso RBAC. | Un intento de acceso sin token válido o sin permiso es rechazado (HTTP 401/403) antes de tocar la base de datos. |
| RNF-03 | **Multi-tenencia:** un usuario de un tenant no debe poder ver ni modificar pacientes de otro tenant. | Consultas y mutaciones siempre filtran por `tenant_id`; una prueba cruzada entre dos tenants ficticios confirma aislamiento total. |
| RNF-04 | **Usabilidad:** un usuario capacitado debe poder completar el formulario de registro en menos de 3 minutos. | Prueba de usabilidad con checklist (ver semana 9) confirma el tiempo objetivo. |
| RNF-05 | **Mantenibilidad:** el módulo debe organizarse en capas con responsabilidades claras (UI, API, negocio, persistencia). | Ver semana 4 — diagrama por capas y listado de responsabilidades. |
| RNF-06 | **Auditabilidad:** ningún cambio a un paciente debe quedar sin trazabilidad de quién, cuándo y qué se modificó. | Cobertura del 100% de las mutaciones (alta/edición) con evento de auditoría asociado. |
| RNF-07 | **Disponibilidad:** el módulo debe estar disponible ≥ 99% del horario hospitalario definido (06:00–22:00). | Monitoreo de uptime durante la fase de despliegue (semana 16-17). |

## 3. Aplicación del principio LSP (Liskov Substitution Principle)

> Fuente de referencia: *Principios SOLID*, https://mvpcluster.com/diseno-de-software-2/
> Principio aplicado: **Liskov Substitution Principle (LSP)** — cualquier implementación de una abstracción
> debe poder sustituir a otra sin alterar la corrección del programa que la consume.

**Dónde se aplica dentro del flujo:** el flujo «registro, búsqueda y actualización segura de un paciente»
depende de una única abstracción, `PatientRepositoryInterface`, para las tres operaciones (`guardar`,
`findByCriterio`, `actualizar`). LSP exige que **toda** implementación de esa interfaz —ya sea el
repositorio de producción (SQL) o uno alterno usado en pruebas/staging (en memoria)— sea sustituible sin que
`PatientService` (el cliente) tenga que adivinar con cuál está trabajando ni envolver las llamadas en
comprobaciones especiales.

**Contrato que toda implementación debe cumplir** (pre/postcondiciones):

1. `findByCriterio(criterio)` **siempre** retorna un array, vacío si no hay coincidencias — nunca `null`.
2. `existeDuplicado(dpi)` siempre retorna `bool`.
3. `guardar(datos)` retorna el `id` creado, o lanza **`DuplicatePatientException`** si el DPI ya existe —
   nunca un tipo de excepción distinto para ese mismo caso.
4. `actualizar(id, cambios)` siempre retorna `bool` indicando éxito.

### Antes (viola LSP)

`InMemoryPatientRepositoryViolatingLSP` implementa la misma interfaz, pero rompe el contrato en dos puntos:
retorna `null` en vez de un array vacío cuando no hay resultados, y lanza una excepción genérica distinta en
vez de `DuplicatePatientException`. Es una implementación que **parece** intercambiable por firma, pero deja
de serlo en tiempo de ejecución.

```php
final class InMemoryPatientRepositoryViolatingLSP implements PatientRepositoryInterface
{
    public function findByCriterio(string $criterio): array
    {
        $resultado = [/* ... */];
        // VIOLACIÓN #1: retorna null en vez de [] cuando no hay resultados.
        return $resultado ?: null;
    }

    public function guardar(array $datos): int
    {
        if ($this->existeDuplicado($datos['dpi'])) {
            // VIOLACIÓN #2: excepción genérica distinta a la del contrato.
            throw new \RuntimeException('Paciente repetido');
        }
        // ...
    }
    // ...
}
```

Al sustituir `SqlPatientRepository` por esta clase dentro de `PatientService`, el código cliente que hace
`foreach ($resultado as $p)` o que hace `catch (DuplicatePatientException $e)` se rompe sin haber cambiado
una sola línea de `PatientService` — exactamente el problema que LSP busca evitar.

### Después (cumple LSP)

`SqlPatientRepository` (producción) e `InMemoryPatientRepository` (pruebas/staging) implementan el mismo
contrato de forma consistente: ambas retornan array vacío cuando no hay coincidencias y ambas lanzan
`DuplicatePatientException` ante un DPI repetido.

```php
final class InMemoryPatientRepository implements PatientRepositoryInterface
{
    public function findByCriterio(string $criterio): array
    {
        $resultado = [];
        foreach ($this->datos as $p) {
            if (/* coincide */ true) { $resultado[] = $p; }
        }
        return $resultado; // array vacío si no hay coincidencias -> cumple el contrato
    }

    public function guardar(array $datos): int
    {
        if ($this->existeDuplicado($datos['dpi'])) {
            throw new DuplicatePatientException("DPI {$datos['dpi']} ya registrado."); // mismo tipo
        }
        $id = $this->siguienteId++;
        $this->datos[$id] = $datos + ['id' => $id];
        return $id;
    }
    // ...
}

final class PatientServiceDemo
{
    public function __construct(private PatientRepositoryInterface $repo) {} // depende SOLO de la abstracción

    public function buscar(string $criterio): array
    {
        return $this->repo->findByCriterio($criterio); // confía en el contrato, sin comprobar null
    }
}
```

### Justificación de responsabilidades y dependencias

- **Responsabilidad de `PatientServiceDemo` (aplicación):** orquestar registro/búsqueda/actualización segura
  del flujo, sin conocer *cómo* se persisten los datos. Su única dependencia declarada es la interfaz
  `PatientRepositoryInterface`, nunca una clase concreta (esto es Inversión de Dependencias, DIP, y es lo
  que **habilita** que LSP tenga sentido: si el cliente dependiera de la clase concreta, la sustitución
  nunca ocurriría).
- **Responsabilidad de cada repositorio (infraestructura):** adaptar el mismo contrato a un backend distinto
  (base de datos real vs. memoria para pruebas). Su única razón para cambiar es el mecanismo de
  almacenamiento — nunca las reglas de negocio.
- **Por qué importa para "actualización segura":** la validación de permiso (RBAC, Excepción E3 del
  diagrama de actividad) y la validación de datos (Excepción E1) ocurren en `PatientServiceDemo`, **antes**
  de llegar al repositorio. Esto significa que el cumplimiento de LSP en la capa de persistencia no debilita
  la seguridad del flujo: cualquier repositorio sustituto sigue pasando por los mismos controles de acceso.

### Evidencia verificable (ejecutable)

Código completo y ejecutable en
[`evidencia/lsp_pacientes_demo.php`](evidencia/lsp_pacientes_demo.php). Se ejecutó con:

```bash
php semanas/semana-02-diseno/evidencia/lsp_pacientes_demo.php
```

Salida real de la ejecución (capturada en
[`evidencia/salida_ejecucion.txt`](evidencia/salida_ejecucion.txt)):

```
== SqlPatientRepository (producción) ==
  registrar() paciente 1 -> id=1
  [OK] registrar() duplicado rechazado con DuplicatePatientException: DPI 1234567890101 ya registrado.
  buscar() sin coincidencias -> array(0)
  [OK] contrato respetado (array, nunca null)
  actualizarSeguro() con permiso -> OK
  actualizarSeguro() SIN permiso -> [OK] rechazado (Excepción E3)

== InMemoryPatientRepository (DESPUÉS - correcto, sustituible) ==
  registrar() paciente 1 -> id=1
  [OK] registrar() duplicado rechazado con DuplicatePatientException: DPI 1234567890101 ya registrado.
  buscar() sin coincidencias -> array(0)
  [OK] contrato respetado (array, nunca null)
  actualizarSeguro() con permiso -> OK
  actualizarSeguro() SIN permiso -> [OK] rechazado (Excepción E3)

== InMemoryPatientRepositoryViolatingLSP (ANTES - viola LSP) ==
  registrar() paciente 1 -> id=1
  [VIOLACION LSP] tipo de excepción inesperado: RuntimeException (Paciente repetido)
  [VIOLACION LSP] buscar() lanzó una excepción inesperada: InMemoryPatientRepositoryViolatingLSP::findByCriterio(): Return value must be of type array, null returned
  actualizarSeguro() con permiso -> OK
  actualizarSeguro() SIN permiso -> [OK] rechazado (Excepción E3)

Conclusión: las dos primeras implementaciones son intercambiables sin cambiar
PatientServiceDemo (cumplen LSP). La tercera rompe el contrato (tipo de retorno y
tipo de excepción), evidenciando por qué NO sería sustituible en producción.
```

Nótese que las dos primeras implementaciones (`SqlPatientRepository` e `InMemoryPatientRepository`) producen
una salida **idéntica** a través del mismo cliente `PatientServiceDemo` — la prueba concreta de que cumplen
LSP. La tercera falla de forma visible (incluso PHP detecta en tiempo de ejecución la violación del tipo de
retorno declarado en la interfaz), evidenciando por qué no sería segura de desplegar como sustituto.
