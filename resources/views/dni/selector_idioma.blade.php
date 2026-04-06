<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Idioma - Language Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .language-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .language-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .welcome-text {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-text p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .language-select {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .language-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }
        
        .language-option {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s ease;
        }
        
        .language-option:hover {
            background-color: #f8f9fa;
        }
        
        .language-option:last-child {
            border-bottom: none;
        }
        
        .flag-icon {
            font-size: 1.2rem;
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .language-name {
            font-weight: 600;
            color: #333;
        }
        
        .language-native {
            font-size: 0.9rem;
            color: #666;
            margin-left: 5px;
        }
        
        .btn-continue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
        }
        
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-continue:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .loading {
            display: none;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .info-text {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            color: white;
            text-align: center;
        }
        
        .info-text i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .welcome-text h1 {
                font-size: 2rem;
            }
            
            .welcome-text p {
                font-size: 1rem;
            }
            
            .language-select {
                font-size: 1rem;
                padding: 12px;
            }
            
            .btn-continue {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
        
        /* Estilos para transiciones */
        .transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .transition-overlay::after {
            content: '';
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estilos para notificaciones de error */
        .error-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            z-index: 10000;
            display: none;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        }
        
        .error-notification i {
            margin-right: 10px;
            color: #ffc107;
        }
        
        .error-notification .close-notification {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            margin-left: 15px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .error-notification .close-notification:hover {
            opacity: 1;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Animaciones para transiciones de página */
        body {
            transition: opacity 0.3s ease;
        }
        
        body.fade-out {
            opacity: 0;
        }
        
        body.fade-in {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <!-- Texto de bienvenida -->
                <div class="welcome-text">
                    <h1>🌍 Selecciona tu idioma</h1>
                    <p>Choose your language / Choisissez votre langue</p>
                    <p>Wählen Sie Ihre Sprache / Scegli la tua lingua</p>
                    <p>Escolha seu idioma</p>
                </div>
                
                <!-- Tarjeta de selección de idioma -->
                <div class="language-card p-4">
                    <div class="text-center mb-4">
                        <h4 class="text-dark mb-3">
                            <i class="fas fa-globe me-2"></i>
                            Idioma preferido / Preferred language
                        </h4>
                    </div>
                    
                    <!-- Selector de idioma -->
                    <div class="mb-4">
                        <select id="idioma" class="language-select">
                            <option value="">-- Selecciona tu idioma / Select your language --</option>
                            <option value="es" data-flag="🇪🇸" data-native="Spanish">🇪🇸 Español</option>
                            <option value="en" data-flag="🇺🇸" data-native="Inglés">🇺🇸 English</option>
                            <option value="fr" data-flag="🇫🇷" data-native="French">🇫🇷 Français</option>
                            <option value="de" data-flag="🇩🇪" data-native="German">🇩🇪 Deutsch</option>
                            <option value="it" data-flag="🇮🇹" data-native="Italian">🇮🇹 Italiano</option>
                            <option value="pt" data-flag="🇵🇹" data-native="Portuguese">🇵🇹 Português</option>
                            <option value="ru" data-flag="🇷🇺" data-native="Russian">🇷🇺 Русский</option>
                            <option value="zh" data-flag="🇨🇳" data-native="Chinese">🇨🇳 中文</option>
                            <option value="ja" data-flag="🇯🇵" data-native="Japanese">🇯🇵 日本語</option>
                            <option value="ko" data-flag="🇰🇷" data-native="Korean">🇰🇷 한국어</option>
                            <option value="ar" data-flag="🇸🇦" data-native="Arabic">🇸🇦 العربية</option>
                            <option value="hi" data-flag="🇮🇳" data-native="Hindi">🇮🇳 हिन्दी</option>
                            <option value="tr" data-flag="🇹🇷" data-native="Turkish">🇹🇷 Türkçe</option>
                            <option value="pl" data-flag="🇵🇱" data-native="Polish">🇵🇱 Polski</option>
                            <option value="nl" data-flag="🇳🇱" data-native="Dutch">🇳🇱 Nederlands</option>
                            <option value="sv" data-flag="🇸🇪" data-native="Swedish">🇸🇪 Svenska</option>
                            <option value="da" data-flag="🇩🇰" data-native="Danish">🇩🇰 Dansk</option>
                            <option value="no" data-flag="🇳🇴" data-native="Norwegian">🇳🇴 Norsk</option>
                            <option value="fi" data-flag="🇫🇮" data-native="Finnish">🇫🇮 Suomi</option>
                            <option value="cs" data-flag="🇨🇿" data-native="Czech">🇨🇿 Čeština</option>
                            <option value="sk" data-flag="🇸🇰" data-native="Slovak">🇸🇰 Slovenčina</option>
                            <option value="hu" data-flag="🇭🇺" data-native="Hungarian">🇭🇺 Magyar</option>
                            <option value="ro" data-flag="🇷🇴" data-native="Romanian">🇷🇴 Română</option>
                            <option value="bg" data-flag="🇧🇬" data-native="Bulgarian">🇧🇬 Български</option>
                            <option value="hr" data-flag="🇭🇷" data-native="Croatian">🇭🇷 Hrvatski</option>
                            <option value="sl" data-flag="🇸🇮" data-native="Slovenian">🇸🇮 Slovenščina</option>
                            <option value="et" data-flag="🇪🇪" data-native="Estonian">🇪🇪 Eesti</option>
                            <option value="lv" data-flag="🇱🇻" data-native="Latvian">🇱🇻 Latviešu</option>
                            <option value="lt" data-flag="🇱🇹" data-native="Lithuanian">🇱🇹 Lietuvių</option>
                            <option value="mt" data-flag="🇲🇹" data-native="Maltese">🇲🇹 Malti</option>
                            <option value="el" data-flag="🇬🇷" data-native="Greek">🇬🇷 Ελληνικά</option>
                            <option value="he" data-flag="🇮🇱" data-native="Hebrew">🇮🇱 עברית</option>
                            <option value="th" data-flag="🇹🇭" data-native="Thai">🇹🇭 ไทย</option>
                            <option value="vi" data-flag="🇻🇳" data-native="Vietnamese">🇻🇳 Tiếng Việt</option>
                            <option value="id" data-flag="🇮🇩" data-native="Indonesian">🇮🇩 Bahasa Indonesia</option>
                            <option value="ms" data-flag="🇲🇾" data-native="Malay">🇲🇾 Bahasa Melayu</option>
                            <option value="tl" data-flag="🇵🇭" data-native="Filipino">🇵🇭 Tagalog</option>
                            <option value="bn" data-flag="🇧🇩" data-native="Bengali">🇧🇩 বাংলা</option>
                            <option value="ur" data-flag="🇵🇰" data-native="Urdu">🇵🇰 اردو</option>
                            <option value="fa" data-flag="🇮🇷" data-native="Persian">🇮🇷 فارسی</option>
                            <option value="uk" data-flag="🇺🇦" data-native="Ukrainian">🇺🇦 Українська</option>
                            <option value="be" data-flag="🇧🇾" data-native="Belarusian">🇧🇾 Беларуская</option>
                            <option value="mk" data-flag="🇲🇰" data-native="Macedonian">🇲🇰 Македонски</option>
                            <option value="sq" data-flag="🇦🇱" data-native="Albanian">🇦🇱 Shqip</option>
                            <option value="sr" data-flag="🇷🇸" data-native="Serbian">🇷🇸 Српски</option>
                            <option value="bs" data-flag="🇧🇦" data-native="Bosnian">🇧🇦 Bosanski</option>
                            <option value="me" data-flag="🇲🇪" data-native="Montenegrin">🇲🇪 Crnogorski</option>
                            <option value="is" data-flag="🇮🇸" data-native="Icelandic">🇮🇸 Íslenska</option>
                            <option value="ga" data-flag="🇮🇪" data-native="Irish">🇮🇪 Gaeilge</option>
                            <option value="cy" data-flag="🇬🇧" data-native="Welsh">🇬🇧 Cymraeg</option>
                            <option value="eu" data-flag="🇪🇸" data-native="Basque">🇪🇸 Euskara</option>
                            <option value="ca" data-flag="🇪🇸" data-native="Catalan">🇪🇸 Català</option>
                            <option value="gl" data-flag="🇪🇸" data-native="Galician">🇪🇸 Galego</option>
                        </select>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="info-text">
                        <i class="fas fa-info-circle"></i>
                        <span id="infoText">Selecciona tu idioma preferido para continuar con el proceso de registro</span>
                    </div>
                    
                    <!-- Botón continuar -->
                    <div class="text-center mt-4">
                        <button id="btnContinuar" class="btn btn-continue text-white" disabled>
                            <span class="loading">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            </span>
                            <span class="btn-text">Continuar / Continue / Continuer / Fortfahren / Continua / Continuar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let selectedLanguage = null;
        
        // Textos informativos en diferentes idiomas
        const infoTexts = {
            es: 'Selecciona tu idioma preferido para continuar con el proceso de registro',
            en: 'Select your preferred language to continue with the registration process',
            fr: 'Sélectionnez votre langue préférée pour continuer le processus d\'enregistrement',
            de: 'Wählen Sie Ihre bevorzugte Sprache aus, um mit dem Registrierungsprozess fortzufahren',
            it: 'Seleziona la tua lingua preferita per continuare con il processo di registrazione',
            pt: 'Selecione seu idioma preferido para continuar com o processo de registro',
            ru: 'Выберите предпочитаемый язык для продолжения процесса регистрации',
            zh: '选择您偏好的语言以继续注册过程',
            ja: '登録プロセスを続行するには、お好みの言語を選択してください',
            ko: '등록 과정을 계속하려면 선호하는 언어를 선택하세요',
            ar: 'اختر لغتك المفضلة للمتابعة مع عملية التسجيل',
            hi: 'पंजीकरण प्रक्रिया जारी रखने के लिए अपनी पसंदीदा भाषा चुनें',
            tr: 'Kayıt işlemine devam etmek için tercih ettiğiniz dili seçin',
            pl: 'Wybierz preferowany język, aby kontynuować proces rejestracji',
            nl: 'Selecteer uw voorkeurstaal om door te gaan met het registratieproces',
            sv: 'Välj ditt föredragna språk för att fortsätta med registreringsprocessen',
            da: 'Vælg dit foretrukne sprog for at fortsætte med registreringsprocessen',
            no: 'Velg ditt foretrukne språk for å fortsette med registreringsprosessen',
            fi: 'Valitse haluamasi kieli jatkaaksesi rekisteröintiprosessia',
            cs: 'Vyberte svůj preferovaný jazyk pro pokračování v registračním procesu',
            sk: 'Vyberte svoj preferovaný jazyk na pokračovanie v registračnom procese',
            hu: 'Válassza ki a preferált nyelvet a regisztrációs folyamat folytatásához',
            ro: 'Selectați limba preferată pentru a continua cu procesul de înregistrare',
            bg: 'Изберете предпочитания език, за да продължите с процеса на регистрация',
            hr: 'Odaberite željeni jezik za nastavak procesa registracije',
            sl: 'Izberite željeni jezik za nadaljevanje procesa registracije',
            et: 'Valige oma eelistatud keel registreerimisprotsessi jätkamiseks',
            lv: 'Izvēlieties savu vēlamo valodu, lai turpinātu reģistrācijas procesu',
            lt: 'Pasirinkite pageidaujamą kalbą, kad tęstumėte registracijos procesą',
            mt: 'Agħżel il-lingwa preferuta tiegħek biex tkompli mal-proċess ta\' reġistrazzjoni',
            el: 'Επιλέξτε την προτιμώμενη γλώσσα σας για να συνεχίσετε τη διαδικασία εγγραφής',
            he: 'בחר את השפה המועדפת שלך כדי להמשיך בתהליך ההרשמה',
            th: 'เลือกภาษาที่คุณต้องการเพื่อดำเนินการต่อในกระบวนการลงทะเบียน',
            vi: 'Chọn ngôn ngữ ưa thích của bạn để tiếp tục quá trình đăng ký',
            id: 'Pilih bahasa pilihan Anda untuk melanjutkan proses pendaftaran',
            ms: 'Pilih bahasa pilihan anda untuk meneruskan proses pendaftaran',
            tl: 'Piliin ang iyong ginustong wika upang magpatuloy sa proseso ng pagpaparehistro',
            bn: 'নিবন্ধন প্রক্রিয়া চালিয়ে যেতে আপনার পছন্দের ভাষা নির্বাচন করুন',
            ur: 'رجسٹریشن کے عمل کو جاری رکھنے کے لیے اپنی ترجیحی زبان منتخب کریں',
            fa: 'زبان مورد نظر خود را برای ادامه فرآیند ثبت نام انتخاب کنید',
            uk: 'Виберіть бажану мову для продовження процесу реєстрації',
            be: 'Выберыце пажаданую мову для працягу працэсу рэгістрацыі',
            mk: 'Изберете го вашиот претпочитан јазик за да продолжите со процесот на регистрација',
            sq: 'Zgjidhni gjuhën tuaj të preferuar për të vazhduar procesin e regjistrimit',
            sr: 'Изаберите жељени језик за наставак процеса регистрације',
            bs: 'Odaberite željeni jezik za nastavak procesa registracije',
            me: 'Odaberite željeni jezik za nastavak procesa registracije',
            is: 'Veldu þitt valda tungumál til að halda áfram með skráningarferlið',
            ga: 'Roghnaigh do theanga is fearr leat chun leanúint ar aghaidh leis an bpróiseas clárúcháin',
            cy: 'Dewiswch eich iaith ffefrynnol i barhau gyda\'r broses cofrestru',
            eu: 'Aukeratu hizkuntza hobetsia erregistro prozesua jarraitzeko',
            ca: 'Seleccioneu la vostra llengua preferida per continuar amb el procés de registre',
            gl: 'Selecciona o teu idioma preferido para continuar co proceso de rexistro'
        };
        
        // Seleccionar idioma
        $('#idioma').change(function() {
            selectedLanguage = $(this).val();
            
            if (selectedLanguage) {
                $('#btnContinuar').prop('disabled', false);
                
                // Actualizar texto informativo
                if (infoTexts[selectedLanguage]) {
                    $('#infoText').text(infoTexts[selectedLanguage]);
                }
            } else {
                $('#btnContinuar').prop('disabled', true);
                $('#infoText').text('Selecciona tu idioma preferido para continuar con el proceso de registro');
            }
        });
        
        // Continuar con el idioma seleccionado
        $('#btnContinuar').click(function() {
            if (!selectedLanguage) return;
            
            // Mostrar loading
            $('.loading').show();
            $('.btn-text').hide();
            $(this).prop('disabled', true);
            
            // Hacer petición AJAX para establecer el idioma
            $.ajax({
                url: '{{ route("dni.cambiarIdioma") }}',
                type: 'POST',
                data: {
                    idioma: selectedLanguage,
                    token: '{{ $token }}',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar overlay de transición
                        const overlay = $('<div class="transition-overlay"></div>');
                        $('body').append(overlay);
                        overlay.fadeIn(300);
                        
                        // Redirigir después de un breve delay para mostrar la transición
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 500);
                    } else {
                        showError('Error al establecer el idioma: ' + response.message);
                        // Restaurar botón
                        $('.loading').hide();
                        $('.btn-text').show();
                        $('#btnContinuar').prop('disabled', false);
                    }
                },
                error: function() {
                    showError('Error al establecer el idioma');
                    // Restaurar botón
                    $('.loading').hide();
                    $('.btn-text').show();
                    $('#btnContinuar').prop('disabled', false);
                }
            });
        });
        

        
        // Función para mostrar errores
        function showError(message) {
            // Crear notificación de error
            const notification = $(`
                <div class="error-notification">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>${message}</span>
                    <button class="close-notification">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Mostrar con animación
            notification.slideDown(300);
            
            // Auto-ocultar después de 5 segundos
            setTimeout(function() {
                notification.slideUp(300, function() {
                    notification.remove();
                });
            }, 5000);
            
            // Cerrar manualmente
            notification.find('.close-notification').click(function() {
                notification.slideUp(300, function() {
                    notification.remove();
                });
            });
        }
        
        // Efecto hover en el select
        $('#idioma').focus(function() {
            $(this).addClass('border-primary');
        }).blur(function() {
            $(this).removeClass('border-primary');
        });
    </script>
</body>
</html>
