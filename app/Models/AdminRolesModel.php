<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo para gestionar roles de administrador adicionales
 * Almacena NITs de usuarios con acceso admin en archivo JSON
 */
final class AdminRolesModel
{
    private string $dataFile;

    public function __construct()
    {
        $this->dataFile = __DIR__ . '/../Data/admins_adicionales.json';
    }

    /**
     * Obtener todos los NITs con rol admin adicional
     */
    public function getAdminsAdicionales(): array
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }

        $content = file_get_contents($this->dataFile);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }

    /**
     * Verificar si un NIT tiene rol admin adicional
     */
    public function esAdminAdicional(string $nit): bool
    {
        $admins = $this->getAdminsAdicionales();
        return in_array($nit, $admins, true);
    }

    /**
     * Agregar un NIT como admin adicional
     */
    public function agregarAdmin(string $nit): bool
    {
        $admins = $this->getAdminsAdicionales();
        
        if (in_array($nit, $admins, true)) {
            return true; // Ya existe
        }

        $admins[] = $nit;
        return $this->guardarAdmins($admins);
    }

    /**
     * Quitar rol admin adicional a un NIT
     */
    public function quitarAdmin(string $nit): bool
    {
        $admins = $this->getAdminsAdicionales();
        $admins = array_filter($admins, fn($a) => $a !== $nit);
        
        return $this->guardarAdmins(array_values($admins));
    }

    /**
     * Guardar array de admins en archivo JSON
     */
    private function guardarAdmins(array $admins): bool
    {
        $dir = dirname($this->dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($admins, JSON_PRETTY_PRINT);
        return file_put_contents($this->dataFile, $json) !== false;
    }
}
