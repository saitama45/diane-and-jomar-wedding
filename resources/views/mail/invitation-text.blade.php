{{-- Plain-text part of the invitation email (raw output: this is not HTML). --}}
{!! $kicker !!}
{!! $bride !!} & {!! $groom !!}

Dear {!! $guest !!},

{!! $note !!}

{!! $weekday !!}, {!! $longDate !!} · {!! $time !!}
{!! $city !!}

@foreach ($events as $event)
{!! $event['title'] !!} · {!! $event['time'] !!}
{!! $event['venue'] !!}{!! $event['address'] ? ', '.$event['address'] : '' !!}

@endforeach
@if ($attire)
Attire & Motif
@if ($attire['colors'])
{!! collect($attire['colors'])->pluck('name')->join(' · ') !!}
@endif
@if ($attire['sponsors'] || $attire['gentlemen'] || $attire['ladies'])
Principal Sponsors{!! $attire['sponsors'] ? ': '.$attire['sponsors'] : '' !!}
@if ($attire['gentlemen'])
Gentlemen: {!! $attire['gentlemen'] !!}
@endif
@if ($attire['ladies'])
Ladies: {!! $attire['ladies'] !!}
@endif
@endif
@if ($attire['guests'] || $attire['guestsNote'])
Our Guests{!! $attire['guests'] ? ': '.$attire['guests'] : '' !!}
@if ($attire['guestsNote'])
{!! $attire['guestsNote'] !!}
@endif
@endif

@endif
@if ($giftGuide)
@if ($giftGuide['title'])
{!! $giftGuide['title'] !!}
@endif
{!! $giftGuide['message'] !!}

@endif
Reserved in your honor: {!! $seatsLabel !!}

Open your invitation: {!! $link !!}
@if ($deadline)
Kindly reply on or before {!! $deadline !!}.
@endif

{!! $hashtag !!}
