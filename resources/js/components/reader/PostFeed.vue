<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';
import PostCard from './PostCard.vue';
import PostCardSkeleton from './PostCardSkeleton.vue';

defineProps({
    posts: { type: Array, required: true },
});
</script>

<template>
    <InfiniteScroll data="posts" only-next>
        <div class="gh-inner gh-feed">
            <PostCard v-for="post in posts" :key="post.id" :post="post" />
        </div>

        <template #loading>
            <div class="gh-inner gh-feed pt-0">
                <PostCardSkeleton v-for="n in 2" :key="n" />
            </div>
        </template>
    </InfiniteScroll>
</template>
