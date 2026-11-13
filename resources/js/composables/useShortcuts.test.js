import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { isTyping, matchShortcut, useShortcuts } from './useShortcuts';

const keydown = (key, target = document.body, extra = {}) => ({ key, target, ctrlKey: false, metaKey: false, altKey: false, ...extra });

describe('isTyping', () => {
    it('is true inside form fields and editable content', () => {
        const editable = document.createElement('div');
        editable.setAttribute('contenteditable', 'true');
        const paragraph = document.createElement('p');
        editable.appendChild(paragraph);

        expect(isTyping({ target: document.createElement('input') })).toBe(true);
        expect(isTyping({ target: document.createElement('textarea') })).toBe(true);
        expect(isTyping({ target: editable })).toBe(true);
        expect(isTyping({ target: paragraph })).toBe(true);
    });

    it('is false elsewhere', () => {
        const readOnly = document.createElement('div');
        readOnly.setAttribute('contenteditable', 'false');

        expect(isTyping({ target: document.body })).toBe(false);
        expect(isTyping({ target: readOnly })).toBe(false);
        expect(isTyping({ target: null })).toBe(false);
    });
});

describe('matchShortcut', () => {
    const search = () => {};
    const bindings = { '/': search };

    it('returns the bound handler', () => {
        expect(matchShortcut(keydown('/'), bindings)).toBe(search);
    });

    it('ignores unbound keys', () => {
        expect(matchShortcut(keydown('x'), bindings)).toBeNull();
    });

    it('ignores keys pressed while typing', () => {
        expect(matchShortcut(keydown('/', document.createElement('input')), bindings)).toBeNull();
    });

    it('leaves modified keys to the browser', () => {
        expect(matchShortcut(keydown('/', document.body, { metaKey: true }), bindings)).toBeNull();
        expect(matchShortcut(keydown('/', document.body, { ctrlKey: true }), bindings)).toBeNull();
    });
});

describe('useShortcuts', () => {
    it('fires while mounted and stops after unmount', () => {
        const handler = vi.fn();
        const wrapper = mount(defineComponent({
            setup() {
                useShortcuts({ '?': handler });

                return () => h('div');
            },
        }));

        window.dispatchEvent(new KeyboardEvent('keydown', { key: '?' }));
        expect(handler).toHaveBeenCalledTimes(1);

        wrapper.unmount();
        window.dispatchEvent(new KeyboardEvent('keydown', { key: '?' }));
        expect(handler).toHaveBeenCalledTimes(1);
    });

    it('prevents the default action of a handled key', () => {
        const wrapper = mount(defineComponent({
            setup() {
                useShortcuts({ '/': () => {} });

                return () => h('div');
            },
        }));
        const event = new KeyboardEvent('keydown', { key: '/', cancelable: true });

        window.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);

        wrapper.unmount();
    });

    it('leaves the default alone when a handler declines the key', () => {
        const wrapper = mount(defineComponent({
            setup() {
                useShortcuts({ Enter: () => false });

                return () => h('div');
            },
        }));
        const event = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true });

        window.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(false);

        wrapper.unmount();
    });
});
