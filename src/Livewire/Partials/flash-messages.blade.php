{{--
    The messages shown above the table, e.g. the result of a bulk action. Dismissing one only hides it: they all go
    away with the next request anyway.

    @var array<int, array{type: string, message: string, key: string}> $messages
    @var \ErickComp\LivewireDataTable\Livewire\Preset $preset
--}}
<div @class($preset->get('messages.container.class', []))>
    @foreach ($messages as $message)
        <div wire:key="lw-dt-message-{{ $message['key'] }}"
            @class($preset->get("messages.{$message['type']}.class", []))
            role="{{ $message['type'] === 'error' ? 'alert' : 'status' }}"
            x-data="{ shown: true }"
            x-show="shown">
            <span>{{ $message['message'] }}</span>
            <button type="button" @class($preset->get('messages.button-dismiss.class', []))
                aria-label="{{ __('erickcomp_lw_data_table::messages.dismiss_message') }}"
                x-on:click="shown = false">&times;</button>
        </div>
    @endforeach
</div>
