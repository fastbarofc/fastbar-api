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

class SupplierController extends Controller
{
   
    public function __construct()
    {
        $this->authorizeResource(Supplier::class, 'supplier');
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

    public function store(Request $request)
    {
        $data = $request->all();

        $supplier = Supplier::create($data);

        if (!empty($data['address']) && !empty($data['address']['street'])) {
            $address = $data['address'];
            Address::create([
                'addressable_id' => $supplier->id,
                'addressable_type' => Supplier::class,
                'street' => $address['street'],
                'district' => $address['district'],
                'complement' => $address['complement'],
                'city_id' => $address['city_id'],
                'state_id' => $address['state_id'],
                'number' => $address['number'],
                'zip_code' => $address['zip_code'],
                'reference' => $address['reference'],
            ]);
        }

        if (!empty($data['bank_details']) && is_array($data['bank_details'])) {
            BankDetail::create([
                'bank_detailable_id' => $supplier->id,
                'bank_detailable_type' => Supplier::class,
                'bank_name' => $data['bank_details']['bank_name'],
                'agency' => $data['bank_details']['agency'],
                'account' => $data['bank_details']['account'],
                'status' => $data['bank_details']['status'],
            ]);
        }
    
        if (!empty($data['pix_keys']) && is_array($data['pix_keys'])) {
            PixKey::create([
                'pix_keyable_id' => $supplier->id,
                'pix_keyable_type' => Supplier::class,
                'type' => $data['pix_keys']['type'],
                'key' => $data['pix_keys']['key'],
            ]);
        }

        return response()->json(new SupplierResource($supplier), 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json(new SupplierResource($supplier), 200);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->all();
        $supplier->update($data);

        if (!empty($data['address'])) {
            $address = $data['address'];
            Address::updateOrCreate(
                [
                    'addressable_id' => $supplier->id,
                    'addressable_type' => Supplier::class,
                ],
                [
                    'street' => $address['street'],
                    'district' => $address['district'],
                    'complement' => $address['complement'],
                    'city_id' => $address['city_id'],
                    'state_id' => $address['state_id'],
                    'number' => $address['number'],
                    'zip_code' => $address['zip_code'],
                    'reference' => $address['reference'],
                ]
            );
        }
        if (!empty($data['bank_details']) && is_array($data['bank_details'])) {
            BankDetail::updateOrCreate(
                [
                    'bank_detailable_id' => $supplier->id,
                    'bank_detailable_type' => Supplier::class,
                ],
                [
                    'bank_name' => $data['bank_details']['bank_name'],
                    'agency' => $data['bank_details']['agency'],
                    'account' => $data['bank_details']['account'],
                    'status' => $data['bank_details']['status'],
                ]
            );
        }
    
        if (!empty($data['pix_keys']) && is_array($data['pix_keys'])) {
            PixKey::updateOrCreate(
                [
                    'pix_keyable_id' => $supplier->id,
                    'pix_keyable_type' => Supplier::class,
                ],
                [
                    'type' => $data['pix_keys']['type'],
                    'key' => $data['pix_keys']['key'],
                ]
            );
        }
        return response()->json(new SupplierResource($supplier), 200);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json([], 204);
    }
}
