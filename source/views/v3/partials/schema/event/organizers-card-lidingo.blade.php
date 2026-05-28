@card([
    'heading' => $lang->organizersTitle,
])
    @if(!empty($organizers))
        @slot('belowContent')
            @element(['classList' => ['u-display--flex', 'o-layout-grid--gap-8', 'u-flex-direction--column']])
                @foreach($organizers as $organizer)
                    @if(!empty($organizer['name']))
                        @element(['classList' => $loop->first ? ['u-margin__top--2'] : []])
                            @typography(['element' => 'h3', 'variant' => 'h4', 'classList' => ['u-margin__top--0']])
                                {!! $organizer['name'] !!}
                            @endtypography
                        @endelement
                    @endif
                @endforeach
            @endelement
        @endslot
    @endif
@endcard
