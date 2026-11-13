<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { ACCEPTED_IMAGE_TYPES, uploadImage } from '../utils/upload';

const html = defineModel({ type: String, default: '' });

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, default: 'Details' },
});

const uploading = ref(0);
const uploadError = ref(null);
const fileInput = ref(null);

const editor = useEditor({
    content: html.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3, 4] },
            link: { openOnClick: false, autolink: true, protocols: ['http', 'https', 'mailto'] },
        }),
        Image,
    ],
    editorProps: {
        attributes: {
            id: props.id,
            class: 'tl-prose',
            role: 'textbox',
            'aria-multiline': 'true',
            'aria-label': props.label,
        },
        handlePaste: (view, event) => insertImages(event.clipboardData?.files),
        handleDrop: (view, event) => {
            const coordinates = view.posAtCoords({ left: event.clientX, top: event.clientY });

            return insertImages(event.dataTransfer?.files, coordinates?.pos);
        },
    },
    onUpdate: ({ editor }) => {
        html.value = editor.isEmpty ? '' : editor.getHTML();
    },
});

// Keep the editor in step when the parent resets or replaces the value.
watch(html, (value) => {
    if (editor.value && value !== editor.value.getHTML() && !(value === '' && editor.value.isEmpty)) {
        editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
});

function insertImages(files, position) {
    const images = Array.from(files ?? []).filter((file) => ACCEPTED_IMAGE_TYPES.includes(file.type));

    if (!images.length) {
        return false;
    }

    uploadError.value = null;

    for (const file of images) {
        uploading.value++;
        uploadImage(file)
            .then((src) => {
                const chain = editor.value.chain().focus();
                (position == null ? chain : chain.setTextSelection(position)).setImage({ src, alt: file.name }).run();
            })
            .catch((error) => (uploadError.value = error.message))
            .finally(() => uploading.value--);
    }

    return true;
}

function pickImage(event) {
    insertImages(event.target.files);
    event.target.value = '';
}

function setLink() {
    const previous = editor.value.getAttributes('link').href ?? '';
    const href = window.prompt('Link URL', previous);

    if (href === null) {
        return;
    }

    const chain = editor.value.chain().focus().extendMarkRange('link');
    (href === '' ? chain.unsetLink() : chain.setLink({ href })).run();
}

const tools = [
    { label: 'Bold', icon: 'B', run: (c) => c.toggleBold(), active: 'bold', class: 'fw-bold' },
    { label: 'Italic', icon: 'I', run: (c) => c.toggleItalic(), active: 'italic', class: 'fst-italic' },
    { label: 'Inline code', icon: '</>', run: (c) => c.toggleCode(), active: 'code', class: 'font-monospace' },
    { label: 'Heading', icon: 'H', run: (c) => c.toggleHeading({ level: 3 }), active: ['heading', { level: 3 }] },
    { label: 'Bulleted list', icon: '•', run: (c) => c.toggleBulletList(), active: 'bulletList' },
    { label: 'Numbered list', icon: '1.', run: (c) => c.toggleOrderedList(), active: 'orderedList' },
    { label: 'Code block', icon: '{ }', run: (c) => c.toggleCodeBlock(), active: 'codeBlock', class: 'font-monospace' },
    { label: 'Quote', icon: '❝', run: (c) => c.toggleBlockquote(), active: 'blockquote' },
];

function isActive(tool) {
    return Array.isArray(tool.active) ? editor.value?.isActive(...tool.active) : editor.value?.isActive(tool.active);
}

onBeforeUnmount(() => editor.value?.destroy());
</script>

<template>
    <div class="tl-editor border rounded">
        <div v-if="editor" class="btn-toolbar gap-1 p-1 border-bottom" role="toolbar" :aria-label="`${label} formatting`">
            <button
                v-for="tool in tools"
                :key="tool.label"
                type="button"
                class="btn btn-sm btn-outline-secondary border-0"
                :class="[tool.class, { active: isActive(tool) }]"
                :aria-label="tool.label"
                :aria-pressed="isActive(tool)"
                :title="tool.label"
                @click="tool.run(editor.chain().focus()).run()"
            >
                {{ tool.icon }}
            </button>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary border-0"
                :class="{ active: editor.isActive('link') }"
                aria-label="Link"
                title="Link"
                @click="setLink"
            >
                🔗
            </button>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary border-0"
                aria-label="Add image"
                title="Add image (or paste / drop one)"
                @click="fileInput.click()"
            >
                🖼
            </button>
            <input
                ref="fileInput"
                type="file"
                class="d-none"
                :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                multiple
                @change="pickImage"
            >
            <span v-if="uploading" class="small text-body-secondary align-self-center ms-auto me-1" role="status">
                Uploading…
            </span>
        </div>
        <EditorContent :editor="editor" />
        <div v-if="uploadError" class="text-danger small px-2 pb-2" role="alert">
            {{ uploadError }}
        </div>
    </div>
</template>
