<?php

namespace App\Support;

class PhpCli
{
    public static function path(): string
    {
        if ($override = config('backup.php_cli_path')) {
            return $override;
        }

        // PHP_BINARY on production points outside open_basedir (e.g. /opt/plesk/php/...),
        // so is_file() on it triggers an open_basedir warning. It comes straight from the
        // running SAPI, so it's already known-good — use it directly without validating.
        if (PHP_OS_FAMILY !== 'Windows') {
            $binary = PHP_BINARY ?: 'php';

            // Under PHP-FPM/CGI (web requests), PHP_BINARY points to the fpm/cgi
            // binary, not the CLI one. PHP_BINDIR is a compile-time constant
            // shared across SAPIs, so it still points at the bin/ dir holding
            // the CLI binary.
            if (str_contains($binary, 'fpm') || str_contains($binary, 'cgi')) {
                $binary = PHP_BINDIR . '/php';
            }

            return $binary;
        }

        $candidate = 'C:\laragon\bin\php\php-8.2.5-Win32-vs16-x64\php.exe';
        if (is_file($candidate)) {
            return $candidate;
        }

        return PHP_BINARY ?: 'php.exe';
    }

    public static function binDir(): string
    {
        return dirname(static::path());
    }
}
