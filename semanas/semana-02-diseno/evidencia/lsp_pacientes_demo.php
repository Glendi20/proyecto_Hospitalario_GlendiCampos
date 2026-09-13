<?php
declare(strict_types=1);

/**
 * Evidencia ejecutable del principio de Sustitución de Liskov (LSP)
 * aplicado al flujo: registro, búsqueda y actualización segura de un paciente.
 *
 * Módulo: Pacientes | Estudiante: Glendi Patricia Campos Orellana (Glendi20)
 *
 * Contrato (pre/postcondiciones) que TODA implementación de PatientRepositoryInterface
 * debe cumplir para ser sustituible sin romper a PatientServiceDemo (el cliente):
 *   1) findByCriterio(): SIEMPRE retorna un array (vacío si no hay coincidencias). Nunca null.
 *   2) existeDuplicado(): SIEMPRE retorna bool.
 *   3) guardar(): retorna el id (int) del paciente creado, o lanza DuplicatePatientException
 *      si el DPI ya existe. Nunca una excepción genérica distinta para ese caso.
 *   4) actualizar(): retorna bool indicando éxito.
 *
 * Ejecutar con:  php semanas/semana-02-diseno/evidencia/lsp_pacientes_demo.php
 */

final class DuplicatePatientException extends \DomainException {}

interface PatientRepositoryInterface
{
    /** @return array<int, array<string,mixed>> nunca null */
    public function findByCriterio(string $criterio): array;
    public function existeDuplicado(string $dpi): bool;
    /** @throws DuplicatePatientException si el DPI ya existe */
    public function guardar(array $datos): int;
    public function actualizar(int $id, array $cambios): bool;
}

/**
 * Implementación de "producción" (simula la tabla SQL con un arreglo en memoria;
 * este demo no requiere una base de datos real para evidenciar el contrato).
 */
final class SqlPatientRepository implements PatientRepositoryInterface
{
    /** @var array<int, array<string,mixed>> */
    private array $tabla = [];
    private int $siguienteId = 1;

    public function findByCriterio(string $criterio): array
    {
        return array_values(array_filter($this->tabla, function (array $p) use ($criterio): bool {
            return str_contains($p['dpi'], $criterio) || stripos($p['nombre'], $criterio) !== false;
        }));
    }

    public function existeDuplicado(string $dpi): bool
    {
        foreach ($this->tabla as $p) {
            if ($p['dpi'] === $dpi) {
                return true;
            }
        }
        return false;
    }

    public function guardar(array $datos): int
    {
        if ($this->existeDuplicado($datos['dpi'])) {
            throw new DuplicatePatientException("DPI {$datos['dpi']} ya registrado.");
        }
        $id = $this->siguienteId++;
        $this->tabla[$id] = $datos + ['id' => $id];
        return $id;
    }

    public function actualizar(int $id, array $cambios): bool
    {
        if (!isset($this->tabla[$id])) {
            return false;
        }
        $this->tabla[$id] = array_merge($this->tabla[$id], $cambios);
        return true;
    }
}

/**
 * Implementación alterna para pruebas/staging (en memoria).
 * CUMPLE el mismo contrato que SqlPatientRepository -> es sustituible (LSP correcto = "DESPUÉS").
 */
final class InMemoryPatientRepository implements PatientRepositoryInterface
{
    /** @var array<int, array<string,mixed>> */
    private array $datos = [];
    private int $siguienteId = 1;

    public function findByCriterio(string $criterio): array
    {
        $resultado = [];
        foreach ($this->datos as $p) {
            if (str_contains($p['dpi'], $criterio) || stripos($p['nombre'], $criterio) !== false) {
                $resultado[] = $p;
            }
        }
        return $resultado; // array vacío si no hay coincidencias -> cumple el contrato
    }

    public function existeDuplicado(string $dpi): bool
    {
        foreach ($this->datos as $p) {
            if ($p['dpi'] === $dpi) {
                return true;
            }
        }
        return false;
    }

    public function guardar(array $datos): int
    {
        if ($this->existeDuplicado($datos['dpi'])) {
            throw new DuplicatePatientException("DPI {$datos['dpi']} ya registrado."); // mismo tipo de excepción
        }
        $id = $this->siguienteId++;
        $this->datos[$id] = $datos + ['id' => $id];
        return $id;
    }

    public function actualizar(int $id, array $cambios): bool
    {
        if (!isset($this->datos[$id])) {
            return false;
        }
        $this->datos[$id] = array_merge($this->datos[$id], $cambios);
        return true;
    }
}

/**
 * Implementación "ANTES": se ve igual por fuera (implementa la misma interfaz) pero
 * ROMPE el contrato en dos puntos, por lo que NO es sustituible sin romper al cliente.
 * Es el ejemplo de violación de LSP.
 */
final class InMemoryPatientRepositoryViolatingLSP implements PatientRepositoryInterface
{
    private array $datos = [];
    private int $siguienteId = 1;

