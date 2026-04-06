<style>
/* ===================================
   PORTAL BOOKING.COM STYLE
   Guía de estilos para portal de reservas
   =================================== */

:root {
    /* Colores Booking.com */
    --booking-blue: #003580;
    --booking-blue-hover: #004585;
    --booking-blue-light: #E9F0FF;
    --booking-yellow: #FFB700;
    --booking-success: #0D7377;
    --booking-error: #EB5757;
    --booking-gray-dark: #333333;
    --booking-gray-medium: #666666;
    --booking-gray-light: #E8E8E8;
    --booking-gray-bg: #F5F5F5;
    --booking-white: #FFFFFF;
    
    /* Espaciado */
    --spacing-xs: 8px;
    --spacing-sm: 16px;
    --spacing-md: 24px;
    --spacing-lg: 32px;
    --spacing-xl: 40px;
    
    /* Tipografía */
    --font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --font-size-base: 16px;
    --font-size-small: 14px;
    --font-size-xsmall: 12px;
    
    /* Transiciones */
    --transition: all 0.2s ease;
}

body {
    margin: 0;
    padding: 0;
    background-color: var(--booking-gray-bg);
}

/* Reset y Base */
.booking-portal {
    font-family: var(--font-family);
    background-color: var(--booking-gray-bg);
    color: var(--booking-gray-dark);
    line-height: 1.6;
}

.booking-portal * {
    box-sizing: border-box;
}

/* Header */
.booking-header {
    background: var(--booking-white);
    border-bottom: 1px solid var(--booking-gray-light);
    padding: var(--spacing-md) 0;
    margin-bottom: var(--spacing-lg);
}

.booking-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--booking-blue);
    margin: 0;
    line-height: 1.2;
}

.booking-header .subtitle {
    color: var(--booking-gray-medium);
    font-size: var(--font-size-base);
    margin-top: var(--spacing-xs);
}

/* Container */
.booking-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-md);
}

@media (max-width: 768px) {
    .booking-container {
        padding: 0 var(--spacing-sm);
    }
}

/* Search Summary Card */
.booking-search-summary {
    background: var(--booking-white);
    border: 1px solid var(--booking-gray-light);
    border-radius: 8px;
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.booking-search-summary h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--booking-gray-dark);
    margin: 0 0 var(--spacing-md) 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.booking-search-summary .summary-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
    font-size: var(--font-size-base);
}

.booking-search-summary .summary-item i {
    color: var(--booking-blue);
    width: 20px;
    text-align: center;
}

.booking-search-summary .summary-item strong {
    color: var(--booking-gray-dark);
    font-weight: 600;
}

.booking-search-summary .summary-item span {
    color: var(--booking-gray-medium);
}

/* Botones */
.booking-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    font-size: var(--font-size-base);
    border: none;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    font-family: var(--font-family);
}

.booking-btn-primary {
    background: var(--booking-blue);
    color: var(--booking-white);
}

.booking-btn-primary:hover {
    background: var(--booking-blue-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
}

.booking-btn-secondary {
    background: var(--booking-white);
    color: var(--booking-blue);
    border: 2px solid var(--booking-blue);
}

.booking-btn-secondary:hover {
    background: var(--booking-blue-light);
}

/* Property Cards */
.booking-properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(560px, 1fr));
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

@media (max-width: 768px) {
    .booking-properties-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
    }
}

.booking-property-card {
    background: var(--booking-white);
    border: 1px solid var(--booking-gray-light);
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

.booking-property-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.booking-property-image {
    width: 300px;
    min-width: 300px;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--booking-white);
    font-size: 48px;
    position: relative;
    overflow: hidden;
}

.booking-property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.booking-property-image::after {
    content: '🏠';
    position: absolute;
    font-size: 64px;
    opacity: 0.3;
}

@media (max-width: 768px) {
    .booking-property-card {
        flex-direction: column;
    }
    
    .booking-property-image {
        width: 100%;
        min-width: 100%;
        height: 200px;
    }
}

.booking-property-content {
    flex: 1;
    padding: var(--spacing-md);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.booking-property-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--booking-blue);
    margin: 0 0 var(--spacing-xs) 0;
    line-height: 1.3;
}

.booking-property-location {
    color: var(--booking-gray-medium);
    font-size: var(--font-size-base);
    margin-bottom: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.booking-property-features {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--spacing-md) 0;
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
}

