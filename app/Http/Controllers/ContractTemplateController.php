<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();

        $contractTemplate = ContractTemplate::firstOrNew(['event_id' => $data['event_id']]);
        $contractTemplate->content = $data['content'];
        $contractTemplate->save();

        return response()->json(['message' => 'Modelo de contrato salvo com sucesso!']);
    }

    public function show($event_id)
    {
        $contractTemplate = ContractTemplate::where('event_id', $event_id)->first();

        return response()->json([
            'content' => $contractTemplate ? $contractTemplate->content : '',
        ]);
    }
}

//fazer um crud
