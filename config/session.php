<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

session_set_save_handler(
    static function (string $savePath, string $sessionName): bool {
        return true;
    },
    static function (): bool {
        return true;
    },
    static function (string $id): string {
        try {
            $stmt = db()->prepare(
                'SELECT session_data FROM php_sessions WHERE session_id = :id AND expires_at > NOW() LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return is_array($row) ? (string) $row['session_data'] : '';
        } catch (Throwable) {
            return '';
        }
    },
    static function (string $id, string $data): bool {
        try {
            $lifetime = (int) ini_get('session.gc_maxlifetime') ?: 1440;
            $stmt = db()->prepare(
                'INSERT INTO php_sessions (session_id, session_data, expires_at)
                 VALUES (:id, :data, DATE_ADD(NOW(), INTERVAL :lt SECOND))
                 ON DUPLICATE KEY UPDATE
                   session_data = VALUES(session_data),
                   expires_at   = VALUES(expires_at)'
            );
            $stmt->execute([':id' => $id, ':data' => $data, ':lt' => $lifetime]);
            return true;
        } catch (Throwable) {
            return false;
        }
    },
    static function (string $id): bool {
        try {
            db()->prepare('DELETE FROM php_sessions WHERE session_id = :id')
               ->execute([':id' => $id]);
            return true;
        } catch (Throwable) {
            return false;
        }
    },
    static function (int $maxLifetime): int|false {
        try {
            $stmt = db()->prepare('DELETE FROM php_sessions WHERE expires_at < NOW()');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Throwable) {
            return false;
        }
    }
);
