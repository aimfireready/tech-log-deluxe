<?php

use App\Support\EntryHtml;

it('keeps the formatting the editor produces', function () {
    $html = '<h2>Heading</h2><p><strong>b</strong> <em>i</em> <u>u</u> <s>s</s> <code>x</code></p>'
        .'<ul><li>one</li></ul><ol><li>two</li></ol><blockquote><p>q</p></blockquote><pre><code class="language-bash">ls</code></pre><hr>';

    expect(EntryHtml::sanitize($html))->toBe(str_replace('<hr>', '<hr />', $html));
});

it('strips scripts, event handlers and javascript links', function () {
    $dirty = '<p onclick="steal()">hi</p><script>alert(1)</script><a href="javascript:alert(1)">x</a><img src="x" onerror="alert(1)">';

    $clean = EntryHtml::sanitize($dirty);

    expect($clean)
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('javascript:');
});

it('drops elements the editor cannot produce', function () {
    expect(EntryHtml::sanitize('<iframe src="https://evil.test"></iframe><style>p{}</style><p>ok</p>'))
        ->toBe('<p>ok</p>');
});

it('forces safe rel on links', function () {
    expect(EntryHtml::sanitize('<a href="https://example.com">x</a>'))
        ->toBe('<a href="https://example.com" rel="noopener noreferrer">x</a>');
});

it('keeps uploaded images with root-relative urls', function () {
    $img = '<img src="/storage/attachments/2026/11/a.png" alt="rack" />';

    expect(EntryHtml::sanitize("<p>{$img}</p>"))->toBe("<p>{$img}</p>");
});

it('treats null as empty', function () {
    expect(EntryHtml::sanitize(null))->toBe('');
});

it('converts html to searchable text without gluing words together', function () {
    $html = '<h2>Firewall</h2><p>Opened&nbsp;port <strong>443</strong></p><ul><li>edge-1</li><li>edge-2</li></ul>';

    expect(EntryHtml::toText($html))->toBe("Firewall\nOpened port 443\nedge-1\nedge-2");
});

it('decodes entities in the text version', function () {
    expect(EntryHtml::toText('<p>R&amp;D &lt;team&gt; &quot;ok&quot;</p>'))->toBe('R&D <team> "ok"');
});

it('lists referenced upload paths once each', function () {
    $html = '<img src="/storage/attachments/2026/11/a.png"><img src="/storage/attachments/2026/11/a.png">'
        .'<img src="https://elsewhere.test/b.png"><img src="/storage/attachments/2026/11/c.webp">';

    expect(EntryHtml::attachmentPaths($html))->toBe(['attachments/2026/11/a.png', 'attachments/2026/11/c.webp']);
});
