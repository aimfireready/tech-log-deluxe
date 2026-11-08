import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import ShortcutHelp from './ShortcutHelp.vue';

describe('ShortcutHelp', () => {
    it('renders nothing while closed', () => {
        const wrapper = mount(ShortcutHelp, { props: { open: false } });

        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('moves focus to the close button when opened', async () => {
        const wrapper = mount(ShortcutHelp, { props: { open: false }, attachTo: document.body });

        await wrapper.setProps({ open: true });
        await nextTick();

        expect(document.activeElement).toBe(wrapper.find('.btn-close').element);
        wrapper.unmount();
    });

    it('closes on Esc', async () => {
        const wrapper = mount(ShortcutHelp, { props: { open: true } });

        await wrapper.find('[role="dialog"]').trigger('keydown', { key: 'Escape' });

        expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });
});
