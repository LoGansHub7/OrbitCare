@props(['title', 'eyebrow' => null])
<header class="mb-4">@if($eyebrow)<div class="text-uppercase small fw-bold text-secondary mb-1">{{ $eyebrow }}</div>@endif<h2 class="h3 mb-0">{{ $title }}</h2></header>
