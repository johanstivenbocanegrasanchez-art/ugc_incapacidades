<?php
declare(strict_types=1);

namespace App\Exportar\Rrhh;

use Core\Controller;
use App\Models\SolicitudModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExportControllerRrhh extends Controller
{
    private array $cabeceras = [
        'ID',
        'NIT EMPLEADO',
        'TIPO SOLICITUD',
        'FECHA SOLICITUD',
        'FECHA INICIO',
        'FECHA FIN',
        'HORAS',
        'DÍAS',
        'ESTADO',
        'OBSERVACIONES',
        'FECHA CREACIÓN'
    ];

    private array $campos = [
        'ID',
        'NIT_EMPLEADO',
        'TIPO_SOLICITUD',
        'FECHA_SOLICITUD',
        'FECHA_INICIO',
        'FECHA_FIN',
        'DURACION_HORAS',
        'DURACION_DIAS',
        'ESTADO',
        'OBSERVACIONES',
        'FECHA_CREACION'
    ];

    public function todasExcelRrhh(): void
    {
        $this->requireRole([ROL_RRHH]);

        $model = new SolicitudModel();

        $todas = $model->getAll();
        $enRevisionJefe = array_values(array_filter(
            $todas,
            fn($s) => ($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE'
        ));

        $data = [
            'Pendientes RRHH'   => $model->getPendientesRRHH(),
            'Histórico completo' => $todas,
            'En revisión jefe'  => $enRevisionJefe,
        ];

        $this->generarExcelPorHojas($data, 'reporte_rrhh');
    }

    private function generarExcelPorHojas(array $data, string $nombreArchivo): void
    {
        $spreadsheet = new Spreadsheet();
        $index = 0;

        foreach ($data as $tituloHoja => $rows) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $sheet->setTitle($this->limpiarTituloHoja($tituloHoja));
            $this->llenarHoja($sheet, $rows);

            $index++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function llenarHoja($sheet, array $rows): void
    {
        foreach ($this->cabeceras as $i => $cabecera) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $cabecera);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        foreach ($rows as $fila => $row) {
            foreach ($this->campos as $i => $campo) {
                $col = chr(65 + $i);
                $valor = $row[$campo] ?? '';

                if ($campo === 'TIPO_SOLICITUD' && isset(TIPOS_SOLICITUD[$valor])) {
                    $valor = TIPOS_SOLICITUD[$valor];
                }

                $sheet->setCellValue($col . ($fila + 2), $valor);
            }
        }
    }

    private function limpiarTituloHoja(string $titulo): string
    {
        $titulo = str_replace(['\\', '/', '*', '[', ']', ':', '?'], ' ', $titulo);
        return mb_substr($titulo, 0, 31);
    }
}