<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class EmpleadoModel extends Model
{
    public function getByNit(string $nit): ?array
    {
        $rows = $this->db->query(
            "SELECT NIT, TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL, ESTADO
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND NIT=:nit",
            [':nit' => $nit]
        );
        return $rows[0] ?? null;
    }

    public function getRol(string $nit): string
    {
        $emp = $this->getByNit($nit);
        if (!$emp) {
            return ROL_EMPLEADO;
        }

        $nivel = (int) ($emp['NIVEL'] ?? 0);
        $cc    = $emp['CENTRO_COSTO'] ?? '';

        if ($nivel >= NIVEL_MIN_ADMIN) {
            return ROL_ADMIN;
        }
        if (in_array($cc, CC_RRHH, true)) {
            return ROL_RRHH;
        }
        if ($nivel >= NIVEL_MIN_JEFE) {
            return ROL_JEFE;
        }

        return ROL_EMPLEADO;
    }

    public function getJefeInmediato(string $nit): ?array
    {
        $rows = $this->db->query(
            "SELECT jf.NIT AS NIT_JEFE,
                    TRIM(jf.NOMBRE||' '||jf.PRIMER_APELLIDO||' '||NVL(jf.SEGUNDO_APELLIDO,'')) AS NOMBRE_JEFE
             FROM EMPLEADO emp
             JOIN EMPLEADO jf ON jf.EMPRESA='BA2' AND jf.ESTADO='A'
                 AND jf.CENTRO_COSTO=emp.CENTRO_COSTO AND jf.NIVEL > emp.NIVEL
             WHERE emp.EMPRESA='BA2' AND emp.ESTADO='A' AND emp.NIT=:nit
             ORDER BY jf.NIVEL ASC FETCH FIRST 1 ROWS ONLY",
            [':nit' => $nit]
        );

        if (!empty($rows[0]) && !empty($rows[0]['NIT_JEFE'])) {
            return $rows[0];
        }

        return null;
    }

    public function esAprendiz(string $centroCosto): bool
    {
        return in_array($centroCosto, CC_APRENDICES, true);
    }

    public function getTodosLosJefes(): array
    {
        return $this->db->query(
            "SELECT NIT, TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND NIVEL >= :nivel
             ORDER BY NOMBRE_COMPLETO",
            [':nivel' => NIVEL_MIN_JEFE]
        ) ?: [];
    }

    /**
     * Obtener empleados por centro de costo (útil para encontrar usuarios RRHH)
     */
    public function getPorCentrosCosto(array $centrosCosto): array
    {
        if (empty($centrosCosto)) {
            return [];
        }

        // Construir placeholders para IN clause
        $placeholders = [];
        $params = [];
        foreach ($centrosCosto as $i => $cc) {
            $placeholders[] = ':cc' . $i;
            $params[':cc' . $i] = $cc;
        }

        $inClause = implode(', ', $placeholders);

        // Construir query con IN clause dinámico
        $sql = "SELECT NIT, TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND CENTRO_COSTO IN ({$inClause})
             ORDER BY NOMBRE_COMPLETO";

        // Para OCI8, necesitamos pasar los parámetros de forma especial
        // Usamos el método query con el array de binds
        return $this->db->query($sql, $params) ?: [];
    }

    /**
     * Verificar si un NIT pertenece a RRHH (por centro de costo)
     */
    public function esRRHH(string $nit): bool
    {
        $emp = $this->getByNit($nit);
        if (!$emp) {
            return false;
        }
        return in_array($emp['CENTRO_COSTO'] ?? '', CC_RRHH, true);
    }
}