.booking-property-features li {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    color: var(--booking-gray-medium);
    font-size: var(--font-size-small);
}

.booking-property-features i {
    color: var(--booking-blue);
    width: 18px;
}

.booking-property-description {
    color: var(--booking-gray-medium);
    font-size: var(--font-size-small);
    line-height: 1.6;
    margin: var(--spacing-md) 0 0 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.booking-property-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--booking-gray-light);
}

.booking-property-price {
    font-size: 24px;
    font-weight: 700;
    color: var(--booking-blue);
}

.booking-property-price-label {
    font-size: var(--font-size-small);
    color: var(--booking-gray-medium);
    display: block;
    font-weight: 400;
}

/* Alerts */
.booking-alert {
    padding: var(--spacing-md);
    border-radius: 8px;
    margin-bottom: var(--spacing-md);
    border-left: 4px solid;
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-sm);
}

.booking-alert-info {
    background: var(--booking-blue-light);
    border-color: var(--booking-blue);
    color: var(--booking-blue);
}

.booking-alert-warning {
    background: #FFF4E6;
    border-color: var(--booking-yellow);
    color: #856404;
}

.booking-alert-success {
    background: #E6F7F8;
    border-color: var(--booking-success);
    color: var(--booking-success);
}

.booking-alert i {
    font-size: 20px;
    margin-top: 2px;
}

/* Section Headers */
.booking-section-header {
    font-size: 24px;
    font-weight: 600;
    color: var(--booking-gray-dark);
    margin: var(--spacing-xl) 0 var(--spacing-md) 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.booking-section-header i {
    color: var(--booking-blue);
}

/* Empty State */
.booking-empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    background: var(--booking-white);
    border-radius: 8px;
    border: 1px solid var(--booking-gray-light);
}

.booking-empty-state i {
    font-size: 64px;
    color: var(--booking-gray-light);
    margin-bottom: var(--spacing-md);
}

.booking-empty-state h3 {
    color: var(--booking-gray-dark);
    margin-bottom: var(--spacing-sm);
}

.booking-empty-state p {
    color: var(--booking-gray-medium);
    margin: 0;
}

/* Loading State */
.booking-loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: var(--spacing-xl);
    color: var(--booking-gray-medium);
}

.booking-loading i {
    animation: spin 1s linear infinite;
    font-size: 32px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Inline Search Form */
.booking-inline-search {
    background: var(--booking-white);
    border: 1px solid var(--booking-gray-light);
    border-radius: 8px;
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: all 0.3s ease;
}

.booking-inline-search.active {
    max-height: 500px;
    opacity: 1;
    margin-bottom: var(--spacing-lg);
}

.booking-search-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.booking-search-field {
    display: flex;
    flex-direction: column;
}

.booking-search-field label {
    font-size: var(--font-size-small);
    font-weight: 600;
    color: var(--booking-gray-dark);
    margin-bottom: var(--spacing-xs);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.booking-search-field label i {
    color: var(--booking-blue);
    width: 16px;
}

.booking-search-field input,
.booking-search-field select {
    padding: 12px 16px;
    border: 2px solid var(--booking-gray-light);
    border-radius: 6px;
    font-size: var(--font-size-base);
    font-family: var(--font-family);
    transition: var(--transition);
    background: var(--booking-white);
    color: var(--booking-gray-dark);
}

.booking-search-field input:focus,
.booking-search-field select:focus {
    outline: none;
    border-color: var(--booking-blue);
    box-shadow: 0 0 0 3px rgba(0, 53, 128, 0.1);
}

.booking-search-actions {
    display: flex;
    gap: var(--spacing-sm);
    justify-content: flex-end;
    padding-top: var(--spacing-sm);
    border-top: 1px solid var(--booking-gray-light);
}

/* Smooth scroll animation */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.booking-inline-search.active .booking-search-form-row {
    animation: slideDown 0.3s ease;
}

/* Responsive Utilities */
@media (max-width: 768px) {
    .booking-header h1 {
        font-size: 24px;
    }
    
    .booking-property-title {
        font-size: 18px;
    }
    
    .booking-property-price {
        font-size: 20px;
    }
    
    .booking-btn {
        padding: 10px 20px;
        font-size: var(--font-size-small);
    }
    
    .booking-search-form-row {
        grid-template-columns: 1fr;
    }
    
    .booking-search-actions {
        flex-direction: column;
    }
    
    .booking-search-actions .booking-btn {
        width: 100%;
    }
}
</style>
