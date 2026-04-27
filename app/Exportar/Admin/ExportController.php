<?php

declare(strict_types=1);

namespace App\Exportar\Admin;

use Core\Controller;
use App\Models\SolicitudModel;
use Shuchkin\SimpleXLSXGen;

final class ExportController extends Controller
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

    public function todasExcel(): void
    {
        $this->requireRole([ROL_ADMIN]);

        $model = new SolicitudModel();

        $todas = $model->getAll();

        $pendienteJefe = $this->filtrarPorEstados($todas, [
            'PENDIENTE_JEFE'
        ]);

        $aprobadoJefe = $this->filtrarPorEstados($todas, [
            'APROBADO_JEFE'
        ]);

        $rechazadoJefe = $this->filtrarPorEstados($todas, [
            'RECHAZADO_JEFE'
        ]);

        $aprobadoRRHH = $this->filtrarPorEstados($todas, [
            'APROBADO_RRHH'
        ]);

        $rechazadoRRHH = $this->filtrarPorEstados($todas, [
            'RECHAZADO_RRHH'
        ]);

        $data = [
            'Pendiente Jefe'    => $pendienteJefe,
            'Aprobado Jefe'     => $aprobadoJefe,
            'Rechazado Jefe'    => $rechazadoJefe,
            'Aprobado RRHH'     => $aprobadoRRHH,
            'Rechazado RRHH'    => $rechazadoRRHH,
            'Total Solicitudes' => $todas,
        ];

        $this->generarExcelPorHojas($data, 'reporte_admin');
    }

    private function filtrarPorEstados(array $rows, array $estados): array
    {
        return array_values(array_filter($rows, function ($row) use ($estados) {
            return isset($row['ESTADO']) && in_array($row['ESTADO'], $estados, true);
        }));
    }

    private function generarExcelPorHojas(array $data, string $nombreArchivo): void
    {
        $rutaLibreria = __DIR__ . '/../../Libraries/SimpleXLSXGen.php';

        if (!file_exists($rutaLibreria)) {
            http_response_code(500);
            echo 'No se encontró la librería SimpleXLSXGen en: ' . $rutaLibreria;
            exit;
        }

        require_once $rutaLibreria;

        $xlsx = new SimpleXLSXGen();

        foreach ($data as $tituloHoja => $rows) {
            $filas = $this->prepararFilas($rows);
            $xlsx->addSheet($filas, $this->limpiarTituloHoja($tituloHoja));
        }

        $archivo = $nombreArchivo . '_' . date('Ymd') . '.xlsx';

        if (ob_get_length()) {
            ob_clean();
        }

        $xlsx->downloadAs($archivo);
        exit;
    }

    private function prepararFilas(array $rows): array
    {
        $filas = [];

        // Primera fila: cabeceras
        $filas[] = $this->cabeceras;

        // Filas de datos
        foreach ($rows as $row) {
            $fila = [];

            foreach ($this->campos as $campo) {
                $valor = $row[$campo] ?? '';

                if ($campo === 'TIPO_SOLICITUD' && defined('TIPOS_SOLICITUD') && isset(TIPOS_SOLICITUD[$valor])) {
                    $valor = TIPOS_SOLICITUD[$valor];
                }

                $fila[] = $valor;
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    private function limpiarTituloHoja(string $titulo): string
    {
        $titulo = str_replace(['\\', '/', '*', '[', ']', ':', '?'], ' ', $titulo);
        $titulo = trim($titulo);

        if ($titulo === '') {
            $titulo = 'Hoja';
        }

        return mb_substr($titulo, 0, 31);
    }
}