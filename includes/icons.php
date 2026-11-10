<?php
    function icon(string $nome, int $tamanho = 20): string
    {
        $paths = [
            'menu' => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
            'close' => '<line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/>',
            'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.4-3.8 4.4-6 7.5-6s6.1 2.2 7.5 6"/>',
            'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7.5a4 4 0 0 1 8 0V11"/>',
            'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><line x1="20" y1="20" x2="15.3" y2="15.3"/>',
            'filter' => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/>',
            'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.2"/><circle cx="12" cy="12" r="0.6" fill="currentColor"/>',
            'gauge' => '<path d="M4 15a8 8 0 1 1 16 0"/><line x1="12" y1="15" x2="15.5" y2="10.5"/><circle cx="12" cy="15" r="1.1" fill="currentColor"/>',
            'pause' => '<rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>',
            'sparkles' => '<path d="M12 4l1.4 4.2L18 10l-4.6 1.8L12 16l-1.4-4.2L6 10l4.6-1.8L12 4z"/><path d="M19 15l.7 2.1L22 18l-2.3.9L19 21l-.7-2.1L16 18l2.3-.9L19 15z"/>',
            'logout' => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><line x1="15" y1="12" x2="21" y2="12"/><polyline points="17 8 21 12 17 16"/>',
            'settings' => '<circle cx="12" cy="12" r="3.2"/><circle cx="12" cy="12" r="7.2"/>'
                . '<line x1="18" y1="12" x2="21" y2="12"/><line x1="3" y1="12" x2="6" y2="12"/>'
                . '<line x1="12" y1="6" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="18"/>'
                . '<line x1="16.2" y1="16.2" x2="18.4" y2="18.4"/><line x1="5.6" y1="5.6" x2="7.8" y2="7.8"/>'
                . '<line x1="16.2" y1="7.8" x2="18.4" y2="5.6"/><line x1="5.6" y1="18.4" x2="7.8" y2="16.2"/>',
            'check' => '<polyline points="4 12 9 17 20 6"/>',
            'alert' => '<path d="M12 3l9.5 17H2.5L12 3z"/><line x1="12" y1="10" x2="12" y2="14.5"/><circle cx="12" cy="17.2" r="0.6" fill="currentColor"/>',
            'plug' => '<path d="M9 3v6M15 3v6M6 9h12l-1.2 6.2a5 5 0 0 1-4.9 4V22"/><line x1="10" y1="19" x2="14" y2="19"/>',
            'eye' => '<path d="M2 12s3.7-6.5 10-6.5S22 12 22 12s-3.7 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/>',
            'eye-off' => '<path d="M3 3l18 18"/><path d="M9.6 5.9C10.4 5.6 11.2 5.5 12 5.5c6.3 0 10 6.5 10 6.5a17 17 0 0 1-3.4 4.1M6.3 7.4A17 17 0 0 0 2 12s3.7 6.5 10 6.5c1.4 0 2.7-.3 3.8-.8"/><path d="M9.9 10a2.6 2.6 0 0 0 3.6 3.6"/>',
            'expand' => '<polyline points="8 3 3 3 3 8"/><polyline points="16 3 21 3 21 8"/><polyline points="21 16 21 21 16 21"/><polyline points="3 16 3 21 8 21"/>',
            'compress' => '<polyline points="9 3 9 8 4 8"/><polyline points="15 3 15 8 20 8"/><polyline points="9 21 9 16 4 16"/><polyline points="15 21 15 16 20 16"/>',
            'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><line x1="9" y1="11" x2="15" y2="11"/><line x1="9" y1="15" x2="15" y2="15"/>',
            'edit' => '<path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16v4z"/><line x1="13.5" y1="7" x2="17" y2="10.5"/>',
            'trash' => '<polyline points="4 7 20 7"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>',
        ];

        $inner = $paths[$nome] ?? '';
        return '<svg width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" '
            . 'aria-hidden="true" focusable="false">' . $inner . '</svg>';
    }
