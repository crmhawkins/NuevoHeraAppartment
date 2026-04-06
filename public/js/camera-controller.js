/**
 * Camera Controller - Manejo de la cámara del dispositivo
 */
class CameraController {
    constructor() {
        this.video = document.getElementById('camera');
        this.stream = null;
        this.currentFacingMode = 'environment'; // Cámara trasera por defecto
        this.isInitialized = false;
        this.constraints = {
            video: {
                facingMode: this.currentFacingMode,
                width: { ideal: 1920, min: 640 },
                height: { ideal: 1080, min: 480 },
                frameRate: { ideal: 30, max: 60 }
            }
        };
    }
    
    /**
     * Inicializar la cámara
     */
    async initializeCamera() {
        try {
            console.log('Iniciando cámara...');
            
            // Verificar soporte de getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                // Fallback para navegadores más antiguos
                if (navigator.getUserMedia) {
                    console.log('Usando getUserMedia legacy...');
                    return await this.initializeCameraLegacy();
                }
                throw new Error('getUserMedia no soportado en este navegador');
            }
            
            // Detener stream anterior si existe
            if (this.stream) {
                this.stopCamera();
            }
            
            // Solicitar acceso a la cámara
            this.stream = await navigator.mediaDevices.getUserMedia(this.constraints);
            
            // Configurar el video
            this.video.srcObject = this.stream;
            
            // Esperar a que el video esté listo
            await this.waitForVideoReady();
            
            this.isInitialized = true;
            console.log('Cámara inicializada correctamente');
            
            // Actualizar estado visual
            const statusElement = document.getElementById('cameraStatus');
            if (statusElement) {
                statusElement.textContent = '✅ Activa';
                statusElement.style.background = 'rgba(76, 175, 80, 0.8)';
            }
            
            return true;
            
        } catch (error) {
            console.error('Error inicializando cámara:', error);
            
            // Actualizar estado visual de error
            const statusElement = document.getElementById('cameraStatus');
            if (statusElement) {
                statusElement.textContent = '❌ Error';
                statusElement.style.background = 'rgba(244, 67, 54, 0.8)';
            }
            
            this.handleCameraError(error);
            return false;
        }
    }
    
    /**
     * Inicializar cámara con API legacy
     */
    async initializeCameraLegacy() {
        return new Promise((resolve, reject) => {
            const constraints = {
                video: {
                    facingMode: this.currentFacingMode,
                    width: { ideal: 1920, min: 640 },
                    height: { ideal: 1080, min: 480 }
                }
            };
            
            navigator.getUserMedia(constraints, (stream) => {
                this.stream = stream;
                this.video.srcObject = stream;
                
                this.waitForVideoReady().then(() => {
                    this.isInitialized = true;
                    console.log('Cámara legacy inicializada correctamente');
                    resolve(true);
                }).catch(reject);
                
            }, (error) => {
                console.error('Error con getUserMedia legacy:', error);
                reject(error);
            });
        });
    }
    
    /**
     * Esperar a que el video esté listo
     */
    waitForVideoReady() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('Timeout esperando video'));
            }, 10000);
            
            this.video.addEventListener('loadedmetadata', () => {
                clearTimeout(timeout);
                console.log(`Video listo: ${this.video.videoWidth}x${this.video.videoHeight}`);
                resolve();
            }, { once: true });
            
            this.video.addEventListener('error', (e) => {
                clearTimeout(timeout);
                reject(new Error('Error cargando video: ' + e.message));
            }, { once: true });
        });
    }
    
    /**
     * Voltear cámara (frontal/trasera)
     */
    async flipCamera() {
        try {
            console.log('Volteando cámara...');
            
            this.currentFacingMode = this.currentFacingMode === 'environment' ? 'user' : 'environment';
            this.constraints.video.facingMode = this.currentFacingMode;
            
            // Detener cámara actual
            this.stopCamera();
            
            // Inicializar nueva cámara
            const success = await this.initializeCamera();
            
            if (success) {
                console.log(`Cámara cambiada a: ${this.currentFacingMode}`);
            }
            
            return success;
            
        } catch (error) {
            console.error('Error volteando cámara:', error);
            return false;
        }
    }
    
    /**
     * Detener la cámara
     */
    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => {
                track.stop();
                console.log('Track detenido:', track.kind);
            });
            this.stream = null;
        }
        
        if (this.video) {
            this.video.srcObject = null;
        }
        
        this.isInitialized = false;
        console.log('Cámara detenida');
    }
    
    /**
     * Capturar frame actual
     */
    captureFrame() {
        if (!this.isInitialized || !this.video.videoWidth) {
            throw new Error('Cámara no inicializada o video no listo');
        }
        
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        
        // Configurar canvas con las dimensiones del video
        canvas.width = this.video.videoWidth;
        canvas.height = this.video.videoHeight;
        
        // Dibujar el frame actual
        context.drawImage(this.video, 0, 0, canvas.width, canvas.height);
        
        // Convertir a base64
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        
        console.log(`Frame capturado: ${canvas.width}x${canvas.height}`);
        
        return {
            imageData: imageData,
            width: canvas.width,
            height: canvas.height,
            canvas: canvas
        };
    }
    
    /**
     * Obtener información de la cámara
     */
    getCameraInfo() {
        if (!this.isInitialized) {
            return null;
        }
        
        return {
            width: this.video.videoWidth,
            height: this.video.videoHeight,
            facingMode: this.currentFacingMode,
            stream: this.stream
        };
    }
    
    /**
     * Verificar si la cámara está funcionando
     */
    isCameraWorking() {
        return this.isInitialized && 
               this.stream && 
               this.video.videoWidth > 0 && 
               this.video.videoHeight > 0;
    }
    
    /**
     * Obtener resolución actual
     */
    getResolution() {
        if (!this.isInitialized) {
            return null;
        }
        
        return {
            width: this.video.videoWidth,
            height: this.video.videoHeight
        };
    }
    
    /**
     * Manejar errores de cámara
     */
    handleCameraError(error) {
        console.error('Error de cámara:', error);
        
        let errorMessage = 'Error desconocido';
        
        switch (error.name) {
            case 'NotAllowedError':
                errorMessage = 'Acceso a la cámara denegado. Por favor, permite el acceso a la cámara.';
                break;
            case 'NotFoundError':
                errorMessage = 'No se encontró ninguna cámara en el dispositivo.';
                break;
            case 'NotReadableError':
                errorMessage = 'La cámara está siendo usada por otra aplicación.';
                break;
            case 'OverconstrainedError':
                errorMessage = 'La cámara no soporta las características solicitadas.';
                break;
            case 'SecurityError':
                errorMessage = 'Error de seguridad al acceder a la cámara.';
                break;
            case 'TypeError':
                errorMessage = 'Error de tipo al acceder a la cámara.';
                break;
            default:
                errorMessage = `Error de cámara: ${error.message}`;
        }
        
        // Emitir evento de error
        this.emitError(errorMessage);
    }
    
    /**
     * Emitir evento de error
     */
    emitError(message) {
        const event = new CustomEvent('cameraError', {
            detail: { message: message }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Verificar soporte de características
     */
    static checkSupport() {
        const support = {
            getUserMedia: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
            canvas: !!document.createElement('canvas').getContext,
            webRTC: !!(window.RTCPeerConnection || window.webkitRTCPeerConnection),
            mobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        };
        
        console.log('Soporte del navegador:', support);
        return support;
    }
    
    /**
     * Obtener dispositivos de cámara disponibles
     */
    async getAvailableCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(device => device.kind === 'videoinput');
            
            console.log('Cámaras disponibles:', cameras);
            return cameras;
            
        } catch (error) {
            console.error('Error obteniendo cámaras:', error);
            return [];
        }
    }
    
    /**
     * Configurar restricciones específicas
     */
    setConstraints(constraints) {
        this.constraints = { ...this.constraints, ...constraints };
        console.log('Restricciones actualizadas:', this.constraints);
    }
    
    /**
     * Obtener restricciones actuales
     */
    getConstraints() {
        return this.constraints;
    }
    
    /**
     * Limpiar recursos
     */
    destroy() {
        this.stopCamera();
        console.log('CameraController destruido');
    }
}

// Exportar para uso global
window.CameraController = CameraController;
