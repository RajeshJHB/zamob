<div @class([
    'flex flex-wrap items-center gap-x-4 gap-y-1 text-gray-600',
    $class ?? null,
])>
    <span>
        <span class="text-gray-500">Start Time:</span>
        <span class="font-medium text-gray-800 whitespace-nowrap">{{ $note->created_at->format('Y-m-d H:i') }}</span>
    </span>
    <span>
        <span class="text-gray-500">Last Time:</span>
        <span class="font-medium text-gray-800 whitespace-nowrap">{{ $note->updated_at->format('Y-m-d H:i') }}</span>
    </span>
</div>
