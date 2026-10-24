{{--
    DIU article / department page hero (DIUFronend article/[slug], department/[slug]/[deptSlug]): the page's
    featured picture, else the theme option page_hero_image, else DIU's; breadcrumb Home › microsite › parents › page.
    Contract: $page (Page), $context
--}}
@include('theme::partials.diu.hero', [
    'title' => $page->title,
    'subtitle' => null,
    'image' => $page->firstMedia('featured_image')?->url('large') ?? theme_image('page_hero_image', $context->isMicrosite() ? 'images/faculty-banner.jpg' : 'images/page-hero.jpg'),
    'breadcrumbs' => [
        ...(\Platform\Context\Breadcrumbs::for($context) ?: [['label' => __('theme::theme.home'), 'url' => url('/')]]),
        ...array_map(fn ($parent) => ['label' => $parent->title, 'url' => url($parent->url())], $page->ancestors()),
        ['label' => $page->title, 'url' => url($page->url())],
    ],
])
