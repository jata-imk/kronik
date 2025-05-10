<?php

namespace App\Services;

class ConsoleService
{
    /**
     * Verifica si el sistema operativo es Windows.
     */
    public function isWindows(): bool
    {
        return stripos(PHP_OS_FAMILY, 'Windows') !== false;
    }

    /**
     * Verifica si WSL está disponible en el sistema (Windows).
     */
    public function hasWSL(): bool
    {
        if (!$this->isWindows()) {
            return false;
        }

        exec('where wsl', $output, $code);
        return $code === 0;
    }

    /**
     * Ejecuta un comando desde consola, con soporte para WSL si aplica.
     *
     * @param string $command Comando a ejecutar
     * @param bool $forceWSL Forzar uso de WSL aunque no sea necesario
     * @return array [
     *     'output' => array de salida (stdout + stderr),
     *     'outputCode' => código de salida,
     *     'usedWSL' => bool indicando si se usó WSL
     * ]
     */
    public function run(string $command, bool $forceWSL = false): array
    {
        $useWSL = $this->isWindows() && ($forceWSL || $this->hasWSL());

        if ($useWSL) {
            $command = 'wsl ' . $command;
        }

        // Redirigir stderr a stdout para capturar todo
        $command .= ' 2>&1';

        exec($command, $output, $outputCode);

        return [
            'output' => $output,
            'outputCode' => $outputCode,
            'usedWSL' => $useWSL,
        ];
    }

    /**
     * Ejecuta un comando y devuelve solo el texto plano (stdout + stderr).
     */
    public function runRaw(string $command, bool $forceWSL = false): string
    {
        $result = $this->run($command, $forceWSL);
        return implode("\n", $result['output']);
    }

    /**
     * Ejecuta un comando y lanza una excepción si falla.
     */
    public function runOrFail(string $command, bool $forceWSL = false): array
    {
        $result = $this->run($command, $forceWSL);

        if ($result['outputCode'] !== 0) {
            throw new \RuntimeException(
                "Error ejecutando comando: '{$command}'\n" .
                    "Código: {$result['outputCode']}\n" .
                    "Salida: " . implode("\n", $result['output'])
            );
        }

        return $result;
    }

    /**
     * Corrige la ruta según el entorno donde se va a ejecutar.
     * En Windows puro → usa doble backslash.
     * En WSL/Linux → usa slash normal.
     */
    public function fixPathSeparator(string $path, bool $forceWSL = false): string
    {
        $useWSL = $this->isWindows() && ($forceWSL || $this->hasWSL());

        if ($useWSL || !$this->isWindows()) {
            // WSL o Linux: convertir a slash normal
            return str_replace('\\', '/', $path);
        } else {
            // Windows puro: usar doble backslash escapado
            return str_replace('/', '\\\\', $path);
        }
    }

    /**
     * Normaliza una ruta para que sea válida en el entorno actual.
     *
     * @param string $path Ruta original (puede ser estilo Windows o Linux)
     * @param bool $forceWSL Forzar modo WSL
     * @param bool $checkExists Verifica si la ruta existe en el sistema
     * @return array [
     *     'normalized' => string, // ruta lista para usar en comandos
     *     'exists' => bool         // true si la ruta existe (si se pidió)
     * ]
     */
    public function normalizePath(string $path, bool $forceWSL = false, bool $checkExists = false): array
    {
        $useWSL = $this->isWindows() && ($forceWSL || $this->hasWSL());
        $normalized = $path;

        if ($useWSL) {
            // Detecta rutas estilo Windows: C:\Users\...
            if (preg_match('/^([a-zA-Z]):[\\\\\/](.*)/', $path, $matches)) {
                $drive = strtolower($matches[1]);
                $rest = str_replace(['\\', '/'], '/', $matches[2]);
                $normalized = "/mnt/{$drive}/{$rest}";
            } else {
                // Solo corregir separadores
                $normalized = str_replace('\\', '/', $path);
            }
        } elseif ($this->isWindows()) {
            // Windows puro: cambiar slashes y escapar
            $normalized = str_replace('/', '\\\\', $path);
        } else {
            // Linux/macOS: solo asegurar slashes
            $normalized = str_replace('\\', '/', $path);
        }

        $exists = true;

        if ($checkExists) {
            if ($useWSL) {
                // Ejecutar 'wsl test -e <ruta>'
                $command = 'wsl test -e ' . escapeshellarg($normalized);
                exec($command, $_, $code);
                $exists = $code === 0;
            } else {
                $exists = file_exists($normalized);
            }
        }

        return [
            'normalized' => $normalized,
            'exists' => $exists
        ];
    }
}
