<x-filament-widgets::widget>
    @php($items = $this->getMissingSetupItems())

    @if (count($items))
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950">
            <p class="font-medium text-amber-800 dark:text-amber-200">请先完成机器人和域名配置</p>
            <ul class="mt-2 list-disc pl-5 text-sm text-amber-700 dark:text-amber-300">
                @foreach ($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-widgets::widget>
