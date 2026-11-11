<?php

namespace App\Http\Controllers;

use App\Services\PlanningRules;
use Illuminate\Http\Request;

class PlanningRuleController extends Controller
{
    public function index()
    {
        return response()->json(['data' => PlanningRules::all()]);
    }

    public function update(Request $request, string $key)
    {
        $rule = collect(PlanningRules::RULES)->firstWhere('key', $key);
        abort_if(!$rule || !$rule['toggle'], 404, 'Regra não encontrada.');
        $data = $request->validate(['enabled' => 'required|boolean']);
        PlanningRules::set($key, (bool) $data['enabled']);

        return response()->json(['data' => PlanningRules::all()]);
    }
}
