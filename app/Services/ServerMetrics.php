<?php

namespace App\Services;

class ServerMetrics
{
    /**
     * Devuelve una captura completa de las métricas del servidor.
     */
    public function snapshot(): array
    {
        return [
            'hostname' => gethostname() ?: 'No disponible',
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php_version' => PHP_VERSION,
            'disk' => $this->disk(),
            'memory' => $this->memory(),
            'cpu_pct' => $this->cpuUsage(),
            'cpu_temp_c' => null,
            'uptime_s' => $this->uptime(),
            'at' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtiene el espacio total, libre y el porcentaje usado del disco.
     */
    private function disk(): array
    {
        $path = PHP_OS_FAMILY === 'Windows' ? 'C:\\' : '/';

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return [
                'total_gb' => null,
                'free_gb' => null,
                'used_pct' => null,
            ];
        }

        return [
            'total_gb' => round($total / 1073741824, 2),
            'free_gb' => round($free / 1073741824, 2),
            'used_pct' => round((($total - $free) / $total) * 100, 1),
        ];
    }

    /**
     * Obtiene información de la memoria RAM en Windows.
     */
    private function memory(): ?array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $command = <<<'POWERSHELL'
(Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize,FreePhysicalMemory | ConvertTo-Json -Compress)
POWERSHELL;

        $output = $this->runPowerShell($command);

        if ($output === null) {
            return null;
        }

        $data = json_decode($output, true);

        if (
            !is_array($data) ||
            empty($data['TotalVisibleMemorySize']) ||
            !isset($data['FreePhysicalMemory'])
        ) {
            return null;
        }

        $totalKb = (float) $data['TotalVisibleMemorySize'];
        $freeKb = (float) $data['FreePhysicalMemory'];

        if ($totalKb <= 0) {
            return null;
        }

        return [
            'total_gb' => round($totalKb / 1048576, 2),
            'free_gb' => round($freeKb / 1048576, 2),
            'used_pct' => round((($totalKb - $freeKb) / $totalKb) * 100, 1),
        ];
    }

    /**
     * Obtiene el porcentaje promedio de uso del procesador.
     */
    private function cpuUsage(): ?float
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $command = <<<'POWERSHELL'
(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average
POWERSHELL;

        $output = $this->runPowerShell($command);

        if ($output === null) {
            return null;
        }

        $value = trim($output);

        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 1);
    }

    /**
     * Obtiene los segundos transcurridos desde el inicio de Windows.
     */
    private function uptime(): ?int
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $command = <<<'POWERSHELL'
[int]((Get-Date) - (Get-CimInstance Win32_OperatingSystem).LastBootUpTime).TotalSeconds
POWERSHELL;

        $output = $this->runPowerShell($command);

        if ($output === null) {
            return null;
        }

        $value = trim($output);

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Ejecuta un comando de PowerShell y devuelve su salida.
     */
    private function runPowerShell(string $command): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        if (!function_exists('mb_convert_encoding')) {
            return null;
        }

        $utf16Command = mb_convert_encoding(
            $command,
            'UTF-16LE',
            'UTF-8'
        );

        $encodedCommand = base64_encode($utf16Command);

        $shellCommand = sprintf(
            'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand %s 2>NUL',
            escapeshellarg($encodedCommand)
        );

        $output = @shell_exec($shellCommand);

        if ($output === null || trim($output) === '') {
            return null;
        }

        return trim($output);
    }
}