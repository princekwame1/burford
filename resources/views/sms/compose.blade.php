<x-layouts.app title="Compose SMS">
    <x-page-header title="Compose SMS" subtitle="Send to one or more numbers, or broadcast to a customer group." :back="route('sms.index')" />
    <form method="POST" action="{{ route('sms.send') }}" class="grid gap-6 lg:grid-cols-3"
        x-data="{ mode: @js(old('mode', 'single')), text: @js(old('message', '')), group: @js(old('group', 'susu_active')), groups: @js($groups),
            get parts() { return this.text.length <= 160 ? 1 : Math.ceil(this.text.length / 153) } }">
        @csrf
        <div class="card lg:col-span-2">
            <div class="card-body space-y-5">
                <div class="inline-flex gap-1 rounded-xl bg-ink-100 p-1 dark:bg-ink-800">
                    <label class="cursor-pointer"><input type="radio" name="mode" value="single" x-model="mode" class="peer sr-only"><span class="tab peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm dark:peer-checked:bg-ink-900 dark:peer-checked:text-white">Specific numbers</span></label>
                    <label class="cursor-pointer"><input type="radio" name="mode" value="group" x-model="mode" class="peer sr-only"><span class="tab peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm dark:peer-checked:bg-ink-900 dark:peer-checked:text-white">Group broadcast</span></label>
                </div>
                <div x-show="mode === 'single'">
                    <x-field label="Phone number(s)" name="to" :value="$to" placeholder="0240000000, 0550000000" help="Separate several numbers with commas or spaces. Ghana numbers are converted to +233 automatically." />
                </div>
                <div x-show="mode === 'group'" x-cloak class="grid gap-2 sm:grid-cols-2">
                    @foreach ($groups as $key => $g)
                        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-ink-200 p-3.5 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50 dark:border-ink-700 dark:has-[:checked]:bg-brand-500/10">
                            <span class="flex items-center gap-3"><input type="radio" name="group" value="{{ $key }}" x-model="group" class="accent-brand-600"><span class="text-sm font-medium">{{ $g['label'] }}</span></span>
                            <span class="badge bg-ink-100 text-ink-600 ring-ink-500/10 dark:bg-ink-800 dark:text-ink-300">{{ $g['count'] }}</span>
                        </label>
                    @endforeach
                </div>
                <div>
                    <label class="label">Message <span class="text-rose-500">*</span></label>
                    <textarea name="message" rows="6" x-model="text" maxlength="918" class="input" placeholder="Dear {name}, …" required></textarea>
                    <div class="mt-1 flex justify-between text-xs text-ink-500">
                        <span>Use <code class="rounded bg-ink-100 px-1 dark:bg-ink-800">{name}</code> and <code class="rounded bg-ink-100 px-1 dark:bg-ink-800">{company}</code> to personalise.</span>
                        <span><span x-text="text.length"></span> chars · <span x-text="parts"></span> SMS part(s)</span>
                    </div>
                    @error('message')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div class="card card-body">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Preview</p>
                <div class="mt-4 rounded-2xl rounded-tl-sm bg-brand-600 p-4 text-sm text-white shadow-sm" x-text="(text || 'Your message…').replaceAll('{name}', 'Ama').replaceAll('{company}', @js(\App\Models\Setting::get('company_name', 'BURFORD Collection')))"></div>
                <p class="mt-3 text-xs text-ink-500" x-show="mode === 'group'">Recipients: <span class="font-semibold" x-text="groups[group]?.count ?? 0"></span></p>
            </div>
            <button class="btn btn-primary w-full py-3" data-confirm-title="Send to the whole group?" data-confirm-button="Send now"
                :data-confirm="mode === 'group' ? `${groups[group]?.label}: ${groups[group]?.count ?? 0} recipient(s), ${parts} SMS part(s) each.` : null"><x-icon name="send" class="size-4" /> Send via TextTango</button>
        </div>
    </form>
</x-layouts.app>
