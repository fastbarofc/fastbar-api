<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use Spatie\Permission\Models\Role;
use App\Models\Address;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\BankDetail;
use App\Models\PixKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SupplierController extends Controller
{
   
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(Supplier::class, 'supplier');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Supplier::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'corporate_name' || $field == 'fantasy_name' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
        }

        $suppliers = $query->paginate($perPage);

        return response()->json([
            'data' => SupplierResource::collection($suppliers),
            'pagination' => [
                'page' => $suppliers->currentPage(),
                'total_pages' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
            'sortable_fields' => Supplier::sortableFields(),
            'filterable_fields' => Supplier::filterableFields()
        ], 200);
    }

    public function store(StoreSupplierRequest $request)
    {
        $data = $request->validated();

        $supplier = DB::transaction(function () use ($data) {
            $supplier = Supplier::create($data);
            $this->saveRelations($supplier, $data);

            return $supplier;
        });

        return response()->json(new SupplierResource($supplier), 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json(new SupplierResource($supplier), 200);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $data = $request->validated();

        DB::transaction(function () use ($supplier, $data) {
            $supplier->update($data);
            $this->saveRelations($supplier, $data);

            if (!empty($data['removed_bank_detail_ids'])) {
                $supplier->bankDetails()->whereIn('id', $data['removed_bank_detail_ids'])->delete();
            }

            if (!empty($data['removed_pix_key_ids'])) {
                $supplier->pixKeys()->whereIn('id', $data['removed_pix_key_ids'])->delete();
            }
        });

        return response()->json(new SupplierResource($supplier), 200);
    }

    private function saveRelations(Supplier $supplier, array $data): void
    {
        $address = $data['address'] ?? null;

        if (!empty($address['street'])) {
            Address::updateOrCreate(
                ['addressable_id' => $supplier->id, 'addressable_type' => Supplier::class],
                Arr::only($address, ['street', 'district', 'complement', 'city_id', 'state_id', 'number', 'zip_code', 'reference'])
            );
        }

        foreach ($data['bank_details'] ?? [] as $bankData) {
            $attributes = Arr::only($bankData, ['bank_name', 'agency', 'account', 'account_holder', 'status']);
            $existing = !empty($bankData['id']) ? $supplier->bankDetails()->find($bankData['id']) : null;

            $existing ? $existing->update($attributes) : $supplier->bankDetails()->create($attributes);
        }

        foreach ($data['pix_keys'] ?? [] as $pixData) {
            $attributes = Arr::only($pixData, ['type', 'key']);
            $existing = !empty($pixData['id']) ? $supplier->pixKeys()->find($pixData['id']) : null;

            $existing ? $existing->update($attributes) : $supplier->pixKeys()->create($attributes);
        }
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json([], 204);
    }
}
