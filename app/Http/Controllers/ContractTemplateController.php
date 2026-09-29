<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    public function show()
    {
        $template = ContractTemplate::current();

        return response()->json([
            'sections' => $template->sectionsWith(),
            'variables' => ContractTemplate::VARIABLES,
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'sections' => 'required|array:' . implode(',', ContractTemplate::SECTIONS),
            'sections.*' => 'nullable|string',
        ], [
            'sections.array' => 'O modelo de contrato recebeu um trecho desconhecido.',
        ]);

        $template = ContractTemplate::current();
        $template->fill($validated)->save();

        return response()->json([
            'message' => 'Modelo de contrato salvo com sucesso!',
            'sections' => $template->sectionsWith(),
            'variables' => ContractTemplate::VARIABLES,
        ]);
    }
}
