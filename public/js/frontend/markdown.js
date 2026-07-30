/* Down the Rabbit Hole — a small, streaming-safe Markdown renderer.

   Why not a library: the guide's output is streamed token by token, so the
   renderer runs on every frame and must be cheap, and it must be safe against
   whatever a model emits. Both are easier to guarantee in ~190 lines than to
   audit in a dependency — and it keeps the page free of a CDN script on the
   critical path.

   SAFETY: the source is HTML-escaped ONCE, up front. Every rule below then
   operates on already-escaped text and only ever emits tags this file wrote,
   so no model output can become markup. Link targets are additionally
   protocol-checked, since an escaped `javascript:` URL is still a live URL.

   Covers what a teaching turn actually uses: headings, emphasis, inline code,
   fenced code, lists, blockquotes, tables, links, rules — and the
   `**Checkpoint:**` line, which gets its own treatment.  */
(function () {
    'use strict';

    var SAFE_PROTOCOL = /^(https?:\/\/|mailto:|#|\/)/i;
    /* Sentinel for extracted code spans. A NUL byte cannot survive escapeHtml's
       input in any realistic model output, so it can never collide with prose.
       QUOTE / BLOCK_START match `&gt;` because escaping runs first. */
    var QUOTE = /^\s*&gt;\s?/;
    var BLOCK_START = /^\s*(#{1,4}\s|&gt;|```|[-*+]\s|\d+[.)]\s)/;
    var MARK = '\u0000';

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* Inline rules. Input is already escaped; `code` is lifted out first so its
       contents can't be re-parsed as emphasis or a link. */
    function inline(text) {
        var codes = [];
        var out = text.replace(/`([^`]+)`/g, function (_, code) {
            codes.push(code);
            return MARK + (codes.length - 1) + MARK;
        });

        out = out
            .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (match, label, href) {
                var url = href.replace(/&amp;/g, '&');
                // Not a protocol we trust: leave the source visible as plain
                // text rather than silently dropping it into a live link.
                if (!SAFE_PROTOCOL.test(url)) return match;
                return '<a href="' + escapeHtml(url) + '" rel="noopener noreferrer nofollow" target="_blank">' +
                    label + '</a>';
            })
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|[\s(])\*([^*\n]+)\*/g, '$1<em>$2</em>')
            .replace(/(^|[\s(])_([^_\n]+)_/g, '$1<em>$2</em>')
            .replace(/~~([^~]+)~~/g, '<s>$1</s>');

        return out.replace(new RegExp(MARK + '(\\d+)' + MARK, 'g'), function (_, index) {
            return '<code>' + codes[Number(index)] + '</code>';
        });
    }

    function tableRow(line) {
        return line.trim().replace(/^\||\|$/g, '').split('|').map(function (cell) {
            return cell.trim();
        });
    }

    function isDivider(line) {
        return /^\s*\|?[\s:|-]*-[\s:|-]*\|?\s*$/.test(line) && line.indexOf('-') !== -1;
    }

    function render(source) {
        return renderBlocks(escapeHtml(source == null ? '' : source).split('\n'));
    }

    /* Block-level pass over ALREADY-ESCAPED lines. Returns an HTML string.
       Nested blocks recurse here, never through render(), so nothing is
       escaped twice. */
    function renderBlocks(lines) {
        var html = [];
        var i = 0;

        function flushList(ordered) {
            var tag = ordered ? 'ol' : 'ul';
            var pattern = ordered ? /^\s*\d+[.)]\s+(.*)$/ : /^\s*[-*+]\s+(.*)$/;
            var items = [];

            while (i < lines.length && pattern.test(lines[i])) {
                items.push('<li>' + inline(lines[i].match(pattern)[1]) + '</li>');
                i++;
            }
            html.push('<' + tag + '>' + items.join('') + '</' + tag + '>');
        }

        while (i < lines.length) {
            var line = lines[i];

            /* Fenced code — consumed verbatim, never inline-parsed. An unclosed
               fence (mid-stream) still renders, so code doesn't flicker in. */
            var fence = line.match(/^\s*```(\w+)?\s*$/);
            if (fence) {
                var lang = fence[1] || '';
                var code = [];
                i++;
                while (i < lines.length && !/^\s*```\s*$/.test(lines[i])) {
                    code.push(lines[i]);
                    i++;
                }
                i++;
                html.push('<pre><code' + (lang ? ' class="language-' + lang + '"' : '') + '>' +
                    code.join('\n') + '</code></pre>');
                continue;
            }

            if (!line.trim()) { i++; continue; }

            var heading = line.match(/^(#{1,4})\s+(.*)$/);
            if (heading) {
                var level = heading[1].length;
                html.push('<h' + level + '>' + inline(heading[2]) + '</h' + level + '>');
                i++;
                continue;
            }

            if (/^\s*([-*_])\1{2,}\s*$/.test(line)) {
                html.push('<hr>');
                i++;
                continue;
            }

            /* Table: a header row followed by a divider row. */
            if (line.indexOf('|') !== -1 && i + 1 < lines.length && isDivider(lines[i + 1])) {
                var head = tableRow(line).map(function (cell) { return '<th>' + inline(cell) + '</th>'; });
                var rows = [];
                i += 2;
                while (i < lines.length && lines[i].indexOf('|') !== -1 && lines[i].trim()) {
                    rows.push('<tr>' + tableRow(lines[i]).map(function (cell) {
                        return '<td>' + inline(cell) + '</td>';
                    }).join('') + '</tr>');
                    i++;
                }
                html.push('<div class="table-scroll"><table><thead><tr>' + head.join('') +
                    '</tr></thead><tbody>' + rows.join('') + '</tbody></table></div>');
                continue;
            }

            // NB: escaping already ran, so a blockquote marker is `&gt;` here.
            if (QUOTE.test(line)) {
                var quote = [];
                while (i < lines.length && QUOTE.test(lines[i])) {
                    quote.push(lines[i].replace(QUOTE, ''));
                    i++;
                }
                html.push('<blockquote>' + renderBlocks(quote) + '</blockquote>');
                continue;
            }

            if (/^\s*[-*+]\s+/.test(line)) { flushList(false); continue; }
            if (/^\s*\d+[.)]\s+/.test(line)) { flushList(true); continue; }

            /* Paragraph — soft-wrapped lines join, a blank line ends it. */
            var para = [];
            while (i < lines.length && lines[i].trim() && !BLOCK_START.test(lines[i])) {
                para.push(lines[i]);
                i++;
            }

            var text = para.join(' ');
            /* The checkpoint is the one line that is a UI element, not prose. */
            var isCheckpoint = /^\s*(\*\*)?Checkpoint\b/i.test(text);
            html.push('<p' + (isCheckpoint ? ' class="dth-checkpoint-line"' : '') + '>' + inline(text) + '</p>');
        }

        return html.join('');
    }

    window.DTHMarkdown = { render: render, escape: escapeHtml };
})();
