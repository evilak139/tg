@extends('install.layout', ['step' => 'environment'])

@section('content')
    <h1 class="text-lg font-bold mb-4">第1步：环境检测</h1>

    <ul class="space-y-2 mb-6">
        @foreach ($checks as $check)
            <li class="flex items-center justify-between p-2 rounded {{ $check['pass'] ? 'bg-green-50' : 'bg-red-50' }}">
                <span>{{ $check['label'] }}</span>
                <span class="text-sm {{ $check['pass'] ? 'text-green-700' : 'text-red-700' }}">
                    {{ $check['pass'] ? '通过' : '未通过' }}（{{ $check['detail'] }}）
                </span>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('install.environment.continue') }}">
        @csrf
        <button type="submit" class="w-full py-2 rounded bg-amber-600 text-white font-medium hover:bg-amber-700">
            下一步
        </button>
    </form>
@endsection
