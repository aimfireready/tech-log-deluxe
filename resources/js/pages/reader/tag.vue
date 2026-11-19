<script setup>
import { Head } from '@inertiajs/vue3';
import ReaderLayout from '../../layouts/ReaderLayout.vue';
import PostFeed from '../../components/reader/PostFeed.vue';

defineOptions({ layout: ReaderLayout });

defineProps({
    tag: { type: Object, required: true },
    posts: { type: Object, required: true },
});
</script>

<template>
    <Head :title="tag.name" />

    <header class="gh-archive">
        <div class="gh-inner">
            <span class="gh-eyebrow">Tag</span>
            <h1>{{ tag.name }}</h1>
            <p>{{ tag.count }} {{ tag.count === 1 ? 'post' : 'posts' }}</p>
        </div>
    </header>

    <PostFeed :posts="posts.data" />
</template>
