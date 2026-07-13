@extends('install.layout', ['step' => 'database'])

@section('content')
    <h1 class="text-lg font-bold mb-4">第2步：数据库配置</h1>

    <form method="POST" action="{{ route('install.database.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">数据库主机</label>
            <input type="text" name="host" value="{{ old('host', '127.0.0.1') }}" required
                   class="w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">端口</label>
            <input type="number" name="port" value="{{ old('port', 3306) }}" required
                   class="w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">数据库名</label>
            <input type="text" name="database" value="{{ old('database') }}" required
                   class="w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">用户名</label>
            <input type="text" name="username" value="{{ old('username') }}" required
                   class="w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">密码</label>
            <input type="password" name="password" class="w-full rounded border-gray-300">
        </div>

        <button type="submit" class="w-full py-2 rounded bg-amber-600 text-white font-medium hover:bg-amber-700">
            测试连接并继续
        </button>
    </form>
@endsection
