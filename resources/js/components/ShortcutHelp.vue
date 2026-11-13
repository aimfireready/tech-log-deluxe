<script setup>
import { nextTick, ref, watch } from 'vue';

const open = defineModel('open', { type: Boolean, default: false });
const closeButton = ref(null);

// Move focus into the dialog so Esc and Tab work straight away.
watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        closeButton.value?.focus();
    }
});

const shortcuts = [
    { keys: ['n'], action: 'New entry' },
    { keys: ['/'], action: 'Focus search' },
    { keys: ['j', 'k'], action: 'Next / previous entry' },
    { keys: ['Enter'], action: 'Expand or collapse the selected entry' },
    { keys: ['o'], action: 'Open the selected entry' },
    { keys: ['Ctrl/⌘', 'Enter'], action: 'Save the entry you are writing' },
    { keys: ['?'], action: 'Show this list' },
    { keys: ['Esc'], action: 'Close dialogs and panels' },
];
</script>

<template>
    <template v-if="open">
        <div
            class="modal d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="shortcut-help-title"
            @click.self="open = false"
            @keydown.esc="open = false"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="shortcut-help-title" class="modal-title h6">
                            Keyboard shortcuts
                        </h2>
                        <button
                            ref="closeButton"
                            type="button"
                            class="btn-close"
                            aria-label="Close"
                            @click="open = false"
                        />
                    </div>
                    <div class="modal-body">
                        <dl class="row mb-0">
                            <template v-for="shortcut in shortcuts" :key="shortcut.action">
                                <dt class="col-3">
                                    <kbd v-for="key in shortcut.keys" :key="key">{{ key }}</kbd>
                                </dt>
                                <dd class="col-9 mb-2">
                                    {{ shortcut.action }}
                                </dd>
                            </template>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop show" />
    </template>
</template>
