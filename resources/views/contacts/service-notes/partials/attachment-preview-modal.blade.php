@once
    @vite('resources/js/service-note-attachment-preview.js')
@endonce

<div
    id="service-note-attachment-preview-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="service-note-attachment-preview-title"
>
    <div class="absolute inset-0 bg-black/40" id="service-note-attachment-preview-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col border border-gray-200">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 shrink-0">
                <h2 id="service-note-attachment-preview-title" class="text-lg font-bold text-gray-900 truncate pr-4">
                    Attachment preview
                </h2>
                <button
                    type="button"
                    id="service-note-attachment-preview-close"
                    class="text-gray-500 hover:text-gray-800 text-2xl leading-none shrink-0"
                    aria-label="Close"
                >&times;</button>
            </div>
            <div id="service-note-attachment-preview-loading" class="px-6 py-12 text-sm text-gray-500 text-center hidden">
                Loading preview…
            </div>
            <div id="service-note-attachment-preview-error" class="px-6 py-8 text-sm text-red-600 hidden"></div>
            <div id="service-note-attachment-preview-body" class="px-6 py-4 overflow-auto flex-1 min-h-0 hidden">
                <iframe
                    id="service-note-attachment-preview-iframe"
                    class="w-full min-h-[70vh] border border-gray-200 rounded hidden"
                    title="Attachment preview"
                ></iframe>
                <img
                    id="service-note-attachment-preview-image"
                    class="max-w-full h-auto mx-auto rounded border border-gray-200 hidden"
                    alt=""
                >
                <div
                    id="service-note-attachment-preview-office"
                    class="prose prose-sm max-w-none text-gray-900 hidden"
                ></div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 shrink-0">
                <p id="service-note-attachment-preview-filename" class="text-sm text-gray-600 truncate"></p>
                <a
                    id="service-note-attachment-preview-download"
                    href="#"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium shrink-0"
                >
                    Download
                </a>
            </div>
        </div>
    </div>
</div>
