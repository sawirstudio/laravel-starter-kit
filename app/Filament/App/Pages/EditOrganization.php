<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\MemberWidget;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;
use Override;

final class EditOrganization extends EditTenantProfile
{
    #[Override]
    public static function getLabel(): string
    {
        return 'Edit Team';
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->schema([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required(),
            ]);
    }

    #[Override]
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                ...$this->getWidgetsSchemaComponents($this->getFooterWidgets()),
            ]);
    }

    #[Override]
    protected function getFooterWidgets(): array
    {
        return [
            MemberWidget::class,
        ];
    }
}
