<?php
/**
 * ============================================================
 * Seguridad.php  —  Utilidades y validaciones de seguridad
 * Sistema Académico JAEGASA
 * ============================================================
 */

class Seguridad
{
    /**
     * Valida que una contraseña cumpla con los requisitos mínimos de seguridad:
     *   - Mínimo 8 caracteres
     *   - Al menos 1 letra mayúscula (A-Z)
     *   - Al menos 1 número (0-9)
     *   - Al menos 1 símbolo / carácter especial
     *
     * @param string $password
     * @return array ['ok' => bool, 'errores' => string[]]
     */
    public static function validarPasswordSegura(string $password): array
    {
        $errores = [];

        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'La contraseña debe incluir al menos una letra mayúscula (A-Z).';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'La contraseña debe incluir al menos un número (0-9).';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errores[] = 'La contraseña debe incluir al menos un símbolo o carácter especial (!@#$%^&*...).';
        }

        return [
            'ok'      => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Genera una contraseña aleatoria segura que cumple estrictamente con los requisitos
     * (mínimo 8 caracteres, 1 mayúscula, 1 número y 1 símbolo).
     *
     * @param int $longitud
     * @return string
     */
    public static function generarPasswordSegura(int $longitud = 10): string
    {
        $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minusculas = 'abcdefghijkmnopqrstuvwxyz';
        $numeros    = '23456789';
        $simbolos   = '!@#$%*-_';

        $pass = [
            $mayusculas[random_int(0, strlen($mayusculas) - 1)],
            $minusculas[random_int(0, strlen($minusculas) - 1)],
            $numeros[random_int(0, strlen($numeros) - 1)],
            $simbolos[random_int(0, strlen($simbolos) - 1)],
        ];

        $todos = $mayusculas . $minusculas . $numeros . $simbolos;
        for ($i = count($pass); $i < max(8, $longitud); $i++) {
            $pass[] = $todos[random_int(0, strlen($todos) - 1)];
        }

        shuffle($pass);
        return implode('', $pass);
    }

    /**
     * Genera un enlace de WhatsApp Web para enviar credenciales a un teléfono.
     *
     * @param string $telefono
     * @param string $nombre
     * @param string $usuario
     * @param string $password
     * @param string $rol
     * @return string|null
     */
    public static function generarUrlWhatsApp(string $telefono, string $nombre, string $usuario, string $password, string $rol = 'Usuario'): ?string
    {
        $telLimpio = preg_replace('/\D/', '', $telefono);
        if ($telLimpio === '') {
            return null;
        }

        if (strlen($telLimpio) === 8) {
            $telLimpio = '591' . $telLimpio;
        }

        $mensaje = "Hola {$nombre}, bienvenido/a al Sistema Académico JAEGASA.\n"
                . "Se ha registrado tu cuenta como {$rol}.\n\n"
                . "📌 Tus credenciales de acceso son:\n"
                . "👤 Usuario: {$usuario}\n"
                . "🔑 Contraseña: {$password}\n\n"
                . "INGRESE AQUÍ PARA ACCEDER AL SISTEMA:\n"
                . "http://localhost:3000/index.php?c=login\n\n";

        return 'https://api.whatsapp.com/send?phone=' . $telLimpio . '&text=' . urlencode($mensaje);
    }

    /**
     * Convierte una cadena de texto a MAYÚSCULAS utilizando UTF-8 (soporta Ñ, acentos, etc.).
     *
     * @param string|null $texto
     * @return string
     */
    public static function mayuscula(?string $texto): string
    {
        if ($texto === null) {
            return '';
        }
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($texto, 'UTF-8')
            : strtoupper($texto);
    }

    /**
     * Recorre un array recursivamente y convierte sus elementos de tipo string a MAYÚSCULAS,
     * excluyendo las claves especificadas (por ejemplo: passwords, tokens, etc.).
     *
     * @param array $datos
     * @param array $excepto Claves a ignorar
     * @return array
     */
    public static function normalizarMayusculas(array $datos, array $excepto = ['password', 'clave', 'contrasena', 'confirmarPassword']): array
    {
        foreach ($datos as $k => $v) {
            if (in_array($k, $excepto, true)) {
                continue;
            }
            if (is_string($v)) {
                $datos[$k] = self::mayuscula($v);
            } elseif (is_array($v)) {
                $datos[$k] = self::normalizarMayusculas($v, $excepto);
            }
        }
        return $datos;
    }
}

