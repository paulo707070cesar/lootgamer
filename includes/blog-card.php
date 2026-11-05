<?php
/** Card de artigo do blog. Variável: $post */
$img = !empty($post['featured_image']) ? url($post['featured_image']) : asset('images/placeholder.svg');
$alt = $post['featured_alt'] ?: $post['title'];
$mins = reading_minutes(word_count((string)$post['content']));
$date = $post['published_at'] ?: $post['created_at'];
?>
<article class="bcard">
  <a class="bcard-img" href="<?= e(url('blog/' . $post['slug'])) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e($img) ?>" alt="<?= e($alt) ?>" loading="lazy" width="640" height="360" data-fallback>
  </a>
  <div class="bcard-body">
    <p class="bcard-meta">
      <?php if (!empty($post['cat_name'])): ?><a href="<?= e(url('blog/categoria/' . $post['cat_slug'])) ?>"><?= e($post['cat_name']) ?></a> · <?php endif; ?>
      <time datetime="<?= e(date('c', strtotime($date))) ?>"><?= e(date('d/m/Y', strtotime($date))) ?></time> · <?= $mins ?> min de leitura
    </p>
    <h2 class="bcard-title"><a href="<?= e(url('blog/' . $post['slug'])) ?>"><?= e($post['title']) ?></a></h2>
    <?php $desc = $post['excerpt'] ?: excerpt_from((string)$post['content'], 150); if ($desc): ?><p class="bcard-desc"><?= e($desc) ?></p><?php endif; ?>
    <a class="bcard-more" href="<?= e(url('blog/' . $post['slug'])) ?>">Ler artigo →</a>
  </div>
</article>
