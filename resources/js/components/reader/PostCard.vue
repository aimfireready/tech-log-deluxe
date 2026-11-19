<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { formatPostDate, readingLabel } from '../../utils/reader';

const props = defineProps({
    post: { type: Object, required: true },
});

const href = computed(() => `/read/entries/${props.post.id}`);
</script>

<template>
    <article class="gh-card" :class="{ 'gh-card-has-image': post.feature_image }">
        <div class="gh-card-body">
            <Link v-if="post.primary_tag" :href="`/read/tags/${post.primary_tag.slug}`" class="gh-eyebrow">
                {{ post.primary_tag.name }}
            </Link>

            <Link :href="href" class="gh-card-link d-flex flex-column gap-2">
                <h2 class="gh-card-title">
                    {{ post.title }}
                </h2>
                <p v-if="post.excerpt" class="gh-card-excerpt">
                    {{ post.excerpt }}
                </p>
            </Link>

            <div class="gh-meta">
                <time :datetime="post.occurred_at">{{ formatPostDate(post.occurred_at) }}</time>
                <span class="gh-dot">{{ readingLabel(post.reading_minutes) }}</span>
            </div>
        </div>

        <Link v-if="post.feature_image" :href="href" tabindex="-1" aria-hidden="true">
            <img class="gh-card-image" :src="post.feature_image" alt="" loading="lazy">
        </Link>
    </article>
</template>
