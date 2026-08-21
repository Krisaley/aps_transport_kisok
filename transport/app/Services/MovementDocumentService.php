<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\MovementDocument;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MovementDocumentService
{
    public const TYPES = ['delivery_note', 'collection_note', 'exchange_note', 'yard_receipt', 'condition_report', 'driver_manifest'];

    public function issue(Movement $movement, string $type, User $user): MovementDocument
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
        $movement->load(['company', 'customer', 'actions.site', 'actions.driver', 'actions.vehicle', 'items.accessories', 'photos']);
        $html = view('documents.movement', ['movement' => $movement, 'type' => $type])->render();
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('a4');
        $pdf->render();
        $bytes = $pdf->output();

        return DB::transaction(function () use ($movement, $type, $user, $bytes) {
            $company = $movement->company()->lockForUpdate()->firstOrFail();
            $number = $company->document_prefix.'-'.str_pad((string) $company->next_document_number, 6, '0', STR_PAD_LEFT);
            $company->increment('next_document_number');
            $path = "documents/{$movement->id}/{$number}-{$type}.pdf";
            Storage::disk('local')->put($path, $bytes);

            return MovementDocument::create(['movement_id' => $movement->id, 'document_type' => $type, 'disk' => 'local', 'path' => $path, 'original_filename' => "{$number}-{$type}.pdf", 'mime_type' => 'application/pdf', 'file_size' => strlen($bytes), 'checksum' => hash('sha256', $bytes), 'template_version' => 'pilot-v1', 'issued_at' => now(), 'created_by' => $user->id]);
        });
    }
}
