<section class="mt-12 grid gap-6 rounded-3xl bg-slate-900 p-6 text-white lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:p-8"
    aria-labelledby="product-lead-title">
    <div class="self-center">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-300">Personal guidance</p>
        <h2 id="product-lead-title" class="mt-3 text-3xl font-bold tracking-tight">Need help choosing?</h2>
        <p class="mt-4 max-w-xl leading-7 text-slate-300">
            Tell us what you need. A local specialist can help with buying advice, accessories, repairs, or another request.
        </p>
    </div>

    <div class="text-slate-950">
        <livewire:public.leads.lead-form :site="$site" :product="$centralProductId"
            :key="'lead-form-'.$site->id.'-'.$centralProductId" />
    </div>
</section>
