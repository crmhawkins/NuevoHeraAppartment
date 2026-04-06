/**
 * Overlay Manager - Gestión de la interfaz visual y feedback al usuario
 */
class OverlayManager {
    constructor() {
        this.guideFrame = document.getElementById('guideFrame');
        this.instructions = document.getElementById('instructions');
        this.progressContainer = document.getElementById('progressContainer');
        this.progressFill = document.getElementById('progressFill');
        this.progressText = document.getElementById('progressText');
        
        this.statusItems = {
            position: document.getElementById('positionStatus'),
            angle: document.getElementById('angleStatus'),
            lighting: document.getElementById('lightingStatus')
        };
        
        this.captureBtn = document.getElementById('captureBtn');
        this.flipBtn = document.getElementById('flipBtn');
        
        this.currentSide = 'front';
        this.isProcessing = false;
        this.lightingInterval = null;
        
        this.setupEventListeners();
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Botón de captura
        if (this.captureBtn) {
            this.captureBtn.addEventListener('click', () => {
                this.onCaptureClick();
            });
        }
        
        // Botón de voltear cámara
        if (this.flipBtn) {
            this.flipBtn.addEventListener('click', () => {
                this.onFlipClick();
            });
        }
        
        // Modales
        this.setupModalListeners();
    }
    
    /**
     * Configurar listeners de modales
     */
    setupModalListeners() {
        const closeErrorModal = document.getElementById('closeErrorModal');
        const closeSuccessModal = document.getElementById('closeSuccessModal');
        
        if (closeErrorModal) {
            closeErrorModal.addEventListener('click', () => {
                this.hideErrorModal();
            });
        }
        
        if (closeSuccessModal) {
            closeSuccessModal.addEventListener('click', () => {
                this.hideSuccessModal();
            });
        }
    }
    
    /**
     * Actualizar resultado de detección
     */
    updateDetectionResult(result) {
        if (!result || !result.found) {
            this.showNoDocument();
            return;
        }
        
        this.showDocumentDetected(result);
    }
    
    /**
     * Mostrar documento detectado
     */
    showDocumentDetected(result) {
        const { confidence, position, quality, lightingAnalysis } = result;
        
        // Actualizar indicadores de estado
        this.updateStatus('position', position.centered, 'Centrado');
        this.updateStatus('angle', confidence > 70, 'Ángulo correcto');
        
        // Usar análisis REAL de iluminación si está disponible
        if (lightingAnalysis) {
            const brightnessValue = Math.round(lightingAnalysis.averageBrightness);
            this.updateStatus('lighting', lightingAnalysis.isGoodLighting, `${lightingAnalysis.level} (${brightnessValue})`);
        } else {
            this.updateStatus('lighting', quality.isGoodLighting, 'Buena iluminación');
        }
        
        // Actualizar instrucciones y estado del botón
        if (confidence > 80) {
            this.showPerfectPosition();
        } else if (confidence > 60) {
            this.showAlmostPerfect(confidence);
        } else {
            this.showNeedsAdjustment(confidence);
        }
        
        // Mostrar guías de posición si es necesario
        if (!position.centered) {
            this.showPositionGuide(position.offsetX, position.offsetY);
        } else {
            this.hidePositionGuide();
        }
        
        // Actualizar clase del marco de guía
        this.updateGuideFrameClass(confidence);
    }
    
    /**
     * Mostrar posición perfecta
     */
    showPerfectPosition() {
        this.instructions.textContent = '✅ Perfecto! Presiona capturar';
        this.instructions.className = 'instructions success';
        this.captureBtn.disabled = false;
        this.guideFrame.classList.add('detected');
        this.guideFrame.classList.remove('almost-detected', 'error');
    }
    
    /**
     * Mostrar casi perfecto
     */
    showAlmostPerfect(confidence) {
        this.instructions.textContent = `⚠️ Casi perfecto (${Math.round(confidence)}%), ajusta un poco`;
        this.instructions.className = 'instructions warning';
        this.captureBtn.disabled = true;
        this.guideFrame.classList.add('almost-detected');
        this.guideFrame.classList.remove('detected', 'error');
    }
    
    /**
     * Mostrar necesita ajuste
     */
    showNeedsAdjustment(confidence) {
        this.instructions.textContent = '❌ Ajusta la posición del DNI';
        this.instructions.className = 'instructions error';
        this.captureBtn.disabled = true;
        this.guideFrame.classList.add('error');
        this.guideFrame.classList.remove('detected', 'almost-detected');
    }
    
