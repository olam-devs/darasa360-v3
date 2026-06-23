<footer class="sticky bottom-0 z-30 bg-white border-t border-gray-200">
    <div class="w-full px-4 py-2.5">
        <div class="flex flex-col gap-1 text-center text-xs text-gray-400 sm:flex-row sm:items-center sm:justify-between sm:text-left">
            <p class="text-gray-600 font-medium">
                &copy; {{ date('Y') }}
                @if(optional($settings)->school_name)
                    {{ \Illuminate\Support\Str::limit($settings->school_name, 48) }}
                @else
                    Darasa Finance
                @endif
            </p>
            <span class="inline-flex items-center gap-1.5 mx-auto sm:mx-0">
                <span class="text-gray-400">Powered by</span>
                <img src="/darasa360-logo.png" alt="Darasa360" class="h-4 w-4 object-contain rounded-full border border-gray-200">
                <strong class="text-blue-600">Darasa<span class="font-normal text-gray-500">360</span></strong>
            </span>
        </div>
    </div>
</footer>
