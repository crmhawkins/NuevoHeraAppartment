/**
 * Document Detector - Detección automática de documentos en la imagen
 */
class DocumentDetector {
    constructor() {
        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.isDetecting = false;
        this.lastDetectionTime = 0;
        this.lightingAnalysis = null; // Para almacenar análisis de iluminación
        this.detectionInterval = 100; // ms entre detecciones
        this.minConfidence = 60; // Confianza mínima para considerar válido
        
        // Configuración para DNI español
        this.dniConfig = {
            aspectRatio: 1.585, // 85.6mm x 53.98mm
            aspectRatioTolerance: 0.1,
            minArea: 0.1, // 10% del frame
            maxArea: 0.8, // 80% del frame
            minWidth: 200,
            minHeight: 120
        };
    }
    
    /**
     * Detectar documento en el video
     */
    async detectDocument(videoElement) {
        const now = Date.now();
        
        // Throttling para evitar sobrecarga
        if (now - this.lastDetectionTime < this.detectionInterval) {
            return null;
        }
        
        if (this.isDetecting) {
            return null;
        }
        
        this.lastDetectionTime = now;
        this.isDetecting = true;
        
        try {
            // Configurar canvas
            this.canvas.width = videoElement.videoWidth;
            this.canvas.height = videoElement.videoHeight;
            
            // Dibujar frame actual
            this.ctx.drawImage(videoElement, 0, 0, this.canvas.width, this.canvas.height);
            
            // Obtener datos de imagen
            const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
            
            // Detectar bordes
            const edges = this.detectEdges(imageData);
            
            // Encontrar contornos
            const contours = this.findContours(edges);
            
            // Buscar rectángulos
            const rectangles = this.findRectangles(contours);
            
            // Validar documentos
            const documents = this.validateDocuments(rectangles);
            
            // Retornar el mejor resultado
            const bestDocument = this.getBestDocument(documents);
            
            return bestDocument;
            
        } catch (error) {
            console.error('Error detectando documento:', error);
            return null;
        } finally {
            this.isDetecting = false;
        }
    }
    
    /**
     * Detectar bordes usando algoritmo simplificado
     */
    detectEdges(imageData) {
        const data = imageData.data;
        const width = imageData.width;
        const height = imageData.height;
        const edges = new Uint8Array(width * height);
        
        let totalBrightness = 0;
        let pixelCount = 0;
        
        // Convertir a escala de grises y aplicar filtro de bordes
        for (let y = 1; y < height - 1; y++) {
            for (let x = 1; x < width - 1; x++) {
                const idx = (y * width + x) * 4;
                
                // Obtener valores RGB
                const r = data[idx];
                const g = data[idx + 1];
                const b = data[idx + 2];
                
                // Convertir a escala de grises
                const gray = Math.round(0.299 * r + 0.587 * g + 0.114 * b);
                
                // Acumular brillo para análisis de iluminación
                totalBrightness += gray;
                pixelCount++;
                
                // Aplicar filtro Sobel simplificado
                const gx = this.getSobelX(data, x, y, width);
                const gy = this.getSobelY(data, x, y, width);
                const magnitude = Math.sqrt(gx * gx + gy * gy);
                
                edges[y * width + x] = Math.min(255, magnitude);
            }
        }
        
        // Calcular brillo promedio
        const averageBrightness = totalBrightness / pixelCount;
        
        // Guardar análisis de iluminación REAL
        this.lightingAnalysis = {
            averageBrightness: averageBrightness,
            isGoodLighting: averageBrightness > 80 && averageBrightness < 200, // Rango óptimo
            isTooDark: averageBrightness < 60,
            isTooBright: averageBrightness > 220,
            level: averageBrightness < 60 ? 'Muy oscuro' : 
                   averageBrightness < 80 ? 'Oscuro' :
                   averageBrightness < 200 ? 'Buena' :
                   averageBrightness < 220 ? 'Muy brillante' : 'Demasiado brillante'
        };
        
        return edges;
    }
    
