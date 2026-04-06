/**
 * Main Scanner - Controlador principal del sistema de escaneo de DNI
 */
class DNIScanner {
    constructor() {
        this.cameraController = new CameraController();
        this.documentDetector = new DocumentDetector();
        this.overlayManager = new OverlayManager();
        this.imageProcessor = new ImageProcessor();
        
        this.currentSide = 'front';
        this.detectionInterval = null;
        this.isInitialized = false;
        this.isProcessing = false;
        
        // Configuración
        this.config = {
            detectionInterval: 100, // ms
            minConfidence: 80,
            maxRetries: 3,
            processingTimeout: 30000 // 30 segundos
        };
        
        // Datos de la sesión
        this.sessionData = {
            token: null,
            reservaId: null,
            csrfToken: null,
            frontImage: null,
            rearImage: null
        };
        
        this.setupEventListeners();
    }
    
    /**
     * Inicializar el scanner
     */
    async initialize() {
        try {
            console.log('Inicializando DNIScanner...');
            
            // Obtener datos de la sesión
            this.loadSessionData();
            
            // Verificar soporte del navegador
            const support = CameraController.checkSupport();
            if (!support.getUserMedia) {
                throw new Error('getUserMedia no soportado');
            }
            
            // Inicializar cámara
            console.log('Iniciando cámara...');
            const cameraReady = await this.cameraController.initializeCamera();
            console.log('Resultado inicialización cámara:', cameraReady);
            if (!cameraReady) {
                throw new Error('No se pudo inicializar la cámara');
            }
            
            // Iniciar detección
            this.startDetection();
            
            // Iniciar análisis de iluminación
            this.overlayManager.startLightingAnalysis();
            
            this.isInitialized = true;
            console.log('DNIScanner inicializado correctamente');
            
            return true;
            
        } catch (error) {
            console.error('Error inicializando scanner:', error);
            this.overlayManager.showErrorModal(error.message);
            return false;
        }
    }
    
    /**
     * Cargar datos de la sesión
     */
    loadSessionData() {
        this.sessionData.token = document.getElementById('token')?.value;
        this.sessionData.reservaId = document.getElementById('reservaId')?.value;
        this.sessionData.csrfToken = document.getElementById('csrfToken')?.value;
        
        if (!this.sessionData.token) {
            throw new Error('Token de reserva no encontrado');
        }
        
        console.log('Datos de sesión cargados:', {
            token: this.sessionData.token,
            reservaId: this.sessionData.reservaId
        });
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Eventos del overlay manager
        document.addEventListener('captureRequested', (event) => {
            this.handleCaptureRequest(event.detail.side);
        });
        
        document.addEventListener('flipCameraRequested', () => {
            this.handleFlipCameraRequest();
        });
        
        // Eventos de error de cámara
        document.addEventListener('cameraError', (event) => {
            this.handleCameraError(event.detail.message);
        });
        
        // Eventos de teclado
        document.addEventListener('keydown', (event) => {
            this.handleKeyPress(event);
        });
        
        // Eventos de visibilidad de página
        document.addEventListener('visibilitychange', () => {
            this.handleVisibilityChange();
        });
    }
    
    /**
     * Iniciar detección de documentos
     */
    startDetection() {
        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
        }
        
        this.detectionInterval = setInterval(async () => {
            if (this.isProcessing || !this.cameraController.isCameraWorking()) {
                return;
            }
            
            try {
                const result = await this.documentDetector.detectDocument(
                    this.cameraController.video
                );
                
                this.overlayManager.updateDetectionResult(result);
                
            } catch (error) {
                console.error('Error en detección:', error);
            }
        }, this.config.detectionInterval);
        
