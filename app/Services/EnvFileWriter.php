<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * 对应07文档第2步"数据库配置"："写入.env对应的DB_*配置项（写入需注意.env文件权限，
 * 写完后需要让新配置在本次请求内生效）"。
 *
 * 目标文件路径可通过构造函数注入，默认是项目根目录的.env——测试时必须换成临时文件，
 * 绝不能让自动化测试真的去改写开发机上的.env（里面是真实的APP_KEY/数据库密码等配置）。
 */
class EnvFileWriter
{
    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('.env');
    }

    /**
     * 更新（或新增）.env里的若干key，已存在的key原地替换，不存在的追加到文件末尾。
     *
     * @param  array<string, string>  $values
     */
    public function update(array $values): void
    {
        $content = File::exists($this->path) ? File::get($this->path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->quoteIfNeeded($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content = rtrim($content)."\n".$line."\n";
            }
        }

        File::put($this->path, $content);

        // 让新值在本次请求内立即生效，不要求重启PHP-FPM/queue worker
        foreach ($values as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    protected function quoteIfNeeded(string $value): string
    {
        if ($value === '' || preg_match('/\s|["\']/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}
