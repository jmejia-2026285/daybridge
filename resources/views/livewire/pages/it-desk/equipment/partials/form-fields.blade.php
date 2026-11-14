<fieldset>
    <legend class="text-3xs font-bold tracking-label text-ink-muted uppercase">Equipo</legend>

    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="type" value="Tipo de equipo" />
            <x-text-input id="type" wire:model="form.type" class="mt-1.5" placeholder="Laptop, desktop, impresora" autofocus />
            <x-input-error :messages="$errors->get('form.type')" />
        </div>

        <div>
            <x-input-label for="brand" value="Marca" />
            <x-text-input id="brand" wire:model="form.brand" class="mt-1.5" placeholder="Dell, HP, Lenovo" />
            <x-input-error :messages="$errors->get('form.brand')" />
        </div>

        <div>
            <x-input-label for="model" value="Modelo" />
            <x-text-input id="model" wire:model="form.model" class="mt-1.5" placeholder="Latitude 5440" />
            <x-input-error :messages="$errors->get('form.model')" />
        </div>

        <div>
            <x-input-label for="serial" value="Número de serie" />
            <x-text-input id="serial" wire:model="form.serial" class="mt-1.5" placeholder="Opcional" />
            <x-input-error :messages="$errors->get('form.serial')" />
        </div>
    </div>
</fieldset>

<fieldset>
    <legend class="text-3xs font-bold tracking-label text-ink-muted uppercase">Cliente</legend>

    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="client_name" value="Nombre completo" />
            <x-text-input id="client_name" wire:model="form.client_name" class="mt-1.5" placeholder="Quién entrega el equipo" />
            <x-input-error :messages="$errors->get('form.client_name')" />
        </div>

        <div>
            <x-input-label for="client_phone" value="Teléfono" />
            <x-text-input id="client_phone" wire:model="form.client_phone" class="mt-1.5" placeholder="5555-5555" inputmode="tel" />
            <x-input-error :messages="$errors->get('form.client_phone')" />
        </div>

        <div>
            <x-input-label for="client_email" value="Correo" />
            <x-text-input id="client_email" type="email" wire:model="form.client_email" class="mt-1.5" placeholder="Opcional" />
            <x-input-error :messages="$errors->get('form.client_email')" />
        </div>
    </div>
</fieldset>

<fieldset>
    <legend class="text-3xs font-bold tracking-label text-ink-muted uppercase">Servicio</legend>

    <div class="mt-3 grid gap-4">
        <div>
            <x-input-label for="issue" value="Motivo de la falla" />
            <textarea
                id="issue"
                wire:model="form.issue"
                rows="3"
                maxlength="255"
                placeholder="Qué reporta el cliente al entregar el equipo"
                class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
            ></textarea>
            <x-input-error :messages="$errors->get('form.issue')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="accessories" value="Accesorios" />
                <x-text-input id="accessories" wire:model="form.accessories" class="mt-1.5" placeholder="Cargador, funda, cable" />
                <x-input-error :messages="$errors->get('form.accessories')" />
            </div>

            <div>
                <x-input-label for="estimated_delivery_date" value="Entrega estimada" />
                <x-text-input id="estimated_delivery_date" type="date" wire:model="form.estimated_delivery_date" class="mt-1.5" min="{{ today()->toDateString() }}" />
                <x-input-error :messages="$errors->get('form.estimated_delivery_date')" />
            </div>
        </div>

        <div>
            <x-input-label for="diagnosis" value="Diagnóstico inicial" />
            <textarea
                id="diagnosis"
                wire:model="form.diagnosis"
                rows="3"
                maxlength="1000"
                placeholder="Opcional: primeras observaciones del técnico"
                class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
            ></textarea>
            <x-input-error :messages="$errors->get('form.diagnosis')" />
        </div>

        <div>
            <x-input-label for="notes" value="Notas internas" />
            <textarea
                id="notes"
                wire:model="form.notes"
                rows="2"
                maxlength="500"
                placeholder="Opcional"
                class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
            ></textarea>
            <x-input-error :messages="$errors->get('form.notes')" />
        </div>
    </div>
</fieldset>
