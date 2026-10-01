<?php
/**
 * Renders a Bootstrap-style pagination bar (matches the existing look used
 * across the list pages) and links back to the current script with a
 * ?page= query param. Any $extra_params (e.g. a search box's ?q=) are kept
 * on every page link so paging never loses a filter the user has applied.
 *
 * Usage:
 *   render_pagination($page, $total_pages);
 *   render_pagination($page, $total_pages, ['q' => $search]);
 */
function render_pagination(int $page, int $total_pages, array $extra_params = []): void
{
    if ($total_pages <= 1) {
        return;
    }

    $build_link = function (int $target_page) use ($extra_params) {
        $params = $extra_params;
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }
        $params['page'] = $target_page;
        return htmlspecialchars('?' . http_build_query($params));
    };

    // Show up to 2 pages either side of the current one, with first/last
    // always reachable so long lists don't produce a huge link row.
    $window = 2;
    $start  = max(1, $page - $window);
    $end    = min($total_pages, $page + $window);
    ?>
    <div class="d-flex justify-content-end mt-4">
      <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm m-0 gap-1">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link rounded-2 text-dark border-0" href="<?php echo $page > 1 ? $build_link($page - 1) : '#'; ?>" aria-label="Previous">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>

          <?php if ($start > 1): ?>
            <li class="page-item"><a class="page-link rounded-2 text-dark border-0 px-3" href="<?php echo $build_link(1); ?>">1</a></li>
            <?php if ($start > 2): ?>
              <li class="page-item disabled"><span class="page-link border-0">&hellip;</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link rounded-2 <?php echo $i === $page ? 'px-3 text-white' : 'text-dark border-0 px-3'; ?>" href="<?php echo $build_link($i); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?>
              <li class="page-item disabled"><span class="page-link border-0">&hellip;</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link rounded-2 text-dark border-0 px-3" href="<?php echo $build_link($total_pages); ?>"><?php echo $total_pages; ?></a></li>
          <?php endif; ?>

          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link rounded-2 text-dark border-0" href="<?php echo $page < $total_pages ? $build_link($page + 1) : '#'; ?>" aria-label="Next">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
    <?php
}
