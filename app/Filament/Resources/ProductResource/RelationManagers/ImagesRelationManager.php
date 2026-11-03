<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Media\UploadedPublicImageStorage;
use App\Services\Product\ProductGalleryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'تصاویر محصول';

    protected static ?string $modelLabel = 'تصویر';

    protected static ?string $pluralModelLabel = 'تصاویر';

    public function form(Form $form): Form
    {
        return $form->schema([
            ShopMediaPicker::image('image', 'products', 'تصویر')
                ->required(fn (string $operation): bool => $operation === 'create'),
            Forms\Components\TextInput::make('price')
                ->label('قیمت این تصویر (تومان)')
                ->numeric()
                ->prefix('تومان')
                ->helperText('برای تصویر اصلی و هر تصویر گالری می‌توانید قیمت جداگانه تعیین کنید. خالی = استفاده از قیمت محصول'),
            Forms\Components\Toggle::make('is_primary')
                ->label('تصویر اصلی')
                ->helperText('فقط یک تصویر می‌تواند اصلی باشد'),
            Forms\Components\TextInput::make('position')
                ->label('ترتیب')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            AdminImageColumn::make('image', 60)->label('پیش‌نمایش'),
            Tables\Columns\IconColumn::make('is_primary')
                ->label('اصلی')
                ->boolean(),
            Tables\Columns\TextColumn::make('price')
                ->label('قیمت تصویر')
                ->formatStateUsing(fn (?int $state) => $state ? number_format($state).' تومان' : '—')
                ->placeholder('قیمت محصول'),
            Tables\Columns\TextColumn::make('position')
                ->label('ترتیب'),
        ])->headerActions([
            Tables\Actions\Action::make('bulkUploadImages')
                ->label('بارگذاری چند تصویر')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('بارگذاری چند تصویر')
                ->modalDescription('چند فایل را یک‌جا انتخاب کنید. پس از اتمام آپلود، تأیید کنید تا به گالری محصول اضافه شوند.')
                ->modalSubmitActionLabel('افزودن به گالری')
                ->form([
                    Forms\Components\FileUpload::make('images')
                        ->label('تصاویر')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('products')
                        ->visibility('public')
                        ->maxSize(51200)
                        ->maxFiles(30)
                        ->required()
                        ->reorderable()
                        ->panelLayout('grid')
                        ->helperText('در پنجرهٔ انتخاب فایل می‌توانید با Ctrl/Cmd یا Shift چند تصویر را هم‌زمان انتخاب کنید.')
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                            return app(UploadedPublicImageStorage::class)->store($file, 'products');
                        }),
                ])
                ->action(function (array $data, ProductGalleryService $gallery, RelationManager $livewire): void {
                    $paths = $gallery->pathsFromUploadState($data['images'] ?? null);
                    $count = $gallery->attachPaths($livewire->getOwnerRecord(), $paths);

                    if ($count === 0) {
                        Notification::make()
                            ->title('تصویری اضافه نشد')
                            ->body('حداقل یک فایل معتبر بارگذاری کنید.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('تصاویر اضافه شد')
                        ->body("{$count} تصویر به گالری محصول اضافه شد.")
                        ->success()
                        ->send();
                }),
            Tables\Actions\CreateAction::make()
                ->label('افزودن تصویر')
                ->modalHeading('افزودن تصویر'),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->label('ویرایش')
                ->modalHeading('ویرایش تصویر'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ]);
    }
}
