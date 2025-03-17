<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductSubscriptionResource\Pages;
use App\Filament\Resources\ProductSubscriptionResource\RelationManagers;
use App\Models\Product;
use App\Models\ProductSubscription;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductSubscriptionResource extends Resource
{
    protected static ?string $model = ProductSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Transactions';

    public static function getNavigationBadge(): ?string
    {
        return (string) ProductSubscription::where('is_paid', false)->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Product and Price')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live(true)
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $product = Product::find($state);
                                            $price = $product ? $product->price_per_person : 0;
                                            $duration = $product ? $product->duration : 0;

                                            $set('price', $price);
                                            $set('duration', $duration);

                                            $tax = 0.11;
                                            $totalTaxAmount = $price * $tax;

                                            $total_amount = $price + $totalTaxAmount;

                                            $set('total_amount', $total_amount);
                                            $set('total_tax_amount', $totalTaxAmount);
                                        })
                                        ->afterStateHydrated(function ($state, Get $get, Set $set) {
                                            $product_id = $state;
                                            if ($product_id) {
                                                $product = Product::find($product_id);
                                                $price = $product ? $product->price_per_person : 0;
                                                $set('price', $price);

                                                $tax = 0.11;
                                                $totalTaxAmount = $price * $tax;
                                                $set('total_tax_amount', number_format($totalTaxAmount, 0, ".", ","));
                                            }
                                        }),

                                    TextInput::make('price')
                                        ->required()
                                        ->label('Price per Person')
                                        ->readOnly()
                                        ->prefix('IDR')
                                        ->numeric()
                                        ->afterStateHydrated(function (Get $get, Set $set) {
                                            $price = $get('price') ?? 0;
                                            $tax = 0.11;
                                            $totalTaxAmount = $price * $tax;
                                            $set('total_tax_amount', $totalTaxAmount);
                                            $set('total_amount', $price + $totalTaxAmount);
                                        }),

                                    TextInput::make('total_amount')
                                        ->required()
                                        ->readOnly()
                                        ->prefix('IDR')
                                        ->readOnly(),

                                    TextInput::make('total_tax_amount')
                                        ->required()
                                        ->readOnly()
                                        ->numeric()
                                        ->prefix('IDR'),

                                    TextInput::make('duration')
                                        ->required()
                                        ->numeric()
                                        ->readOnly()
                                        ->prefix('month')
                                ])
                        ]),

                    Step::make('Customer Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('phone')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('email')
                                        ->required()
                                        ->maxLength(255),
                                ])
                        ]),

                    Step::make('Payment Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('booking_trx_id')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('customer_bank_name')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('customer_bank_account')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('customer_bank_number')
                                        ->required()
                                        ->maxLength(255),

                                    ToggleButtons::make('is_paid')
                                        ->required()
                                        ->label('Apakah sudah membayar?')
                                        ->boolean()
                                        ->grouped()
                                        ->icons([
                                            true => 'heroicon-o-pencil',
                                            false => 'heroicon-o-clock',
                                        ]),

                                    FileUpload::make('proof')
                                        ->required()
                                        ->image()
                                ])
                        ])
                ])
                    ->columnSpan('full')
                    ->columns(1)
                    ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('product.thumbnail'),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('booking_trx_id')
                    ->searchable(),

                IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Terverifikasi'),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->action(function (ProductSubscription $record) {
                        $record->is_paid = true;
                        $record->save();

                        Notification::make()
                            ->title('Order Approved')
                            ->success()
                            ->body('The order has been successfully approved.')
                            ->send();
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(ProductSubscription $record) => !$record->is_paid),

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
            'index' => Pages\ListProductSubscriptions::route('/'),
            'create' => Pages\CreateProductSubscription::route('/create'),
            'edit' => Pages\EditProductSubscription::route('/{record}/edit'),
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
