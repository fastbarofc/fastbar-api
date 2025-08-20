# Módulo de Compras - Parthenon System API

## Visão Geral

Este documento descreve a implementação completa do módulo de compras no sistema Parthenon, desenvolvido seguindo rigorosamente os padrões do Laravel e as convenções estabelecidas no projeto.

## Estrutura Criada

### 1. Migrations

#### 2025_08_18_100000_create_purchase_requests_table.php
- **Tabela**: `purchase_requests`
- **Campos**:
  - `id` (Primary Key)
  - `company_id` (Foreign Key → companies)
  - `requester_id` (Foreign Key → users)
  - `sector_id` (Foreign Key → sectors)
  - `expected_date` (Data prevista de necessidade)
  - `status` (Enum: not_confirmed, confirmed, authorized)
  - `created_at`, `updated_at`, `deleted_at` (Soft Deletes)

#### 2025_08_18_100001_create_purchase_request_product_table.php
- **Tabela**: `purchase_request_product` (Pivot)
- **Campos**:
  - `id`, `purchase_request_id`, `product_id`
  - `factor` (string), `reference` (string), `quantity` (integer)
  - `created_at`, `updated_at`

#### 2025_08_18_100002_create_purchase_orders_table.php
- **Tabela**: `purchase_orders`
- **Campos**:
  - `id` (Primary Key)
  - `company_id` (Foreign Key → companies)
  - `price` (decimal 10,2)
  - `financial_operation_id` (Foreign Key → financial_operations)
  - `cost_center_id` (Foreign Key → cost_centers)
  - `created_at`, `updated_at`, `deleted_at` (Soft Deletes)

#### 2025_08_18_100003_create_purchase_order_product_table.php
- **Tabela**: `purchase_order_product` (Pivot)
- **Campos**:
  - `id`, `purchase_order_id`, `product_id`, `purchase_request_id`
  - `factor` (string), `reference` (string), `quantity` (integer), `price` (decimal 10,2)
  - `created_at`, `updated_at`

### 2. Models

#### PurchaseRequest
- **Localização**: `app/Models/PurchaseRequest.php`
- **Traits**: HasFactory, SoftDeletes, Auditable, Sortable, Filterable
- **Relacionamentos**:
  - `company()` → belongsTo(Company::class)
  - `requester()` → belongsTo(User::class, 'requester_id')
  - `sector()` → belongsTo(Sector::class)
  - `products()` → belongsToMany(Product::class) com pivot
  - `purchaseRequestProducts()` → hasMany(PurchaseRequestProduct::class)
  - `purchaseOrderProducts()` → hasMany(PurchaseOrderProduct::class)
- **Constantes de Status**:
  - `STATUS_NOT_CONFIRMED = 'not_confirmed'`
  - `STATUS_CONFIRMED = 'confirmed'`
  - `STATUS_AUTHORIZED = 'authorized'`

#### PurchaseOrder
- **Localização**: `app/Models/PurchaseOrder.php`
- **Traits**: HasFactory, SoftDeletes, Auditable, Sortable, Filterable
- **Relacionamentos**:
  - `company()` → belongsTo(Company::class)
  - `financialOperation()` → belongsTo(FinancialOperation::class)
  - `costCenter()` → belongsTo(CostCenter::class)
  - `products()` → belongsToMany(Product::class) com pivot expandido
  - `purchaseOrderProducts()` → hasMany(PurchaseOrderProduct::class)

#### PurchaseRequestProduct (Pivot Model)
- **Localização**: `app/Models/PurchaseRequestProduct.php`
- **Estende**: `Illuminate\Database\Eloquent\Relations\Pivot`
- **Traits**: HasFactory
- **Relacionamentos**:
  - `purchaseRequest()` → belongsTo(PurchaseRequest::class)
  - `product()` → belongsTo(Product::class)
- **Benefícios**: Acesso direto aos atributos pivot, validações específicas, eventos pivot

#### PurchaseOrderProduct (Pivot Model)
- **Localização**: `app/Models/PurchaseOrderProduct.php`
- **Estende**: `Illuminate\Database\Eloquent\Relations\Pivot`
- **Traits**: HasFactory
- **Relacionamentos**:
  - `purchaseOrder()` → belongsTo(PurchaseOrder::class)
  - `product()` → belongsTo(Product::class)
  - `purchaseRequest()` → belongsTo(PurchaseRequest::class)
- **Benefícios**: Acesso direto aos atributos pivot, validações específicas, eventos pivot

### 3. Form Request Classes

#### StorePurchaseRequestRequest
- **Localização**: `app/Http/Requests/StorePurchaseRequestRequest.php`
- **Validações**:
  - company_id: required|integer|exists:companies,id
  - requester_id: required|integer|exists:users,id
  - sector_id: required|integer|exists:sectors,id
  - expected_date: required|date|after_or_equal:today
  - products: required|array|min:1 (com validações para cada produto)

