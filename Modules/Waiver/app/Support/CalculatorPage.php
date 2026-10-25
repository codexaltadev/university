<?php

namespace Modules\Waiver\Support;

/**
 * What /waiver-calculator shows around the form, ready for a theme (`$page` in waiver::calculator.page):
 * the hero (title, line, picture) and the "Important Notice" with its link, from Settings › Waiver
 * Calculator. Themes hold no PHP logic (T-1).
 */
class CalculatorPage
{
    public function __construct(protected array $data = []) {}

    public function title(): string
    {
        return (string) (settings('waiver.title') ?: __('waiver::waiver.calculator.title'));
    }

    public function text(): ?string
    {
        return filled($text = settings('waiver.text')) ? (string) $text : null;
    }

    public function image(): ?string
    {
        return media_url(settings('waiver.image'), 'large');
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(): array
    {
        return [
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            ['label' => __('waiver::waiver.calculator.title'), 'url' => url('/'.CalculatorWebsite::PAGE)],
        ];
    }

    /** The notice above the form (the calculator's disclaimer), when there is one. @return array{title: string, text: string, url: ?string, label: string}|null */
    public function notice(): ?array
    {
        if (blank($text = settings('waiver.disclaimer'))) {
            return null;
        }

        return [
            'title' => (string) (settings('waiver.notice_title') ?: __('waiver::waiver.calculator.notice')),
            'text' => (string) $text,
            'url' => filled($url = settings('waiver.notice_url')) ? url((string) $url) : null,
            'label' => (string) (settings('waiver.notice_label') ?: __('waiver::waiver.calculator.view_details')),
        ];
    }

    public function tuitionFeesUrl(): string
    {
        return url('/tuition-fees');
    }
}
