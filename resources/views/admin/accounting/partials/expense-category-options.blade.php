@foreach(\App\Models\Expense::CATEGORY_GROUPS as $group => $categoryKeys)
    <optgroup label="{{ $group }}">
        @foreach($categoryKeys as $categoryKey)
            @if(isset($expenseCategories[$categoryKey]))
                @php($categorySmartDefaults = \App\Models\Expense::categoryDefaults($categoryKey))
                <option value="{{ $categoryKey }}"
                    @selected(($selectedCategory ?? null) === $categoryKey)
                    @if($includeSmartDefaults ?? false)
                        data-responsibility="{{ $categorySmartDefaults['responsibility'] }}"
                        data-owner-billable="{{ $categorySmartDefaults['owner_billable'] ? '1' : '0' }}"
                        data-recurring="{{ $categorySmartDefaults['recurring'] ? '1' : '0' }}"
                        data-vat-mode="{{ $categorySmartDefaults['vat_mode'] }}"
                    @endif>{{ $expenseCategories[$categoryKey] }}</option>
            @endif
        @endforeach
    </optgroup>
@endforeach
