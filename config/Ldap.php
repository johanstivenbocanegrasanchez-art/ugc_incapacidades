<?php

declare(strict_types=1);

namespace Config;

use Core\Config;

final class Ldap
{
    public static function authenticate(string $cedula, string $password): ?array
    {
        $host = Config::get('LDAP_HOST', '');
        $port = Config::getInt('LDAP_PORT', 389);
        $user = Config::get('LDAP_USER', '');
        $pass = Config::get('LDAP_PASSWORD', '');
        $tree = Config::get('LDAP_TREE', '');

        $conn = @ldap_connect("ldap://{$host}:{$port}");
        if (!$conn) {
            return null;
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        if (!@ldap_bind($conn, $user, $pass)) {
            return null;
        }

        $filter  = '(sAMAccountName=' . ldap_escape($cedula, '', LDAP_ESCAPE_FILTER) . ')';
        $result  = ldap_search($conn, $tree, $filter, ['cn', 'mail', 'telephonenumber', 'department', 'title']);
        $entries = ldap_get_entries($conn, $result);

        if ($entries['count'] < 1) {
            return null;
        }

        $dn = $entries[0]['dn'];
        if (!@ldap_bind($conn, $dn, $password)) {
            return null;
        }

        $e = $entries[0];
        return [
            'nombre'       => $e['cn'][0] ?? $cedula,
            'email'        => $e['mail'][0] ?? '',
            'telefono'     => $e['telephonenumber'][0] ?? '',
            'departamento' => $e['department'][0] ?? '',
            'titulo'       => $e['title'][0] ?? '',
        ];
    }
}
