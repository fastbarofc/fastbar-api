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
    
    
            if (isset($data['erro'])) {
                return response()->json(['error' => 'CEP inválido'], 404);
            }
            $state = State::where('letter', $data['uf'])->first();
            
            if (!$state) {
                return response()->json(['error' => 'Estado não encontrado'], 404);
            }
    
            $city = City::where('name', $data['localidade'])
                ->where('state_id', $state->id)
                ->first();
    
            if (!$city) {
                return response()->json(['error' => 'Cidade não encontrada'], 404);
            }
    
    
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
            return response()->json(['error' => 'Erro ao buscar o CEPpppp'], 500);
        }
    }
    

}
