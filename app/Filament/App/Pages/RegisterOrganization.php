<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;

final class RegisterOrganization extends RegisterTenant
{
    #[Override]
    public static function getLabel(): string
    {
        return 'Register Team';
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->schema([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required()
                    ->default(fn (): string => $this->user()->name."'s Team"),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Override]
    protected function handleRegistration(array $data): Model
    {
        $name = $data['name'];
        $user = $this->user();

        assert(is_string($name));

        $organization = Organization::query()->create([
            'name' => $name,
            'user_id' => $user->id,
        ]);

        $organization->orgUsers()->create([
            'user_id' => $user->id,
        ]);

        return $organization;
    }

    private function user(): User
    {
        $user = auth()->user();

        assert($user instanceof User);

        return $user;
    }
}
