<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceNoteRequest;
use App\Http\Requests\UpdateServiceNoteRequest;
use App\Models\Contact;
use App\Models\NoteType;
use App\Models\ServiceNote;
use App\Support\ContactPermissions;
use App\Support\ImeiStaffAudit;
use App\Support\RepairServiceNoteBodyParser;
use App\Support\RepairServiceNoteTemplate;
use App\Support\ServiceNoteAttachmentPreview;
use App\Support\ServiceNoteAttachmentStorage;
use App\Support\ServiceNoteNumberAssigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceNoteController extends Controller
{
    public function create(Contact $contact): View
    {
        $contact->load('relatedContact');

        return view('contacts.service-notes.form', $this->serviceNoteFormData($contact, null));
    }

    public function store(StoreServiceNoteRequest $request, Contact $contact): RedirectResponse
    {
        $noteNumber = ServiceNoteNumberAssigner::nextNumber();

        $note = ServiceNote::query()->create([
            'contact_id' => $contact->id,
            'primary_service_note_id' => $request->validated('primary_service_note_id'),
            'note_number' => $noteNumber,
            'note_type_id' => (int) $request->validated('note_type_id'),
            'status' => $request->validated('status'),
            'heading' => trim($request->validated('heading')),
            'body' => $this->normalizedNoteBody($request->validated('body')),
            'created_by' => $request->user()->id,
            'staff' => ImeiStaffAudit::appendEmail('', (string) $request->user()->email),
        ]);

        if ($request->hasFile('attachment')) {
            ServiceNoteAttachmentStorage::store($note, $request->file('attachment'));
        }

        return redirect()
            ->route('contacts.show', ['contact' => $contact, 'note' => $note->id])
            ->with('message', 'Service note '.$note->formattedNoteNumber().' saved.');
    }

    public function edit(Request $request, ServiceNote $serviceNote): View
    {
        abort_unless(ContactPermissions::canEditServiceNote($request->user(), $serviceNote), 403);

        $serviceNote->load(['contact.relatedContact', 'noteType', 'notesLinkingHere']);

        return view('contacts.service-notes.form', $this->serviceNoteFormData($serviceNote->contact, $serviceNote));
    }

    public function update(UpdateServiceNoteRequest $request, ServiceNote $serviceNote): RedirectResponse
    {
        abort_unless(ContactPermissions::canEditServiceNote($request->user(), $serviceNote), 403);

        $serviceNote->update([
            'note_type_id' => (int) $request->validated('note_type_id'),
            'status' => $request->validated('status'),
            'heading' => trim($request->validated('heading')),
            'body' => trim($request->validated('body')),
            'primary_service_note_id' => $request->validated('primary_service_note_id'),
            'staff' => ImeiStaffAudit::appendEmail((string) $serviceNote->staff, (string) $request->user()->email),
        ]);

        if ($request->boolean('remove_attachment')) {
            ServiceNoteAttachmentStorage::deleteFile($serviceNote);
        }

        if ($request->hasFile('attachment')) {
            ServiceNoteAttachmentStorage::store($serviceNote, $request->file('attachment'));
        }

        return redirect()
            ->route('contacts.show', ['contact' => $serviceNote->contact_id, 'note' => $serviceNote->id])
            ->with('message', 'Service note '.$serviceNote->formattedNoteNumber().' updated.');
    }

    public function destroy(Request $request, ServiceNote $serviceNote): RedirectResponse
    {
        abort_unless(ContactPermissions::canDeleteServiceNote($request->user(), $serviceNote), 403);

        $contactId = $serviceNote->contact_id;
        $label = $serviceNote->formattedNoteNumber();

        if ($serviceNote->attachment_path !== null && $serviceNote->attachment_path !== '') {
            Storage::disk(ServiceNoteAttachmentStorage::DISK)->delete($serviceNote->attachment_path);
        }

        $serviceNote->delete();

        return redirect()
            ->route('contacts.show', $contactId)
            ->with('message', 'Service note '.$label.' deleted.');
    }

    public function print(Request $request, ServiceNote $serviceNote): View
    {
        abort_unless($request->user() !== null, 403);

        $serviceNote->load(['contact.relatedContact', 'noteType']);

        if (RepairServiceNoteTemplate::isRepairTypeName($serviceNote->noteType?->name ?? '')) {
            return view('contacts.service-notes.print-repair', [
                'note' => $serviceNote,
                'contact' => $serviceNote->contact,
                'fields' => RepairServiceNoteBodyParser::parse($serviceNote->body),
            ]);
        }

        return view('contacts.service-notes.print', [
            'note' => $serviceNote,
            'contact' => $serviceNote->contact,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceNoteFormData(Contact $contact, ?ServiceNote $note): array
    {
        $repairNoteTypeId = NoteType::query()
            ->where('name', RepairServiceNoteTemplate::TYPE_NAME)
            ->value('id');

        $selectedRelatedId = (int) old(
            'primary_service_note_id',
            request()->query('primary_service_note_id', $note?->primary_service_note_id ?? 0),
        );

        $selectedRelatedNote = $selectedRelatedId > 0
            ? ServiceNote::query()
                ->where('contact_id', $contact->id)
                ->whereKey($selectedRelatedId)
                ->first()
            : null;

        $relatedNotesForSelect = ServiceNote::query()
            ->where('contact_id', $contact->id)
            ->when($note, fn ($query) => $query->whereKeyNot($note->id))
            ->with('noteType')
            ->orderByDesc('noted_at')
            ->orderByDesc('id')
            ->get();

        return [
            'contact' => $contact,
            'note' => $note,
            'noteTypes' => NoteType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'canEdit' => true,
            'repairNoteTypeId' => $repairNoteTypeId !== null ? (int) $repairNoteTypeId : null,
            'repairNoteHeadingTemplate' => RepairServiceNoteTemplate::HEADING,
            'repairNoteBodyTemplate' => RepairServiceNoteTemplate::BODY,
            'selectedRelatedNote' => $selectedRelatedNote,
            'relatedNotesForSelect' => $relatedNotesForSelect,
        ];
    }

    private function normalizedNoteBody(mixed $body): ?string
    {
        $normalized = trim((string) ($body ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    public function attachment(Request $request, ServiceNote $serviceNote): StreamedResponse
    {
        $this->authorizeAttachmentAccess($request, $serviceNote);

        return Storage::disk(ServiceNoteAttachmentStorage::DISK)->download(
            $serviceNote->attachment_path,
            $serviceNote->attachment_original_name ?? 'attachment',
        );
    }

    public function preview(Request $request, ServiceNote $serviceNote): Response
    {
        $this->authorizeAttachmentAccess($request, $serviceNote);
        abort_unless(ServiceNoteAttachmentPreview::canPreview($serviceNote), 404);

        $filename = $serviceNote->attachment_original_name ?? 'attachment';

        return Storage::disk(ServiceNoteAttachmentStorage::DISK)->response(
            $serviceNote->attachment_path,
            $filename,
            [
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ],
        );
    }

    private function authorizeAttachmentAccess(Request $request, ServiceNote $serviceNote): void
    {
        abort_unless($request->user() !== null, 403);
        abort_unless($serviceNote->hasAttachment(), 404);
    }
}