    /**
     * Mostrar no hay documento
     */
    showNoDocument() {
        this.instructions.textContent = 'Coloca el DNI dentro del marco';
        this.instructions.className = 'instructions';
        this.captureBtn.disabled = true;
        this.guideFrame.classList.remove('detected', 'almost-detected', 'error');
        
        // Mostrar estado básico en lugar de "N/A"
        this.updateStatus('position', false, 'Ajustar');
        this.updateStatus('angle', false, 'Ajustar');
        this.updateStatus('lighting', false, 'Analizando...');
        
        this.hidePositionGuide();
        
        // Iniciar análisis continuo de iluminación
        this.startLightingAnalysis();
    }
    
    /**
     * Iniciar análisis continuo de iluminación
     */
    startLightingAnalysis() {
        if (this.lightingInterval) {
            clearInterval(this.lightingInterval);
        }
        
        this.lightingInterval = setInterval(() => {
            this.analyzeLighting();
        }, 500); // Analizar cada 500ms
    }
    
    /**
     * Detener análisis de iluminación
     */
    stopLightingAnalysis() {
        if (this.lightingInterval) {
            clearInterval(this.lightingInterval);
            this.lightingInterval = null;
        }
    }
    
    /**
     * Analizar iluminación de la cámara
     */
    analyzeLighting() {
        const video = document.getElementById('camera');
        if (!video || !video.videoWidth) return;
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        let totalBrightness = 0;
        let pixelCount = 0;
        
        // Analizar cada 10 píxeles para optimizar rendimiento
        for (let i = 0; i < data.length; i += 40) { // 4 bytes por píxel * 10 píxeles
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            
            const gray = Math.round(0.299 * r + 0.587 * g + 0.114 * b);
            totalBrightness += gray;
            pixelCount++;
        }
        
        const averageBrightness = totalBrightness / pixelCount;
        
        // Actualizar indicador de iluminación
        let level, isGood;
        if (averageBrightness < 60) {
            level = `Muy oscuro (${Math.round(averageBrightness)})`;
            isGood = false;
        } else if (averageBrightness < 80) {
            level = `Oscuro (${Math.round(averageBrightness)})`;
            isGood = false;
        } else if (averageBrightness < 200) {
            level = `Buena (${Math.round(averageBrightness)})`;
            isGood = true;
        } else if (averageBrightness < 220) {
            level = `Muy brillante (${Math.round(averageBrightness)})`;
            isGood = false;
        } else {
            level = `Demasiado brillante (${Math.round(averageBrightness)})`;
            isGood = false;
        }
        
        this.updateStatus('lighting', isGood, level);
    }
    
    /**
     * Actualizar indicador de estado
     */
    updateStatus(type, success, text) {
        const item = this.statusItems[type];
        if (!item) return;
        
        item.classList.remove('success', 'warning', 'error');
        
        if (success) {
            item.classList.add('success');
        } else {
            item.classList.add('error');
        }
        
        const textElement = item.querySelector('.text');
        if (textElement) {
            textElement.textContent = text;
        }
    }
    
    /**
     * Actualizar clase del marco de guía
     */
    updateGuideFrameClass(confidence) {
        this.guideFrame.classList.remove('detected', 'almost-detected', 'error');
        
        if (confidence > 80) {
            this.guideFrame.classList.add('detected');
        } else if (confidence > 60) {
            this.guideFrame.classList.add('almost-detected');
        } else {
            this.guideFrame.classList.add('error');
        }
    }
    
    /**
     * Mostrar guías de posición
     */
    showPositionGuide(offsetX, offsetY) {
        this.hidePositionGuide();
        
        const tolerance = 20;
        
        // Flecha horizontal
        if (Math.abs(offsetX) > tolerance) {
            const arrow = document.createElement('div');
            arrow.className = 'position-guide arrow-x';
            arrow.style.left = offsetX > 0 ? '10px' : 'calc(100% - 30px)';
            arrow.innerHTML = offsetX > 0 ? '←' : '→';
            arrow.title = offsetX > 0 ? 'Mover a la izquierda' : 'Mover a la derecha';
            this.guideFrame.appendChild(arrow);
        }
        
        // Flecha vertical
        if (Math.abs(offsetY) > tolerance) {
            const arrow = document.createElement('div');
            arrow.className = 'position-guide arrow-y';
            arrow.style.top = offsetY > 0 ? '10px' : 'calc(100% - 30px)';
            arrow.innerHTML = offsetY > 0 ? '↑' : '↓';
            arrow.title = offsetY > 0 ? 'Mover hacia arriba' : 'Mover hacia abajo';
            this.guideFrame.appendChild(arrow);
        }
    }
    
