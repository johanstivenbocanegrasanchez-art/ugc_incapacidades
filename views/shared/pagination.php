<?php

declare(strict_types=1);

if (!function_exists('ugcPaginateRows')) {
    function ugcPaginateRows(array $rows, string $paramName = 'pagina', int $perPage = 5): array
    {
        $perPage = max(1, $perPage);
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET[$paramName] ?? 1)));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'rows' => array_slice($rows, $offset, $perPage),
            'current' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'paramName' => $paramName,
        ];
    }
}

if (!function_exists('ugcBuildPageUrl')) {
    function ugcBuildPageUrl(string $paramName, int $page): string
    {
        $params = $_GET;
        $params[$paramName] = $page;

        $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
        $query = http_build_query($params);

        return $path . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('ugcRenderPagination')) {
    function ugcRenderPagination(array $pagination, string $label = 'registros'): void
    {
        $totalPages = (int) ($pagination['totalPages'] ?? 1);
        if ($totalPages <= 1) {
            return;
        }

        $current = (int) ($pagination['current'] ?? 1);
        $total = (int) ($pagination['total'] ?? 0);
        $perPage = (int) ($pagination['perPage'] ?? 5);
        $paramName = (string) ($pagination['paramName'] ?? 'pagina');
        $windowSize = 4;
        $start = max(2, min($current - 1, max(2, $totalPages - $windowSize + 2)));
        $end = min($totalPages, $start + $windowSize - 2);
        $firstShown = (($current - 1) * $perPage) + 1;
        $lastShown = min($total, $current * $perPage);
        ?>
        <nav class="ugc-pagination" aria-label="Paginacion de <?= htmlspecialchars($label) ?>">
            <div class="ugc-pagination__meta">
                <?= $firstShown ?>-<?= $lastShown ?> de <?= $total ?>
            </div>

            <div class="ugc-pagination__controls">
                <?php if ($current > 1): ?>
                    <a class="btn btn-outline btn-sm ugc-pagination__step" href="<?= htmlspecialchars(ugcBuildPageUrl($paramName, $current - 1)) ?>" aria-label="Pagina anterior">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        <span>Anterior</span>
                    </a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm ugc-pagination__step is-disabled" aria-disabled="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        <span>Anterior</span>
                    </span>
                <?php endif; ?>

                <?php if ($current === 1): ?>
                    <span class="btn btn-green btn-sm ugc-pagination__page is-active" aria-current="page">1</span>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm ugc-pagination__page" href="<?= htmlspecialchars(ugcBuildPageUrl($paramName, 1)) ?>">1</a>
                <?php endif; ?>

                <?php if ($start > 2): ?>
                    <span class="ugc-pagination__ellipsis">...</span>
                <?php endif; ?>

                <?php for ($page = $start; $page <= $end; $page++): ?>
                    <?php if ($page === $current): ?>
                        <span class="btn btn-green btn-sm ugc-pagination__page is-active" aria-current="page"><?= $page ?></span>
                    <?php else: ?>
                        <a class="btn btn-outline btn-sm ugc-pagination__page" href="<?= htmlspecialchars(ugcBuildPageUrl($paramName, $page)) ?>"><?= $page ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <span class="ugc-pagination__ellipsis">...</span>
                <?php endif; ?>

                <?php if ($current < $totalPages): ?>
                    <a class="btn btn-outline btn-sm ugc-pagination__step" href="<?= htmlspecialchars(ugcBuildPageUrl($paramName, $current + 1)) ?>" aria-label="Pagina siguiente">
                        <span>Siguiente</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm ugc-pagination__step is-disabled" aria-disabled="true">
                        <span>Siguiente</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </span>
                <?php endif; ?>
            </div>
        </nav>
        <?php
    }
}
