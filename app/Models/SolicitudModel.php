<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class SolicitudModel extends Model
{
    private const LIST_SELECT = "ID, NIT_EMPLEADO, NIT_JEFE, TIPO_SOLICITUD, DURACION_HORAS, DURACION_DIAS, "
        . "OBSERVACIONES, OBSERVACION_JEFE, OBSERVACION_RRHH, RUTA_COMPROBANTE, ESTADO, "
        . "TO_CHAR(FECHA_INICIO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_INICIO, "
        . "TO_CHAR(FECHA_FIN, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_FIN, "
        . "TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION, "
        . "TO_CHAR(FECHA_GESTION_JEFE, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_JEFE";

    private const DETAIL_SELECT = "ID, NIT_EMPLEADO, NIT_JEFE, NIT_RRHH, TIPO_SOLICITUD, DURACION_HORAS, DURACION_DIAS, "
        . "OBSERVACIONES, OBSERVACION_JEFE, OBSERVACION_RRHH, RUTA_COMPROBANTE, ESTADO, ACTIVO, "
        . "TO_CHAR(FECHA_INICIO, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_INICIO, "
        . "TO_CHAR(FECHA_FIN, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_FIN, "
        . "TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION, "
        . "TO_CHAR(FECHA_MODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_MODIFICACION, "
        . "TO_CHAR(FECHA_GESTION_JEFE, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_JEFE, "
        . "TO_CHAR(FECHA_GESTION_RRHH, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_GESTION_RRHH";

    public function crear(array $data): bool
    {
        $fechaHora = date('Y-m-d H:i:s');
        return $this->db->execute(
            "INSERT INTO ICEBERG.SOLICITUDES_PERMISOS
                (NIT_EMPLEADO, NIT_JEFE, TIPO_SOLICITUD, FECHA_INICIO, FECHA_FIN,
                 DURACION_HORAS, DURACION_DIAS, OBSERVACIONES, RUTA_COMPROBANTE, ESTADO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION)
             VALUES
                (:nit_emp, :nit_jefe, :tipo, TO_DATE(:f_ini,'YYYY-MM-DD'), TO_DATE(:f_fin,'YYYY-MM-DD'),
                 :horas, :dias, :obs, :ruta_comprobante, 'PENDIENTE_JEFE', 1, TO_DATE(:fecha_hora,'YYYY-MM-DD HH24:MI:SS'), TO_DATE(:fecha_hora,'YYYY-MM-DD HH24:MI:SS'))",
            [
                ':nit_emp'          => $data['nit_empleado'],
                ':nit_jefe'         => $data['nit_jefe'],
                ':tipo'             => $data['tipo_solicitud'],
                ':f_ini'            => $data['fecha_inicio'],
                ':f_fin'            => $data['fecha_fin'],
                ':horas'            => $data['duracion_horas'] ?: null,
                ':dias'             => $data['duracion_dias'] ?: null,
                ':obs'              => $data['observaciones'] ?: null,
                ':ruta_comprobante' => $data['ruta_archivo'] ?: null,
                ':fecha_hora'       => $fechaHora,
            ]
        );
    }

    public function editar(int $id, string $nit, array $data): bool
    {
        $jefeUpdate = !empty($data['nit_jefe_actualizado']) ? ", NIT_JEFE=:nit_jefe" : "";
        $pdfUpdate = !empty($data['ruta_archivo']) ? ", RUTA_COMPROBANTE=:ruta_comprobante" : "";
        $binds = [
            ':tipo'  => $data['tipo_solicitud'],
            ':f_ini' => $data['fecha_inicio'],
            ':f_fin' => $data['fecha_fin'],
            ':horas' => $data['duracion_horas'] ?: null,
            ':dias'  => $data['duracion_dias'] ?: null,
            ':obs'   => $data['observaciones'] ?: null,
            ':id'    => $id,
            ':nit'   => $nit,
        ];

        if (!empty($data['nit_jefe_actualizado'])) {
            $binds[':nit_jefe'] = $data['nit_jefe_actualizado'];
        }

        if (!empty($data['ruta_archivo'])) {
            $binds[':ruta_comprobante'] = $data['ruta_archivo'];
        }

        $fechaHora = date('Y-m-d H:i:s');
        $binds[':fecha_hora'] = $fechaHora;
        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
             SET TIPO_SOLICITUD=:tipo, FECHA_INICIO=TO_DATE(:f_ini,'YYYY-MM-DD'),
                 FECHA_FIN=TO_DATE(:f_fin,'YYYY-MM-DD'), DURACION_HORAS=:horas,
                 DURACION_DIAS=:dias, OBSERVACIONES=:obs, FECHA_MODIFICACION=TO_DATE(:fecha_hora,'YYYY-MM-DD HH24:MI:SS'){$jefeUpdate}{$pdfUpdate}
             WHERE ID=:id AND NIT_EMPLEADO=:nit AND ESTADO='PENDIENTE_JEFE' AND ACTIVO=1",
            $binds
        );
    }

    public function eliminar(int $id, string $nit): bool
    {
        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS SET ACTIVO=0
             WHERE ID=:id AND NIT_EMPLEADO=:nit AND ESTADO='PENDIENTE_JEFE' AND ACTIVO=1",
            [':id' => $id, ':nit' => $nit]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT " . self::DETAIL_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ID=:id AND ACTIVO=1",
            [':id' => $id]
        );
    }

    public function getByEmpleado(string $nit): array
    {
        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getPendientesJefe(string $nit): array
    {
        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit AND ESTADO='PENDIENTE_JEFE' AND ACTIVO=1
             ORDER BY FECHA_CREACION ASC",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getByJefe(string $nit): array
    {
        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getGestionadasByJefe(string $nit): array
    {
        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit AND ACTIVO=1
               AND ESTADO IN ('APROBADO_JEFE', 'RECHAZADO_JEFE', 'APROBADO_RRHH', 'RECHAZADO_RRHH')
             ORDER BY FECHA_GESTION_JEFE DESC
             FETCH FIRST 50 ROWS ONLY",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getPendientesRRHH(): array
    {
        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ESTADO='APROBADO_JEFE' AND ACTIVO=1
             ORDER BY FECHA_CREACION ASC"
        ) ?: [];
    }

    public function getAll(array $filtros = []): array
    {
        $where = ['ACTIVO=1'];
        $binds = [];

        if (!empty($filtros['estado'])) {
            $where[] = "ESTADO=:estado";
            $binds[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['nit'])) {
            $where[] = "(NIT_EMPLEADO=:nit OR NIT_JEFE=:nit2)";
            $binds[':nit'] = $filtros['nit'];
            $binds[':nit2'] = $filtros['nit'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = "TIPO_SOLICITUD=:tipo";
            $binds[':tipo'] = $filtros['tipo'];
        }

        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FECHA_CREACION DESC",
            $binds
        ) ?: [];
    }

    public function contarPorEstado(): array
    {
        $result = [];
        $rows = $this->db->query(
            "SELECT ESTADO, COUNT(*) AS TOTAL
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ACTIVO=1 GROUP BY ESTADO"
        ) ?: [];

        foreach ($rows as $row) {
            $result[$row['ESTADO']] = (int) $row['TOTAL'];
        }

        return $result;
    }

    public function getUltimoIdByEmpleado(string $nit): int
    {
        $row = $this->db->queryOne(
            "SELECT ID
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC
             FETCH FIRST 1 ROWS ONLY",
            [':nit' => $nit]
        );

        return (int) ($row['ID'] ?? 0);
    }

    public function getByEmpleadoEstados(string $nit, array $estados): array
    {
        [$inClause, $binds] = $this->buildInClause($estados, 'estado');
        $binds[':nit'] = $nit;

        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1 AND ESTADO IN ({$inClause})
             ORDER BY FECHA_CREACION DESC",
            $binds
        ) ?: [];
    }

    public function getByJefeEstados(string $nit, array $estados, string $orderBy = 'FECHA_CREACION DESC'): array
    {
        [$inClause, $binds] = $this->buildInClause($estados, 'estado');
        $binds[':nit'] = $nit;

        return $this->db->query(
            "SELECT " . self::LIST_SELECT . "
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit AND ACTIVO=1 AND ESTADO IN ({$inClause})
             ORDER BY {$orderBy}",
            $binds
        ) ?: [];
    }

    public function getResumenEmpleado(string $nit): array
    {
        $row = $this->db->queryOne(
            "SELECT
                COUNT(*) AS TOTAL,
                SUM(CASE WHEN ESTADO='PENDIENTE_JEFE' THEN 1 ELSE 0 END) AS PENDIENTES,
                SUM(CASE WHEN ESTADO IN ('APROBADO_JEFE', 'APROBADO_RRHH') THEN 1 ELSE 0 END) AS APROBADAS,
                SUM(CASE WHEN ESTADO IN ('RECHAZADO_JEFE', 'RECHAZADO_RRHH') THEN 1 ELSE 0 END) AS RECHAZADAS
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1",
            [':nit' => $nit]
        ) ?? [];

        return [
            'total' => (int) ($row['TOTAL'] ?? 0),
            'pendientes' => (int) ($row['PENDIENTES'] ?? 0),
            'aprobadas' => (int) ($row['APROBADAS'] ?? 0),
            'rechazadas' => (int) ($row['RECHAZADAS'] ?? 0),
        ];
    }

    public function getResumenJefe(string $nit): array
    {
        $row = $this->db->queryOne(
            "SELECT
                SUM(CASE WHEN NIT_JEFE=:nit AND ESTADO='PENDIENTE_JEFE' THEN 1 ELSE 0 END) AS PENDIENTES,
                SUM(CASE WHEN NIT_JEFE=:nit AND ESTADO IN ('APROBADO_JEFE', 'APROBADO_RRHH', 'RECHAZADO_RRHH') THEN 1 ELSE 0 END) AS APROBADAS,
                SUM(CASE WHEN NIT_JEFE=:nit AND ESTADO='RECHAZADO_JEFE' THEN 1 ELSE 0 END) AS RECHAZADAS,
                SUM(CASE WHEN NIT_EMPLEADO=:nit THEN 1 ELSE 0 END) AS MIS_SOLICITUDES,
                SUM(CASE WHEN NIT_JEFE=:nit AND ESTADO IN ('APROBADO_JEFE', 'RECHAZADO_JEFE', 'APROBADO_RRHH', 'RECHAZADO_RRHH') THEN 1 ELSE 0 END) AS GESTIONADAS
             FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE ACTIVO=1 AND (NIT_JEFE=:nit OR NIT_EMPLEADO=:nit)",
            [':nit' => $nit]
        ) ?? [];

        return [
            'pendientes' => (int) ($row['PENDIENTES'] ?? 0),
            'aprobadas' => (int) ($row['APROBADAS'] ?? 0),
            'rechazadas' => (int) ($row['RECHAZADAS'] ?? 0),
            'misSolicitudes' => (int) ($row['MIS_SOLICITUDES'] ?? 0),
            'gestionadas' => (int) ($row['GESTIONADAS'] ?? 0),
        ];
    }

    public function aprobarJefe(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarJefe($id, $nit, $obs, 'APROBADO_JEFE');
    }

    public function rechazarJefe(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarJefe($id, $nit, $obs, 'RECHAZADO_JEFE');
    }

    public function aprobarRRHH(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarRRHH($id, $nit, $obs, 'APROBADO_RRHH');
    }

    public function rechazarRRHH(int $id, string $nit, string $obs): bool
    {
        return $this->gestionarRRHH($id, $nit, $obs, 'RECHAZADO_RRHH');
    }

    private function gestionarJefe(int $id, string $nit, string $obs, string $nuevoEstado): bool
    {
        $fechaHora = date('Y-m-d H:i:s');
        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
             SET ESTADO=:estado, FECHA_GESTION_JEFE=TO_DATE(:fecha_hora,'YYYY-MM-DD HH24:MI:SS'), OBSERVACION_JEFE=:obs
             WHERE ID=:id AND NIT_JEFE=:nit AND ESTADO='PENDIENTE_JEFE' AND ACTIVO=1",
            [':estado' => $nuevoEstado, ':obs' => $obs, ':id' => $id, ':nit' => $nit, ':fecha_hora' => $fechaHora]
        );
    }

    private function gestionarRRHH(int $id, string $nit, string $obs, string $nuevoEstado): bool
    {
        $fechaHora = date('Y-m-d H:i:s');
        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
             SET ESTADO=:estado, NIT_RRHH=:nr, FECHA_GESTION_RRHH=TO_DATE(:fecha_hora,'YYYY-MM-DD HH24:MI:SS'), OBSERVACION_RRHH=:obs
             WHERE ID=:id AND ESTADO='APROBADO_JEFE' AND ACTIVO=1",
            [':estado' => $nuevoEstado, ':obs' => $obs, ':id' => $id, ':nr' => $nit, ':fecha_hora' => $fechaHora]
        );
    }

    private function buildInClause(array $values, string $prefix): array
    {
        $placeholders = [];
        $binds = [];

        foreach (array_values($values) as $index => $value) {
            $key = ':' . $prefix . $index;
            $placeholders[] = $key;
            $binds[$key] = $value;
        }

        return [implode(', ', $placeholders), $binds];
    }
}