#### UpdatePurchaseRequestRequest
- **Localização**: `app/Http/Requests/UpdatePurchaseRequestRequest.php`
- **Validações**: Semelhantes ao Store, mas com campos nullable

#### StorePurchaseOrderRequest
- **Localização**: `app/Http/Requests/StorePurchaseOrderRequest.php`
- **Validações**:
  - price: required|numeric|min:0|max:99999999.99
  - financial_operation_id: required|integer|exists:financial_operations,id
  - cost_center_id: required|integer|exists:cost_centers,id
  - products com purchase_request_id obrigatório

#### UpdatePurchaseOrderRequest
- **Localização**: `app/Http/Requests/UpdatePurchaseOrderRequest.php`
- **Validações**: Semelhantes ao Store, mas com campos nullable

### 4. API Resources

#### PurchaseRequestResource
- **Localização**: `app/Http/Resources/PurchaseRequestResource.php`
- **Campos Retornados**: Todos os campos do model + relacionamentos carregados
- **Relacionamentos**: company, requester, sector, products (com dados do pivot)

#### PurchaseOrderResource
- **Localização**: `app/Http/Resources/PurchaseOrderResource.php`
- **Campos Retornados**: Todos os campos do model + relacionamentos carregados
- **Relacionamentos**: company, financialOperation, costCenter, products (com dados do pivot)

### 5. Controllers

#### PurchaseRequestController
- **Localização**: `app/Http/Controllers/PurchaseRequestController.php`
- **Métodos RESTful**:
  - `index()` - Listagem com paginação, filtros e ordenação
  - `store()` - Criação com transação para sync de produtos
  - `show()` - Exibição com relacionamentos carregados
  - `update()` - Atualização com transação
  - `destroy()` - Remoção com soft delete
- **Funcionalidades**: Filtros, ordenação, paginação, transações DB

#### PurchaseOrderController
- **Localização**: `app/Http/Controllers/PurchaseOrderController.php`
- **Métodos**: Idênticos ao PurchaseRequestController
- **Diferencial**: Gerencia pivot mais complexo com purchase_request_id

### 6. Policies

#### PurchaseRequestPolicy
- **Localização**: `app/Policies/PurchaseRequestPolicy.php`
- **Permissões**: list-purchase-requests, view-purchase-requests, create-purchase-requests, update-purchase-requests, delete-purchase-requests

#### PurchaseOrderPolicy
- **Localização**: `app/Policies/PurchaseOrderPolicy.php`
- **Permissões**: list-purchase-orders, view-purchase-orders, create-purchase-orders, update-purchase-orders, delete-purchase-orders

### 7. Rotas API

**Arquivo**: `routes/api.php`

```php
// Purchase module routes
Route::resource('purchase-requests', PurchaseRequestController::class);
Route::resource('purchase-orders', PurchaseOrderController::class);
```

**Rotas Geradas**:
- GET `/api/purchase-requests` - Listagem de pedidos de compra
- POST `/api/purchase-requests` - Criação de pedido de compra
- GET `/api/purchase-requests/{id}` - Exibição de pedido específico
- PUT/PATCH `/api/purchase-requests/{id}` - Atualização de pedido
- DELETE `/api/purchase-requests/{id}` - Remoção de pedido

- GET `/api/purchase-orders` - Listagem de ordens de compra
- POST `/api/purchase-orders` - Criação de ordem de compra
- GET `/api/purchase-orders/{id}` - Exibição de ordem específica
- PUT/PATCH `/api/purchase-orders/{id}` - Atualização de ordem
- DELETE `/api/purchase-orders/{id}` - Remoção de ordem

### 8. Factories e Seeders

#### PurchaseRequestFactory
- **Localização**: `database/factories/PurchaseRequestFactory.php`
- **Dados Gerados**: Status aleatório, datas futuras, IDs de registros existentes

#### PurchaseOrderFactory
- **Localização**: `database/factories/PurchaseOrderFactory.php`
- **Dados Gerados**: Preços aleatórios, IDs de registros existentes

#### PurchaseRequestSeeder
- **Localização**: `database/seeders/PurchaseRequestSeeder.php`
- **Criação**: 10 pedidos de compra com 1-5 produtos cada

#### PurchaseOrderSeeder
- **Localização**: `database/seeders/PurchaseOrderSeeder.php`
- **Criação**: 5 ordens de compra com 1-3 produtos cada

### 9. Autorização

**Arquivo**: `app/Providers/AuthServiceProvider.php`
- Policies registradas automaticamente
- Permissões criadas automaticamente pelo sistema baseado nas tabelas
- Gates configurados para verificação de admin