    public function findByCriterio(string $criterio): array
    {
        $resultado = [];
        foreach ($this->datos as $p) {
            if (str_contains($p['dpi'], $criterio) || stripos($p['nombre'], $criterio) !== false) {
                $resultado[] = $p;
            }
        }
        // VIOLACIÓN #1: retorna null en vez de [] cuando no hay resultados.
        return $resultado ?: null;
    }

    public function existeDuplicado(string $dpi): bool
    {
        foreach ($this->datos as $p) {
            if ($p['dpi'] === $dpi) {
                return true;
            }
        }
        return false;
    }

    public function guardar(array $datos): int
    {
        if ($this->existeDuplicado($datos['dpi'])) {
            // VIOLACIÓN #2: lanza una excepción genérica distinta a la del contrato.
            throw new \RuntimeException('Paciente repetido');
        }
        $id = $this->siguienteId++;
        $this->datos[$id] = $datos + ['id' => $id];
        return $id;
    }

    public function actualizar(int $id, array $cambios): bool
    {
        if (!isset($this->datos[$id])) {
            return false;
        }
        $this->datos[$id] = array_merge($this->datos[$id], $cambios);
        return true;
    }
}

/**
 * Cliente (equivalente simplificado de PatientService) que SOLO conoce la interfaz,
 * nunca una implementación concreta -> aquí se verifican juntos DIP y LSP.
 */
final class PatientServiceDemo
{
    public function __construct(private PatientRepositoryInterface $repo) {}

    public function registrar(array $datos): int
    {
        return $this->repo->guardar($datos);
    }

    /** @return array<int, array<string,mixed>> */
    public function buscar(string $criterio): array
    {
        $resultado = $this->repo->findByCriterio($criterio);
        // El cliente confía en el contrato: SIEMPRE puede iterar el resultado sin comprobar null antes.
        foreach ($resultado as $p) {
            // no-op: solo demuestra que $resultado es iterable de forma segura.
        }
        return $resultado;
    }

    public function actualizarSeguro(int $id, array $cambios, bool $permisoConcedido): bool
    {
        if (!$permisoConcedido) {
            return false; // Excepción E3 del diagrama de actividad: permiso denegado (RBAC).
        }
        return $this->repo->actualizar($id, $cambios);
    }
}

/* --------------------------- Arnés de pruebas (evidencia) --------------------------- */

function escenario(string $nombre, PatientRepositoryInterface $repo): void
{
    echo "== {$nombre} ==\n";
    $service = new PatientServiceDemo($repo);

    $id1 = $service->registrar(['dpi' => '1234567890101', 'nombre' => 'Paciente Ficticio Uno']);
    echo "  registrar() paciente 1 -> id={$id1}\n";

    try {
        $service->registrar(['dpi' => '1234567890101', 'nombre' => 'Duplicado']);
        echo "  [FALLO] se permitió un DPI duplicado\n";
    } catch (DuplicatePatientException $e) {
        echo "  [OK] registrar() duplicado rechazado con DuplicatePatientException: {$e->getMessage()}\n";
    } catch (\Throwable $e) {
        echo '  [VIOLACION LSP] tipo de excepción inesperado: ' . get_class($e) . " ({$e->getMessage()})\n";
    }

    try {
        $resultado = $service->buscar('sin-coincidencias');
        $tipo = is_array($resultado) ? 'array(' . count($resultado) . ')' : gettype($resultado);
        echo "  buscar() sin coincidencias -> {$tipo}\n";
        if (!is_array($resultado)) {
            echo '  [VIOLACION LSP] se esperaba array vacío, se obtuvo ' . var_export($resultado, true) . "\n";
        } else {
            echo "  [OK] contrato respetado (array, nunca null)\n";
        }
    } catch (\Throwable $e) {
        echo "  [VIOLACION LSP] buscar() lanzó una excepción inesperada: {$e->getMessage()}\n";
    }

    $actualizado = $service->actualizarSeguro($id1, ['telefono' => '00000000'], permisoConcedido: true);
    echo '  actualizarSeguro() con permiso -> ' . ($actualizado ? 'OK' : 'FALLO') . "\n";

    $rechazado = $service->actualizarSeguro($id1, ['telefono' => '99999999'], permisoConcedido: false);
    echo '  actualizarSeguro() SIN permiso -> ' . ($rechazado ? '[FALLO] no debía actualizar' : '[OK] rechazado (Excepción E3)') . "\n";
    echo "\n";
}

escenario('SqlPatientRepository (producción)', new SqlPatientRepository());
escenario('InMemoryPatientRepository (DESPUÉS - correcto, sustituible)', new InMemoryPatientRepository());
escenario('InMemoryPatientRepositoryViolatingLSP (ANTES - viola LSP)', new InMemoryPatientRepositoryViolatingLSP());

echo "Conclusión: las dos primeras implementaciones son intercambiables sin cambiar\n";
echo "PatientServiceDemo (cumplen LSP). La tercera rompe el contrato (tipo de retorno y\n";
echo "tipo de excepción), evidenciando por qué NO sería sustituible en producción.\n";
