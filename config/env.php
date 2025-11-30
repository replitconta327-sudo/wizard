<?php
/**
 * Carregador de variáveis de ambiente
 */
class Env {
    private static $loaded = false;
    private static $vars = [];
    
    public static function load() {
        if (self::$loaded) return;
        
        $envFile = __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Ignorar comentários
                if (strpos(trim($line), '#') === 0) continue;
                
                // Parse da variável
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remover aspas
                    if (preg_match('/^".*"$/', $value)) {
                        $value = substr($value, 1, -1);
                    } elseif (preg_match("/^'.*'$/", $value)) {
                        $value = substr($value, 1, -1);
                    }
                    
                    self::$vars[$key] = $value;
                    $_ENV[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        if (!self::$loaded) self::load();
        
        // Verificar variáveis de ambiente do sistema
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // Verificar variáveis carregadas do .env
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        
        return $default;
    }
    
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
    
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

// Carregar automaticamente ao incluir este arquivo
Env::load();
?>
