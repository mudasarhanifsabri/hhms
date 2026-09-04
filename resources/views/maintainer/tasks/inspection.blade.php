@extends('layouts.app')

@section('content')
<style>#inspection-wizard [hidden]{display:none!important}#inspection-wizard progress{accent-color:#6844e8}#inspection-wizard .pwa-inspection-item{margin-bottom:16px}</style>
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Inspection', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        <div class="pwa-detail-head">
            <span class="pwa-chip purple">{{ $inspection->inspection_number }}</span>
            <span class="pwa-status-pill">{{ $inspection->type_label }}</span>
        </div>
        <h2 class="pwa-title">{{ $task->title }}</h2>
        <p class="pwa-subtitle">{{ $inspection->property?->building?->building_name ?? $task->booking?->property?->building?->name ?? 'Property' }} • {{ $inspection->property?->name ?? $task->booking?->property?->name ?? 'Unit' }}</p>

        <form action="{{ route('maintainer.task.inspection.submit', $task->id) }}" method="POST" enctype="multipart/form-data" class="pwa-form" id="inspection-wizard">
            <div id="wizard-progress" class="pwa-section" hidden><strong id="wizard-step" aria-live="polite"></strong><progress id="wizard-bar" max="100" value="0" style="width:100%" aria-label="Inspection progress"></progress></div>
            <section data-wizard-step data-step-title="Rooms" class="pwa-section"><h3>Rooms to inspect</h3><p>Work through each room, record condition and photos, then count inventory.</p><ul>@foreach($inspection->items->groupBy('area') as $area => $roomItems)<li>{{ $area }} · {{ $roomItems->count() }} checks</li>@endforeach</ul><small>Nothing is marked Good automatically. All rooms must be reviewed.</small></section>
            @csrf
            @foreach($inspection->items->groupBy('area') as $area => $items)
                <section data-wizard-step data-step-title="{{ $area }}" class="pwa-section pwa-inspection-area">
                    <h3>{{ $area }}</h3>
                    @foreach($items as $item)
                        <div class="pwa-inspection-item">
                            <strong>{{ $item->item }}</strong>
                            <div class="pwa-segment pwa-condition-segment">
                                @foreach(['good' => 'Good', 'issue' => 'Issue', 'na' => 'N/A'] as $key => $label)
                                    <label>
                                        <input type="radio" name="items[{{ $item->id }}][condition]" value="{{ $key }}" @checked(old('items.'.$item->id.'.condition') === $key) required>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="pwa-field">
                                <label>Remark</label>
                                <textarea name="items[{{ $item->id }}][comment]" rows="2" placeholder="Add issue details if needed.">{{ old('items.'.$item->id.'.comment', $item->comment) }}</textarea>
                            </div>
                            @include('maintainer.tasks.inspection-photos')
                        </div>
                    @endforeach
                </section>
            @endforeach

            @if(count($inventoryRows))
            <section data-wizard-step data-step-title="Inventory" class="pwa-section"><h3>Inventory counts</h3><p>Count all items present. Damaged is included in Found. Office approval updates stock.</p>
            @foreach($inventoryRows as $row)
            <div class="pwa-inspection-item"><strong>{{ $row['room'] }} — {{ $row['name'] }}</strong><p>Required {{ $row['required'] }} · Previous count {{ $row['before'] }}</p>
                <div class="row g-2"><div class="col-6"><label>Found<input class="form-control" type="number" min="0" max="100000" name="inventory[{{ $row['id'] }}][found]" value="{{ old('inventory.'.$row['id'].'.found') }}" required></label></div>
                <div class="col-6"><label>Damaged<input class="form-control" type="number" min="0" name="inventory[{{ $row['id'] }}][damaged]" value="{{ old('inventory.'.$row['id'].'.damaged') }}" required></label></div></div>
                <label>Evidence / notes<textarea class="form-control" name="inventory[{{ $row['id'] }}][notes]" placeholder="Describe damage; attach photos under the room checklist below">{{ old('inventory.'.$row['id'].'.notes') }}</textarea></label>
            </div>
            @endforeach</section>
            @endif
            <section data-wizard-step data-step-title="Review & Submit" class="pwa-section"><h3>Review inspection</h3><div id="wizard-review" aria-live="polite"></div><p>Submitting completes the task. Inventory counts still need office approval.</p><div class="pwa-field">
                <label for="notes">Final Notes</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Overall inspection notes.">{{ old('notes', $inspection->notes) }}</textarea>
            </div>

            </section><div id="wizard-navigation" class="d-flex gap-2" hidden><button type="button" id="wizard-back" class="pwa-secondary-button">Back</button><button type="button" id="wizard-next" class="pwa-primary-button purple">Next</button></div>
            <button id="wizard-submit" class="pwa-primary-button green" type="submit">Submit Inspection</button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('inspection-wizard');
    if (!form) return;
    form.querySelectorAll('[data-inspection-photos]').forEach(picker => {
        let files = [], urls = [];
        const stored = picker.querySelector('[data-photo-files]'), list = picker.querySelector('[data-photo-list]'), message = picker.querySelector('[data-photo-message]');
        function render() {
            urls.forEach(url => URL.revokeObjectURL(url)); urls = []; list.replaceChildren();
            const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); stored.files = transfer.files;
            files.forEach((file,index) => {
                const wrapper = document.createElement('div'), img = document.createElement('img'), remove = document.createElement('button');
                const url = URL.createObjectURL(file); urls.push(url); img.src = url; img.alt = file.name; img.width=72; img.height=72; img.style.objectFit='cover';
                remove.type='button'; remove.className='btn btn-light btn-sm'; remove.textContent='Remove'; remove.setAttribute('aria-label','Remove '+file.name);
                remove.addEventListener('click', () => { files.splice(index,1); render(); });
                wrapper.append(img,remove); list.append(wrapper);
            });
            message.textContent = files.length+' of 5 photos selected';
        }
        picker.querySelectorAll('[data-photo-pick]').forEach(input => input.addEventListener('change', () => {
            const added = [...input.files]; input.value='';
            if (files.length+added.length > 5 || added.some(f => f.size > 5*1024*1024 || !['image/jpeg','image/png','image/webp'].includes(f.type))) {
                message.textContent='Choose up to 5 JPG, PNG or WebP photos, each no larger than 5 MB.'; return;
            }
            files.push(...added); render();
        }));
    });
    const steps = [...form.querySelectorAll('[data-wizard-step]')];
    const next = document.getElementById('wizard-next'), back = document.getElementById('wizard-back');
    const submit = document.getElementById('wizard-submit');
    let current = 0;
    form.noValidate = true;
    document.getElementById('wizard-progress').hidden = false;
    document.getElementById('wizard-navigation').hidden = false;
    function review() {
        const box = document.getElementById('wizard-review');
        box.replaceChildren();
        steps.forEach(step => {
            const radios = [...step.querySelectorAll('input[type=radio]:checked')];
            if (!radios.length) return;
            const p = document.createElement('p');
            p.textContent = step.dataset.stepTitle + ': ' + radios.filter(x => x.value === 'good').length + ' good, ' + radios.filter(x => x.value === 'issue').length + ' issues, ' + radios.filter(x => x.value === 'na').length + ' N/A';
            box.append(p);
        });
        form.querySelectorAll('input[name^="inventory"][name$="[found]"]').forEach(found => {
            const damaged = form.elements.namedItem(found.name.replace('[found]', '[damaged]'));
            const p = document.createElement('p');
            p.textContent = found.closest('.pwa-inspection-item').querySelector('strong').textContent + ': ' + found.value + ' found, ' + damaged.value + ' damaged';
            box.append(p);
        });
    }
    function show(scroll = false) {
        steps.forEach((s,i) => { s.hidden = i !== current; });
        document.getElementById('wizard-step').textContent = 'Step ' + (current+1) + ' of ' + steps.length + ' — ' + steps[current].dataset.stepTitle;
        document.getElementById('wizard-bar').value = (current+1)/steps.length*100;
        back.disabled = current === 0;
        next.hidden = current === steps.length-1;
        submit.hidden = current !== steps.length-1;
        if (current === steps.length-1) review();
        if (scroll) document.getElementById('wizard-progress').scrollIntoView({block:'start'});
    }
    function valid(step) {
        for (const field of step.querySelectorAll('input,textarea,select')) {
            if (field.name.startsWith('inventory') && field.name.endsWith('[damaged]')) {
                const found = form.elements.namedItem(field.name.replace('[damaged]','[found]'));
                field.setCustomValidity(Number(field.value) > Number(found.value) ? 'Damaged cannot exceed Found.' : '');
            }
            if (!field.checkValidity()) { field.reportValidity(); return false; }
        }
        return true;
    }
    next.addEventListener('click', () => { if (valid(steps[current])) { current++; show(true); } });
    back.addEventListener('click', () => { current--; show(true); });
    form.addEventListener('submit', event => {
        for (let i=0;i<steps.length;i++) {
            current=i; show();
            if (!valid(steps[i])) { event.preventDefault(); return; }
        }
        submit.disabled=true; submit.textContent='Submitting…';
    });
    show();
});
</script>
@endsection
