@extends('install.layout', ['step' => 'migrate'])

@section('content')
    <h1 class="text-lg font-bold mb-4">第3步：初始化数据库</h1>

    <p class="text-sm text-gray-600 mb-6">
        即将执行数据库迁移，并写入积分配置、消息模板的默认值。
    </p>

    <form method="POST" action="{{ route('install.migrate.store') }}">
        @csrf
        <button type="submit" class="w-full py-2 rounded bg-amber-600 text-white font-medium hover:bg-amber-700">
            开始初始化
        </button>
    </form>
@endsection
