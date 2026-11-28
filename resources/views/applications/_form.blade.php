<div class="grid gap-4 sm:grid-cols-2">
    <flux:select
        name="company_id"
        :label="__('Company')"
        :placeholder="__('Choose a company')"
    >
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" @selected((string) old('company_id', $application->company_id) === (string) $company->id)>
                {{ $company->name }}
            </option>
        @endforeach
    </flux:select>

    <flux:select name="status" :label="__('Status')">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $application->status?->value) === $status->value)>
                {{ $status->label() }}
            </option>
        @endforeach
    </flux:select>
</div>

<flux:input
    name="role_title"
    :label="__('Role title')"
    :value="old('role_title', $application->role_title)"
    type="text"
    :placeholder="__('e.g. Backend Engineer')"
/>

<div class="grid gap-4 sm:grid-cols-2">
    <flux:input
        name="salary_min"
        :label="__('Salary from (RM per month)')"
        :value="old('salary_min', $application->salary_min)"
        type="number"
        min="0"
    />

    <flux:input
        name="salary_max"
        :label="__('Salary to (RM per month)')"
        :value="old('salary_max', $application->salary_max)"
        type="number"
        min="0"
    />
</div>

<flux:input
    name="applied_on"
    :label="__('Applied on')"
    :value="old('applied_on', $application->applied_on?->format('Y-m-d'))"
    type="date"
/>

<flux:textarea
    name="notes"
    :label="__('Notes')"
    rows="4"
>{{ old('notes', $application->notes) }}</flux:textarea>
