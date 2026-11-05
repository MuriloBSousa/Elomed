<?php

/**
 * Pagination Helper
 * Manages pagination of results
 */

class Pagination {
    private int $total;
    private int $perPage;
    private int $currentPage;
    private string $url;
    private array $params;
    
    public function __construct(int $total, int $perPage = null, int $currentPage = 1, string $url = '', array $params = []) {
        $this->total = $total;
        $this->perPage = $perPage ?? Constants::PAGINATION_DEFAULT_LIMIT;

        // Caps the number of items per page
        $maxLimit = Constants::PAGINATION_MAX_LIMIT;
        if ($this->perPage > $maxLimit) {
            $this->perPage = $maxLimit;
        }

        // Clamps the current page to a valid range: at least 1 and, when there
        // is content, no further than the last page.
        $lastPage = max(1, $this->getTotalPages());
        $this->currentPage = min($lastPage, max(1, $currentPage));
        $this->url = $url;
        $this->params = $params;
    }
    
    /**
     * Gets the total number of pages
     */
    public function getTotalPages(): int {
        return (int) ceil($this->total / $this->perPage);
    }
    
    /**
     * Gets the offset for the SQL query
     */
    public function getOffset(): int {
        return ($this->currentPage - 1) * $this->perPage;
    }
    
    /**
     * Gets the limit for the SQL query
     */
    public function getLimit(): int {
        return $this->perPage;
    }
    
    /**
     * Checks whether there is a previous page
     */
    public function hasPreviousPage(): bool {
        return $this->currentPage > 1;
    }
    
    /**
     * Checks whether there is a next page
     */
    public function hasNextPage(): bool {
        return $this->currentPage < $this->getTotalPages();
    }
    
    /**
     * Gets the previous page number
     */
    public function getPreviousPage(): int {
        return max(1, $this->currentPage - 1);
    }
    
    /**
     * Gets the next page number
     */
    public function getNextPage(): int {
        return min($this->getTotalPages(), $this->currentPage + 1);
    }
    
    /**
     * Gets the URL for a specific page
     */
    public function getUrl(int $page): string {
        $params = array_merge($this->params, ['page' => $page]);
        $queryString = http_build_query($params);
        
        return $this->url . ($queryString ? '?' . $queryString : '');
    }
    
    /**
     * Gets the URL of the previous page
     */
    public function getPreviousUrl(): string {
        return $this->getUrl($this->getPreviousPage());
    }
    
    /**
     * Gets the URL of the next page
     */
    public function getNextUrl(): string {
        return $this->getUrl($this->getNextPage());
    }
    
    /**
     * Gets the URL of the first page
     */
    public function getFirstUrl(): string {
        return $this->getUrl(1);
    }
    
    /**
     * Gets the URL of the last page
     */
    public function getLastUrl(): string {
        return $this->getUrl($this->getTotalPages());
    }
    
    /**
     * Gets the number of items being displayed
     */
    public function getFrom(): int {
        return min($this->total, $this->getOffset() + 1);
    }
    
    /**
     * Gets the number of the last item being displayed
     */
    public function getTo(): int {
        return min($this->total, $this->getOffset() + $this->perPage);
    }
    
    /**
     * Gets an array with the page numbers to be displayed
     */
    public function getPages(int $surrounding = 2): array {
        $totalPages = $this->getTotalPages();
        $currentPage = $this->currentPage;
        
        if ($totalPages <= 1) {
            return [1];
        }
        
        $pages = [];
        
        // Always shows the first page
        $pages[] = 1;
        
        // Pages around the current one
        for ($i = max(2, $currentPage - $surrounding); $i <= min($totalPages - 1, $currentPage + $surrounding); $i++) {
            $pages[] = $i;
        }
        
        // Always shows the last page
        if ($totalPages > 1) {
            $pages[] = $totalPages;
        }
        
        // Removes duplicates and sorts
        $pages = array_unique($pages);
        sort($pages);
        
        return $pages;
    }
    
    /**
     * Gets pagination information as an array
     */
    public function toArray(): array {
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'total_pages' => $this->getTotalPages(),
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->hasNextPage(),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage(),
            'previous_url' => $this->hasPreviousPage() ? $this->getPreviousUrl() : null,
            'next_url' => $this->hasNextPage() ? $this->getNextUrl() : null,
            'first_url' => $this->getFirstUrl(),
            'last_url' => $this->getLastUrl(),
            'pages' => $this->getPages()
        ];
    }
    
    /**
     * Renders the pagination HTML
     */
    public function render(string $view = 'pagination'): string {
        $data = $this->toArray();

        if ($data['total_pages'] <= 1) {
            return '';
        }

        $pagination = $this;
        ob_start();
        require __DIR__ . "/../views/components/{$view}.php";
        return ob_get_clean();
    }
}