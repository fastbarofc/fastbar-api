<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    public function getAddressByCep($cep)
    {
        $client = new Client();
        try {
            $response = $client->get("https://viacep.com.br/ws/{$cep}/json/");
            $data = json_decode($response->getBody(), true);
    
            Log::info($data);
    
            if (isset($data['erro'])) {
                return response()->json(['error' => 'CEP inválido'], 404);
            }
            $state = State::where('letter', $data['uf'])->first();
            
            if (!$state) {
                Log::error("Estado não encontrado para UF: {$data['uf']}");
                return response()->json(['error' => 'Estado não encontrado'], 404);
            }
    
            $city = City::where('name', $data['localidade'])
                ->where('state_id', $state->id)
                ->first();
    
            if (!$city) {
                Log::error("Cidade não encontrada: {$data['localidade']} no estado {$state->name}");
                return response()->json(['error' => 'Cidade não encontrada'], 404);
            }
    
            Log::info($city);
            Log::info($state);
    
            return response()->json([
                'street' => $data['logradouro'] ?? '',
                'district' => $data['bairro'] ?? '',
                'city_id' => $city->id ?? null,
                'city_name' => $city->name ?? $data['localidade'] ?? '',
                'state_id' => $state->id ?? null,
                'state_name' => $state->name ?? '',
                'state_letter' => $state->letter ?? $data['uf'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar o CEP: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar o CEPpppp'], 500);
        }
    }
    

}
