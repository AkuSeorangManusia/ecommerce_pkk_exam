<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlert extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Low Stock Alerts';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('track_stock', true)
                    ->where('is_active', true)
                    ->whereColumn('stock', '<=', 'low_stock_threshold')
                    ->orderBy('stock', 'asc')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->images ? [$record->images[0] ?? null] : [])
                    ->stacked()
                    ->limit(1),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Current Stock')
                    ->badge()
                    ->color(fn ($record) => $record->stock <= 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('low_stock_threshold')
                    ->label('Threshold'),
                Tables\Columns\TextColumn::make('stock_needed')
                    ->label('Restock Qty')
                    ->getStateUsing(fn ($record) => max(0, ($record->low_stock_threshold * 2) - $record->stock))
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Update Stock')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', $record)),
            ])
            ->emptyStateHeading('All products are well stocked!')
            ->emptyStateDescription('No products are currently below their low stock threshold.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25]);
    }
}
