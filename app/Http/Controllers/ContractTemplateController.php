<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{

    public function show(ContractTemplate $contractTemplate)
    {
        $contractTemplate = ContractTemplate::first();
        
        return response()->json([
            'content' => $contractTemplate ? $contractTemplate->content : '',
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->only('content');

        $data['content'] = $data['content'] ?? '';
        
        $contractTemplate = ContractTemplate::first();

        if ($contractTemplate) {
            $contractTemplate->update($data);
            return response()->json(['message' => 'Modelo de contrato atualizado com sucesso!']);
        } else {
            ContractTemplate::create($data);
            return response()->json(['message' => 'Modelo de contrato criado com sucesso!']);
        }
    }

   
}   



