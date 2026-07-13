<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安装向导 - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12">
    <div class="w-full max-w-xl bg-white rounded-xl shadow p-8">
        <div class="flex justify-between mb-8 text-sm">
            @foreach (['environment' => '环境检测', 'database' => '数据库配置', 'migrate' => '初始化数据库', 'admin' => '管理员账号'] as $key => $label)
                <div class="flex-1 text-center {{ ($step ?? '') === $key ? 'font-bold text-amber-600' : 'text-gray-400' }}">
                    {{ $label }}
                </div>
            @endforeach
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
