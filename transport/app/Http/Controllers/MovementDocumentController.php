<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\MovementDocument;
use App\Services\MovementDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovementDocumentController extends Controller
{
    public function preview(Request $r, Movement $movement, string $type): View
    {
        abort_unless($r->user()->canAccessCompany((int) $movement->company_id), 403);
        abort_unless(in_array($type, MovementDocumentService::TYPES, true), 404);

        return view('documents.movement', ['movement' => $movement->load(['company', 'customer', 'actions.site', 'actions.driver', 'actions.vehicle', 'items.accessories', 'photos']), 'type' => $type]);
    }

    public function issue(Request $r, Movement $movement, string $type, MovementDocumentService $service): RedirectResponse
    {
        Gate::authorize('user.document.issue');
        abort_unless($r->user()->canAccessCompany((int) $movement->company_id), 403);
        $doc = $service->issue($movement, $type, $r->user());

        return redirect()->route('documents.download', $doc);
    }

    public function download(Request $r, MovementDocument $document): StreamedResponse
    {
        abort_unless($r->user()->canAccessCompany((int) $document->movement->company_id), 403);

        return Storage::disk($document->disk)->download($document->path, $document->original_filename);
    }
}
