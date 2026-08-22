<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Transport mobile</title>
    <link rel="manifest" href="/manifest.webmanifest">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <header class="sticky top-0 z-10 bg-slate-900 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))] text-white shadow">
        <div class="mx-auto flex max-w-3xl items-center justify-between">
            <div><p class="text-xs uppercase tracking-widest text-slate-300">APS Transport</p><h1 class="text-lg font-semibold">Mobile jobs</h1></div>
            <div class="flex items-center gap-2"><button id="install-app" hidden class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-900">Install app</button><span id="connection-state" class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold">Online</span></div>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-3 py-4 pb-28">
        <div id="sync-alert" class="mb-3 hidden rounded-lg border p-3 text-sm"></div>
        <nav class="mb-4 grid grid-cols-3 gap-2" aria-label="Mobile sections">
            <button data-tab="jobs" class="pwa-tab rounded-lg bg-slate-900 px-3 py-3 font-semibold text-white">My jobs</button>
            <button data-tab="yard" class="pwa-tab rounded-lg bg-white px-3 py-3 font-semibold shadow">Yard receipt</button>
            <button data-tab="outbox" class="pwa-tab rounded-lg bg-white px-3 py-3 font-semibold shadow">Outbox <span id="outbox-count"></span></button>
        </nav>
        <section id="tab-jobs"><div id="jobs" class="space-y-3"></div></section>
        <section id="tab-yard" class="hidden">
            <form id="yard-form" class="space-y-4 rounded-xl bg-white p-4 shadow">
                <h2 class="text-xl font-semibold">Receive an unexpected machine</h2>
                <p class="text-sm text-slate-600">Save immediately with the detail available. Scheduling can reconcile it later.</p>
                <label class="block text-sm font-medium">Company<select name="company_id" id="yard-company" required class="mt-1 w-full rounded-lg border p-3"></select></label>
                <label class="block text-sm font-medium">Customer<select name="customer_id" id="yard-customer" class="mt-1 w-full rounded-lg border p-3"><option value="">Unknown / identify later</option></select></label>
                <label class="block text-sm font-medium">Receiving yard<select name="site_id" id="yard-site" required class="mt-1 w-full rounded-lg border p-3"></select></label>
                <label class="block text-sm font-medium">Delivered by<input name="delivered_by" required class="mt-1 w-full rounded-lg border p-3" autocomplete="name"></label>
                <label class="block text-sm font-medium">Reason for return<textarea name="reason" required class="mt-1 w-full rounded-lg border p-3"></textarea></label>
                <div class="grid grid-cols-2 gap-3"><label class="block text-sm font-medium">Stock number<input name="stock_number" class="mt-1 w-full rounded-lg border p-3"></label><label class="block text-sm font-medium">Serial number<input name="serial_number" class="mt-1 w-full rounded-lg border p-3"></label></div>
                <label class="block text-sm font-medium">Machine description<input name="description" required class="mt-1 w-full rounded-lg border p-3"></label>
                <label class="block text-sm font-medium">Condition<textarea name="condition_notes" class="mt-1 w-full rounded-lg border p-3"></textarea></label>
                <label class="block text-sm font-medium">Accessories<textarea name="accessories" class="mt-1 w-full rounded-lg border p-3" placeholder="Keys, charger, harness, manuals..."></textarea></label>
                <label class="block text-sm font-medium">Photographs<input name="photos" type="file" accept="image/*" capture="environment" multiple class="mt-1 w-full rounded-lg border p-3"></label>
                <button class="min-h-12 w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white" type="submit">Save yard receipt</button>
            </form>
        </section>
        <section id="tab-outbox" class="hidden"><div id="outbox" class="space-y-3"></div></section>
    </main>

    <dialog id="job-dialog" class="m-auto w-[min(94vw,38rem)] rounded-xl p-0 backdrop:bg-slate-950/60">
        <form id="job-form" class="space-y-4 p-5">
            <div class="flex justify-between"><h2 id="job-title" class="text-xl font-semibold"></h2><button type="button" data-close class="rounded p-2">✕</button></div>
            <input type="hidden" name="movement_id"><input type="hidden" name="expected_lock_version"><input type="hidden" name="to_status">
            <label class="block text-sm font-medium">Recipient name<input name="recipient_name" class="mt-1 w-full rounded-lg border p-3"></label>
            <label class="block text-sm font-medium">Notes / reason<textarea name="reason" class="mt-1 w-full rounded-lg border p-3"></textarea></label>
            <label class="block text-sm font-medium">Photos<input name="photos" type="file" accept="image/*" capture="environment" multiple class="mt-1 w-full rounded-lg border p-3"></label>
            <div><p class="mb-1 text-sm font-medium">Signature</p><canvas id="signature" width="600" height="220" class="h-36 w-full touch-none rounded-lg border bg-white"></canvas><button id="clear-signature" type="button" class="mt-1 text-sm text-blue-700">Clear signature</button></div>
            <button class="min-h-12 w-full rounded-lg bg-blue-700 p-3 font-semibold text-white" type="submit">Save update</button>
        </form>
    </dialog>

    <script>window.TRANSPORT_BOOTSTRAP = @json($bootstrap); window.TRANSPORT_SYNC = @json(route('pwa.sync'));</script>
    <script src="/pwa.js" defer></script>
</body>
</html>
