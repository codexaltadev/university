{{--
    DIU Parteners on its own: the logos run the full width; on department pages DIU's grey page
    shows behind them. Contract: $section (content: grey, space_below, partners_title, partners, partners_button_label, partners_button_url)
--}}
<div @class(['py-px', 'bg-gray-50' => $section->content['grey'], 'pb-[50px] md:pb-[80px]' => $section->content['space_below']])>
    @include('theme::partials.diu.partners', ['content' => $section->content])
</div>
