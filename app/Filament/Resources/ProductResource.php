<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminTable;
use App\Filament\Support\Filters\PriceRangeFilter;
use App\Filament\Support\ProductDeleteActions;
use App\Filament\Support\RichContentEditor;
use App\Filament\Support\SeoFormSchema;
use App\Models\Product;
use App\Support\AdminAccess;
use Illuminate\Database\Eloquent\Model;
use App\Support\ShopLabels;
use App\Support\SlugFromTitle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    /** Admin URLs use id — model route key is slug (shopfront); avoids 404 after create when slug changes from draft-* */
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'محصولات';

    protected static ?string $modelLabel = 'محصول';

    protected static ?string $pluralModelLabel = 'محصولات';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات اصلی')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('دسته‌بندی')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('brand_id')
                            ->label('برند')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('product_template_id', null)),

                        ...self::catalogTaxonomyFormFields(),

                        Forms\Components\Hidden::make('_slug_name_source')
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\Hidden $component, $state): void {
                                if (filled($state)) {
                                    return;
                                }
                                $record = $component->getLivewire()->getRecord();
                                if (! $record instanceof Product) {
                                    return;
                                }
                                $name = trim($record->name);
                                $slug = trim((string) $record->slug);
                                if ($name !== '' && $slug === SlugFromTitle::fromProductName($name)) {
                                    $component->state($name);

                                    return;
                                }
                                $component->state('__manual__');
                            }),

                        Forms\Components\TextInput::make('name')
                            ->label('نام محصول')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, Forms\Get $get): void {
                                $slug = trim((string) $get('slug'));
                                $source = (string) $get('_slug_name_source');
                                $auto = SlugFromTitle::fromProductName($state);

                                if ($slug === '' || str_starts_with($slug, 'draft-')) {
                                    $set('slug', $auto);
                                    $set('_slug_name_source', $state);

                                    return;
                                }

                                if ($source !== '__manual__' && ($source === '' || $slug === SlugFromTitle::fromProductName($source))) {
                                    $set('slug', $auto);
                                    $set('_slug_name_source', $state);
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('اسلاگ')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, Forms\Get $get): void {
                                $name = trim((string) $get('name'));
                                $slug = trim((string) $state);
                                if ($name !== '' && $slug === SlugFromTitle::fromProductName($name)) {
                                    $set('_slug_name_source', $name);
                                } else {
                                    $set('_slug_name_source', '__manual__');
                                }
                            })
                            ->helperText('از نام ساخته می‌شود: فاصله → خط تیره (مثلاً سرویس-چینی). حروف فارسی و انگلیسی حفظ می‌شوند.'),

                        Forms\Components\Textarea::make('short_description')
                            ->label('توضیحات کوتاه')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichContentEditor::make('description', 'توضیحات کامل', 'products'),
                    ])->columns(2),

                Forms\Components\Section::make('قیمت و موجودی')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('قیمت (تومان)')
                            ->numeric()
                            ->prefix('تومان')
                            ->required(),

                        Forms\Components\TextInput::make('sale_price')
                            ->label('قیمت فروش ویژه (تومان)')
                            ->numeric()
                            ->prefix('تومان')
                            ->helperText('در صورت وارد کردن، این قیمت به جای قیمت اصلی نمایش داده می‌شود'),

                        Forms\Components\TextInput::make('stock')
                            ->label('موجودی')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('فعلاً از این فرم ثبت می‌شود. پس از راه‌اندازی «مدیر موجودی»، مرجع نهایی موجودی از آن پنل خواهد بود.'),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('weight')
                            ->label('وزن (گرم)')
                            ->numeric()
                            ->suffix('گرم'),
                    ])->columns(2),

                Forms\Components\Section::make('وضعیت و نمایش')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true)
                            ->helperText('در صورت غیرفعال بودن، محصول در سایت نمایش داده نمی‌شود'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('محصول ویژه')
                            ->default(false)
                            ->helperText('محصولات ویژه در بخش‌های خاصی از سایت نمایش داده می‌شوند'),
                    ])->columns(2),

                ...SeoFormSchema::productSection(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->width('3.5rem')
                    ->grow(false),

                AdminImageColumn::make('primary_image_path', 44, 'تصویر'),

                Tables\Columns\TextColumn::make('name')
                    ->label('نام محصول')
                    ->searchable()
                    ->sortable()
                    ->wrap(false)
                    ->lineClamp(2)
                    ->extraCellAttributes(['class' => 'fi-admin-product-name'])
                    ->tooltip(fn (Product $record): string => $record->name),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('دسته‌بندی')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->width('7rem')
                    ->grow(false)
                    ->wrap(false),

                Tables\Columns\TextColumn::make('productFamily.name')
                    ->label('خانواده')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('productPlant.name')
                    ->label('کارخانه')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('قالب')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت')
                    ->alignEnd()
                    ->formatStateUsing(fn (?int $state): string => ShopLabels::formatMoney($state))
                    ->sortable()
                    ->width('9rem')
                    ->grow(false)
                    ->extraCellAttributes(['class' => 'fi-admin-product-price']),

                Tables\Columns\TextColumn::make('stock')
                    ->label('موجودی')
                    ->sortable()
                    ->width('4rem')
                    ->grow(false)
                    ->alignCenter()
                    ->extraCellAttributes(['class' => 'fi-admin-product-stock']),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean()
                    ->width('3.5rem')
                    ->grow(false)
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean()
                    ->width('3.5rem')
                    ->grow(false)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->shopJalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاریخ ویرایش')
                    ->shopJalaliDateTime()
                    ->sortable()
                    ->width('8.5rem')
                    ->grow(false),

                Tables\Columns\TextColumn::make('lastEditedBy.name')
                    ->label('آخرین ویرایش توسط')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->width('6.5rem')
                    ->grow(false)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('دسته‌بندی'),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('برند'),
                PriceRangeFilter::make('price', 10000000),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('وضعیت'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('محصول ویژه'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewStorefront')
                    ->label('نمایش محصول')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->color('gray')
                    ->url(fn (Product $record): string => route('products.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Product $record): bool => filled($record->slug)),
                Tables\Actions\EditAction::make()
                    ->label('ویرایش')
                    ->iconButton()
                    ->url(fn (Product $record): string => static::getUrl('edit', ['record' => $record->getKey()])),
                ProductDeleteActions::tableDeleteAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ProductDeleteActions::tableBulkDeleteAction(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'category:id,name',
            'brand:id,name',
            'productFamily:id,name',
            'productPlant:id,name',
            'productTemplate:id,name',
            'lastEditedBy:id,name',
            'images' => fn ($query) => $query
                ->select('id', 'product_id', 'image', 'is_primary', 'position')
                ->orderByDesc('is_primary')
                ->orderBy('position')
                ->limit(1),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttributesRelationManager::class,
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
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

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }

    public static function getUrl(
        string $name = 'index',
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
    ): string {
        $record = $parameters['record'] ?? null;
        if ($record instanceof Product) {
            $parameters['record'] = $record->getKey();
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    private static function catalogTaxonomyFormFields(): array
    {
        if (! Schema::hasTable('product_families')) {
            return [];
        }

        $fields = [
            Forms\Components\Select::make('product_family_id')
                ->label('خانواده محصول')
                ->relationship(
                    'productFamily',
                    'name',
                    fn ($query) => $query->where('is_active', true)->orderBy('position')
                )
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (callable $set) {
                    $set('product_plant_id', null);
                    $set('product_template_id', null);
                }),
        ];

        if (Schema::hasTable('product_plants')) {
            $fields[] = Forms\Components\Select::make('product_plant_id')
                ->label('کارخانه')
                ->relationship(
                    'productPlant',
                    'name',
                    modifyQueryUsing: function ($query, Get $get) {
                        $familyId = $get('product_family_id');

                        return $query
                            ->where('is_active', true)
                            ->orderBy('position')
                            ->when(
                                $familyId,
                                fn ($q) => $q->where(function ($q) use ($familyId) {
                                    $q->where('product_family_id', $familyId)
                                        ->orWhereNull('product_family_id');
                                })
                            );
                    }
                )
                ->searchable()
                ->preload()
                ->helperText('مثال: چینی پردیس، چینی اصفهان. با انتخاب خانواده، لیست فیلتر می‌شود.');
        }

        if (! Schema::hasTable('product_templates')) {
            $fields[] = Forms\Components\Placeholder::make('product_templates_missing')
                ->label('قالب محصول')
                ->content('جدول قالب محصول هنوز ساخته نشده. روی سرور: php artisan migrate --force');

            return $fields;
        }

        $fields[] = Forms\Components\Select::make('product_template_id')
            ->label('قالب محصول')
            ->relationship(
                'productTemplate',
                'name',
                modifyQueryUsing: function ($query, Get $get) {
                    $brandId = $get('brand_id');
                    if (! $brandId) {
                        return $query->whereRaw('0 = 1');
                    }

                    $familyId = $get('product_family_id');

                    return $query
                        ->where('brand_id', $brandId)
                        ->where('is_active', true)
                        ->orderBy('position')
                        ->when(
                            $familyId,
                            fn ($q) => $q->where(function ($q) use ($familyId) {
                                $q->where('product_family_id', $familyId)
                                    ->orWhereNull('product_family_id');
                            })
                        );
                }
            )
            ->searchable()
            ->preload()
            ->disabled(fn (Get $get): bool => ! $get('brand_id'))
            ->helperText('ابتدا برند را انتخاب کنید. قالب باید متعلق به همان برند باشد.');

        return $fields;
    }
}