    /**
     * Ocultar guías de posición
     */
    hidePositionGuide() {
        const guides = this.guideFrame.querySelectorAll('.position-guide');
        guides.forEach(guide => guide.remove());
    }
    
    /**
     * Cambiar a lado trasero
     */
    switchToRearSide() {
        this.currentSide = 'rear';
        this.instructions.textContent = 'Ahora captura la trasera del DNI';
        this.instructions.className = 'instructions';
        this.guideFrame.classList.remove('detected', 'almost-detected', 'error');
        this.captureBtn.disabled = true;
        this.hidePositionGuide();
        
        // Actualizar texto del botón
        const btnText = this.captureBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = 'Capturar Trasera';
        }
    }
    
    /**
     * Mostrar progreso de procesamiento
     */
    showProgress(message = 'Procesando...') {
        this.isProcessing = true;
        this.progressContainer.style.display = 'block';
        this.progressText.textContent = message;
        this.progressFill.style.width = '0%';
        this.captureBtn.disabled = true;
        this.flipBtn.disabled = true;
        
        // Animar progreso
        this.animateProgress();
    }
    
    /**
     * Animar barra de progreso
     */
    animateProgress() {
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            this.progressFill.style.width = progress + '%';
            
            if (progress >= 90) {
                clearInterval(interval);
            }
        }, 200);
        
        this.progressInterval = interval;
    }
    
    /**
     * Completar progreso
     */
    completeProgress() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
        
        this.progressFill.style.width = '100%';
        this.progressText.textContent = 'Completado!';
        
        setTimeout(() => {
            this.hideProgress();
        }, 1000);
    }
    
    /**
     * Ocultar progreso
     */
    hideProgress() {
        this.isProcessing = false;
        this.progressContainer.style.display = 'none';
        this.captureBtn.disabled = false;
        this.flipBtn.disabled = false;
    }
    
    /**
     * Mostrar modal de error
     */
    showErrorModal(message) {
        const modal = document.getElementById('errorModal');
        const messageElement = document.getElementById('errorMessage');
        
        if (modal && messageElement) {
            messageElement.textContent = message;
            modal.style.display = 'flex';
        }
    }
    
    /**
     * Ocultar modal de error
     */
    hideErrorModal() {
        const modal = document.getElementById('errorModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    /**
     * Mostrar modal de éxito
     */
    showSuccessModal(message) {
        const modal = document.getElementById('successModal');
        const messageElement = document.getElementById('successMessage');
        
        if (modal && messageElement) {
            messageElement.textContent = message;
            modal.style.display = 'flex';
        }
    }
    
    /**
     * Ocultar modal de éxito
     */
    hideSuccessModal() {
        const modal = document.getElementById('successModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    /**
     * Mostrar estado de carga
     */
    showLoading() {
        document.body.classList.add('loading');
    }
    
    /**
     * Ocultar estado de carga
     */
    hideLoading() {
        document.body.classList.remove('loading');
    }
    
    /**
     * Actualizar instrucciones específicas
     */
    updateInstructions(message, type = 'info') {
        this.instructions.textContent = message;
        this.instructions.className = `instructions ${type}`;
    }
    
    /**
     * Obtener lado actual
     */
    getCurrentSide() {
        return this.currentSide;
    }
    
    /**
     * Verificar si está procesando
     */
    isCurrentlyProcessing() {
        return this.isProcessing;
    }
    
    /**
     * Evento click en capturar
     */
    onCaptureClick() {
        if (this.isProcessing) return;
        
        const event = new CustomEvent('captureRequested', {
            detail: { side: this.currentSide }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Evento click en voltear
     */
    onFlipClick() {
        if (this.isProcessing) return;
        
        const event = new CustomEvent('flipCameraRequested');
        document.dispatchEvent(event);
    }
    
    /**
     * Resetear interfaz
     */
    reset() {
        this.currentSide = 'front';
        this.isProcessing = false;
        this.showNoDocument();
        this.hideProgress();
        this.hidePositionGuide();
        
        // Resetear texto del botón
        const btnText = this.captureBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = 'Capturar';
        }
    }
    
    /**
     * Destruir overlay manager
     */
    destroy() {
        this.hideProgress();
        this.hidePositionGuide();
        console.log('OverlayManager destruido');
    }
}

// Exportar para uso global
window.OverlayManager = OverlayManager;
