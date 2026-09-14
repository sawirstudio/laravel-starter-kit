<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Organization;
use App\Models\OrgInvitation;
use App\Models\OrgUser;
use App\Models\Role;
use App\Notifications\OrgInvitationCreated;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

/**
 * Members and pending invitations of the current organization in one table.
 *
 * @phpstan-type Row array{key: string, id: int, type: 'member'|'invitation', name: string, email: string, status: string, roles: array<int, string>, expires_at: CarbonInterface|null, is_owner: bool}
 */
final class MemberWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Members')
            ->records(fn (?string $search): array => $this->records($search))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Member' ? 'success' : 'warning'),
                TextColumn::make('roles')
                    ->listWithLineBreaks()
                    ->placeholder('No roles'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->headerActions([
                Action::make('invite')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Select::make('roles')
                            ->multiple()
                            ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('expires_at')
                            ->default(now()->addWeek())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $invitation = OrgInvitation::query()->create([
                            'email' => $data['email'],
                            'expires_at' => $data['expires_at'],
                            'organization_id' => $this->organization()->getKey(),
                            'created_by_id' => Filament::auth()->id(),
                        ]);
                        $roles = $data['roles'] ?? [];
                        assert(is_array($roles));

                        $invitation->roles()->sync($roles);
                        $invitation->notify(new OrgInvitationCreated);

                        Notification::make()->title('Invitation sent')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('remove')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(
                        /** @param Row $record */
                        fn (array $record): bool => $record['type'] === 'member' && ! $record['is_owner'],
                    )
                    ->action(
                        /** @param Row $record */
                        function (array $record): void {
                            OrgUser::query()->whereKey($record['id'])->delete();

                            Notification::make()->title('Member removed')->success()->send();
                        },
                    ),
                Action::make('resend')
                    ->visible(
                        /** @param Row $record */
                        fn (array $record): bool => $record['type'] === 'invitation',
                    )
                    ->action(
                        /** @param Row $record */
                        function (array $record): void {
                            OrgInvitation::query()->whereKey($record['id'])->firstOrFail()->notify(new OrgInvitationCreated);

                            Notification::make()->title('Invitation resent')->success()->send();
                        },
                    ),
                Action::make('revoke')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(
                        /** @param Row $record */
                        fn (array $record): bool => $record['type'] === 'invitation',
                    )
                    ->action(
                        /** @param Row $record */
                        function (array $record): void {
                            OrgInvitation::query()->whereKey($record['id'])->delete();

                            Notification::make()->title('Invitation revoked')->success()->send();
                        },
                    ),
            ]);
    }

    /**
     * @return array<string, Row>
     */
    private function records(?string $search): array
    {
        $organization = $this->organization();
        $rows = [];

        foreach (OrgUser::query()->whereBelongsTo($organization)->with(['user', 'roles'])->get() as $orgUser) {
            $user = $orgUser->user()->firstOrFail();

            $rows["member-{$orgUser->id}"] = [
                'key' => "member-{$orgUser->id}",
                'id' => $orgUser->id,
                'type' => 'member',
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'Member',
                'roles' => $orgUser->roles->map(fn (Role $role): string => $role->name)->values()->all(),
                'expires_at' => null,
                'is_owner' => $orgUser->user_id === $organization->user_id,
            ];
        }

        foreach (OrgInvitation::query()->whereBelongsTo($organization)->with('roles')->get() as $invitation) {
            $rows["invitation-{$invitation->id}"] = [
                'key' => "invitation-{$invitation->id}",
                'id' => $invitation->id,
                'type' => 'invitation',
                'name' => $invitation->email,
                'email' => $invitation->email,
                'status' => 'Invited',
                'roles' => $invitation->roles->map(fn (Role $role): string => $role->name)->values()->all(),
                'expires_at' => $invitation->expires_at,
                'is_owner' => false,
            ];
        }

        if ($search === null || $search === '') {
            return $rows;
        }

        return array_filter(
            $rows,
            fn (array $row): bool => Str::contains($row['name'].' '.$row['email'], $search, ignoreCase: true),
        );
    }

    private function organization(): Organization
    {
        $tenant = Filament::getTenant();

        assert($tenant instanceof Organization);

        return $tenant;
    }
}
