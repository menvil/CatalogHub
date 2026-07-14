<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="lead-form-title">
    <h2 id="lead-form-title" class="text-2xl font-bold tracking-tight text-slate-950">Request help</h2>
    <p class="mt-2 text-sm text-slate-600">Tell us what you need and a local specialist can contact you.</p>

    <form wire:submit="submit" class="mt-6 space-y-5">
        @if ($submitted)
            <p role="status" class="rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-800">
                Thank you. Your request has been received.
            </p>
        @endif

        @error('form')
            <p role="alert" class="rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">{{ $message }}</p>
        @enderror

        <div>
            <label for="lead-type" class="block text-sm font-medium text-slate-800">Request type</label>
            <select id="lead-type" wire:model="type"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
                <option value="">Choose a request type</option>
                @foreach ($leadTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="lead-name" class="block text-sm font-medium text-slate-800">Your name</label>
            <input id="lead-name" type="text" wire:model="name" autocomplete="name"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="lead-email" class="block text-sm font-medium text-slate-800">Email</label>
                <input id="lead-email" type="email" wire:model="email" autocomplete="email"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="lead-phone" class="block text-sm font-medium text-slate-800">Phone</label>
                <input id="lead-phone" type="tel" wire:model="phone" autocomplete="tel"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
                @error('phone')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="lead-city" class="block text-sm font-medium text-slate-800">City (optional)</label>
            <input id="lead-city" type="text" wire:model="city" autocomplete="address-level2"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
        </div>

        <div>
            <label for="lead-message" class="block text-sm font-medium text-slate-800">How can we help?</label>
            <textarea id="lead-message" wire:model="message" rows="5"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950"></textarea>
        </div>

        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
            Send request
        </button>
    </form>
</section>
