<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateListingParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        $validator = Validator::make($request->query(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:0|max:1000',
            'direction' => 'nullable|in:asc,desc',
            'order_by' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z_]+(\.[A-Za-z_]+)?$/'],
            'filter_by' => 'nullable|string|json',
        ], [
            'page.*' => 'A página deve ser um número a partir de 1.',
            'per_page.*' => 'A quantidade por página deve ser um número entre 0 e 1000.',
            'direction.in' => 'A direção da ordenação deve ser asc ou desc.',
            'order_by.*' => 'O campo de ordenação é inválido.',
            'filter_by.*' => 'Os filtros devem ser um JSON válido.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $filters = $request->query('filter_by');

            if (!is_string($filters) || $validator->errors()->has('filter_by')) {
                return;
            }

            if (!$this->isListOfFilters(json_decode($filters, true))) {
                $validator->errors()->add('filter_by', 'Os filtros devem ser uma lista de objetos campo: valor.');
            }
        });

        $validator->validate();

        return $next($request);
    }

    private function isListOfFilters(mixed $filters): bool
    {
        if (!is_array($filters) || !array_is_list($filters)) {
            return false;
        }

        foreach ($filters as $filter) {
            if (!is_array($filter) || $filter === [] || array_is_list($filter)) {
                return false;
            }
        }

        return true;
    }
}
