<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Management';
    protected static ?string $label = 'User';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama')
                ->required(),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                ->dehydrated(fn($state) => filled($state))
                ->required(fn(string $operation) => $operation === 'create')
                ->helperText('Kosongkan jika tidak ingin mengubah password'),

            Select::make('roles')
                ->label('Role')
                ->options(Role::pluck('name', 'name'))
                ->multiple()
                ->preload()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'admin'     => 'danger',
                        'pelanggan' => 'info',
                        default     => 'gray',
                    }),

                TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Total Booking'),

                TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->date('d M Y'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->options(Role::pluck('name', 'name'))
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->whereHas('roles', fn($q) =>
                                $q->where('name', $data['value'])
                            );
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn(User $record) => !$record->hasRole('admin')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
            'view'   => Pages\ViewUser::route('/{record}'),
        ];
    }
}
