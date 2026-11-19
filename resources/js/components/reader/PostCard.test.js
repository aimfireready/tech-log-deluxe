import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import PostCard from './PostCard.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

const post = {
    id: 7,
    title: 'Manwe: installed <Python>',
    excerpt: 'For testing',
    feature_image: '/storage/attachments/2026/11/a.png',
    reading_minutes: 2,
    occurred_at: '2026-11-18T15:00:00Z',
    author: 'Gandalf',
    primary_tag: { name: 'Linux', slug: 'linux' },
    tags: [{ name: 'Linux', slug: 'linux' }],
};

describe('PostCard', () => {
    it('links the title, tag and image to the reading view, escaping the title', () => {
        const wrapper = mount(PostCard, { props: { post } });

        expect(wrapper.find('h2').text()).toBe('Manwe: installed <Python>');
        expect(wrapper.find('img').attributes('src')).toBe(post.feature_image);
        const hrefs = wrapper.findAll('a').map((a) => a.attributes('href'));
        expect(hrefs).toContain('/read/entries/7');
        expect(hrefs).toContain('/read/tags/linux');
        expect(wrapper.text()).toContain('2 min read');
        expect(wrapper.find('time').attributes('datetime')).toBe(post.occurred_at);
    });

    it('puts the thumbnail column in only when there is an image', () => {
        expect(mount(PostCard, { props: { post } }).classes()).toContain('gh-card-has-image');

        const wrapper = mount(PostCard, {
            props: { post: { ...post, feature_image: null, primary_tag: null, tags: [] } },
        });

        expect(wrapper.classes()).not.toContain('gh-card-has-image');
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.find('.gh-eyebrow').exists()).toBe(false);
    });
});
