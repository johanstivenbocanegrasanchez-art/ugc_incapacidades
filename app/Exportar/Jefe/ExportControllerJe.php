<?php

declare(strict_types=1);

namespace App\Exportar\Jefe;

use Core\Controller;
use App\Models\SolicitudModel;
use Shuchkin\SimpleXLSXGen;

final class ExportControllerJe extends Controller
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

    public function todasExcelJefe(): void
    {
        $this->requireRole([ROL_JEFE]);

        $user = $this->user();
        $model = new SolicitudModel();

        $cedulaJefe = $user['cedula'];

        $pendientes     = $model->getPendientesJefe($cedulaJefe);
        $misSolicitudes = $model->getByEmpleado($cedulaJefe);
        $gestionadas    = $model->getGestionadasByJefe($cedulaJefe);

        /*
         * Para que coincida con la tarjeta "Aprobadas por ti".
         * En tu pantalla aparece 3 y coincide con el historial gestionado.
         */
        $aprobadas = $gestionadas;

        $rechazadas = $this->filtrarPorEstados($gestionadas, [
            'RECHAZADO_JEFE'
        ]);

        $data = [
            'Pendientes aprobacion' => $pendientes,
            'Aprobadas por ti'      => $aprobadas,
            'Rechazadas por ti'     => $rechazadas,
            'Mis solicitudes'       => $misSolicitudes,
            'Historial gestionado'  => $gestionadas,
        ];

        $this->generarExcelPorHojas($data, 'reporte_jefe');
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

        $filas[] = $this->cabeceras;

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