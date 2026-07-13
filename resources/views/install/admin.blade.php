@extends('install.layout', ['step' => 'admin'])

@section('content')
    <h1 class="text-lg font-bold mb-4">第4步：创建超级管理员账号</h1>

    <form method="POST" action="{{ route('install.admin.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">用户名</label>
            <input type="text" name="username" value="{{ old('username') }}" required
                   class="w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">密码</label>
            <input type="password" name="password" required class="w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">确认密码</label>
            <input type="password" name="password_confirmation" required class="w-full rounded border-gray-300">
        </div>

        <button type="submit" class="w-full py-2 rounded bg-amber-600 text-white font-medium hover:bg-amber-700">
            完成安装
        </button>
    </form>
@endsection
