<?php

if (!function_exists('get_seo_meta')) {
    /**
     * Obtener meta tags SEO para una ruta específica
     * 
     * @param string|null $routeName Nombre de la ruta (ej: 'web.index')
     * @return \App\Models\SeoMeta|null
     */
    function get_seo_meta($routeName = null)
    {
        if (!$routeName) {
            $routeName = request()->route()->getName();
        }
        
        if (!$routeName) {
            return null;
        }
        
        return \App\Models\SeoMeta::getByRoute($routeName);
    }
}

if (!function_exists('render_seo_meta_tags')) {
    /**
     * Renderizar todos los meta tags SEO en el <head>
     * 
     * @param string|null $routeName Nombre de la ruta
     * @return string HTML con los meta tags
     */
    function render_seo_meta_tags($routeName = null)
    {
        $seoMeta = get_seo_meta($routeName);
        
        if (!$seoMeta || !$seoMeta->active) {
            return '';
        }
        
        $html = [];
        $locale = app()->getLocale();
        $baseUrl = config('app.url');
        
        // Title
        if ($seoMeta->page_title) {
            $html[] = '<title>' . e($seoMeta->page_title) . '</title>';
        }
        
        // Meta Description
        if ($seoMeta->meta_description) {
            $html[] = '<meta name="description" content="' . e($seoMeta->meta_description) . '">';
        }
        
        // Meta Keywords
        if ($seoMeta->meta_keywords) {
            $html[] = '<meta name="keywords" content="' . e($seoMeta->meta_keywords) . '">';
        }
        
        // Robots
        if ($seoMeta->robots) {
            $html[] = '<meta name="robots" content="' . e($seoMeta->robots) . '">';
        }
        
        // Canonical URL
        if ($seoMeta->canonical_url) {
            $html[] = '<link rel="canonical" href="' . e($seoMeta->canonical_url) . '">';
        } else {
            // Generar canonical automático
            $html[] = '<link rel="canonical" href="' . e(request()->url()) . '">';
        }
        
        // Hreflang
        $hreflangs = [
            'es' => $seoMeta->hreflang_es,
            'en' => $seoMeta->hreflang_en,
            'fr' => $seoMeta->hreflang_fr,
            'de' => $seoMeta->hreflang_de,
            'it' => $seoMeta->hreflang_it,
            'pt' => $seoMeta->hreflang_pt,
        ];
        
        foreach ($hreflangs as $lang => $url) {
            if ($url) {
                $html[] = '<link rel="alternate" hreflang="' . $lang . '" href="' . e($url) . '">';
            }
        }
        
        // Open Graph
        $ogTitle = $seoMeta->og_title ?: $seoMeta->page_title;
        $ogDescription = $seoMeta->og_description ?: $seoMeta->meta_description;
        $ogImage = $seoMeta->og_image ?: ($baseUrl . '/LOGO-HAWKINS.png');
        
        if ($ogTitle) {
            $html[] = '<meta property="og:title" content="' . e($ogTitle) . '">';
        }
        if ($ogDescription) {
            $html[] = '<meta property="og:description" content="' . e($ogDescription) . '">';
        }
        if ($ogImage) {
            $html[] = '<meta property="og:image" content="' . e($ogImage) . '">';
        }
        $html[] = '<meta property="og:type" content="' . e($seoMeta->og_type ?: 'website') . '">';
        $html[] = '<meta property="og:url" content="' . e(request()->url()) . '">';
        $html[] = '<meta property="og:locale" content="' . e($locale . '_' . strtoupper($locale)) . '">';
        
        // Twitter Card
        $twitterTitle = $seoMeta->twitter_title ?: $seoMeta->page_title;
        $twitterDescription = $seoMeta->twitter_description ?: $seoMeta->meta_description;
        $twitterImage = $seoMeta->twitter_image ?: $ogImage;
        
        $html[] = '<meta name="twitter:card" content="' . e($seoMeta->twitter_card ?: 'summary_large_image') . '">';
        if ($twitterTitle) {
            $html[] = '<meta name="twitter:title" content="' . e($twitterTitle) . '">';
        }
        if ($twitterDescription) {
            $html[] = '<meta name="twitter:description" content="' . e($twitterDescription) . '">';
        }
        if ($twitterImage) {
            $html[] = '<meta name="twitter:image" content="' . e($twitterImage) . '">';
        }
        
        // Structured Data (JSON-LD)
        if ($seoMeta->structured_data && is_array($seoMeta->structured_data)) {
            $html[] = '<script type="application/ld+json">' . json_encode($seoMeta->structured_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
        }
        
        return implode("\n    ", $html);
    }
}

