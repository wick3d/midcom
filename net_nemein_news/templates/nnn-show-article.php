<?php
/**
 * @package net_nemein_news
 */
?>
<div class="hentry">
    <h1 tal:content="net_nemein_news/article_dm/types/title/as_html" class="entry-title">Headline</h1>

    <div tal:content="net_nemein_news/article/metadata/published" class="published">2007-08-01</div>

    <div tal:content="structure net_nemein_news/article_dm/types/content/as_html" class="entry-content">
        Content
    </div>
</div>