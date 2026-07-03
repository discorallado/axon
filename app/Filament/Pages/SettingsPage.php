<?php

namespace App\Filament\Pages;

use App\Models\UserSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

class SettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings-page';

    public string $theme_color = 'blue';

    public string $nav_style = 'sidebar';

    public bool $notify_email = true;

    public bool $notify_database = true;

    public static function getNavigationLabel(): string
    {
        return __('settings.title');
    }

    public function getTitle(): string
    {
        return __('settings.title');
    }

    public function mount(): void
    {
        $userId = auth()->id();

        $this->theme_color = UserSetting::get($userId, 'theme_color', 'blue');
        $this->nav_style = UserSetting::get($userId, 'nav_style', 'sidebar');
        $this->notify_email = (bool) UserSetting::get($userId, 'notify_email', '1');
        $this->notify_database = (bool) UserSetting::get($userId, 'notify_database', '1');
    }

    public function save(): void
    {
        $userId = auth()->id();

        UserSetting::set($userId, 'theme_color', $this->theme_color);
        UserSetting::set($userId, 'nav_style', $this->nav_style);
        UserSetting::set($userId, 'notify_email', $this->notify_email ? '1' : '0');
        UserSetting::set($userId, 'notify_database', $this->notify_database ? '1' : '0');

        $this->applyThemeColor($this->theme_color);

        Notification::make()
            ->title(__('settings.saved'))
            ->success()
            ->send();
    }

    private function applyThemeColor(string $color): void
    {
        $map = [
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'rose' => Color::Rose,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
        ];

        FilamentColor::register(['primary' => $map[$color] ?? Color::Blue]);
    }

    public static function availableColors(): array
    {
        return [
            'blue' => __('settings.colors.blue'),
            'indigo' => __('settings.colors.indigo'),
            'violet' => __('settings.colors.violet'),
            'purple' => __('settings.colors.purple'),
            'fuchsia' => __('settings.colors.fuchsia'),
            'rose' => __('settings.colors.rose'),
            'red' => __('settings.colors.red'),
            'orange' => __('settings.colors.orange'),
            'amber' => __('settings.colors.amber'),
            'lime' => __('settings.colors.lime'),
            'green' => __('settings.colors.green'),
            'teal' => __('settings.colors.teal'),
            'cyan' => __('settings.colors.cyan'),
            'sky' => __('settings.colors.sky'),
        ];
    }
}
