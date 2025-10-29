@php

    $steps = ['Basics', 'Pack Sizes', 'Pricing', 'Inventory', 'Category', 'Review'];

    if ($type != 'simple') {
        array_splice($steps, 0, 0, 'Varients');
    }

@endphp

<div class="progress-container">
    <div class="step-progress" style="margin-top: 110px;margin-bottom: 20px;">
        <div class="step-line"></div>

        @foreach ($steps as $step)
            <div class="step-item @if($currentStep == $loop->iteration) active @elseif($loop->iteration != 1 && $currentStep > ($loop->iteration - 1)) completed @else pending @endif">
                <div class="step-circle"> {{ $loop->iteration }} </div>
                <div class="step-label"> {{ $step  }} </div>
            </div>
        @endforeach

    </div>
</div>