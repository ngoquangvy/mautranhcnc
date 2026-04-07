<?php
if (!defined('MT_CNC_AUTH')) exit('Access Denied');
/**
 * FileCache — Server-side Page Cache
 * ─────────────────────────────────
 * Cache file lưu tại  /cache/*.json
 * Cấu hình bật/tắt   /cache/cfg.json
 *
 * Dùng:
 *   require_once '../includes/cache.php';
 *   $data = FileCache::get('home_index');
 *   if (!$data) { // query DB; FileCache::set('home_index', $data, FileCache::HOME_TTL); }
 */
class FileCache
{
    // ══════════════════════════════════════════════
    //  TTL SETTINGS
    // ══════════════════════════════════════════════
    const HOME_TTL  = 3600;       // /home index: 1 giờ
    const ADMIN_TTL = 31536000;   // /admin index: 1 năm (dùng nút toggle để làm mới)
    // ══════════════════════════════════════════════

    private static function dir(): string
    {
        $d = dirname(__DIR__) . '/cache/';
        if (!is_dir($d)) {
            mkdir($d, 0755, true);
        }
        return $d;
    }

    private static function cfgFile(): string
    {
        return self::dir() . 'cfg.json';
    }

    private static function cacheFile(string $key): string
    {
        // Chỉ dùng ký tự an toàn cho tên file
        return self::dir() . preg_replace('/[^a-z0-9_]/', '_', $key) . '.json';
    }

    // ─────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────

    /** Trả về true nếu cache đang BẬT */
    public static function isEnabled(): bool
    {
        $f = self::cfgFile();
        if (!file_exists($f)) return true; // mặc định: BẬT
        $d = json_decode(file_get_contents($f), true);
        return (bool)($d['enabled'] ?? true);
    }

    /**
     * Đổi trạng thái cache BẬT ↔ TẮT.
     * Khi TẮT → xóa tất cả cache file để lần BẬT tiếp có data mới.
     * @return bool Trạng thái MỚI (true = BẬT, false = TẮT)
     */
    public static function toggle(): bool
    {
        $new = !self::isEnabled();
        file_put_contents(self::cfgFile(), json_encode(['enabled' => $new]));
        if (!$new) {
            self::clearAll(); // tắt cache → xóa cache cũ ngay
        }
        return $new;
    }

    /**
     * Lấy dữ liệu từ cache.
     * @return array|null  null nếu: cache tắt / không tồn tại / hết TTL
     */
    public static function get(string $key): ?array
    {
        if (!self::isEnabled()) return null;

        $f = self::cacheFile($key);
        if (!file_exists($f)) return null;

        $raw = json_decode(file_get_contents($f), true);
        if (!$raw) return null;

        if ((time() - $raw['ts']) > $raw['ttl']) {
            @unlink($f);
            return null;
        }

        return $raw['data'];
    }

    /** Lưu dữ liệu vào cache */
    public static function set(string $key, array $data, int $ttl): void
    {
        if (!self::isEnabled()) return;
        file_put_contents(
            self::cacheFile($key),
            json_encode(
                ['ts' => time(), 'ttl' => $ttl, 'key' => $key, 'data' => $data],
                JSON_UNESCAPED_UNICODE
            )
        );
    }

    /** Xóa tất cả cache file (giữ lại cfg.json) */
    public static function clearAll(): void
    {
        foreach (glob(self::dir() . '*.json') as $f) {
            if (basename($f) !== 'cfg.json') {
                @unlink($f);
            }
        }
    }

    /** Thông tin debug: key + thời gian còn lại */
    public static function info(string $key): array
    {
        $f = self::cacheFile($key);
        if (!file_exists($f)) return ['exists' => false];
        $raw = json_decode(file_get_contents($f), true);
        if (!$raw) return ['exists' => false];
        $age     = time() - $raw['ts'];
        $remains = $raw['ttl'] - $age;
        return [
            'exists'     => true,
            'age_s'      => $age,
            'remains_s'  => max(0, $remains),
            'expires_at' => date('Y-m-d H:i:s', $raw['ts'] + $raw['ttl']),
        ];
    }
}
