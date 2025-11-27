<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Order Information')
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->default(fn () => 'ORD-' . strtoupper(uniqid()))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('email')->email()->required(),
                                        Forms\Components\TextInput::make('phone'),
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $customer = \App\Models\Customer::find($state);
                                            if ($customer) {
                                                $set('shipping_name', $customer->name);
                                                $set('shipping_phone', $customer->phone);
                                                $set('shipping_address', $customer->address);
                                                $set('shipping_city', $customer->city);
                                                $set('shipping_state', $customer->state);
                                                $set('shipping_postal_code', $customer->postal_code);
                                                $set('shipping_country', $customer->country ?? 'Indonesia');
                                            }
                                        }
                                    }),
                            ])->columns(2),

                        Forms\Components\Section::make('Order Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->options(Product::query()->active()->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('unit_price', $product->price);
                                                        $set('product_name', $product->name);
                                                        $set('product_sku', $product->sku);
                                                        $quantity = $get('quantity') ?? 1;
                                                        $set('subtotal', $product->price * $quantity);
                                                    }
                                                }
                                            })
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $set('subtotal', $unitPrice * ($state ?? 1));
                                            })
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $quantity = $get('quantity') ?? 1;
                                                $set('subtotal', ($state ?? 0) * $quantity);
                                            })
                                            ->columnSpan(2),
                                        Forms\Components\Hidden::make('product_name'),
                                        Forms\Components\Hidden::make('product_sku'),
                                        Forms\Components\Hidden::make('subtotal'),
                                    ])
                                    ->columns(6)
                                    ->defaultItems(1)
                                    ->addActionLabel('Add Product')
                                    ->reorderable(false)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        self::updateTotals($get, $set);
                                    })
                                    ->deleteAction(
                                        fn ($action) => $action->after(fn (Forms\Get $get, Forms\Set $set) => self::updateTotals($get, $set))
                                    )
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Shipping Information')
                            ->schema([
                                Forms\Components\TextInput::make('shipping_name')
                                    ->label('Recipient Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('shipping_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('shipping_address')
                                    ->label('Address')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('shipping_city')
                                    ->label('City')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('shipping_state')
                                    ->label('State / Province')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('shipping_postal_code')
                                    ->label('Postal Code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('shipping_country')
                                    ->label('Country')
                                    ->required()
                                    ->maxLength(255)
                                    ->default('Indonesia'),
                            ])->columns(2),

                        Forms\Components\Section::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Customer Notes')
                                    ->rows(2),
                                Forms\Components\Textarea::make('admin_notes')
                                    ->label('Admin Notes')
                                    ->rows(2)
                                    ->helperText('Internal notes, not visible to customer'),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $now = now();
                                        if ($state === 'shipped' && !$get('shipped_at')) {
                                            $set('shipped_at', $now);
                                        }
                                        if ($state === 'delivered' && !$get('delivered_at')) {
                                            $set('delivered_at', $now);
                                            if (!$get('shipped_at')) {
                                                $set('shipped_at', $now);
                                            }
                                        }
                                    }),
                                Forms\Components\Select::make('payment_status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'failed' => 'Failed',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state === 'paid' && !$get('paid_at')) {
                                            $set('paid_at', now());
                                        }
                                    }),
                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'bank_transfer' => 'Bank Transfer',
                                        'credit_card' => 'Credit Card',
                                        'e_wallet' => 'E-Wallet',
                                        'cod' => 'Cash on Delivery',
                                    ])
                                    ->native(false),
                            ]),

                        Forms\Components\Section::make('Order Summary')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Calculated from order items'),
                                Forms\Components\TextInput::make('tax')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('12% of subtotal'),
                                Forms\Components\TextInput::make('shipping_cost')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        self::updateTotals($get, $set);
                                    }),
                                Forms\Components\TextInput::make('discount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        self::updateTotals($get, $set);
                                    }),
                                Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Subtotal + Tax + Shipping - Discount'),
                            ]),

                        Forms\Components\Section::make('Timestamps')
                            ->schema([
                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->label('Paid At')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-filled when payment status is "Paid"'),
                                Forms\Components\DateTimePicker::make('shipped_at')
                                    ->label('Shipped At')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-filled when status is "Shipped"'),
                                Forms\Components\DateTimePicker::make('delivered_at')
                                    ->label('Delivered At')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-filled when status is "Delivered"'),
                            ])
                            ->collapsed(),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'info',
                        'shipped' => 'warning',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('shipping_city')
                    ->label('City')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('markAsPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->payment_status === 'pending')
                        ->action(fn ($record) => $record->markAsPaid()),
                    Tables\Actions\Action::make('markAsShipped')
                        ->label('Mark as Shipped')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === 'processing')
                        ->action(fn ($record) => $record->markAsShipped()),
                    Tables\Actions\Action::make('markAsDelivered')
                        ->label('Mark as Delivered')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === 'shipped')
                        ->action(fn ($record) => $record->markAsDelivered()),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        // Get all items from the repeater
        $items = $get('items') ?? [];
        
        // Calculate subtotal from all items
        $subtotal = collect($items)->sum(function ($item) {
            return ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
        });
        
        // Calculate tax at 12%
        $tax = $subtotal * 0.12;
        
        // Get shipping and discount
        $shippingCost = (float) ($get('shipping_cost') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        
        // Calculate total
        $total = $subtotal + $tax + $shippingCost - $discount;
        
        // Update the form fields
        $set('subtotal', round($subtotal, 2));
        $set('tax', round($tax, 2));
        $set('total', round($total, 2));
    }
}