## Diferenças: Model vs Pivot

### 🔧 **Por que usar `Pivot` ao invés de `Model`?**

#### **Classe `Pivot`:**
- **Específica para tabelas many-to-many** com campos adicionais
- **Acesso otimizado** aos atributos pivot via `$model->pivot->attribute`
- **Eventos específicos** para operações pivot (pivotAttaching, pivotDetaching, etc.)
- **Integração nativa** com `->using(PivotModel::class)` nos relacionamentos
- **Performance otimizada** para operações de pivot

#### **Exemplo de uso com Pivot:**
```php
// Com Pivot Model customizado
$purchaseRequest->products()->attach($productId, [
    'factor' => 'Caixa',
    'quantity' => 10
]);

// Acesso direto aos dados pivot
$pivot = $purchaseRequest->products()->first()->pivot;
echo $pivot->quantity; // 10
```

#### **Relacionamentos atualizados:**
```php
// PurchaseRequest.php
public function products()
{
    return $this->belongsToMany(Product::class, 'purchase_request_product')
        ->using(PurchaseRequestProduct::class) // ← Pivot customizado
        ->withPivot(['factor', 'reference', 'quantity'])
        ->withTimestamps();
}
```

## Padrões Seguidos

1. **Convenções Laravel**: Nomes de tabelas, models, controllers seguem exatamente as convenções
2. **RESTful API**: Todos os controllers seguem padrões RESTful
3. **Soft Deletes**: Implementado em todas as entidades principais
4. **Auditing**: Rastreamento de mudanças usando owen-it/laravel-auditing
5. **Filtros e Ordenação**: Traits Filterable e Sortable implementadas
6. **Validações**: Form Requests com mensagens em português
7. **Resources**: Formatação padronizada de retorno da API
8. **Transações**: Operações críticas envolvidas em transações
9. **Relacionamentos**: Eager loading para performance
10. **Factories e Seeders**: Para dados de teste e desenvolvimento
11. **Pivot Models**: Uso de `Illuminate\Database\Eloquent\Relations\Pivot` para tabelas many-to-many
12. **Custom Pivot**: Modelos pivot customizados com `->using()` nos relacionamentos

## Como Testar

### 1. Executar Migrations
```bash
./vendor/bin/sail artisan migrate
```

### 2. Popular Dados de Teste
```bash
./vendor/bin/sail artisan db:seed --class=PurchaseRequestSeeder
./vendor/bin/sail artisan db:seed --class=PurchaseOrderSeeder
```

### 3. Testar APIs

#### Criar Pedido de Compra
```bash
POST /api/purchase-requests
Content-Type: application/json

{
    "company_id": 1,
    "requester_id": 1,
    "sector_id": 1,
    "expected_date": "2025-09-15",
    "status": "not_confirmed",
    "products": [
        {
            "product_id": 1,
            "factor": "Caixa",
            "reference": "CX001",
            "quantity": 10
        }
    ]
}
```

#### Listar Pedidos de Compra
```bash
GET /api/purchase-requests?per_page=10&order_by=created_at&direction=desc
```

#### Criar Ordem de Compra
```bash
POST /api/purchase-orders
Content-Type: application/json

{
    "company_id": 1,
    "price": 1500.00,
    "financial_operation_id": 1,
    "cost_center_id": 1,
    "products": [
        {
            "product_id": 1,
            "purchase_request_id": 1,
            "factor": "Caixa",
            "reference": "CX001",
            "quantity": 10,
            "price": 150.00
        }
    ]
}
```

## Status da Implementação

✅ **Concluído**:
- Migrations criadas e executadas
- Models com relacionamentos completos (incluindo models pivot)
- Form Requests com validações
- API Resources para formatação
- Controllers RESTful completos
- Policies para autorização
- Rotas API registradas
- Factories e Seeders funcionais
- Dados de teste criados
- Documentação completa

🔧 **Próximos Passos Opcionais**:
- Testes unitários e de integração
- Documentação Swagger/OpenAPI
- Eventos e Listeners para auditoria
- Filtros avançados por data/status
- Relatórios de compras
- Integração com sistema de aprovação
- Notificações por email
- Dashboard de compras

## Dados de Teste Criados

- **Purchase Requests**: 10 registros
- **Purchase Orders**: 5 registros  
- **Purchase Request Products**: 27 relacionamentos
- **Purchase Order Products**: 14 relacionamentos
- **Companies**: 1 registro (Parthenon Buffet)
- **Cost Centers**: 2 registros (Administrativo e Produção)

Toda a estrutura está pronta para uso e seguindo rigorosamente os padrões estabelecidos no projeto Parthenon System API. 