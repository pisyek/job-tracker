<label for="company_id">Company</label>
<select id="company_id" name="company_id">
    <option value="">Choose a company</option>
    @foreach ($companies as $company)
        <option value="{{ $company->id }}" @selected((string) old('company_id', $application->company_id) === (string) $company->id)>
            {{ $company->name }}
        </option>
    @endforeach
</select>
@error('company_id') <small>{{ $message }}</small> @enderror

<label for="role_title">Role title</label>
<input id="role_title" name="role_title" type="text" value="{{ old('role_title', $application->role_title) }}">
@error('role_title') <small>{{ $message }}</small> @enderror

<label for="status">Status</label>
<select id="status" name="status">
    @foreach ($statuses as $status)
        <option value="{{ $status->value }}" @selected(old('status', $application->status?->value) === $status->value)>
            {{ $status->label() }}
        </option>
    @endforeach
</select>
@error('status') <small>{{ $message }}</small> @enderror

<label for="salary_min">Salary from (RM per month)</label>
<input id="salary_min" name="salary_min" type="number" min="0" value="{{ old('salary_min', $application->salary_min) }}">
@error('salary_min') <small>{{ $message }}</small> @enderror

<label for="salary_max">Salary to (RM per month)</label>
<input id="salary_max" name="salary_max" type="number" min="0" value="{{ old('salary_max', $application->salary_max) }}">
@error('salary_max') <small>{{ $message }}</small> @enderror

<label for="applied_on">Applied on</label>
<input id="applied_on" name="applied_on" type="date" value="{{ old('applied_on', $application->applied_on?->format('Y-m-d')) }}">
@error('applied_on') <small>{{ $message }}</small> @enderror

<label for="notes">Notes</label>
<textarea id="notes" name="notes" rows="4">{{ old('notes', $application->notes) }}</textarea>
@error('notes') <small>{{ $message }}</small> @enderror