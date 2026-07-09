<x-layouts.app title="Add Rider — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="riders" />

            <div class="admin-content">
                <h1 class="admin-heading">Add Rider</h1>

                <x-flash-status />

                <form method="POST" action="{{ route('admin.riders.store') }}" class="auth-form" style="max-width:520px;">
                    @csrf

                    <x-input-field label="Full Name" name="name" :value="old('name')" required />
                    <x-input-field label="Email" name="email" type="email" :value="old('email')" required />
                    <x-input-field label="Phone" name="phone" :value="old('phone')" required />

                    <x-select-field label="Vehicle Type" name="vehicle_type"
                        :options="['motorcycle' => 'Motorcycle', 'bicycle' => 'Bicycle', 'van' => 'Van']"
                        :value="old('vehicle_type', 'motorcycle')" />

                    <x-input-field label="Number Plate (optional)" name="number_plate" :value="old('number_plate')" />

                    <x-select-field label="Status" name="status"
                        :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']"
                        :value="old('status', 'active')" />

                    <div class="form-group" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;">
                        <input type="hidden" name="available" value="0">
                        <input type="checkbox" name="available" id="available" value="1"
                               {{ old('available', '1') ? 'checked' : '' }} style="width:auto;">
                        <label for="available" style="margin:0;font-weight:500;">Available for delivery</label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-input" rows="3">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                        <x-button type="submit" variant="primary">Save Rider</x-button>
                        <a href="{{ route('admin.riders.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</x-layouts.app>
