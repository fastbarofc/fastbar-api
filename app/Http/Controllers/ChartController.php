<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChartRequest;
use App\Models\User;
use App\Services\Charts\CommercialCharts;
use App\Services\Charts\FinancialCharts;
use App\Services\Charts\MonthlyCharts;

class ChartController extends Controller
{
    private const FORBIDDEN = 'Você não tem permissão para ver estes gráficos.';

    public function financial(ChartRequest $request)
    {
        abort_unless($this->can($request->user(), FinancialCharts::PERMISSION), 403, self::FORBIDDEN);

        return response()->json((new FinancialCharts($request->filters()))->data());
    }

    public function commercial(ChartRequest $request)
    {
        abort_unless($this->can($request->user(), CommercialCharts::PERMISSION), 403, self::FORBIDDEN);

        return response()->json((new CommercialCharts($request->filters()))->data());
    }

    public function monthly(ChartRequest $request)
    {
        $financial = $this->can($request->user(), FinancialCharts::PERMISSION);
        $commercial = $this->can($request->user(), CommercialCharts::PERMISSION);

        abort_unless($financial || $commercial, 403, self::FORBIDDEN);

        return response()->json((new MonthlyCharts($request->filters(), $financial, $commercial))->data());
    }

    private function can(User $user, string $permission): bool
    {
        return $user->isAdmin() || $user->getPermissionsViaRoles()->contains('slug', $permission);
    }
}
