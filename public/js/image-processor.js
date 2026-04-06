/**
 * Image Processor - Procesamiento y optimización de imágenes capturadas
 */
class ImageProcessor {
    constructor() {
        this.maxWidth = 1920;
        this.maxHeight = 1080;
        this.quality = 0.8;
        this.format = 'image/jpeg';
    }
    
    /**
     * Procesar imagen capturada
     */
    async processImage(captureData, documentRect = null) {
        try {
            console.log('Procesando imagen...', {
                width: captureData.width,
                height: captureData.height,
                hasDocument: !!documentRect
            });
            
            let processedCanvas = captureData.canvas;
            
            // Si tenemos información del documento, recortar
            if (documentRect) {
                processedCanvas = this.cropDocument(captureData.canvas, documentRect);
            }
            
            // Aplicar correcciones
            processedCanvas = this.applyCorrections(processedCanvas);
            
            // Optimizar para envío
            const optimizedImage = this.optimizeForUpload(processedCanvas);
            
            console.log('Imagen procesada exitosamente');
            
            return {
                success: true,
                imageData: optimizedImage.imageData,
                width: optimizedImage.width,
                height: optimizedImage.height,
                size: optimizedImage.size,
                originalSize: captureData.imageData.length
            };
            
        } catch (error) {
            console.error('Error procesando imagen:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Recortar documento de la imagen
     */
    cropDocument(canvas, documentRect) {
        const { x, y, width, height } = documentRect;
        
        // Crear nuevo canvas para el recorte
        const croppedCanvas = document.createElement('canvas');
        const ctx = croppedCanvas.getContext('2d');
        
        // Configurar dimensiones
        croppedCanvas.width = width;
        croppedCanvas.height = height;
        
        // Dibujar la región recortada
        ctx.drawImage(
            canvas,
            x, y, width, height,
            0, 0, width, height
        );
        
        console.log(`Documento recortado: ${width}x${height}`);
        
        return croppedCanvas;
    }
    
    /**
     * Aplicar correcciones a la imagen
     */
    applyCorrections(canvas) {
        const correctedCanvas = document.createElement('canvas');
        const ctx = correctedCanvas.getContext('2d');
        
        correctedCanvas.width = canvas.width;
        correctedCanvas.height = canvas.height;
        
        // Obtener datos de imagen
        const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
        
        // Aplicar correcciones
        const correctedData = this.correctImage(imageData);
        
        // Dibujar imagen corregida
        ctx.putImageData(correctedData, 0, 0);
        
        return correctedCanvas;
    }
    
    /**
     * Corregir imagen (brillo, contraste, nitidez)
     */
    correctImage(imageData) {
        const data = imageData.data;
        const correctedData = new ImageData(
            new Uint8ClampedArray(data),
            imageData.width,
            imageData.height
        );
        
        // Calcular estadísticas de la imagen
        const stats = this.calculateImageStats(data);
        
        // Aplicar correcciones
        for (let i = 0; i < data.length; i += 4) {
            let r = data[i];
            let g = data[i + 1];
            let b = data[i + 2];
            
            // Corrección de brillo
            const brightnessAdjustment = this.calculateBrightnessAdjustment(stats);
            r = this.clamp(r + brightnessAdjustment);
            g = this.clamp(g + brightnessAdjustment);
            b = this.clamp(b + brightnessAdjustment);
            
            // Corrección de contraste
            const contrastAdjustment = this.calculateContrastAdjustment(stats);
            r = this.clamp((r - 128) * contrastAdjustment + 128);
            g = this.clamp((g - 128) * contrastAdjustment + 128);
            b = this.clamp((b - 128) * contrastAdjustment + 128);
            
            // Aplicar correcciones
            correctedData.data[i] = r;
            correctedData.data[i + 1] = g;
            correctedData.data[i + 2] = b;
            correctedData.data[i + 3] = data[i + 3]; // Alpha sin cambios
        }
        
        return correctedData;
    }
    
    /**
     * Calcular estadísticas de la imagen
     */
    calculateImageStats(data) {
        let sum = 0;
        let min = 255;
        let max = 0;
        let count = 0;
        
        for (let i = 0; i < data.length; i += 4) {
            const gray = (data[i] + data[i + 1] + data[i + 2]) / 3;
            sum += gray;
            min = Math.min(min, gray);
            max = Math.max(max, gray);
            count++;
        }
        
        return {
            average: sum / count,
            min: min,
            max: max,
            contrast: max - min
        };
    }
    
    /**
     * Calcular ajuste de brillo
     */
    calculateBrightnessAdjustment(stats) {
        const targetBrightness = 128;
        const adjustment = targetBrightness - stats.average;
        return Math.max(-50, Math.min(50, adjustment * 0.5));
    }
    
    /**
     * Calcular ajuste de contraste
     */
    calculateContrastAdjustment(stats) {
        const targetContrast = 100;
        const currentContrast = stats.contrast;
        
        if (currentContrast < 50) {
            return 1.5; // Aumentar contraste
        } else if (currentContrast > 150) {
            return 0.8; // Reducir contraste
        }
        
        return 1.0; // Sin cambios
    }
    
    /**
     * Limitar valor entre 0 y 255
     */
    clamp(value) {
        return Math.max(0, Math.min(255, Math.round(value)));
    }
    
    /**
     * Optimizar imagen para envío
     */
    optimizeForUpload(canvas) {
        // Redimensionar si es necesario
        const resizedCanvas = this.resizeIfNeeded(canvas);
        
        // Convertir a formato optimizado
        const imageData = resizedCanvas.toDataURL(this.format, this.quality);
        
        return {
            imageData: imageData,
            width: resizedCanvas.width,
            height: resizedCanvas.height,
            size: imageData.length,
            format: this.format,
            quality: this.quality
        };
    }
    
    /**
     * Redimensionar si es necesario
     */
    resizeIfNeeded(canvas) {
        if (canvas.width <= this.maxWidth && canvas.height <= this.maxHeight) {
            return canvas;
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        const ratio = Math.min(
            this.maxWidth / canvas.width,
            this.maxHeight / canvas.height
        );
        
        const newWidth = Math.round(canvas.width * ratio);
        const newHeight = Math.round(canvas.height * ratio);
        
        // Crear nuevo canvas redimensionado
        const resizedCanvas = document.createElement('canvas');
        const ctx = resizedCanvas.getContext('2d');
        
        resizedCanvas.width = newWidth;
        resizedCanvas.height = newHeight;
        
        // Dibujar imagen redimensionada
        ctx.drawImage(canvas, 0, 0, newWidth, newHeight);
        
        console.log(`Imagen redimensionada: ${canvas.width}x${canvas.height} -> ${newWidth}x${newHeight}`);
        
        return resizedCanvas;
    }
    
    /**
     * Validar calidad de imagen
     */
    validateImageQuality(canvas) {
        const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
        const stats = this.calculateImageStats(imageData.data);
        
        const validation = {
            brightness: this.validateBrightness(stats.average),
            contrast: this.validateContrast(stats.contrast),
            resolution: this.validateResolution(canvas.width, canvas.height),
            overall: false
        };
        
        validation.overall = validation.brightness && validation.contrast && validation.resolution;
        
        return validation;
    }
    
    /**
     * Validar brillo
     */
    validateBrightness(average) {
        return average >= 80 && average <= 200;
    }
    
    /**
     * Validar contraste
     */
    validateContrast(contrast) {
        return contrast >= 50;
    }
    
    /**
     * Validar resolución
     */
    validateResolution(width, height) {
        return width >= 400 && height >= 300;
    }
    
    /**
     * Crear thumbnail de la imagen
     */
    createThumbnail(canvas, maxSize = 200) {
        const thumbnailCanvas = document.createElement('canvas');
        const ctx = thumbnailCanvas.getContext('2d');
        
        // Calcular dimensiones del thumbnail
        const ratio = Math.min(maxSize / canvas.width, maxSize / canvas.height);
        const width = Math.round(canvas.width * ratio);
        const height = Math.round(canvas.height * ratio);
        
        thumbnailCanvas.width = width;
        thumbnailCanvas.height = height;
        
        // Dibujar thumbnail
        ctx.drawImage(canvas, 0, 0, width, height);
        
        return thumbnailCanvas.toDataURL('image/jpeg', 0.7);
    }
    
    /**
     * Obtener información de la imagen
     */
    getImageInfo(canvas) {
        return {
            width: canvas.width,
            height: canvas.height,
            aspectRatio: canvas.width / canvas.height,
            area: canvas.width * canvas.height,
            format: this.format,
            quality: this.quality
        };
    }
    
    /**
     * Configurar parámetros de procesamiento
     */
    configure(config) {
        if (config.maxWidth) this.maxWidth = config.maxWidth;
        if (config.maxHeight) this.maxHeight = config.maxHeight;
        if (config.quality) this.quality = config.quality;
        if (config.format) this.format = config.format;
        
        console.log('Configuración de procesamiento actualizada:', {
            maxWidth: this.maxWidth,
            maxHeight: this.maxHeight,
            quality: this.quality,
            format: this.format
        });
    }
    
    /**
     * Obtener configuración actual
     */
    getConfiguration() {
        return {
            maxWidth: this.maxWidth,
            maxHeight: this.maxHeight,
            quality: this.quality,
            format: this.format
        };
    }
    
    /**
     * Limpiar recursos
     */
    destroy() {
        console.log('ImageProcessor destruido');
    }
}

// Exportar para uso global
window.ImageProcessor = ImageProcessor;



