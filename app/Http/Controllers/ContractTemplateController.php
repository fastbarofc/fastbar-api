<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{

    public function show(Request $request)
    {
        $companyId = $request->query('company_id');

        $query = ContractTemplate::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id');
        }

        $contractTemplate = $query->first();

        return response()->json([
            'content' => $contractTemplate ? $contractTemplate->content : '',
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->only(['content', 'company_id']);
        $data['content'] = $data['content'] ?? '';

        $companyId = $data['company_id'] ?? null;

        if ($companyId) {
            $contractTemplate = ContractTemplate::where('company_id', $companyId)->first();
        } else {
            $contractTemplate = ContractTemplate::whereNull('company_id')->first();
        }

        if ($contractTemplate) {
            $contractTemplate->update($data);
            return response()->json(['message' => 'Modelo de contrato atualizado com sucesso!']);
        } else {
            ContractTemplate::create($data);
            return response()->json(['message' => 'Modelo de contrato criado com sucesso!']);
        }
    }
}
