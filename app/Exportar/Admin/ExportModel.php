<?php
declare(strict_types=1);

namespace App\Exportar\Admin;

use Core\Model;

final class ExportModel extends Model
{
    public function getTodasLasSolicitudes(): array
    {
        $sql = "SELECT 
                    ID,
                    NIT_EMPLEADO,
                    TIPO_SOLICITUD,
                    FECHA_INICIO,
                    FECHA_FIN,
                    DURACION_HORAS,
                    DURACION_DIAS,
                    ESTADO,
                    OBSERVACIONES,
                    FECHA_CREACION
                FROM SOLICITUDES_PERMISOS
                ORDER BY FECHA_CREACION DESC";

        return $this->db->query($sql);
    }

    public function getSolicitudesAgrupadas(): array
    {
        $rows = $this->getTodasLasSolicitudes();

        $agrupadas = [
            'PENDIENTE_JEFE' => [],
            'APROBADO_JEFE'  => [],
            'RECHAZADO_JEFE' => [],
            'APROBADO_RRHH'  => [],
            'RECHAZADO_RRHH' => [],
            'TODAS'          => $rows
        ];

        foreach ($rows as $row) {
            $estado = strtoupper(trim($row['ESTADO'] ?? ''));

            if (isset($agrupadas[$estado])) {
                $agrupadas[$estado][] = $row;
            }
        }

        return $agrupadas;
    }
}