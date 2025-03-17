<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Product';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('tagline')
                            ->required()
                            ->maxLength(255),

                        FileUpload::make('thumbnail')
                            ->image()
                            ->required(),

                        FileUpload::make('photo')
                            ->image()
                            ->required(),

                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('IDR')
                            ->live(true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $price = (int) $get('price');
                                $capacity = (int) $get('capacity');
                                if ($capacity > 0) {
                                    $set('price_per_person', $price / $capacity);
                                } else {
                                    $set('price_per_person', null);
                                }
                            }),

                        TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->prefix('People')
                            ->live(true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $price = (int) $get('price');
                                $capacity = (int) $get('capacity');
                                if ($capacity > 0) {
                                    $set('price_per_person', $price / $capacity);
                                } else {
                                    $set('price_per_person', null);
                                }
                            }),

                        TextInput::make('price_per_person')
                            ->numeric()
                            ->readOnly()
                            ->prefix('IDR')
                            ->afterStateHydrated(function (Get $get, Set $set) {
                                $price = $get('price');
                                $capacity = $get('capacity');
                                if ($capacity > 0) {
                                    $set('price_per_person', $price / $capacity);
                                } else {
                                    $set('price_per_person', null);
                                }
                            }),

                        TextInput::make('duration')
                            ->required()
                            ->numeric()
                            ->prefix('month')
                    ]),

                Fieldset::make('Additional')
                    ->schema([
                        Textarea::make('about')
                            ->required()
                            ->autosize(),

                        Repeater::make('keypoints')
                            ->relationship('keypoints')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                            ]),

                        Select::make('is_popular')
                            ->options([
                                true => 'Popular',
                                false => 'Not Popular'
                            ])
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail'),

                TextColumn::make('name')
                    ->searchable(),

                IconColumn::make('is_popular')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Popular'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
