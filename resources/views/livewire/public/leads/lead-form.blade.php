<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="lead-form-title">
    <h2 id="lead-form-title" class="text-2xl font-bold tracking-tight text-slate-950">Request help</h2>
    <p class="mt-2 text-sm text-slate-600">Tell us what you need and a local specialist can contact you.</p>

    <form class="mt-6 space-y-5">
        <div>
            <label for="lead-name" class="block text-sm font-medium text-slate-800">Your name</label>
            <input id="lead-name" type="text" wire:model="name" autocomplete="name"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="lead-email" class="block text-sm font-medium text-slate-800">Email</label>
                <input id="lead-email" type="email" wire:model="email" autocomplete="email"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
            </div>
            <div>
                <label for="lead-phone" class="block text-sm font-medium text-slate-800">Phone</label>
                <input id="lead-phone" type="tel" wire:model="phone" autocomplete="tel"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
            </div>
        </div>

        <div>
            <label for="lead-message" class="block text-sm font-medium text-slate-800">How can we help?</label>
            <textarea id="lead-message" wire:model="message" rows="5"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950"></textarea>
        </div>

        <button type="button" disabled class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white opacity-60">
            Send request
        </button>
    </form>
</section>
