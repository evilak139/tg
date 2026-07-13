<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $defaultInstalledLockPath;

    protected function setUp(): void
    {
        parent::setUp();

        // EnsureInstalled中间件（对应07文档）默认要求存在安装锁文件才放行普通路由。
        // 绝大多数测试都假定系统"已安装"，这里给每个测试一个独立的临时锁文件，
        // 完全不碰真实的storage/app/installed.lock。InstallWizardTest测试"未安装"
        // 场景时会自己把这个config重新指向别的临时路径/删掉。
        $this->defaultInstalledLockPath = sys_get_temp_dir().'/testing_installed_'.uniqid().'.lock';
        file_put_contents($this->defaultInstalledLockPath, 'test');
        config(['install.lock_path' => $this->defaultInstalledLockPath]);
    }

    protected function tearDown(): void
    {
        if (isset($this->defaultInstalledLockPath) && file_exists($this->defaultInstalledLockPath)) {
            @unlink($this->defaultInstalledLockPath);
        }

        parent::tearDown();
    }
}
