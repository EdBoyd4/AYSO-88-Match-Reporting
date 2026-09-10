<?php
declare(strict_types=1);

/**
 * Tiny dependency-free .env reader. Returns a key/value array; it does NOT
 * touch $_ENV / getenv so nothing else in the process is affected. gss88 has
 * no phpdotenv of its own and we don't want its config to depend on the login
 * library being composer-installed.
 */
final class Gss88Env
{
    /**
     * @return array<string, string>
     */
    public static function load(string $path): array
    {
        $out = [];
        if (!is_readable($path)) {
            return $out;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if (strlen($val) >= 2
                && ($val[0] === '"' || $val[0] === "'")
                && $val[strlen($val) - 1] === $val[0]) {
                $val = substr($val, 1, -1);
            }
            if ($key !== '') {
                $out[$key] = $val;
            }
        }

        return $out;
    }
}
