import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import HighlightedText from './HighlightedText.vue';
import EntryCard from './EntryCard.vue';
import TagInput from './TagInput.vue';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            tags: [
                { name: 'Active Directory', slug: 'active-directory' },
                { name: 'Firewall', slug: 'firewall' },
            ],
        },
    }),
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

describe('HighlightedText', () => {
    it('marks matched segments and escapes everything as text', () => {
        const wrapper = mount(HighlightedText, {
            props: { segments: [{ text: '<b>x</b> ', match: false }, { text: 'nginx', match: true }] },
        });

        expect(wrapper.find('mark').text()).toBe('nginx');
        expect(wrapper.find('b').exists()).toBe(false);
        expect(wrapper.text()).toContain('<b>x</b>');
    });
});

describe('TagInput', () => {
    const mountInput = (tags = []) => mount(TagInput, {
        props: { id: 't', modelValue: tags, 'onUpdate:modelValue': (value) => wrapper.setProps({ modelValue: value }) },
    });
    let wrapper;

    it('adds a typed tag on Enter and comma', async () => {
        wrapper = mountInput();
        const input = wrapper.find('input');

        await input.setValue('dns');
        await input.trigger('keydown', { key: 'Enter' });
        await input.setValue('vpn');
        await input.trigger('keydown', { key: ',' });

        expect(wrapper.props('modelValue')).toEqual(['dns', 'vpn']);
    });

    it('suggests known tags and uses their spelling', async () => {
        wrapper = mountInput();
        const input = wrapper.find('input');

        await input.setValue('fire');
        expect(wrapper.findAll('[role="option"]').map((o) => o.text())).toEqual(['Firewall']);

        await input.trigger('keydown', { key: 'Enter' });
        expect(wrapper.props('modelValue')).toEqual(['Firewall']);
    });

    it('removes the last tag with Backspace on an empty input', async () => {
        wrapper = mountInput(['dns', 'vpn']);

        await wrapper.find('input').trigger('keydown', { key: 'Backspace' });

        expect(wrapper.props('modelValue')).toEqual(['dns']);
    });

    it('removes a tag with its close button', async () => {
        wrapper = mountInput(['dns', 'vpn']);

        await wrapper.find('[aria-label="Remove tag dns"]').trigger('click');

        expect(wrapper.props('modelValue')).toEqual(['vpn']);
    });
});

describe('EntryCard', () => {
    const entry = {
        id: 7,
        title: 'Restarted nginx',
        body: '<p>Full <strong>body</strong></p>',
        excerpt: 'Full body',
        occurred_at: '2026-11-12T09:30:00Z',
        author: { id: 1, name: 'Ops' },
        tags: [{ name: 'Web', slug: 'web' }],
    };

    it('shows the excerpt until expanded, then the body', async () => {
        const wrapper = mount(EntryCard, {
            props: { entry, expanded: false, 'onUpdate:expanded': (value) => wrapper.setProps({ expanded: value }) },
        });

        expect(wrapper.find('.tl-excerpt').text()).toBe('Full body');
        expect(wrapper.find('strong').exists()).toBe(false);

        await wrapper.find('.tl-entry-toggle').trigger('click');

        expect(wrapper.find('strong').text()).toBe('body');
        expect(wrapper.find('article').attributes('aria-expanded')).toBe('true');
    });

    it('shows search highlights instead of the plain title', () => {
        const wrapper = mount(EntryCard, {
            props: { entry: { ...entry, highlight: { title: [{ text: 'Restarted ', match: false }, { text: 'nginx', match: true }], snippet: [] } } },
        });

        expect(wrapper.find('h2 mark').text()).toBe('nginx');
    });

    it('asks the timeline to filter when a tag is clicked', async () => {
        const wrapper = mount(EntryCard, { props: { entry } });

        await wrapper.find('.tl-tag').trigger('click');

        expect(wrapper.emitted('filter-tag')).toEqual([['web']]);
    });

    it('links to the entry page', () => {
        const wrapper = mount(EntryCard, { props: { entry } });

        expect(wrapper.find('a[href="/entries/7"]').exists()).toBe(true);
    });
});
