<?php

namespace App\Services;

use App\Models\PointsConfig;
use Illuminate\Support\Collection;

/**
 * points_config（key-value）的读取封装，对应01文档 points_config 表。
 * 一个请求/一次机器人更新处理周期内缓存一份，避免每次 get() 都查库。
 */
class PointsConfigRepository
{
    protected ?Collection $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return $value === null ? $default : (int) $value;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        return $value === null ? $default : (float) $value;
    }

    /**
     * @return array<mixed>
     */
    public function getJson(string $key, array $default = []): array
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    public function forget(): void
    {
        $this->cache = null;
    }

    protected function all(): Collection
    {
        return $this->cache ??= PointsConfig::query()->pluck('value', 'key');
    }
}