    /**
     * Obtener componente X del filtro Sobel
     */
    getSobelX(data, x, y, width) {
        const idx = (y * width + x) * 4;
        const idxL = (y * width + (x - 1)) * 4;
        const idxR = (y * width + (x + 1)) * 4;
        
        const grayL = 0.299 * data[idxL] + 0.587 * data[idxL + 1] + 0.114 * data[idxL + 2];
        const grayR = 0.299 * data[idxR] + 0.587 * data[idxR + 1] + 0.114 * data[idxR + 2];
        
        return grayR - grayL;
    }
    
    /**
     * Obtener componente Y del filtro Sobel
     */
    getSobelY(data, x, y, width) {
        const idx = (y * width + x) * 4;
        const idxU = ((y - 1) * width + x) * 4;
        const idxD = ((y + 1) * width + x) * 4;
        
        const grayU = 0.299 * data[idxU] + 0.587 * data[idxU + 1] + 0.114 * data[idxU + 2];
        const grayD = 0.299 * data[idxD] + 0.587 * data[idxD + 1] + 0.114 * data[idxD + 2];
        
        return grayD - grayU;
    }
    
    /**
     * Encontrar contornos en la imagen de bordes
     */
    findContours(edges) {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const threshold = 50; // Umbral para considerar un borde
        const visited = new Array(width * height).fill(false);
        const contours = [];
        
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const idx = y * width + x;
                
                if (!visited[idx] && edges[idx] > threshold) {
                    const contour = this.traceContour(edges, x, y, width, height, visited, threshold);
                    
                    if (contour.length > 50) { // Filtrar contornos muy pequeños
                        contours.push(contour);
                    }
                }
            }
        }
        
        return contours;
    }
    
    /**
     * Rastrear un contorno específico
     */
    traceContour(edges, startX, startY, width, height, visited, threshold) {
        const contour = [];
        const stack = [{x: startX, y: startY}];
        
        while (stack.length > 0) {
            const {x, y} = stack.pop();
            const idx = y * width + x;
            
            if (x < 0 || x >= width || y < 0 || y >= height || visited[idx]) {
                continue;
            }
            
            if (edges[idx] > threshold) {
                visited[idx] = true;
                contour.push({x, y});
                
                // Agregar vecinos a la pila
                for (let dy = -1; dy <= 1; dy++) {
                    for (let dx = -1; dx <= 1; dx++) {
                        if (dx === 0 && dy === 0) continue;
                        stack.push({x: x + dx, y: y + dy});
                    }
                }
            }
        }
        
        return contour;
    }
    
    /**
     * Encontrar rectángulos en los contornos
     */
    findRectangles(contours) {
        const rectangles = [];
        
        for (const contour of contours) {
            if (contour.length < 4) continue;
            
            // Aproximar el contorno a un polígono
            const approx = this.approximatePolygon(contour);
            
            if (approx.length === 4) {
                const rect = this.contourToRectangle(approx);
                if (rect) {
                    rectangles.push(rect);
                }
            }
        }
        
        return rectangles;
    }
    
    /**
     * Aproximar contorno a polígono
     */
    approximatePolygon(contour) {
        if (contour.length < 4) return contour;
        
        // Algoritmo simplificado de aproximación
        const epsilon = 0.02 * this.getContourPerimeter(contour);
        const approx = [];
        
        // Encontrar puntos extremos
        let minX = contour[0].x, maxX = contour[0].x;
        let minY = contour[0].y, maxY = contour[0].y;
        
        for (const point of contour) {
            minX = Math.min(minX, point.x);
            maxX = Math.max(maxX, point.x);
            minY = Math.min(minY, point.y);
            maxY = Math.max(maxY, point.y);
        }
        
        // Crear rectángulo aproximado
        approx.push({x: minX, y: minY});
        approx.push({x: maxX, y: minY});
        approx.push({x: maxX, y: maxY});
        approx.push({x: minX, y: maxY});
        
        return approx;
    }
    
    /**
     * Calcular perímetro del contorno
     */
    getContourPerimeter(contour) {
        let perimeter = 0;
        
        for (let i = 0; i < contour.length; i++) {
            const current = contour[i];
            const next = contour[(i + 1) % contour.length];
            
            const dx = next.x - current.x;
            const dy = next.y - current.y;
            perimeter += Math.sqrt(dx * dx + dy * dy);
        }
        
        return perimeter;
    }
    
    /**
     * Convertir contorno a rectángulo
     */
    contourToRectangle(contour) {
        if (contour.length !== 4) return null;
        
        // Calcular bounding box
        let minX = contour[0].x, maxX = contour[0].x;
        let minY = contour[0].y, maxY = contour[0].y;
        
        for (const point of contour) {
            minX = Math.min(minX, point.x);
            maxX = Math.max(maxX, point.x);
            minY = Math.min(minY, point.y);
            maxY = Math.max(maxY, point.y);
        }
        
        return {
            x: minX,
            y: minY,
            width: maxX - minX,
            height: maxY - minY,
            contour: contour
        };
    }
    
    /**
     * Validar si los rectángulos son documentos válidos
     */
    validateDocuments(rectangles) {
        const documents = [];
        
        for (const rect of rectangles) {
            const validation = this.validateDocument(rect);
            if (validation.isValid) {
                documents.push({
                    rectangle: rect,
                    confidence: validation.confidence,
                    position: validation.position,
                    quality: validation.quality
                });
            }
        }
        
        return documents;
    }
    
    /**
     * Validar un documento específico
     */
    validateDocument(rectangle) {
        const {x, y, width, height} = rectangle;
        
        // Verificar tamaño mínimo
        if (width < this.dniConfig.minWidth || height < this.dniConfig.minHeight) {
            return { isValid: false, reason: 'Tamaño insuficiente' };
        }
        
        // Calcular proporción de aspecto
        const aspectRatio = width / height;
        const aspectRatioDiff = Math.abs(aspectRatio - this.dniConfig.aspectRatio);
        
        // Verificar proporción de aspecto
        if (aspectRatioDiff > this.dniConfig.aspectRatioTolerance) {
            return { isValid: false, reason: 'Proporción incorrecta' };
        }
        
        // Calcular área relativa
        const frameArea = this.canvas.width * this.canvas.height;
        const rectArea = width * height;
        const areaRatio = rectArea / frameArea;
        
        // Verificar área
        if (areaRatio < this.dniConfig.minArea || areaRatio > this.dniConfig.maxArea) {
            return { isValid: false, reason: 'Área incorrecta' };
        }
        
        // Calcular confianza
        const confidence = this.calculateConfidence(rectangle, aspectRatio, areaRatio);
        
        // Verificar posición
        const position = this.checkPosition(rectangle);
        
        // Verificar calidad
        const quality = this.checkQuality(rectangle);
        
        return {
            isValid: confidence >= this.minConfidence,
            confidence: confidence,
            position: position,
            quality: quality,
            aspectRatio: aspectRatio,
            areaRatio: areaRatio
        };
    }
    
    /**
     * Calcular confianza del documento
     */
    calculateConfidence(rectangle, aspectRatio, areaRatio) {
        let confidence = 0;
        
        // Proporción de aspecto (40% del score)
        const aspectRatioDiff = Math.abs(aspectRatio - this.dniConfig.aspectRatio);
        const aspectScore = Math.max(0, 40 - (aspectRatioDiff * 200));
        confidence += aspectScore;
        
        // Área (30% del score)
        const areaScore = this.calculateAreaScore(areaRatio);
        confidence += areaScore;
        
        // Posición (20% del score)
        const positionScore = this.calculatePositionScore(rectangle);
        confidence += positionScore;
        
        // Calidad (10% del score)
        const qualityScore = this.calculateQualityScore(rectangle);
        confidence += qualityScore;
        
        return Math.min(100, Math.max(0, confidence));
    }
    
    /**
     * Calcular score de área
     */
    calculateAreaScore(areaRatio) {
        const idealArea = 0.4; // 40% del frame
        const diff = Math.abs(areaRatio - idealArea);
        return Math.max(0, 30 - (diff * 100));
    }
    
    /**
     * Calcular score de posición
     */
    calculatePositionScore(rectangle) {
        const centerX = rectangle.x + rectangle.width / 2;
        const centerY = rectangle.y + rectangle.height / 2;
        const frameCenterX = this.canvas.width / 2;
        const frameCenterY = this.canvas.height / 2;
        
        const distance = Math.sqrt(
            Math.pow(centerX - frameCenterX, 2) + 
            Math.pow(centerY - frameCenterY, 2)
        );
        
        const maxDistance = Math.sqrt(
            Math.pow(frameCenterX, 2) + Math.pow(frameCenterY, 2)
        );
        
        const positionRatio = distance / maxDistance;
        return Math.max(0, 20 - (positionRatio * 20));
    }
    
    /**
     * Calcular score de calidad
     */
    calculateQualityScore(rectangle) {
        // Simulación de verificación de calidad
        // En una implementación real, aquí se analizaría la nitidez, contraste, etc.
        return 10; // Score base
    }
    
    /**
     * Verificar posición del documento
     */
    checkPosition(rectangle) {
        const centerX = rectangle.x + rectangle.width / 2;
        const centerY = rectangle.y + rectangle.height / 2;
        const frameCenterX = this.canvas.width / 2;
        const frameCenterY = this.canvas.height / 2;
        
        const tolerance = 50; // píxeles
        
        return {
            centered: Math.abs(centerX - frameCenterX) < tolerance && 
                     Math.abs(centerY - frameCenterY) < tolerance,
            offsetX: centerX - frameCenterX,
            offsetY: centerY - frameCenterY,
            distance: Math.sqrt(
                Math.pow(centerX - frameCenterX, 2) + 
                Math.pow(centerY - frameCenterY, 2)
            )
        };
    }
    
    /**
     * Verificar calidad del documento
     */
    checkQuality(rectangle) {
        // Extraer región del documento
        const imageData = this.ctx.getImageData(
            rectangle.x, rectangle.y, rectangle.width, rectangle.height
        );
        
        // Análisis básico de calidad
        const brightness = this.calculateBrightness(imageData);
        const contrast = this.calculateContrast(imageData);
        
        return {
            brightness: brightness,
            contrast: contrast,
            isGoodLighting: brightness > 100 && brightness < 200,
            isGoodContrast: contrast > 50
        };
    }
    
    /**
     * Calcular brillo promedio
     */
    calculateBrightness(imageData) {
        const data = imageData.data;
        let sum = 0;
        
        for (let i = 0; i < data.length; i += 4) {
            const gray = (data[i] + data[i + 1] + data[i + 2]) / 3;
            sum += gray;
        }
        
        return sum / (data.length / 4);
    }
    
    /**
     * Calcular contraste
     */
    calculateContrast(imageData) {
        const data = imageData.data;
        let min = 255, max = 0;
        
        for (let i = 0; i < data.length; i += 4) {
            const gray = (data[i] + data[i + 1] + data[i + 2]) / 3;
            min = Math.min(min, gray);
            max = Math.max(max, gray);
        }
        
        return max - min;
    }
    
    /**
     * Obtener el mejor documento detectado
     */
    getBestDocument(documents) {
        if (documents.length === 0) {
            return { found: false };
        }
        
        // Ordenar por confianza
        documents.sort((a, b) => b.confidence - a.confidence);
        
        const best = documents[0];
        
        return {
            found: true,
            rectangle: best.rectangle,
            confidence: best.confidence,
            position: best.position,
            quality: best.quality,
            lightingAnalysis: this.lightingAnalysis, // Incluir análisis real de iluminación
            allDocuments: documents
        };
    }
    
    /**
     * Configurar parámetros de detección
     */
    configure(config) {
        this.dniConfig = { ...this.dniConfig, ...config };
        console.log('Configuración de detección actualizada:', this.dniConfig);
    }
    
    /**
     * Obtener configuración actual
     */
    getConfiguration() {
        return this.dniConfig;
    }
}

// Exportar para uso global
window.DocumentDetector = DocumentDetector;
