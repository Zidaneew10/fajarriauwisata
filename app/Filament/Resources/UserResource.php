<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'User';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'User';

    public static function canAccess(): bool
    {
        return static::isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi User')
                ->schema([

                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required(),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),

                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) =>
                            filled($state)
                                ? Hash::make($state)
                                : null
                        )
                        ->dehydrated(fn ($state) =>
                            filled($state)
                        )
                        ->required(fn (string $operation) =>
                            $operation === 'create'
                        )
                        ->helperText('Kosongkan jika tidak ingin mengganti password'),

                    Select::make('roles')
                        ->label('Role')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required(),

                ])
                ->columns(2),

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
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'admin' => 'danger',
                        'pelanggan' => 'info',
                        default => 'gray',
                    }),

    

                TextColumn::make('created_at')
                    ->label('Tanggal Bergabung')
                    ->date('d M Y'),

            ])

            ->filters([

                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name'),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn (User $record) =>
                        !$record->hasRole('admin')
                    ),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
