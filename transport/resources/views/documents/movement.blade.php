<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ str($type)->headline() }} {{ $movement->reference }}</title>
    <style>
        @page{margin:14mm}body{font-family:DejaVu Sans,sans-serif;color:#172033;font-size:10px}h1{font-size:20px;margin:0}h2{font-size:14px;margin:16px 0 6px}.head{display:table;width:100%;border-bottom:3px solid #173b70;padding-bottom:10px}.head>div{display:table-cell;vertical-align:top}.right{text-align:right}.meta,.items{width:100%;border-collapse:collapse;margin-top:10px}.meta td,.items td,.items th{border:1px solid #b8c2ce;padding:6px}.items th{background:#e8eef6;text-align:left}.action{page-break-inside:avoid;margin-top:14px}.sign{height:65px}.muted{color:#536273}.empty{text-align:center;color:#536273}.no-print{margin:0 0 12px}@media print{.no-print{display:none}}
    </style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">Print / save PDF</button></div>
<div class="head">
    <div>@if($movement->company->logo_path)<img src="{{ public_path('storage/'.$movement->company->logo_path) }}" style="max-height:60px;max-width:220px" alt="">@endif<h1>{{ $movement->company->trading_name ?: $movement->company->name }}</h1><p>{{ $movement->company->homeSite?->formattedAddress() }}<br>{{ $movement->company->phone }} · {{ $movement->company->email }}</p>@if($movement->company->registration_number || $movement->company->vat_number)<p class="muted">Company No. {{ $movement->company->registration_number ?: '—' }} · VAT {{ $movement->company->vat_number ?: '—' }}</p>@endif</div>
    <div class="right"><h2>Equipment Delivery &amp; Collection</h2><strong>{{ str($type)->headline() }}</strong><br>{{ $movement->reference }}<br>{{ now()->format('d M Y H:i') }}</div>
</div>

<table class="meta">
    <tr><td><strong>Date</strong><br>{{ $movement->planned_date?->format('d M Y') ?: 'Unscheduled' }}</td><td><strong>Advice note</strong><br>{{ $movement->advice_note ?: '—' }}</td><td><strong>Job number</strong><br>{{ $movement->job_number ?: '—' }}</td></tr>
    <tr><td><strong>Customer</strong><br>{{ $movement->customer->name }}<br>{{ $movement->customer->account_number }}</td><td><strong>From</strong><br>{{ $movement->actions->first()?->site?->name }}<br>{{ $movement->actions->first()?->site?->postcode }}</td><td><strong>To</strong><br>{{ $movement->actions->last()?->site?->name }}<br>{{ $movement->actions->last()?->site?->postcode }}</td></tr>
    <tr><td><strong>Contact</strong><br>{{ $movement->contact_name ?: '—' }} {{ $movement->contact_number }}</td><td><strong>Driver</strong><br>{{ $movement->actions->first()?->driver?->name ?: $movement->driver?->name ?: 'Unassigned' }}</td><td><strong>Vehicle</strong><br>{{ $movement->actions->first()?->vehicle?->registration ?: $movement->vehicle?->registration ?: 'Unassigned' }}</td></tr>
</table>

<h2>Route plan</h2>
<table class="items">
    <thead><tr><th>#</th><th>Action</th><th>Location</th><th>Schedule</th><th>Driver / vehicle</th></tr></thead>
    <tbody>@foreach ($movement->actions as $action)<tr><td>{{ $action->sequence }}</td><td>{{ str($action->action_type->value)->headline() }}</td><td>{{ $action->site?->name }}<br>{{ $action->site?->address_line_1 }}, {{ $action->site?->postcode }}</td><td>{{ $action->schedule_start?->format('d M Y H:i') ?: 'Unscheduled' }}<br>{{ $action->schedule_end?->format('d M Y H:i') }}</td><td>{{ $action->driver?->name ?: 'Unassigned' }}<br>{{ $action->vehicle?->registration }}</td></tr>@endforeach</tbody>
</table>

@foreach ($movement->actions as $action)
    @php
        $deliveryItems = $movement->items->where('delivery_action_id', $action->id);
        $collectionItems = $movement->items->where('collection_action_id', $action->id);
    @endphp
    <div class="action">
        <h2>{{ $action->sequence }}. {{ $action->site?->name }} — {{ str($action->action_type->value)->headline() }}</h2>
        @if ($deliveryItems->isNotEmpty())
            <table class="items"><thead><tr><th colspan="5">Delivery Lines</th></tr><tr><th>Stock No.</th><th>Description</th><th>Serial Number</th><th>Qty.</th><th>Completed</th></tr></thead><tbody>@foreach ($deliveryItems as $item)<tr><td>{{ $item->stock_number ?: '—' }}</td><td>{{ $item->description }}@if($item->accessories->isNotEmpty())<br><span class="muted">{{ $item->accessories->pluck('description')->join(', ') }}</span>@endif</td><td>{{ $item->serial_number ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ $item->completed ? 'Yes' : '□' }}</td></tr>@endforeach</tbody></table>
        @endif
        @if ($collectionItems->isNotEmpty())
            <table class="items"><thead><tr><th colspan="5">Collection Lines</th></tr><tr><th>Stock No.</th><th>Description</th><th>Serial Number</th><th>Qty.</th><th>Completed</th></tr></thead><tbody>@foreach ($collectionItems as $item)<tr><td>{{ $item->stock_number ?: '—' }}</td><td>{{ $item->description }}@if($item->accessories->isNotEmpty())<br><span class="muted">{{ $item->accessories->pluck('description')->join(', ') }}</span>@endif</td><td>{{ $item->serial_number ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ $item->completed ? 'Yes' : '□' }}</td></tr>@endforeach</tbody></table>
        @endif
    </div>
@endforeach

<h2>Instructions</h2><p>{{ $movement->notes ?: 'None recorded.' }}</p>
<table class="meta"><tr><td class="sign"><strong>Customer</strong><br>Name:<br><br>Signature:</td><td class="sign"><strong>{{ $movement->company->name }}</strong><br>Name:<br><br>Signature: @if($signature = $movement->photos->firstWhere('photo_type', 'signature'))<img src="{{ Storage::disk($signature->disk)->path($signature->path) }}" style="max-height:50px;max-width:220px">@endif</td></tr><tr><td><strong>Date:</strong></td><td><strong>Date:</strong></td></tr></table>
<p class="muted">Generated from the retained movement record. Reissues and amendments remain auditable.</p>
</body>
</html>
