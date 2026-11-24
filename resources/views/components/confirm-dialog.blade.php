{{--
    One confirmation dialog for the whole app, replacing the browser's native
    confirm(). A button asks for confirmation with:

        x-on:click="$dispatch('confirm-action', { message: '¿Eliminar este camión?', run: () => $wire.delete(1) })"

    `run` is only called if the person confirms.
--}}
<div
    x-data="{ message: '', run: null }"
    x-on:confirm-action.window="message = $event.detail.message; run = $event.detail.run; $flux.modal('confirm-action').show()"
>
    <flux:modal name="confirm-action" class="max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" x-text="message"></flux:heading>
                <flux:text class="mt-2">{{ __('Esta acción no se puede deshacer.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" x-on:click="$flux.modal('confirm-action').close(); run && run()">{{ __('Eliminar') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
