<?php

namespace App\Http\Controllers;

use App\Support\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentCompanyController extends Controller
{
    public function update(Request $request, CurrentCompany $currentCompany): RedirectResponse
    {
        $data = $request->validate(['company_id' => ['required', 'integer', 'exists:companies,id']]);
        abort_unless($currentCompany->availableFor($request->user())->contains('id', (int) $data['company_id']), 403);

        $request->session()->put(CurrentCompany::SESSION_KEY, (int) $data['company_id']);

        return redirect()->route('operations.movements.index');
    }
}
