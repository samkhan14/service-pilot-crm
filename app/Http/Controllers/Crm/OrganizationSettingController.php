<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\TeamSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingController extends Controller
{
    use AuthorizesCrm;

    public function edit(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ManageSettings);
        $team = $this->currentTeam($request);

        return Inertia::render('crm/settings/organization', [
            'settings' => TeamSetting::forTeam($team),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageSettings);
        $team = $this->currentTeam($request);

        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'size:3'],
            'tax_label' => ['required', 'string', 'max:80'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'working_hours_start' => ['required', 'integer', 'min:0', 'max:23'],
            'working_hours_end' => ['required', 'integer', 'min:1', 'max:24'],
            'default_payment_terms_days' => ['required', 'integer', 'min:1', 'max:120'],
            'payment_instructions' => ['nullable', 'string'],
        ]);

        TeamSetting::forTeam($team)->update($data);

        return back()->with('success', 'Organization settings saved.');
    }
}
