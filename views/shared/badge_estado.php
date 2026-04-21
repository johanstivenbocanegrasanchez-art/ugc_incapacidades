<?php
function badgeEstado(string $estado): string {
    $map = [
        'PENDIENTE_JEFE' => ['badge-pendiente','Pendiente Jefe'],
        'APROBADO_JEFE'  => ['badge-rrhh',     'Aprobado Jefe'],
        'RECHAZADO_JEFE' => ['badge-rechazado','Rechazado Jefe'],
        'APROBADO_RRHH'  => ['badge-aprobado', 'Aprobado RRHH'],
        'RECHAZADO_RRHH' => ['badge-rechazado','Rechazado RRHH'],
    ];
    [$cls,$lbl] = $map[$estado] ?? ['badge-pendiente', $estado];
    return '<span class="badge '.$cls.'">'.htmlspecialchars($lbl).'</span>';
}
