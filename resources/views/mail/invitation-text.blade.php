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
Reserved in your honor: {!! $seatsLabel !!}

Open your invitation: {!! $link !!}
@if ($deadline)
Kindly reply on or before {!! $deadline !!}.
@endif

{!! $hashtag !!}