        console.log('Detección iniciada');
    }
    
    /**
     * Detener detección
     */
    stopDetection() {
        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
            this.detectionInterval = null;
        }
        console.log('Detección detenida');
    }
    
    /**
     * Manejar solicitud de captura
     */
    async handleCaptureRequest(side) {
        if (this.isProcessing) {
            console.log('Ya se está procesando una imagen');
            return;
        }
        
        try {
            this.isProcessing = true;
            this.stopDetection();
            
            console.log(`Capturando ${side} del DNI...`);
            
            // Mostrar progreso
            this.overlayManager.showProgress('Capturando imagen...');
            
            // Capturar frame
            const captureData = this.cameraController.captureFrame();
            
            // Obtener información del documento detectado
            const detectionResult = await this.documentDetector.detectDocument(
                this.cameraController.video
            );
            
            // Procesar imagen
            this.overlayManager.showProgress('Procesando imagen...');
            const processedImage = await this.imageProcessor.processImage(
                captureData,
                detectionResult.found ? detectionResult.rectangle : null
            );
            
            if (!processedImage.success) {
                throw new Error(processedImage.error);
            }
            
            // Enviar al servidor
            this.overlayManager.showProgress('Enviando al servidor...');
            const serverResult = await this.sendToServer(processedImage, side);
            
            if (serverResult.success) {
                // Guardar imagen procesada
                this.sessionData[side + 'Image'] = processedImage;
                
                // Completar progreso
                this.overlayManager.completeProgress();
                
                // Determinar siguiente paso
                if (side === 'front') {
                    this.switchToRearSide();
                } else {
                    this.completeProcess();
                }
                
            } else {
                throw new Error(serverResult.message);
            }
            
        } catch (error) {
            console.error('Error capturando imagen:', error);
            this.overlayManager.hideProgress();
            this.overlayManager.showErrorModal(error.message);
            this.startDetection();
        } finally {
            this.isProcessing = false;
        }
    }
    
    /**
     * Cambiar a lado trasero
     */
    switchToRearSide() {
        this.currentSide = 'rear';
        this.overlayManager.switchToRearSide();
        this.startDetection();
        
        console.log('Cambiado a lado trasero');
    }
    
    /**
     * Completar proceso
     */
    async completeProcess() {
        try {
            console.log('Completando proceso de verificación...');
            
            this.overlayManager.showProgress('Finalizando verificación...');
            
            // Enviar solicitud de finalización
            const result = await this.completeVerification();
            
            if (result.success) {
                this.overlayManager.completeProgress();
                this.overlayManager.showSuccessModal('Verificación completada exitosamente');
                
                // Redirigir después de un delay
                setTimeout(() => {
                    window.location.href = result.redirect_url;
                }, 2000);
                
            } else {
                throw new Error(result.message);
            }
            
        } catch (error) {
            console.error('Error completando proceso:', error);
            this.overlayManager.hideProgress();
            this.overlayManager.showErrorModal(error.message);
        }
    }
    
    /**
     * Manejar solicitud de voltear cámara
     */
    async handleFlipCameraRequest() {
        if (this.isProcessing) {
            console.log('No se puede voltear cámara durante el procesamiento');
            return;
        }
        
        try {
            console.log('Volteando cámara...');
            
            this.stopDetection();
            const success = await this.cameraController.flipCamera();
            
            if (success) {
                this.startDetection();
                console.log('Cámara volteada exitosamente');
            } else {
                throw new Error('Error volteando cámara');
            }
            
        } catch (error) {
            console.error('Error volteando cámara:', error);
            this.overlayManager.showErrorModal('Error volteando cámara: ' + error.message);
            this.startDetection();
        }
    }
    
    /**
     * Manejar errores de cámara
     */
    handleCameraError(message) {
        console.error('Error de cámara:', message);
        this.overlayManager.showErrorModal(message);
    }
    
    /**
     * Manejar teclas presionadas
     */
    handleKeyPress(event) {
        if (this.isProcessing) return;
        
        switch (event.key) {
            case ' ':
            case 'Enter':
                event.preventDefault();
                if (!this.overlayManager.captureBtn.disabled) {
                    this.handleCaptureRequest(this.currentSide);
                }
                break;
            case 'f':
            case 'F':
                event.preventDefault();
                this.handleFlipCameraRequest();
                break;
            case 'Escape':
                event.preventDefault();
                this.overlayManager.hideErrorModal();
                this.overlayManager.hideSuccessModal();
                break;
        }
    }
    
    /**
     * Manejar cambio de visibilidad de página
     */
    handleVisibilityChange() {
        if (document.hidden) {
            this.stopDetection();
        } else if (this.isInitialized && !this.isProcessing) {
            this.startDetection();
        }
    }
    
    /**
     * Enviar imagen al servidor
     */
    async sendToServer(processedImage, side) {
        try {
            const formData = new FormData();
            formData.append('image', processedImage.imageData);
            formData.append('side', side);
            formData.append('token', this.sessionData.token);
            formData.append('_token', this.sessionData.csrfToken);
            
            const response = await fetch('/dni-scanner/process', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            console.log('Respuesta del servidor:', result);
            
            return result;
            
        } catch (error) {
            console.error('Error enviando al servidor:', error);
            return {
                success: false,
                message: 'Error de conexión: ' + error.message
            };
        }
    }
    
    /**
     * Completar verificación
     */
    async completeVerification() {
        try {
            const formData = new FormData();
            formData.append('token', this.sessionData.token);
            formData.append('_token', this.sessionData.csrfToken);
            
            const response = await fetch('/dni-scanner/complete', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            console.log('Verificación completada:', result);
            
            return result;
            
        } catch (error) {
            console.error('Error completando verificación:', error);
            return {
                success: false,
                message: 'Error completando verificación: ' + error.message
            };
        }
    }
    
    /**
     * Configurar parámetros
     */
    configure(config) {
        this.config = { ...this.config, ...config };
        console.log('Configuración actualizada:', this.config);
    }
    
    /**
     * Obtener estado actual
     */
    getStatus() {
        return {
            isInitialized: this.isInitialized,
            isProcessing: this.isProcessing,
            currentSide: this.currentSide,
            cameraWorking: this.cameraController.isCameraWorking(),
            detectionActive: !!this.detectionInterval,
            sessionData: this.sessionData
        };
    }
    
    /**
     * Limpiar recursos
     */
    destroy() {
        console.log('Destruyendo DNIScanner...');
        
        this.stopDetection();
        this.cameraController.destroy();
        this.overlayManager.destroy();
        this.imageProcessor.destroy();
        
        this.isInitialized = false;
        this.isProcessing = false;
        
        console.log('DNIScanner destruido');
    }
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Inicializando sistema de escaneo de DNI...');
    
    try {
        const scanner = new DNIScanner();
        const success = await scanner.initialize();
        
        if (success) {
            console.log('Sistema de escaneo inicializado correctamente');
            
            // Hacer disponible globalmente para debugging
            window.dniScanner = scanner;
            
        } else {
            console.error('Error inicializando sistema de escaneo');
        }
        
    } catch (error) {
        console.error('Error crítico inicializando sistema:', error);
        
        // Mostrar error al usuario
        const errorModal = document.getElementById('errorModal');
        const errorMessage = document.getElementById('errorMessage');
        
        if (errorModal && errorMessage) {
            errorMessage.textContent = 'Error inicializando el sistema: ' + error.message;
            errorModal.style.display = 'flex';
        }
    }
});

// Manejar errores no capturados
window.addEventListener('error', (event) => {
    console.error('Error no capturado:', event.error);
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Promise rechazada:', event.reason);
});
