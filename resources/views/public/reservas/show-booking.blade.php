@extends('layouts.public-booking')

@section('title', (($apartamento->titulo ?? $apartamento->nombre) ?: '') . ' - Apartamentos Algeciras')

@section('styles')
<style>
        /* GALERÍA ESTILO BOOKING.COM */
        .booking-gallery-view-all {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s ease;
            z-index: 10;
        }
        
        .booking-gallery-view-all:hover {
            background: rgba(0, 0, 0, 0.9);
        }
        
        .booking-gallery-more {
            position: relative;
            cursor: pointer;
        }
        
        .booking-gallery-more-count {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            border-radius: 6px;
        }
        .booking-gallery {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 8px;
            margin: 0 0 24px 0;
            height: 400px;
            max-height: 400px;
            position: relative;
        }
        
        .booking-gallery-main {
            position: relative;
            border-radius: 8px;
            background: #f0f0f0;
            height: 400px;
            max-height: 400px;
            width: 100%;
            overflow: hidden;
        }
        
        .booking-gallery-main img {
            width: 100%;
            height: 400px;
            max-height: 400px;
            object-fit: cover;
            object-position: center center;
            display: block;
        }
        
        .booking-gallery-thumbs {
            display: grid;
            grid-template-rows: 196px 196px;
            gap: 8px;
            height: 400px;
            max-height: 400px;
        }
        
        .booking-gallery-thumb {
            position: relative;
            border-radius: 8px;
            cursor: pointer;
            background: #f0f0f0;
            border: 2px solid transparent;
            transition: all 0.2s ease;
            width: 100%;
            height: 196px;
            max-height: 196px;
            overflow: hidden;
        }
        
        .booking-gallery-thumb:hover {
            border-color: #0071C2;
        }
        
        .booking-gallery-thumb.active {
            border-color: #0071C2;
            box-shadow: 0 0 0 2px rgba(0, 113, 194, 0.3);
        }
        
        .booking-gallery-thumb img {
            width: 100%;
            height: 196px;
            max-height: 196px;
            object-fit: cover;
            object-position: center center;
            display: block;
        }
        
        /* Fila de miniaturas adicionales (estilo Booking.com) */
        .booking-gallery-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-top: 8px;
            height: 100px;
        }
        
        .booking-gallery-row-item {
            position: relative;
            overflow: hidden;
            border-radius: 6px;
            cursor: pointer;
            background: #f0f0f0;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .booking-gallery-row-item:hover {
            border-color: #0071C2;
            transform: scale(1.05);
        }
        
        .booking-gallery-row-item.active {
            border-color: #0071C2;
            box-shadow: 0 0 0 2px rgba(0, 113, 194, 0.3);
        }
        
        .booking-gallery-row-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
        }
        
        /* RATING SECTION */
        .booking-rating {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 0;
        }
        
        .booking-rating-badge {
            background: #003580;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 18px;
        }
        
        .booking-rating-text {
            font-size: 16px;
            color: #333;
        }
        
        /* SERVICIOS POPULARES */
        .booking-popular-services {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 24px 0;
        }
        
        .booking-service-icon {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 15px;
            color: #333;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .booking-service-icon:hover {
            border-color: #0071C2;
            box-shadow: 0 2px 6px rgba(0, 113, 194, 0.2);
        }
        
        .booking-service-icon i,
        .booking-service-icon span[style*="font-size"] {
            color: #0071C2 !important;
            font-size: 18px !important;
            width: 20px;
            text-align: center;
        }
        
        .booking-service-icon span:not([style*="font-size"]) {
            font-weight: 500;
            color: #333;
        }
        
        /* SERVICIOS POR CATEGORÍA */
        .booking-services-category {
            margin-bottom: 24px;
        }
        
        .booking-services-category-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #E0E0E0;
        }
        
        .booking-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .booking-service-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: #F8F9FA;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .booking-service-item:hover {
            background: #F0F4F8;
            border-color: #0071C2;
        }
        
        .booking-service-icon-box {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .booking-service-icon-box i,
        .booking-service-icon-box span[style*="font-size"] {
            color: #0071C2 !important;
            font-size: 14px !important;
        }
        
        .booking-service-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        
        .booking-service-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            line-height: 1.3;
            word-wrap: break-word;
        }
        
        .booking-service-description {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        /* SECCIÓN DISPONIBILIDAD */
        .booking-availability-section {
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            padding: 24px;
            margin: 40px 0;
        }
        
        .booking-availability-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 16px;
        }
        
        .booking-availability-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: #FFF3CD;
            border: 1px solid #FFC107;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #856404;
            font-size: 14px;
        }
        
        .booking-availability-info i {
            color: #DC3545;
            font-size: 18px;
        }
        
        .booking-availability-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .booking-availability-date-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .booking-availability-date-group label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }
        
        .booking-availability-date-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 15px;
        }
        
        .booking-availability-date-input:focus {
            outline: none;
            border-color: #0071C2;
            box-shadow: 0 0 0 3px rgba(0, 113, 194, 0.1);
        }
        
        .booking-availability-guests {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: #F5F5F5;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        
        .booking-availability-search-btn {
            background: #0071C2;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease;
        }
        
        .booking-availability-search-btn:hover {
            background: #005fa3;
        }
        
        .booking-accommodation-type {
            background: #0071C2;
            color: white;
            padding: 12px 16px;
            border-radius: 6px 6px 0 0;
            font-weight: 600;
            margin-top: 24px;
        }
        
        .booking-accommodation-details {
            background: white;
            border: 1px solid #E0E0E0;
            border-top: none;
            border-radius: 0 0 6px 6px;
            padding: 16px;
        }
        
        .booking-accommodation-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 24px;
            align-items: center;
        }
        
        .booking-accommodation-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .booking-accommodation-info h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0071C2;
        }
        
        .booking-accommodation-beds {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .booking-accommodation-people {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .booking-show-prices-btn {
            background: #0071C2;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease;
        }
        
        .booking-show-prices-btn:hover {
            background: #005fa3;
        }
        
        .booking-view-availability-btn {
            background: #0071C2;
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 16px;
            transition: background 0.2s ease;
        }
        
        .booking-view-availability-btn:hover {
            background: #005fa3;
        }
        
        @media (max-width: 768px) {
            .booking-availability-form {
                grid-template-columns: 1fr;
            }
            
            .booking-accommodation-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        
        /* CATEGORY RATINGS */
        .booking-category-rating {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .booking-rating-bar {
            flex: 1;
            height: 8px;
            background: #E0E0E0;
            border-radius: 4px;
            margin: 0 16px;
            position: relative;
            overflow: hidden;
        }
        
        .booking-rating-bar-fill {
            height: 100%;
            background: #003580;
            border-radius: 4px;
        }
        
        /* ALREDEDORES */
        .booking-nearby-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .booking-nearby-list li {
            padding: 8px 0;
            border-bottom: 1px solid #E0E0E0;
            display: flex;
            justify-content: space-between;
        }
        
        /* PROPERTY DESCRIPTION */
        .property-description {
            color: #333;
            line-height: 1.8;
            font-size: 16px;
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .property-description * {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .property-description p,
        .property-description div {
            margin-bottom: 16px;
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .property-description strong,
        .property-description b {
            font-weight: 600 !important;
        }
        
        .property-description a {
            color: #0071C2;
            text-decoration: underline !important;
        }
        
        /* Forzar normal weight y sin subrayado en TODO el contenido traducido */
        .property-description [style*="font-weight"],
        .property-details-section [style*="font-weight"],
        .property-details-section p[style*="font-weight"],
        .property-details-section div[style*="font-weight"],
        .property-details-section [style*="font-weight: 700"],
        .property-details-section [style*="font-weight:700"],
        .property-details-section [style*="font-weight: bold"],
        .property-details-section [style*="font-weight:bold"],
        .property-description [style*="text-decoration"],
        .property-details-section [style*="text-decoration"],
        .property-details-section p[style*="text-decoration"],
        .property-details-section div[style*="text-decoration"] {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        /* Eliminar subrayado de TODOS los elementos excepto enlaces */
        .property-description *:not(a),
        .property-details-section *:not(a),
        .host-languages *:not(a),
        .booking-alert *:not(a) {
            text-decoration: none !important;
        }
        
        /* Forzar que los enlaces tengan subrayado solo en hover */
        .property-description a,
        .property-details-section a {
            text-decoration: none !important;
        }
        
        .property-description a:hover,
        .property-details-section a:hover {
            text-decoration: underline !important;
        }
        
        /* Estilos para sección de idiomas del host */
        .host-languages-label {
            font-weight: 700 !important;
            text-decoration: none !important;
        }
        
        .host-language-badge {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .host-language-badge strong,
        .host-language-badge b {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        /* Estilos para alertas del widget de reserva */
        .booking-alert strong {
            font-weight: 700 !important;
            text-decoration: none !important;
        }
        
        .booking-alert p {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .booking-alert * {
            text-decoration: none !important;
        }
        
        /* También forzar en elementos con clases específicas del contenido traducido */
        [class*="e7addce19e"],
        [class*="dc338fb28e"],
        [class*="b99b6ef58f"],
        [class*="b05437f6d2"] {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        /* Forzar en todos los elementos dentro de property-details-section */
        .property-details-section p,
        .property-details-section div,
        .property-details-section span {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .property-details-section strong,
        .property-details-section b {
            font-weight: 600 !important;
        }
        
        /* ACCORDION FAQ */
        .booking-accordion-item {
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        
        .booking-accordion-header {
            padding: 16px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }
        
        .booking-accordion-header:hover {
            background: #F5F5F5;
        }
        
        .booking-accordion-body {
            padding: 0 16px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        
        .booking-accordion-item.active .booking-accordion-body {
            padding: 16px;
            max-height: 500px;
        }
        
        /* WIDGET DE RESERVA MEJORADO */
        .booking-widget {
            background: #FFB700;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .booking-widget-search {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .booking-widget-search input,
        .booking-widget-search select {
            width: 100%;
            padding: 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .booking-widget-search input:focus,
        .booking-widget-search select:focus {
            outline: none;
            border-color: #0071C2;
            box-shadow: 0 0 0 3px rgba(0, 113, 194, 0.1);
        }
        
        .booking-widget-search-btn {
            width: 100%;
            background: #0071C2;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }
        
        /* ============================================
           WIDGET DE RESERVA - ESTILO BOOKING.COM
           ============================================ */
        .booking-reservation-widget {
            background: white;
            border: 2px solid #0071C2;
            border-radius: 12px;
            padding: 0;
            margin-top: 24px;
            box-shadow: 0 4px 16px rgba(0, 113, 194, 0.15);
            overflow: hidden;
            position: sticky !important;
            top: 24px !important;
            z-index: 10;
            align-self: start;
            width: 100%;
        }

        .booking-reservation-header {
            background: linear-gradient(135deg, #0071C2 0%, #0056CC 100%);
            color: white;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .booking-reservation-header i {
            font-size: 24px;
        }

        .booking-reservation-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: white;
        }

        .booking-reservation-details {
            padding: 20px 24px;
            border-bottom: 1px solid #E8E8E8;
        }

        .booking-reservation-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #F5F5F5;
        }

        .booking-reservation-detail-item:last-child {
            border-bottom: none;
        }

        .booking-reservation-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .booking-reservation-value {
            font-size: 15px;
            color: #1D1D1F;
            font-weight: 600;
        }

        .booking-reservation-price {
            padding: 24px;
            background: linear-gradient(135deg, #F8F9FA 0%, #FFFFFF 100%);
            border-bottom: 1px solid #E8E8E8;
            text-align: center;
        }

        .booking-reservation-price-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .booking-reservation-price-amount {
            font-size: 36px;
            font-weight: 700;
            color: #003580;
            margin: 8px 0;
            line-height: 1.2;
        }

        .booking-reservation-price-per-night {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        .booking-reservation-status {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 0;
        }

        .booking-reservation-status i {
            font-size: 40px;
            margin-bottom: 8px;
        }

        .booking-reservation-status-error i {
            color: #EB5757;
        }

        .booking-reservation-status-warning i {
            color: #FFB700;
        }

        .booking-reservation-status-info {
            color: #666;
        }

        .booking-reservation-status-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .booking-reservation-status-error .booking-reservation-status-title {
            color: #EB5757;
        }

        .booking-reservation-status-warning .booking-reservation-status-title {
            color: #856404;
        }

        .booking-reservation-status-text {
            font-size: 13px;
            color: #666;
            text-align: center;
            line-height: 1.4;
        }

        .booking-reservation-btn {
            width: 100%;
            padding: 18px 24px;
            border: none;
            border-radius: 0;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .booking-reservation-btn-active {
            background: linear-gradient(135deg, #0071C2 0%, #0056CC 100%);
            color: white;
        }

        .booking-reservation-btn-active:hover {
            background: linear-gradient(135deg, #0056CC 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 113, 194, 0.3);
        }

        .booking-reservation-btn-disabled {
            background: #E8E8E8;
            color: #999;
            cursor: not-allowed;
        }

        .booking-reservation-btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .booking-reservation-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 24px;
            color: #0071C2;
            text-decoration: none !important;
            font-weight: normal !important;
            font-size: 15px;
            border-top: 1px solid #E8E8E8;
            transition: all 0.2s ease;
        }

        .booking-reservation-link:hover {
            background: #F8F9FA;
            color: #0056CC;
            text-decoration: none !important;
        }

        .booking-reservation-link i {
            font-size: 16px;
        }
        
        .booking-reservation-link * {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        .booking-reservation-link strong,
        .booking-reservation-link b {
            font-weight: normal !important;
            text-decoration: none !important;
        }
        
        /* LAYOUT PRINCIPAL */
        .booking-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .booking-main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-top: 24px;
            align-items: start;
        }
        
        @media (max-width: 992px) {
            .booking-main-content {
                grid-template-columns: 1fr;
            }
            
            /* En móviles/tablets, el bloque buscar va arriba de la galería */
            .booking-gallery-search-row {
                flex-direction: column;
            }
            
            .booking-search-box {
                flex: 1 1 100% !important;
                margin-bottom: 16px;
            }
        }
        
        /* Estilos para el bloque de búsqueda */
        .booking-search-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .booking-search-input:hover {
            border-color: #0071C2;
        }
        
        .booking-search-input:focus {
            border-color: #0071C2;
            box-shadow: 0 0 0 3px rgba(0, 113, 194, 0.1);
        }
        
        .booking-search-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            font-size: 15px;
            background: white;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .booking-search-select:hover {
            border-color: #0071C2;
        }
        
        .booking-search-select:focus {
            border-color: #0071C2;
            box-shadow: 0 0 0 3px rgba(0, 113, 194, 0.1);
        }
        
        .booking-search-submit-btn {
            width: 100%;
            padding: 14px;
            background: #FFB700;
            color: #003580;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            margin-top: 8px;
        }
        
        .booking-search-submit-btn:hover {
            background: #FFA500;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 183, 0, 0.3);
        }
        
        .booking-search-submit-btn:active {
            transform: translateY(0);
        }
        
        /* FOOTER */
        .booking-footer {
            background: #F5F5F5;
            padding: 40px 0;
            margin-top: 60px;
            border-top: 1px solid #E0E0E0;
        }
        
        .booking-footer-columns {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .booking-footer h5 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }
        
        .booking-footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .booking-footer ul li {
            margin-bottom: 8px;
        }
        
        .booking-footer ul li a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .booking-footer ul li a:hover {
            text-decoration: underline;
        }
        
        /* MEJORAS VISUALES */
        .booking-container-header {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .booking-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 24px 0;
        }
        
        .booking-title-section {
            flex: 1;
        }
        
        .booking-reserve-btn-top {
            background: #0071C2;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .booking-reserve-btn-top:hover {
            background: #005fa3;
            color: white;
        }
    </style>
@endsection

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">Inicio</a>
            <span class="booking-breadcrumb-separator">></span>
            <a href="{{ route('web.apartamentos') }}">Apartamentos</a>
            @if(isset($apartamento))
                <span class="booking-breadcrumb-separator">></span>
                <strong>{{ $apartamento->titulo ?? $apartamento->nombre }}</strong>
            @endif
        </div>
    </div>
</div>
@endsection

@section('content')
    <div class="booking-detail-container" style="margin-top: 24px;">
        <!-- FILA: BUSCAR + GALERÍA -->
        <div class="booking-gallery-search-row" style="display: flex; gap: 24px; margin-bottom: 24px; align-items: flex-start;">
            <!-- Bloque Buscar (Izquierda) -->
            <div class="booking-search-box" style="flex: 0 0 320px; background: #2571C2; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px;">
                <h3 style="margin: 0 0 16px 0; color: white; font-size: 20px; font-weight: 700;">{{ __('apartment_detail.search') }}</h3>
                <form action="{{ route('web.reservas.portal') }}" method="GET">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label for="checkin" style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: white;">{{ __('apartment_detail.checkin') }}</label>
                            <input type="text" 
                                   id="checkin" 
                                   name="fecha_entrada" 
                                   placeholder="dd/mm/aaaa" 
                                   value="{{ $fechaEntrada ?? '' }}" 
                                   required
                                   class="booking-search-input">
                        </div>
                        <div>
                            <label for="checkout" style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: white;">{{ __('apartment_detail.checkout') }}</label>
                            <input type="text" 
                                   id="checkout" 
                                   name="fecha_salida" 
                                   placeholder="dd/mm/aaaa" 
                                   value="{{ $fechaSalida ?? '' }}" 
                                   required
                                   class="booking-search-input">
                        </div>
                        <div>
                            <label for="adultos_search" style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: white;">{{ __('apartment_detail.adults') }}</label>
                            <select name="adultos" id="adultos_search" class="booking-search-select">
                                @for($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}" {{ ($adultos ?? 2) == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'adulto' : 'adultos' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="ninos_search" style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: white;">{{ __('apartment_detail.children') }}</label>
                            <select name="ninos" id="ninos_search" class="booking-search-select">
                                @for($i = 0; $i <= 6; $i++)
                                    <option value="{{ $i }}" {{ ($ninos ?? 0) == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'niño' : 'niños' }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="booking-search-submit-btn" style="background: #FFB700; color: #003580;">
                            <i class="fas fa-search"></i> {{ __('apartment_detail.search') }}
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Galería (Derecha) -->
            <div style="flex: 1;">
        @php
            $photos = $apartamento->photos()->ordenadas()->get();
            $primaryPhoto = $photos->where('is_primary', true)->first() ?? $photos->first();
            $remainingPhotos = $photos->skip(3); // Fotos después de las primeras 3
        @endphp
        @if($photos->isNotEmpty())
            <div class="booking-gallery">
                <div class="booking-gallery-main">
                    @if($primaryPhoto)
                        @php
                            $mainImageUrl = $primaryPhoto->path 
                                ? asset('storage/' . $primaryPhoto->path) 
                                : ($primaryPhoto->url ?? asset('images/placeholder.jpg'));
                        @endphp
                        <img id="mainGalleryImage" 
                             src="{{ $mainImageUrl }}" 
                             alt="{{ $apartamento->titulo }}">
                        @if($photos->count() > 1)
                            <button class="booking-gallery-view-all" onclick="showAllPhotos()">
                                <i class="fas fa-images"></i> {{ __('apartment_detail.view_all_photos', ['count' => $photos->count()]) }}
                            </button>
                        @endif
                    @endif
                </div>
                @if($photos->count() > 1)
                    <div class="booking-gallery-thumbs">
                        @foreach($photos->skip(1)->take(2) as $index => $photo)
                            @php
                                $thumbUrl = $photo->path 
                                    ? asset('storage/' . $photo->path) 
                                    : ($photo->url ?? asset('images/placeholder.jpg'));
                            @endphp
                            <div class="booking-gallery-thumb" 
                                 onclick="changeGalleryImage('{{ $thumbUrl }}', {{ $photo->id }})">
                                <img src="{{ $thumbUrl }}" alt="Thumbnail {{ $index + 1 }}">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            @if($photos->count() > 3)
                <!-- Fila de miniaturas adicionales (estilo Booking.com) -->
                <div class="booking-gallery-row">
                    @foreach($photos->skip(3)->take(5) as $index => $photo)
                        @php
                            $thumbUrl = $photo->path 
                                ? asset('storage/' . $photo->path) 
                                : ($photo->url ?? asset('images/placeholder.jpg'));
                        @endphp
                        <div class="booking-gallery-row-item" 
                             onclick="changeGalleryImage('{{ $thumbUrl }}', {{ $photo->id }})">
                            <img src="{{ $thumbUrl }}" alt="Thumbnail {{ $index + 4 }}">
                        </div>
                    @endforeach
                    @if($photos->count() > 8)
                        <div class="booking-gallery-row-item booking-gallery-more" onclick="showAllPhotos()">
                            <div class="booking-gallery-more-count" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; border-radius: 6px;">
                                +{{ $photos->count() - 8 }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endif
            </div>
        </div>
        
        <!-- TÍTULO Y RATING (DESPUÉS DE LA GALERÍA) -->
        <div class="booking-title-row" style="margin-top: 24px;">
            <div class="booking-title-section">
                <h1 style="font-size: 32px; font-weight: 700; color: #333; margin-bottom: 8px;">
                    {{ $apartamento->titulo ?? $apartamento->nombre }}
                </h1>
                <div style="color: #666; margin-bottom: 8px; font-size: 16px;">
                    <i class="fas fa-map-marker-alt"></i>
                    @if($apartamento->address)
                        {{ $apartamento->address }}
                    @endif
                    @if($apartamento->address && $apartamento->zip_code), @endif
                    @if($apartamento->zip_code){{ $apartamento->zip_code }}@endif
                    @if(($apartamento->address || $apartamento->zip_code) && $apartamento->city), @endif
                    @if($apartamento->city){{ $apartamento->city }}@endif
                    @if($apartamento->country), {{ $apartamento->country }}@endif
                    <a href="#" style="color: #0071C2; margin-left: 8px;">
                        <i class="fas fa-map"></i> {{ __('apartment_detail.view_on_map') }}
                    </a>
                    @if($apartamento->location_rating && $apartamento->location_rating >= 9)
                        <span style="background: #0D7377; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">
                            {{ __('apartment_detail.excellent_location') }}
                        </span>
                    @endif
                </div>
                @if($apartamento->rating_score)
                    <div class="booking-rating">
                        <div class="booking-rating-badge">
                            {{ number_format($apartamento->rating_score, 1) }}
                        </div>
                        <div class="booking-rating-text">
                            <strong>{{ __('apartment_detail.very_good') }}</strong>
                            @if($apartamento->reviews_count)
                                · {{ $apartamento->reviews_count }} {{ __('apartment_detail.reviews') }}
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div>
                <a href="#reservar" class="booking-reserve-btn-top">
                    <i class="fas fa-calendar-check"></i> {{ __('apartment_detail.book_now') }}
                </a>
            </div>
        </div>
        
        <!-- CONTENIDO PRINCIPAL -->
        <div class="booking-main-content">
            <!-- COLUMNA IZQUIERDA -->
            <div>
                <!-- Servicios Populares -->
                @php
                    $serviciosPopulares = $apartamento->serviciosPopulares;
                @endphp
                
                @if($serviciosPopulares->isNotEmpty())
                    <div class="property-details-section" style="margin-top: 0;">
                        <h2 style="font-size: 24px; margin-bottom: 16px;">{{ __('apartment_detail.most_popular_services') }}</h2>
                        <div class="booking-popular-services">
                            @foreach($serviciosPopulares as $servicio)
                                <div class="booking-service-icon">
                                    @if($servicio->icono)
                                        {!! $servicio->icono !!}
                                    @else
                                        <i class="fas fa-check"></i>
                                    @endif
                                    <span>{{ isset($servicio->nombre_translated) ? $servicio->nombre_translated : $servicio->nombre }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Sección Disponibilidad -->
                <div class="booking-availability-section">
                    <h2 class="booking-availability-title">{{ __('apartment_detail.availability') }}</h2>
                    
                    @if(!$fechaEntrada || !$fechaSalida)
                        <div class="booking-availability-info">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ __('apartment_detail.enter_dates_for_availability') }}</span>
                        </div>
                    @elseif(isset($disponible) && !$disponible)
                        <div class="booking-availability-info" style="background: #F8D7DA; border-color: #DC3545; color: #721C24;">
                            <i class="fas fa-times-circle"></i>
                            <span>{{ __('apartment_detail.no_availability_selected_dates') }}</span>
                        </div>
                    @elseif(isset($disponible) && $disponible && !$precioTotal)
                        <div class="booking-availability-info" style="background: #FFF3CD; border-color: #FFC107; color: #856404;">
                            <i class="fas fa-info-circle"></i>
                            <span>{{ __('apartment_detail.available_contact_price') }}</span>
                        </div>
                    @elseif(isset($disponible) && $disponible && $precioTotal)
                        <div class="booking-availability-info" style="background: #D1E7DD; border-color: #28A745; color: #155724;">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ __('apartment_detail.available_for_dates') }}</span>
                        </div>
                    @endif
                    
                    <form action="{{ route('web.reservas.show', $apartamento->id) }}" method="GET" class="booking-availability-form">
                        <div class="booking-availability-date-group">
                            <label for="fecha_entrada">{{ __('apartment_detail.checkin') }}</label>
                            <input type="text" 
                                   id="fecha_entrada" 
                                   name="fecha_entrada" 
                                   class="booking-availability-date-input"
                                   value="{{ $fechaEntrada ?? '' }}"
                                   placeholder="dd/mm/aaaa"
                                   required>
                        </div>
                        
                        <div class="booking-availability-date-group">
                            <label for="fecha_salida">{{ __('apartment_detail.checkout') }}</label>
                            <input type="text" 
                                   id="fecha_salida" 
                                   name="fecha_salida" 
                                   class="booking-availability-date-input"
                                   value="{{ $fechaSalida ?? '' }}"
                                   placeholder="dd/mm/aaaa"
                                   required>
                        </div>
                        
                        <div style="display: flex; align-items: flex-end;">
                            <div class="booking-availability-guests" style="cursor: default; background: white; border: 1px solid #E0E0E0; display: flex; align-items: center; gap: 8px; padding: 12px;">
                                <select name="adultos" style="border: none; outline: none; background: transparent; font-size: 15px; cursor: pointer;">
                                    @for($i = 1; $i <= 8; $i++)
                                        <option value="{{ $i }}" {{ ($adultos ?? 2) == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? __('reservation.adult') : __('reservation.adults') }}</option>
                                    @endfor
                                </select>
                                <span>·</span>
                                <select name="ninos" style="border: none; outline: none; background: transparent; font-size: 15px; cursor: pointer;">
                                    @for($i = 0; $i <= 6; $i++)
                                        <option value="{{ $i }}" {{ ($ninos ?? 0) == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? __('reservation.child') : __('reservation.children') }}</option>
                                    @endfor
                                </select>
                                <span>· 1 {{ __('apartment_detail.room') }}</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="booking-availability-search-btn">
                            <i class="fas fa-search me-2"></i>{{ __('apartment_detail.search') }}
                        </button>
                    </form>
                    
                    <!-- Tipo de Alojamiento -->
                    <div class="booking-accommodation-type">
                        {{ __('apartment_detail.accommodation_type') }}
                    </div>
                    <div class="booking-accommodation-details">
                        <div class="booking-accommodation-row">
                            <div class="booking-accommodation-info">
                                <i class="fas fa-chevron-right" style="color: #0071C2;"></i>
                                <div>
                                    <h4>{{ __('apartment_detail.apartment_with_bedrooms', ['count' => $apartamento->bedrooms ?? 1]) }}</h4>
                                    <div class="booking-accommodation-beds">
                                        @if($apartamento->bedrooms && $apartamento->bedrooms > 0)
                                            @for($i = 0; $i < $apartamento->bedrooms; $i++)
                                                @if($i < $apartamento->bedrooms - 1)
                                                    <i class="fas fa-bed" style="color: #666; font-size: 14px;"></i>
                                                @endif
                                            @endfor
                                            <span>{{ $apartamento->bedrooms }} {{ $apartamento->bedrooms == 1 ? __('apartment_detail.bed') : __('apartment_detail.beds') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="booking-accommodation-people">
                                <i class="fas fa-user" style="color: #666;"></i>
                                <span>x{{ $apartamento->max_guests ?? 4 }}</span>
                            </div>
                            @if($fechaEntrada && $fechaSalida && isset($disponible))
                                <button class="booking-show-prices-btn" onclick="scrollToReservation()">
                                    {{ $precioTotal ? __('apartment_detail.show_prices') : __('apartment_detail.view_availability') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    @if($fechaEntrada && $fechaSalida)
                        <button class="booking-view-availability-btn" onclick="scrollToReservation()">
                            {{ $precioTotal && isset($disponible) && $disponible ? __('apartment_detail.view_prices_and_book') : __('apartment_detail.view_availability') }}
                        </button>
                    @else
                        <button class="booking-view-availability-btn" onclick="document.getElementById('reservar').scrollIntoView({behavior: 'smooth', block: 'start'})">
                            {{ __('apartment_detail.view_availability') }}
                        </button>
                    @endif
                </div>
                
                <!-- Descripción -->
                @if($apartamento->description)
                    <div class="property-details-section" style="margin-top: 40px;">
                        <h2 style="font-size: 24px; margin-bottom: 20px;">{{ __('apartment_detail.about_apartment') }}</h2>
                        <div class="property-description" style="margin-top: 20px;">
                            {!! isset($apartamento->description_translated) ? $apartamento->description_translated : $apartamento->description !!}
                        </div>
                    </div>
                @endif
                
                <!-- Rating por Categorías -->
                @if($apartamento->cleanliness_rating || $apartamento->location_rating || $apartamento->value_rating || $apartamento->service_rating)
                    <div class="property-details-section" style="margin-top: 40px;">
                        <h2 style="font-size: 24px; margin-bottom: 16px;">
                            {{ __('apartment_detail.customer_reviews') }}
                        </h2>
                        @if($apartamento->rating_score)
                            <div style="margin-bottom: 24px;">
                                <div style="font-size: 32px; font-weight: 700; color: #003580;">
                                    {{ number_format($apartamento->rating_score, 1) }}
                                </div>
                                <div style="color: #666;">
                                    @if($apartamento->reviews_count)
                                        {{ __('apartment_detail.based_on_reviews', ['count' => $apartamento->reviews_count]) }}
                                    @endif
                                </div>
                            </div>
                        @endif
                        
                        @if($apartamento->cleanliness_rating)
                            <div class="booking-category-rating">
                                <span><strong>{{ __('apartment_detail.cleanliness') }}</strong></span>
                                <div class="booking-rating-bar">
                                    <div class="booking-rating-bar-fill" style="width: {{ ($apartamento->cleanliness_rating / 10) * 100 }}%"></div>
                                </div>
                                <span><strong>{{ number_format($apartamento->cleanliness_rating, 1) }}</strong></span>
                            </div>
                        @endif
                        
                        @if($apartamento->location_rating)
                            <div class="booking-category-rating">
                                <span><strong>{{ __('apartment_detail.location') }}</strong></span>
                                <div class="booking-rating-bar">
                                    <div class="booking-rating-bar-fill" style="width: {{ ($apartamento->location_rating / 10) * 100 }}%"></div>
                                </div>
                                <span><strong>{{ number_format($apartamento->location_rating, 1) }}</strong></span>
                            </div>
                        @endif
                        
                        @if($apartamento->value_rating)
                            <div class="booking-category-rating">
                                <span><strong>{{ __('apartment_detail.value_for_money') }}</strong></span>
                                <div class="booking-rating-bar">
                                    <div class="booking-rating-bar-fill" style="width: {{ ($apartamento->value_rating / 10) * 100 }}%"></div>
                                </div>
                                <span><strong>{{ number_format($apartamento->value_rating, 1) }}</strong></span>
                            </div>
                        @endif
                        
                        @if($apartamento->service_rating)
                            <div class="booking-category-rating">
                                <span><strong>{{ __('apartment_detail.staff') }}</strong></span>
                                <div class="booking-rating-bar">
                                    <div class="booking-rating-bar-fill" style="width: {{ ($apartamento->service_rating / 10) * 100 }}%"></div>
                                </div>
                                <span><strong>{{ number_format($apartamento->service_rating, 1) }}</strong></span>
                            </div>
                        @endif
                    </div>
                @endif
                
                <!-- Todos los Servicios (Agrupados por Categoría) -->
                @php
                    $todosServicios = $apartamento->servicios;
                    $serviciosPorCategoria = $todosServicios->groupBy('categoria');
                @endphp
                
                @if($todosServicios->isNotEmpty())
                    <div class="property-details-section" style="margin-top: 40px;">
                        <h2 style="font-size: 24px; margin-bottom: 20px;">
                            {{ __('apartment_detail.services_of', ['name' => $apartamento->titulo ?? $apartamento->nombre]) }}
                        </h2>
                        
                        @foreach($serviciosPorCategoria as $categoria => $serviciosCat)
                            @if($categoria !== 'Servicios más populares')
                                <div class="booking-services-category">
                                    <h3 class="booking-services-category-title">
                                        {{ $categoria ?: __('apartment_detail.other_services') }}
                                    </h3>
                                    <div class="booking-services-grid">
                                        @foreach($serviciosCat as $servicio)
                                            <div class="booking-service-item">
                                                <div class="booking-service-icon-box">
                                                    @if($servicio->icono)
                                                        {!! $servicio->icono !!}
                                                    @else
                                                        <i class="fas fa-check"></i>
                                                    @endif
                                                </div>
                                                <div class="booking-service-content">
                                                    <span class="booking-service-name">{{ isset($servicio->nombre_translated) ? $servicio->nombre_translated : $servicio->nombre }}</span>
                                                    @if($servicio->descripcion)
                                                        <span class="booking-service-description">{{ isset($servicio->descripcion_translated) ? $servicio->descripcion_translated : $servicio->descripcion }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
                
                <!-- Normas de la casa (Estilo Booking.com) -->
                @php
                    $normasCasa = $apartamento->normasCasa;
                @endphp
                
                @if($normasCasa->isNotEmpty() || $apartamento->check_in_time || $apartamento->cancellation_policy)
                    <div class="property-details-section" style="margin-top: 40px;">
                        <h2 style="font-size: 24px; margin-bottom: 8px; font-weight: 700; color: #333;">
                            {{ __('apartment_detail.house_rules') }}
                        </h2>
                        <p style="font-size: 16px; color: #333; margin-bottom: 24px;">
                            {{ ($apartamento->titulo ?? $apartamento->nombre) }} {{ __('apartment_detail.accepts_special_requests') }}
                        </p>
                        
                        <div style="border-top: 1px solid #E0E0E0;">
                            <!-- Normas desde BD (NormasCasa) -->
                            @foreach($normasCasa as $norma)
                                <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0;">
                                    <div style="display: flex; align-items: flex-start; gap: 16px;">
                                        <!-- Columna izquierda: Icono + Título -->
                                        <div style="flex: 0 0 220px; font-weight: 600; color: #333; display: flex; align-items: flex-start; gap: 8px; padding-top: 2px;">
                                            @if($norma->icono)
                                                <span style="font-size: 16px; flex-shrink: 0; margin-top: 2px;">{!! $norma->icono !!}</span>
                                            @endif
                                            <span style="line-height: 1.5;">{{ isset($norma->titulo_translated) ? $norma->titulo_translated : $norma->titulo }}</span>
                                        </div>
                                        <!-- Columna derecha: Descripción -->
                                        <div style="flex: 1; color: #333; line-height: 1.6;">
                                            {!! isset($norma->descripcion_translated) ? $norma->descripcion_translated : $norma->descripcion !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            <!-- Fallback: Normas automáticas desde campos del apartamento (si no hay normas en BD) -->
                            @if($normasCasa->isEmpty())
                                <!-- Entrada -->
                                @if($apartamento->check_in_time)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">→</span>
                                            <span>{{ __('apartment_detail.checkin') }}</span>
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            @if($apartamento->check_out_time)
                                                {{ __('apartment_detail.from_time_to_time', ['from' => \Carbon\Carbon::parse($apartamento->check_in_time)->format('H:i'), 'to' => \Carbon\Carbon::parse($apartamento->check_out_time)->format('H:i')]) }}
                                            @else
                                                {{ \Carbon\Carbon::parse($apartamento->check_in_time)->format('H:i') }}
                                            @endif
                                            @if($apartamento->check_in_instructions)
                                                <div style="font-size: 14px; color: #666; margin-top: 4px;">
                                                    {!! isset($apartamento->check_in_instructions_translated) ? $apartamento->check_in_instructions_translated : $apartamento->check_in_instructions !!}
                                                </div>
                                            @else
                                                <div style="font-size: 14px; color: #666; margin-top: 4px;">
                                                    {{ __('apartment_detail.tell_accommodation_arrival_time') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Salida -->
                                @if($apartamento->check_out_time)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">→</span>
                                            <span>{{ __('apartment_detail.checkout') }}</span>
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            {{ __('apartment_detail.from_time_to_time', ['from' => '8:00', 'to' => \Carbon\Carbon::parse($apartamento->check_out_time)->format('H:i')]) }}
                                            @if($apartamento->check_out_instructions)
                                                <div style="font-size: 14px; color: #666; margin-top: 4px;">
                                                    {!! isset($apartamento->check_out_instructions_translated) ? $apartamento->check_out_instructions_translated : $apartamento->check_out_instructions !!}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Cancelación / prepago -->
                                @if($apartamento->cancellation_policy)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 20px; height: 20px; border-radius: 50%; background: #E0E0E0; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">i</span>
                                            <span>{{ __('apartment_detail.cancellation_prepayment') }}</span>
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            {{ __('apartment_detail.cancellation_conditions_vary') }} 
                                            @if($fechaEntrada && $fechaSalida)
                                                <a href="#reservar" style="color: #0071C2; text-decoration: underline;">{{ __('apartment_detail.enter_stay_dates') }}</a> 
                                            @else
                                                {{ __('apartment_detail.enter_stay_dates') }}
                                            @endif
                                            {{ __('apartment_detail.check_conditions_to_reserve') }}
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Horario límite -->
                                @if($apartamento->quiet_hours_start && $apartamento->quiet_hours_end)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 20px; height: 20px; border-radius: 50%; background: #E0E0E0; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">i</span>
                                            <span>{{ __('apartment_detail.time_limit') }}</span>
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            {{ __('apartment_detail.accommodation_closes_from_to', ['from' => \Carbon\Carbon::parse($apartamento->quiet_hours_start)->format('H:i'), 'to' => \Carbon\Carbon::parse($apartamento->quiet_hours_end)->format('H:i')]) }}
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Condiciones sobre daños -->
                                @if($apartamento->security_deposit)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 20px; height: 20px; border-radius: 50%; background: #E0E0E0; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">i</span>
                                            <span>{{ __('apartment_detail.damage_conditions') }}</span>
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            {{ __('apartment_detail.damage_deposit_warning', ['amount' => number_format($apartamento->security_deposit, 2, ',', '.')]) }}
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Camas para niños -->
                                @if($apartamento->min_age_child || $apartamento->extra_bed_available)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">⚮</span>
                                            <span>Camas para niños</span>
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            Condiciones para estancias con niños
                                            @if($apartamento->min_age_child)
                                                <div style="font-size: 14px; color: #666; margin-top: 4px;">
                                                    Edad mínima para niños: {{ $apartamento->min_age_child }} años
                                                </div>
                                            @endif
                                            @if($apartamento->extra_bed_available && $apartamento->extra_bed_price)
                                                <div style="font-size: 14px; color: #666; margin-top: 4px;">
                                                    Cama supletoria disponible: {{ number_format($apartamento->extra_bed_price, 2, ',', '.') }} € por noche
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Fumadores / No fumadores -->
                                @if($apartamento->smoking_allowed !== null)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333;">
                                            {{ $apartamento->smoking_allowed ? 'Fumadores' : 'No fumadores' }}
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            {{ $apartamento->smoking_allowed ? 'Este alojamiento permite fumar.' : 'Este alojamiento es para no fumadores.' }}
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Mascotas -->
                                @if($apartamento->pets_allowed !== null)
                                    <div style="padding: 20px 0; border-bottom: 1px solid #E0E0E0; display: flex; align-items: flex-start;">
                                        <div style="flex: 0 0 200px; font-weight: 600; color: #333;">
                                            Mascotas
                                        </div>
                                        <div style="flex: 1; color: #333;">
                                            {{ $apartamento->pets_allowed ? 'Se admiten mascotas en este alojamiento.' : 'No se admiten mascotas en este alojamiento.' }}
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
                
                <!-- Alrededores (Desde BD) -->
                @php
                    $lugaresCercanos = $apartamento->lugaresCercanos;
                    $lugaresPorCategoria = $lugaresCercanos->groupBy('categoria');
                @endphp
                
                @if($lugaresCercanos->isNotEmpty())
                    <div class="property-details-section" style="margin-top: 40px;">
                        <h2 style="font-size: 24px; margin-bottom: 16px;">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Muy buena ubicación - Ver mapa
                        </h2>
                        
                        @php
                            $categoriaLabels = [
                                'que_hay_cerca' => '¿Qué hay cerca?',
                                'restaurantes' => 'Restaurantes y cafeterías',
                                'transporte' => 'Transporte público',
                                'playas' => 'Playas en la zona',
                                'aeropuertos' => 'Aeropuertos más cercanos'
                            ];
                        @endphp
                        
                        @foreach($lugaresPorCategoria as $categoria => $lugaresCat)
                            <h3 style="font-size: 18px; margin-top: 16px; margin-bottom: 8px;">
                                {{ $categoriaLabels[$categoria] ?? ucfirst(str_replace('_', ' ', $categoria)) }}
                            </h3>
                            <ul class="booking-nearby-list">
                                @foreach($lugaresCat as $lugar)
                                    <li>
                                        <span>
                                            @if($lugar->tipo)
                                                <strong>{{ $lugar->tipo }}</strong> • 
                                            @endif
                                            {{ $lugar->nombre }}
                                        </span>
                                        @if($lugar->distancia)
                                            <strong>
                                                {{ number_format($lugar->distancia, 2, ',', '.') }} {{ $lugar->unidad_distancia }}
                                            </strong>
                                        @else
                                            <strong>Muy cerca</strong>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                @endif
                
                <!-- Información del Host/Propietario -->
                @php
                    $hostNombre = \App\Models\Setting::get('host_nombre', 'Apartamentos Algeciras');
                    $hostIniciales = \App\Models\Setting::get('host_iniciales', 'HA');
                    // $hostDescripcion ya viene traducido del controlador
                    if (!isset($hostDescripcion)) {
                        $hostDescripcion = \App\Models\Setting::get('host_descripcion', 'Alojamientos de calidad en el corazón de Algeciras');
                    }
                    $hostIdiomas = json_decode(\App\Models\Setting::get('host_idiomas', '["Español", "Inglés"]'), true) ?: ['Español', 'Inglés'];
                    // Si el apartamento tiene idiomas propios, usar esos; si no, usar los globales
                    $idiomasAMostrar = ($apartamento->languages_spoken && is_array($apartamento->languages_spoken) && count($apartamento->languages_spoken) > 0) 
                        ? $apartamento->languages_spoken 
                        : $hostIdiomas;
                @endphp
                <div class="property-details-section" style="margin-top: 40px;">
                    <!-- Primera fila: Logo + Título -->
                    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 16px;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #0071C2; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 700; flex-shrink: 0;">
                            {{ $hostIniciales }}
                        </div>
                        <h2 style="font-size: 24px; margin: 0;">{{ __('apartment_detail.managed_by') }} {{ $hostNombre }}</h2>
                    </div>
                    
                    <!-- Segunda fila: Descripción + Idiomas -->
                    <div>
                        <div class="property-description" style="color: #666; margin: 0;">{!! $hostDescripcion !!}</div>
                        @if(count($idiomasAMostrar) > 0)
                            <div class="host-languages" style="margin-top: 12px;">
                                <strong class="host-languages-label">{{ __('apartment_detail.languages_spoken') }}:</strong>
                                <div class="host-languages-buttons" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                    @foreach($idiomasAMostrar as $lang)
                                        @php
                                            // Mapeo de nombres de idiomas a claves de traducción
                                            $langMap = [
                                                'Español' => __('apartment_detail.language_spanish'),
                                                'Inglés' => __('apartment_detail.language_english'),
                                                'Francés' => __('apartment_detail.language_french'),
                                                'Alemán' => __('apartment_detail.language_german'),
                                                'Italiano' => __('apartment_detail.language_italian'),
                                                'Portugués' => __('apartment_detail.language_portuguese'),
                                                'Spanish' => __('apartment_detail.language_spanish'),
                                                'English' => __('apartment_detail.language_english'),
                                                'French' => __('apartment_detail.language_french'),
                                                'German' => __('apartment_detail.language_german'),
                                                'Italian' => __('apartment_detail.language_italian'),
                                                'Portuguese' => __('apartment_detail.language_portuguese'),
                                            ];
                                            $langTranslated = $langMap[$lang] ?? $lang;
                                        @endphp
                                        <span class="host-language-badge" style="padding: 4px 12px; background: #F5F5F5; border-radius: 4px; font-size: 14px;">{{ $langTranslated }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Preguntas Frecuentes (Desde BD) -->
                @php
                    $faqs = $apartamento->faqs;
                @endphp
                
                @if($faqs->isNotEmpty())
                    <div class="property-details-section" style="margin-top: 40px;">
                        <h2 style="font-size: 24px; margin-bottom: 16px;">Preguntas frecuentes sobre {{ $apartamento->titulo ?? $apartamento->nombre }}</h2>
                        
                        @foreach($faqs as $faq)
                            <div class="booking-accordion-item">
                                <div class="booking-accordion-header" onclick="toggleAccordion(this)">
                                    <span>{{ $faq->pregunta }}</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="booking-accordion-body">
                                    <p>{!! nl2br(e($faq->respuesta)) !!}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <!-- COLUMNA DERECHA (Widget de Reserva) -->
            <div>
                <!-- Widget de Reserva - Estilo Booking.com -->
                <div class="booking-reservation-widget" id="reservar">
                    <div class="booking-reservation-header">
                        <i class="fas fa-calendar-check"></i>
                        <h3>{{ __('apartment_detail.book_now') }}</h3>
                    </div>
                    
                    @if($fechaEntrada && $fechaSalida)
                        <div class="booking-reservation-details">
                            <div class="booking-reservation-detail-item">
                                <span class="booking-reservation-label">{{ __('apartment_detail.checkin') }}:</span>
                                <span class="booking-reservation-value">{{ \Carbon\Carbon::parse($fechaEntrada)->format('d/m/Y') }}</span>
                            </div>
                            <div class="booking-reservation-detail-item">
                                <span class="booking-reservation-label">{{ __('apartment_detail.checkout') }}:</span>
                                <span class="booking-reservation-value">{{ \Carbon\Carbon::parse($fechaSalida)->format('d/m/Y') }}</span>
                            </div>
                            <div class="booking-reservation-detail-item">
                                <span class="booking-reservation-label">{{ __('apartment_detail.guests') }}:</span>
                                <span class="booking-reservation-value">{{ $adultos ?? 2 }} {{ __('reservation.adults') }}{{ $ninos > 0 ? ', ' . $ninos . ' ' . __('reservation.children') : '' }}</span>
                            </div>
                            <div class="booking-reservation-detail-item">
                                <span class="booking-reservation-label">{{ __('apartment_detail.nights') }}:</span>
                                <span class="booking-reservation-value">{{ $noches ?? \Carbon\Carbon::parse($fechaEntrada)->diffInDays(\Carbon\Carbon::parse($fechaSalida)) }}</span>
                            </div>
                        </div>
                        
                        <div class="booking-reservation-price">
                            @if(isset($disponible) && !$disponible)
                                <div class="booking-reservation-status booking-reservation-status-error">
                                    <i class="fas fa-times-circle"></i>
                                    <div class="booking-reservation-status-title">{{ __('apartment_detail.not_available') }}</div>
                                    <div class="booking-reservation-status-text">{{ __('apartment_detail.not_available_selected_dates') }}</div>
                                </div>
                            @elseif($precioTotal)
                                <div class="booking-reservation-price-label">{{ __('apartment_detail.total_price_for_nights', ['nights' => $noches]) }}</div>
                                <div class="booking-reservation-price-amount">{{ number_format($precioTotal, 2, ',', '.') }} €</div>
                                @if($precioPorNoche)
                                    <div class="booking-reservation-price-per-night">{{ number_format($precioPorNoche, 2, ',', '.') }} € {{ __('apartment_detail.per_night') }}</div>
                                @endif
                            @elseif(isset($disponible) && $disponible && !$precioTotal)
                                <div class="booking-reservation-status booking-reservation-status-warning">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="booking-reservation-status-title">{{ __('apartment_detail.available') }}</div>
                                    <div class="booking-reservation-status-text">{{ __('apartment_detail.contact_for_price') }}</div>
                                </div>
                            @else
                                <div class="booking-reservation-status booking-reservation-status-info">
                                    <div class="booking-reservation-status-title">{{ __('apartment_detail.pending_calculation') }}</div>
                                    <div class="booking-reservation-status-text">{{ __('apartment_detail.complete_data_for_price') }}</div>
                                </div>
                            @endif
                        </div>
                        
                        @if(isset($disponible) && $disponible && $precioTotal)
                            <a href="{{ route('web.reservas.formulario', [
                                'apartamento' => $apartamento->id,
                                'fecha_entrada' => $fechaEntrada,
                                'fecha_salida' => $fechaSalida,
                                'adultos' => $adultos,
                                'ninos' => $ninos ?? 0
                            ]) }}" 
                               class="booking-reservation-btn booking-reservation-btn-active" 
                               style="text-decoration: none; display: block; text-align: center;">
                                <i class="fas fa-calendar-check"></i>
                                {{ __('apartment_detail.book_now') }}
                            </a>
                        @else
                            <button class="booking-reservation-btn booking-reservation-btn-disabled" disabled>
                                <i class="fas fa-lock"></i>
                            @if(isset($disponible) && !$disponible)
                                {{ __('apartment_detail.not_available') }}
                            @else
                                {{ __('apartment_detail.price_not_available') }}
                            @endif
                        </button>
                        @endif
                    @else
                        <div class="booking-alert booking-alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>{{ __('apartment_detail.select_dates') }}</strong>
                                <p>{{ __('apartment_detail.complete_form_for_availability') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    <a href="{{ route('web.reservas.portal', request()->only(['fecha_entrada', 'fecha_salida', 'adultos', 'ninos'])) }}" 
                       class="booking-reservation-link">
                        <i class="fas fa-search"></i> {{ __('apartment_detail.modify_dates') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
        // Flatpickr para fechas de disponibilidad
        const fechaEntradaEl = document.getElementById("fecha_entrada");
        const fechaSalidaEl = document.getElementById("fecha_salida");
        
        if (fechaEntradaEl) {
            flatpickr("#fecha_entrada", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "es",
                defaultDate: "{{ $fechaEntrada ?? '' }}",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0 && fechaSalidaEl) {
                        const fechaSalidaPicker = flatpickr("#fecha_salida");
                        if (fechaSalidaPicker) {
                            fechaSalidaPicker.set("minDate", selectedDates[0]);
                        }
                    }
                }
            });
        }
        
        if (fechaSalidaEl) {
            flatpickr("#fecha_salida", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "es",
                defaultDate: "{{ $fechaSalida ?? '' }}"
            });
        }
        
        // Flatpickr para fechas del widget (si existen)
        const checkinEl = document.getElementById("checkin");
        const checkoutEl = document.getElementById("checkout");
        
        if (checkinEl) {
            flatpickr("#checkin", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "es",
                defaultDate: "{{ $fechaEntrada ?? '' }}",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0 && checkoutEl) {
                        flatpickr("#checkout").set("minDate", selectedDates[0]);
                    }
                }
            });
        }
        
        if (checkoutEl) {
            flatpickr("#checkout", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "es",
                defaultDate: "{{ $fechaSalida ?? '' }}"
            });
        }
        
        // Cambiar imagen de galería
        function changeGalleryImage(src, photoId = null) {
            const mainImage = document.getElementById('mainGalleryImage');
            if (mainImage) {
                mainImage.src = src;
                
                // Actualizar thumbnails activos
                document.querySelectorAll('.booking-gallery-thumb, .booking-gallery-row-item').forEach(thumb => {
                    thumb.classList.remove('active');
                });
                
                if (photoId) {
                    // Buscar el thumbnail que corresponde a esta foto
                    const activeThumb = document.querySelector(`[onclick*="'${src}'"], [onclick*='${photoId}']`);
                    if (activeThumb) {
                        activeThumb.classList.add('active');
                    }
                }
            }
        }
        
        // Mostrar todas las fotos (modal o galería completa)
        function showAllPhotos() {
            // Por ahora, simplemente hacer scroll a la galería
            // TODO: Implementar modal con todas las fotos
            document.querySelector('.booking-gallery').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Accordion FAQ
        function toggleAccordion(header) {
            const item = header.closest('.booking-accordion-item');
            const isActive = item.classList.contains('active');
            
            // Cerrar todos
            document.querySelectorAll('.booking-accordion-item').forEach(el => {
                el.classList.remove('active');
            });
            
            // Abrir el clickeado si no estaba activo
            if (!isActive) {
                item.classList.add('active');
            }
        }
        
        // Toggle todos los servicios
        function toggleAllServices() {
            const list = document.getElementById('allServicesList');
            const icon = document.getElementById('servicesToggleIcon');
            if (list.style.display === 'none') {
                list.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                list.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        
        // Scroll al widget de reserva y resaltar el precio
        function scrollToReservation() {
            // Intentar encontrar el widget de reserva
            let reservaWidget = document.getElementById('reservar');
            
            if (reservaWidget) {
                // Scroll suave al widget
                reservaWidget.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start',
                    inline: 'nearest'
                });
                
                // Pequeño delay para que el scroll termine antes de aplicar el efecto
                setTimeout(function() {
                    // Resaltar el widget con un efecto visual
                    reservaWidget.style.transition = 'all 0.3s ease';
                    reservaWidget.style.boxShadow = '0 0 25px rgba(0, 113, 194, 0.5)';
                    reservaWidget.style.transform = 'scale(1.01)';
                    
                    setTimeout(function() {
                        reservaWidget.style.boxShadow = '';
                        reservaWidget.style.transform = '';
                    }, 2000);
                    
                    // Si hay un precio, resaltarlo también
                    const precioElement = document.querySelector('.reservation-price-total');
                    if (precioElement) {
                        precioElement.style.transition = 'all 0.3s ease';
                        precioElement.style.border = '2px solid #0071C2';
                        precioElement.style.background = '#E9F0FF';
                        precioElement.style.boxShadow = '0 4px 12px rgba(0, 113, 194, 0.2)';
                        
                        setTimeout(function() {
                            precioElement.style.border = '';
                            precioElement.style.background = '#F9F9F9';
                            precioElement.style.boxShadow = '';
                        }, 2000);
                    }
                }, 500);
            } else {
                // Fallback: scroll a cualquier elemento con clase booking-reservation-card
                const card = document.querySelector('.booking-reservation-card');
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    </script>
@endsection
