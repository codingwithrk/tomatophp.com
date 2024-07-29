<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Tenant;
use Throwable;
use function Filament\Support\is_app_url;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation(collect($data)->except('domain')->toArray());
        $record->domains()->create(['domain' => collect($data)->get('domain')]);
        return $record;
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        $this->callHook('beforeValidate');

        $data = $this->form->getState();

        $this->callHook('afterValidate');

        $data = $this->mutateFormDataBeforeCreate($data);

        $this->callHook('beforeCreate');

        $this->record = $this->handleRecordCreation($data);

        $this->form->model($this->getRecord())->saveRelationships();

        $this->callHook('afterCreate');

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->form->model($this->getRecord()::class);
            $this->record = null;

            $this->fillForm();

            return;
        }

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
    }


    /**
     * @throws Throwable
     */
    private function createTenantRecord(array $data)
    {
        \Log::info("Saving Tenant");
        $record = new Tenant(collect($data)->except('domain')->toArray());
        $record->saveOrFail();
        \Log::info("Saving Domains");
        $record = $record::find($record->id);
        $record->domains()->create(['domain' => collect($data)->get('domain')]);
        return $record;
    }
}
