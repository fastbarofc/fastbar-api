<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Gate;
use App\Models\Ceremonial;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\CollaboratorIncident;
use App\Models\Contract;
use App\Models\Decorator;
use App\Models\Event;
use App\Models\EventCapture;
use App\Models\EventGift;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\ExtraSale;
use App\Models\Kit;
use App\Models\Material;
use App\Models\SectorOffice;
use App\Models\OfficeBudget;
use App\Models\OfficeEvent;
use App\Models\Partner;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Qualification;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use App\Policies\CeremonialPolicy;
use App\Policies\ClientPolicy;
use App\Policies\CollaboratorPolicy;
use App\Policies\ContractPolicy;
use App\Policies\CollaboratorIncidentPolicy;
use App\Policies\DecoratorPolicy;
use App\Policies\EventCapturePolicy;
use App\Policies\EventGiftPolicy;
use App\Policies\EventPlacePolicy;
use App\Policies\EventPolicy;
use App\Policies\EventStatusPolicy;
use App\Policies\EventTypePolicy;
use App\Policies\ExtraSalePolicy;
use App\Policies\KitPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\OfficeEventPolicy;
use App\Policies\SectorOfficePolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\ProductPolicy;
use App\Policies\QualificationPolicy;
use App\Policies\SectorPolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
use App\Policies\OfficeBudgetPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ceremonial::class => CeremonialPolicy::class,
        Client::class => ClientPolicy::class,
        Collaborator::class => CollaboratorPolicy::class,
        CollaboratorIncident::class => CollaboratorIncidentPolicy::class,
        Contract::class => ContractPolicy::class,
        Decorator::class => DecoratorPolicy::class,
        EventCapture::class => EventCapturePolicy::class,
        Event::class => EventPolicy::class,
        EventGift::class => EventGiftPolicy::class,
        EventPlace::class => EventPlacePolicy::class,
        EventStatus::class => EventStatusPolicy::class,
        EventType::class => EventTypePolicy::class,
        ExtraSale::class => ExtraSalePolicy::class,
        Kit::class => KitPolicy::class,
        Material::class => MaterialPolicy::class,
        SectorOffice::class => SectorOfficePolicy::class,
        OfficeEvent::class => OfficeEventPolicy::class,
        Partner::class => PartnerPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        Product::class => ProductPolicy::class,
        Qualification::class => QualificationPolicy::class,
        Sector::class => SectorPolicy::class,
        Unit::class => UnitPolicy::class,
        User::class => UserPolicy::class,
        OfficeBudget::class => OfficeBudgetPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        Gate::after(function ($user, $ability, $result, $arguments) {
            if (!$result) {
                abort(403, 'Forbidden');
            }
        });
    }
}
