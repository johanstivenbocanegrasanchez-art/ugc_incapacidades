<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class SolicitudModel extends Model
{
    public function crear(array $data): bool
    {
        return $this->db->execute(
            "INSERT INTO ICEBERG.SOLICITUDES_PERMISOS
                (NIT_EMPLEADO, NIT_JEFE, TIPO_SOLICITUD, FECHA_SOLICITUD, FECHA_INICIO, FECHA_FIN,
                 DURACION_HORAS, DURACION_DIAS, OBSERVACIONES, RUTA_COMPROBANTE, ESTADO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION)
             VALUES
                (:nit_emp, :nit_jefe, :tipo, SYSDATE, TO_DATE(:f_ini,'YYYY-MM-DD'), TO_DATE(:f_fin,'YYYY-MM-DD'),
                 :horas, :dias, :obs, :ruta_comprobante, 'PENDIENTE_JEFE', 1, SYSDATE, SYSDATE)",
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

        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
             SET TIPO_SOLICITUD=:tipo, FECHA_INICIO=TO_DATE(:f_ini,'YYYY-MM-DD'),
                 FECHA_FIN=TO_DATE(:f_fin,'YYYY-MM-DD'), DURACION_HORAS=:horas,
                 DURACION_DIAS=:dias, OBSERVACIONES=:obs, FECHA_MODIFICACION=SYSDATE{$jefeUpdate}{$pdfUpdate}
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
        $rows = $this->db->query(
            "SELECT * FROM ICEBERG.SOLICITUDES_PERMISOS WHERE ID=:id AND ACTIVO=1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function getByEmpleado(string $nit): array
    {
        return $this->db->query(
            "SELECT * FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_EMPLEADO=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getPendientesJefe(string $nit): array
    {
        return $this->db->query(
            "SELECT * FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit AND ESTADO='PENDIENTE_JEFE' AND ACTIVO=1
             ORDER BY FECHA_CREACION ASC",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getByJefe(string $nit): array
    {
        return $this->db->query(
            "SELECT * FROM ICEBERG.SOLICITUDES_PERMISOS
             WHERE NIT_JEFE=:nit AND ACTIVO=1
             ORDER BY FECHA_CREACION DESC",
            [':nit' => $nit]
        ) ?: [];
    }

    public function getPendientesRRHH(): array
    {
        return $this->db->query(
            "SELECT * FROM ICEBERG.SOLICITUDES_PERMISOS
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
            "SELECT * FROM ICEBERG.SOLICITUDES_PERMISOS
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
        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
             SET ESTADO=:estado, FECHA_GESTION_JEFE=SYSDATE, OBSERVACION_JEFE=:obs
             WHERE ID=:id AND NIT_JEFE=:nit AND ESTADO='PENDIENTE_JEFE' AND ACTIVO=1",
            [':estado' => $nuevoEstado, ':obs' => $obs, ':id' => $id, ':nit' => $nit]
        );
    }

    private function gestionarRRHH(int $id, string $nit, string $obs, string $nuevoEstado): bool
    {
        return $this->db->execute(
            "UPDATE ICEBERG.SOLICITUDES_PERMISOS
             SET ESTADO=:estado, NIT_RRHH=:nr, FECHA_GESTION_RRHH=SYSDATE, OBSERVACION_RRHH=:obs
             WHERE ID=:id AND ESTADO='APROBADO_JEFE' AND ACTIVO=1",
            [':estado' => $nuevoEstado, ':obs' => $obs, ':id' => $id, ':nr' => $nit]
        );
    }
}
