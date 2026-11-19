<script setup>
import { Deferred, Head, Link } from '@inertiajs/vue3';
import ReaderLayout from '../../layouts/ReaderLayout.vue';
import PostCard from '../../components/reader/PostCard.vue';
import PostCardSkeleton from '../../components/reader/PostCardSkeleton.vue';
import { formatPostDate, initial, readingLabel } from '../../utils/reader';

defineOptions({ layout: ReaderLayout });

defineProps({
    post: { type: Object, required: true },
    newer: { type: Object, default: null },
    older: { type: Object, default: null },
    related: { type: Array, default: undefined },
});
</script>

<template>
    <Head :title="post.title" />

    <article class="gh-article">
        <header class="gh-inner">
            <div class="gh-article-header">
                <Link v-if="post.primary_tag" :href="`/read/tags/${post.primary_tag.slug}`" class="gh-eyebrow">
                    {{ post.primary_tag.name }}
                </Link>
                <h1 class="gh-article-title">
                    {{ post.title }}
                </h1>

                <div class="gh-byline">
                    <span class="gh-avatar" aria-hidden="true">{{ initial(post.author) }}</span>
                    <div>
                        <div class="gh-byline-name">
                            {{ post.author ?? 'Unknown author' }}
                        </div>
                        <div class="gh-meta">
                            <time :datetime="post.occurred_at">{{ formatPostDate(post.occurred_at) }}</time>
                            <span class="gh-dot">{{ readingLabel(post.reading_minutes) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="gh-inner">
            <!-- body is sanitized on the server before it is stored (App\Support\EntryHtml). -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div v-if="post.body" class="gh-content" v-html="post.body" />
            <p v-else class="gh-content gh-content-empty">
                This entry has no details beyond its title.
            </p>

            <footer class="gh-article-footer">
                <Link v-for="tag in post.tags" :key="tag.slug" :href="`/read/tags/${tag.slug}`" class="gh-tag-pill">
                    {{ tag.name }}
                </Link>
                <Link v-if="$page.props.auth?.user" :href="`/entries/${post.id}`" class="gh-edit">
                    Edit in app →
                </Link>
            </footer>

            <nav v-if="newer || older" class="gh-pager" aria-label="More posts">
                <Link v-if="older" :href="`/read/entries/${older.id}`">
                    <span class="gh-meta">← Older</span>
                    <strong>{{ older.title }}</strong>
                </Link>
                <Link v-if="newer" :href="`/read/entries/${newer.id}`" class="gh-pager-next">
                    <span class="gh-meta">Newer →</span>
                    <strong>{{ newer.title }}</strong>
                </Link>
            </nav>
        </div>
    </article>

    <Deferred data="related">
        <template #fallback>
            <aside class="gh-read-more" aria-hidden="true">
                <div class="gh-inner">
                    <h2>Read more</h2>
                </div>
                <div class="gh-inner gh-feed">
                    <PostCardSkeleton v-for="n in 3" :key="n" />
                </div>
            </aside>
        </template>

        <aside v-if="related.length" class="gh-read-more" aria-labelledby="read-more-title">
            <div class="gh-inner">
                <h2 id="read-more-title">
                    {{ post.primary_tag ? `More in ${post.primary_tag.name}` : 'Read more' }}
                </h2>
            </div>
            <div class="gh-inner gh-feed">
                <PostCard v-for="item in related" :key="item.id" :post="item" />
            </div>
        </aside>
    </Deferred>
</template>
