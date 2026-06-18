const modal = document.getElementById('service-note-attachment-preview-modal');
const backdrop = document.getElementById('service-note-attachment-preview-backdrop');
const closeButton = document.getElementById('service-note-attachment-preview-close');
const titleEl = document.getElementById('service-note-attachment-preview-title');
const loadingEl = document.getElementById('service-note-attachment-preview-loading');
const errorEl = document.getElementById('service-note-attachment-preview-error');
const bodyEl = document.getElementById('service-note-attachment-preview-body');
const iframeEl = document.getElementById('service-note-attachment-preview-iframe');
const imageEl = document.getElementById('service-note-attachment-preview-image');
const officeEl = document.getElementById('service-note-attachment-preview-office');
const filenameEl = document.getElementById('service-note-attachment-preview-filename');
const downloadEl = document.getElementById('service-note-attachment-preview-download');

let activeDownloadUrl = null;
let pptxPreviewInstance = null;

function resetPreviewSurfaces() {
    loadingEl.classList.add('hidden');
    errorEl.classList.add('hidden');
    bodyEl.classList.add('hidden');
    iframeEl.classList.add('hidden');
    imageEl.classList.add('hidden');
    officeEl.classList.add('hidden');
    iframeEl.removeAttribute('src');
    imageEl.removeAttribute('src');
    officeEl.innerHTML = '';
    errorEl.textContent = '';

    if (pptxPreviewInstance !== null) {
        pptxPreviewInstance.destroy();
        pptxPreviewInstance = null;
    }
}

function showError(message) {
    resetPreviewSurfaces();
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
}

function openModal() {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeModal() {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    resetPreviewSurfaces();
    activeDownloadUrl = null;
}

async function fetchAttachmentBuffer(previewUrl) {
    const response = await fetch(previewUrl, {
        credentials: 'same-origin',
        headers: {
            Accept: '*/*',
        },
    });

    if (!response.ok) {
        throw new Error('Could not load the attachment for preview.');
    }

    return response.arrayBuffer();
}

async function renderWordPreview(arrayBuffer) {
    const mammoth = await import('mammoth');

    return mammoth.default.convertToHtml({ arrayBuffer })
        .then((result) => {
            officeEl.innerHTML = result.value !== ''
                ? result.value
                : '<p class="text-gray-600">This document has no visible content.</p>';

            if (result.messages.length > 0) {
                officeEl.insertAdjacentHTML(
                    'beforeend',
                    '<p class="mt-4 text-xs text-gray-500">Some formatting may not appear in preview.</p>',
                );
            }
        });
}

async function renderExcelPreview(arrayBuffer) {
    const XLSX = await import('xlsx');
    const workbook = XLSX.read(arrayBuffer, { type: 'array' });
    const sheetNames = workbook.SheetNames;

    if (sheetNames.length === 0) {
        officeEl.innerHTML = '<p class="text-gray-600">This spreadsheet has no sheets.</p>';

        return;
    }

    const fragments = sheetNames.map((sheetName) => {
        const sheet = workbook.Sheets[sheetName];
        const tableHtml = XLSX.utils.sheet_to_html(sheet, { id: `sheet-${sheetName}` });

        return `
            <section class="mb-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">${sheetName}</h3>
                <div class="overflow-x-auto border border-gray-200 rounded">${tableHtml}</div>
            </section>
        `;
    });

    officeEl.innerHTML = fragments.join('');
}

async function renderPowerPointPreview(arrayBuffer) {
    const { init: initPptxPreview } = await import('pptx-preview');
    officeEl.innerHTML = '<div id="service-note-pptx-preview-host" class="w-full"></div>';

    const host = document.getElementById('service-note-pptx-preview-host');
    if (!host) {
        throw new Error('Could not prepare PowerPoint preview.');
    }

    pptxPreviewInstance = initPptxPreview(host, {
        width: Math.min(host.clientWidth || 960, 960),
    });

    await pptxPreviewInstance.preview(arrayBuffer);
}

async function loadPreview(previewUrl, previewKind, filename, downloadUrl) {
    resetPreviewSurfaces();
    openModal();

    titleEl.textContent = filename || 'Attachment preview';
    filenameEl.textContent = filename || '';
    downloadEl.href = downloadUrl;
    activeDownloadUrl = downloadUrl;

    loadingEl.classList.remove('hidden');

    try {
        if (previewKind === 'image') {
            loadingEl.classList.add('hidden');
            bodyEl.classList.remove('hidden');
            imageEl.alt = filename || 'Attachment preview';
            imageEl.src = previewUrl;
            imageEl.classList.remove('hidden');

            return;
        }

        if (previewKind === 'pdf' || previewKind === 'text') {
            loadingEl.classList.add('hidden');
            bodyEl.classList.remove('hidden');
            iframeEl.src = previewUrl;
            iframeEl.classList.remove('hidden');

            return;
        }

        const arrayBuffer = await fetchAttachmentBuffer(previewUrl);

        loadingEl.classList.add('hidden');
        bodyEl.classList.remove('hidden');
        officeEl.classList.remove('hidden');

        if (previewKind === 'word') {
            await renderWordPreview(arrayBuffer);

            return;
        }

        if (previewKind === 'excel') {
            await renderExcelPreview(arrayBuffer);

            return;
        }

        if (previewKind === 'powerpoint') {
            await renderPowerPointPreview(arrayBuffer);

            return;
        }

        showError('Preview is not available for this file type.');
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Could not preview this attachment.';
        showError(message);
    }
}

function handlePreviewClick(event) {
    const button = event.target.closest('[data-service-note-preview]');
    if (!button) {
        return;
    }

    const previewUrl = button.dataset.previewUrl;
    const previewKind = button.dataset.previewKind;
    const filename = button.dataset.previewFilename || '';
    const downloadUrl = button.dataset.downloadUrl || previewUrl.replace(/\/preview$/, '');

    if (!previewUrl || !previewKind) {
        return;
    }

    event.preventDefault();
    loadPreview(previewUrl, previewKind, filename, downloadUrl);
}

if (modal) {
    document.addEventListener('click', handlePreviewClick);

    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}
