<?php
    $location_names = [];
    foreach ($locations as $loc_item) {
        $location_names[$loc_item->get('id')] = $loc_item->get('name');
    }
?>

<div class="myplants-header">
    <h1>{{ __('app.nav_plants') }}</h1>
    <h2>{{ count($plants) === 1 ? __('app.plant_count_one') : __('app.plant_count', ['count' => count($plants)]) }}</h2>
</div>

@include('flashmsg.php')

<div class="myplants-filter" id="myplants-filter">
    <a class="myplants-filter-chip is-active" href="javascript:void(0);" data-location="0">{{ __('app.all') }}</a>
    @foreach ($locations as $loc_item)
        <a class="myplants-filter-chip" href="javascript:void(0);" data-location="{{ $loc_item->get('id') }}">{{ $loc_item->get('name') }}</a>
    @endforeach
</div>

<div class="myplants-grid" id="myplants-grid">
    @if (count($plants) > 0)
        @foreach ($plants as $plant)
            <a class="myplants-card" href="{{ url('/plants/details/' . $plant->get('id')) }}" data-location="{{ $plant->get('location') }}">
                <div class="myplants-card-photo" style="background-image: url('{{ abs_photo($plant->get('photo')) }}');">
                    @if ($plant->get('health_state') !== 'in_good_standing')
                        <span class="myplants-card-health"><i class="{{ PlantsModel::$plant_health_states[$plant->get('health_state')]['icon'] }} plant-state-{{ $plant->get('health_state') }}"></i></span>
                    @endif
                </div>

                <div class="myplants-card-body">
                    <div class="myplants-card-name">{{ $plant->get('name') }}</div>

                    @if ($plant->get('scientific_name'))
                        <div class="myplants-card-species">{{ $plant->get('scientific_name') }}</div>
                    @endif

                    <div class="myplants-card-meta">
                        <span class="myplants-card-location"><i class="fas fa-map-marker-alt"></i>&nbsp;{{ $location_names[$plant->get('location')] ?? '?' }}</span>

                        @if ($plant->get('last_watered'))
                            <span class="myplants-card-watered"><i class="fas fa-tint"></i>&nbsp;{{ (new Carbon($plant->get('last_watered')))->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    @else
        <div class="plants-empty">
            <div class="plants-empty-image">
                <img src="{{ asset('img/plantsempty.png') }}" alt="image"/>
            </div>

            <div class="plants-empty-text">{{ __('app.content_empty') }}</div>
        </div>
    @endif
</div>

<div class="myplants-fab">
    <a href="javascript:void(0);" onclick="document.getElementById('inpLocationId').value = document.querySelector('#myplants-filter .is-active').getAttribute('data-location') || 0; window.vue.bShowAddPlant = true;" title="{{ __('app.add_plant') }}">
        <i class="fas fa-plus"></i>
    </a>
</div>

<script>
document.getElementById('myplants-filter').addEventListener('click', function(e) {
    const chip = e.target.closest('.myplants-filter-chip');
    if (!chip) return;

    document.querySelectorAll('#myplants-filter .myplants-filter-chip').forEach(function(c) { c.classList.remove('is-active'); });
    chip.classList.add('is-active');

    const loc = chip.getAttribute('data-location');
    document.querySelectorAll('#myplants-grid .myplants-card').forEach(function(card) {
        card.style.display = (loc === '0' || card.getAttribute('data-location') === loc) ? '' : 'none';
    });
});
</script>
