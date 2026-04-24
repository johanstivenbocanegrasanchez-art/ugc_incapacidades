<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class EmpleadoModel extends Model
{
    private static array $byNitCache = [];
    private static array $rolCache = [];
    private static array $jefeCache = [];
    private static ?array $todosLosJefesCache = null;
    private static array $centrosCostoCache = [];
    private static ?array $todosCache = null;

    public function getByNit(string $nit): ?array
    {
        if (array_key_exists($nit, self::$byNitCache)) {
            return self::$byNitCache[$nit];
        }

        $row = $this->db->queryOne(
            "SELECT NIT, TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL, ESTADO, FECHA_INGRESO
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND NIT=:nit
             ORDER BY FECHA_INGRESO DESC, EMPLEADO DESC",
            [':nit' => $nit]
        );

        self::$byNitCache[$nit] = $row;
        return $row;
    }

    public function getRol(string $nit): string
    {
        if (isset(self::$rolCache[$nit])) {
            return self::$rolCache[$nit];
        }

        // Solo el Super Admin único tiene rol ADMIN por defecto
        if ($nit === SUPER_ADMIN_NIT) {
            return self::$rolCache[$nit] = ROL_ADMIN;
        }

        $emp = $this->getByNit($nit);
        if (!$emp) {
            return self::$rolCache[$nit] = ROL_EMPLEADO;
        }

        $nivel = (int) ($emp['NIVEL'] ?? 0);
        $cc    = $emp['CENTRO_COSTO'] ?? '';

        // Ya no se asigna ROL_ADMIN automáticamente por NIVEL >= 7
        // Los admins adicionales se definen en admins_adicionales.json

        if (in_array($cc, CC_RRHH, true)) {
            return self::$rolCache[$nit] = ROL_RRHH;
        }
        if ($nivel >= NIVEL_MIN_JEFE) {
            return self::$rolCache[$nit] = ROL_JEFE;
        }

        return self::$rolCache[$nit] = ROL_EMPLEADO;
    }

    public function getJefeInmediato(string $nit): ?array
    {
        if (array_key_exists($nit, self::$jefeCache)) {
            return self::$jefeCache[$nit];
        }

        // Implementación basada en la consulta proporcionada por el jefe
        // Sigue la estructura organizacional real de la UGC con mapeo de centros de costo
        
        
        $rows = $this->db->query(
            "WITH emp_buscado AS (
              -- Solo el empleado que estamos buscando
              SELECT
                e.ROWID AS rid_emp,
                e.EMPRESA,
                e.EMPLEADO,
                e.NIT,
                TRIM(
                  e.NOMBRE || ' ' ||
                  e.PRIMER_APELLIDO || ' ' ||
                  NVL(e.SEGUNDO_APELLIDO, '')
                ) AS NOMBRE_COMPLETO,
                e.CENTRO_COSTO,
                e.NIVEL,
                e.ESTADO
              FROM EMPLEADO e
              WHERE e.EMPRESA = 'BA2'
                AND e.ESTADO  = 'A'
                AND e.NIT = :nit
            ),
            
            base AS (
              -- Todos los empleados activos para buscar jefes entre ellos
              SELECT
                e.ROWID AS rid_emp,
                e.EMPRESA,
                e.EMPLEADO,
                e.NIT,
                TRIM(
                  e.NOMBRE || ' ' ||
                  e.PRIMER_APELLIDO || ' ' ||
                  NVL(e.SEGUNDO_APELLIDO, '')
                ) AS NOMBRE_COMPLETO,
                e.CENTRO_COSTO,
                e.NIVEL,
                e.ESTADO
              FROM EMPLEADO e
              WHERE e.EMPRESA = 'BA2'
                AND e.ESTADO  = 'A'
            ),

            cc_map AS (
              SELECT DISTINCT
                b.CENTRO_COSTO,
                CASE
                  -- =========================
                  -- RECTORÍA (cabecera 1020001)
                  -- =========================
                  WHEN b.CENTRO_COSTO IN (
                    '1020001','2101001','2201001','2231101','2231102','2242101','2242102',
                    '2311101','2312201','2321101','2325801','2351003','2351005',
                    '2411001','2411002','2411004','2412004','2415001','2415002',
                    '2416001','2416002','5010001','5010002','2341001'
                  ) THEN '1020001'

                  -- =========================================
                  -- VICERRECTORÍA DESARROLLO ACADÉMICO (2202001)
                  -- =========================================
                  WHEN b.CENTRO_COSTO IN (
                    '2102001','2312101','2321201','2322000','2322801','2322802',
                    '2323000','2324000','2325000','2325901','2326000','2331001','2343001','2351000'
                  ) THEN '2202001'

                  -- ==================================
                  -- VICERRECTORÍA GESTIÓN FINANCIERA (2413001)
                  -- ==================================
                  WHEN b.CENTRO_COSTO IN (
                    '2413001','2351001','2412001','2412003','2413002','2413003','2413004',
                    '2414001','2414002','2414004'
                  ) THEN '2413001'

                  -- ==========================================
                  -- VICERRECTORÍA INNOVACIÓN Y EMPRESARISMO (2341001)
                  -- ==========================================
                  WHEN b.CENTRO_COSTO IN (
                    '2341001','2211101','2331003','2341002','2341003','2341007','2342001',
                    '2351004','2414101','2414102','2414103','2414104','2414105'
                  ) THEN '2341001'

                  ELSE NULL
                END AS CC_CABECERA
              FROM emp_buscado b
            ),

            -- 1) Jefe dentro del mismo centro de costo (NIVEL máximo)
            jefe_mismo_cc AS (
              SELECT
                b1.rid_emp,
                b2.NIT,
                b2.NOMBRE_COMPLETO,
                b2.NIVEL,
                ROW_NUMBER() OVER (
                  PARTITION BY b1.rid_emp
                  ORDER BY b2.NIVEL DESC
                ) AS rn
              FROM emp_buscado b1
              JOIN base b2
                ON b2.CENTRO_COSTO = b1.CENTRO_COSTO
               AND b2.NIVEL > b1.NIVEL
            ),

            -- 2) Si no hay, jefe en el centro de costo cabecera del segmento (NIVEL máximo)
            jefe_cabecera AS (
              SELECT
                b1.rid_emp,
                b2.NIT,
                b2.NOMBRE_COMPLETO,
                b2.NIVEL,
                ROW_NUMBER() OVER (
                  PARTITION BY b1.rid_emp
                  ORDER BY b2.NIVEL DESC
                ) AS rn
              FROM emp_buscado b1
              JOIN cc_map m
                ON m.CENTRO_COSTO = b1.CENTRO_COSTO
              JOIN base b2
                ON b2.CENTRO_COSTO = m.CC_CABECERA
               AND b2.NIVEL > b1.NIVEL
            )

            SELECT
              COALESCE(j1.NIT,             j2.NIT)             AS NIT_JEFE,
              COALESCE(j1.NOMBRE_COMPLETO, j2.NOMBRE_COMPLETO) AS NOMBRE_JEFE,
              COALESCE(j1.NIVEL,           j2.NIVEL)           AS NIVEL_JEFE,
              CASE
                WHEN j1.NIT IS NOT NULL THEN 'MISMO_CENTRO_COSTO'
                WHEN j2.NIT IS NOT NULL THEN 'CABECERA_SEGMENTO'
                ELSE 'SIN_JEFE'
              END AS FUENTE_JEFE

            FROM emp_buscado b
            LEFT JOIN jefe_mismo_cc j1
              ON j1.rid_emp = b.rid_emp
             AND j1.rn = 1
            LEFT JOIN jefe_cabecera j2
              ON j2.rid_emp = b.rid_emp
             AND j2.rn = 1",
            [':nit' => $nit]
        );
        

        if (!empty($rows[0]) && !empty($rows[0]['NIT_JEFE'])) {
            return self::$jefeCache[$nit] = [
                'NIT_JEFE' => $rows[0]['NIT_JEFE'],
                'NOMBRE_JEFE' => $rows[0]['NOMBRE_JEFE']
            ];
        }

        self::$jefeCache[$nit] = null;
        return null;
    }

    public function esAprendiz(string $centroCosto): bool
    {
        return in_array($centroCosto, CC_APRENDICES, true);
    }

    public function getTodosLosJefes(): array
    {
        if (self::$todosLosJefesCache !== null) {
            return self::$todosLosJefesCache;
        }

        self::$todosLosJefesCache = $this->db->query(
            "SELECT NIT, TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A' AND NIVEL >= :nivel
             ORDER BY NOMBRE_COMPLETO",
            [':nivel' => NIVEL_MIN_JEFE]
        ) ?: [];

        return self::$todosLosJefesCache;
    }

    /**
     * Obtener empleados por centro de costo (útil para encontrar usuarios RRHH)
     */
    public function getPorCentrosCosto(array $centrosCosto): array
    {
        if (empty($centrosCosto)) {
            return [];
        }

        $cacheKey = implode('|', array_map('strval', $centrosCosto));
        if (isset(self::$centrosCostoCache[$cacheKey])) {
            return self::$centrosCostoCache[$cacheKey];
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
        self::$centrosCostoCache[$cacheKey] = $this->db->query($sql, $params) ?: [];
        return self::$centrosCostoCache[$cacheKey];
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

    /**
     * Obtener todos los empleados activos (sin filtro de nivel)
     * Usado para el panel de administración
     */
    public function getTodos(): array
    {
        if (self::$todosCache !== null) {
            return self::$todosCache;
        }

        self::$todosCache = $this->db->query(
            "SELECT NIT, TRIM(NOMBRE||' '||PRIMER_APELLIDO||' '||NVL(SEGUNDO_APELLIDO,'')) AS NOMBRE_COMPLETO,
                    CENTRO_COSTO, NIVEL, ESTADO
             FROM EMPLEADO
             WHERE EMPRESA='BA2' AND ESTADO='A'
             ORDER BY NOMBRE_COMPLETO",
            []
        ) ?: [];

        return self::$todosCache;
    }
}
