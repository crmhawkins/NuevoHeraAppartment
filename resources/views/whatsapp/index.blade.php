
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Chat Whatsapp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="theme-color" content="#ffffff">
    <style>
        body {
          background-color: #0e283c;
          -webkit-font-smoothing: antialiased;
          -moz-osx-font-smoothing: grayscale;
          text-rendering: optimizeLegibility;
        }

        .container {
          margin: 60px auto;
          background: #fff;
          padding: 0;
          border-radius: 7px;
        }

        .profile-image {
          width: 50px;
          height: 50px;
          border-radius: 40px;
        }

        .settings-tray {
          background: #eee;
          padding: 10px 15px;
          border-radius: 7px;
        }
        .settings-tray .no-gutters {
          padding: 0;
        }
        .settings-tray--right {
          float: right;
        }
        .settings-tray--right i {
          margin-top: 10px;
          font-size: 25px;
          color: grey;
          margin-left: 14px;
          transition: 0.3s;
        }
        .settings-tray--right i:hover {
          color: #74b9ff;
          cursor: pointer;
        }

        .search-box {
          background: #fafafa;
          padding: 10px 13px;
        }
        .search-box .input-wrapper {
          background: #fff;
          border-radius: 40px;
        }
        .search-box .input-wrapper i {
          color: grey;
          margin-left: 7px;
          vertical-align: middle;
        }

        input {
          border: none;
          border-radius: 30px;
          width: 80%;
        }
        input::placeholder {
          color: #e3e3e3;
          font-weight: 300;
          margin-left: 20px;
        }
        input:focus {
          outline: none;
        }

        .friend-drawer {
          padding: 10px 15px;
          display: flex;
          vertical-align: baseline;
          background: #fff;
          transition: 0.3s ease;
        }
        .friend-drawer--grey {
          background: #eee;
        }
        .friend-drawer .text {
          margin-left: 12px;
          width: 70%;
        }
        .friend-drawer .text h6 {
          margin-top: 6px;
          margin-bottom: 0;
        }
        .friend-drawer .text p {
          margin: 0;
        }
        .friend-drawer .time {
          color: grey;
        }
        .friend-drawer--onhover:hover {
          background: #74b9ff;
          cursor: pointer;
        }
        .friend-drawer--onhover:hover p,
        .friend-drawer--onhover:hover h6,
        .friend-drawer--onhover:hover .time {
          color: #fff !important;
        }
        p.fecha_mensaje {
            font-size: 0.8rem;
            margin-bottom: 5px;
            margin-top: 10px;
        }

        hr {
          margin: 5px auto;
          width: 60%;
        }

        .chat-bubble {
          padding: 10px 14px;
          background: #fff;
          margin: 10px 30px;
          border-radius: 9px;
          position: relative;
          animation: fadeIn 1s ease-in;
        }
        .chat-bubble2 {
          padding: 10px 14px;
          background: #d9fdd3;
          margin: 10px 30px;
          border-radius: 9px;
          position: relative;
          animation: fadeIn 1s ease-in;
        }
        .chat-bubble2:after {
          content: "";
          position: absolute;
          top: 50%;
          width: 0;
          height: 0;
          border: 20px solid transparent;
          border-bottom: 0;
          margin-top: -10px;
        }
        .chat-bubble:after {
          content: "";
          position: absolute;
          top: 50%;
          width: 0;
          height: 0;
          border: 20px solid transparent;
          border-bottom: 0;
          margin-top: -10px;
        }
        .chat-bubble--left:after {
          left: 0;
          border-right-color: #fff;
          border-left: 0;
          margin-left: -20px;
        }
        .chat-bubble--right:after {
          right: 0;
          border-left-color: #fff;
          border-right: 0;
          margin-right: -20px;
        }
        .chat-bubble2--right:after {
          right: 0;
          border-left-color: #d9fdd3;
          border-right: 0;
          margin-right: -20px;
        }

        @keyframes fadeIn {
          0% {
            opacity: 0;
          }
          100% {
            opacity: 1;
          }
        }
        .offset-md-9 .chat-bubble {
          background: #74b9ff;
          color: #fff;
        }

        .chat-box-tray {
          background: #eee;
          display: flex;
          align-items: baseline;
          padding: 10px 15px;
          align-items: center;
          margin-top: 19px;
          bottom: 0;
          position: absolute;
            width: 100%;
        }
        .chat-box-tray input {
          margin: 0 10px;
          padding: 6px 2px;
        }
        .chat-box-tray i {
          color: grey;
          font-size: 30px;
          vertical-align: middle;
        }
        .chat-box-tray i:last-of-type {
          margin-left: 25px;
        }
        html, body {
        height: 100%;
        margin: 0;
        }

        body {
        background-color: #0e283c;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center
        }

        .container {
        height: 90vh; /* Ajusta el contenedor al 100% de la altura de la ventana */
        max-width: 85vw; /* Asegura que el ancho no se desborde */
        overflow: hidden; /* Evita cualquier desbordamiento del contenedor */
        display: flex; /* Habilita la disposición flexible de los elementos internos */
        flex-direction: column; /* Organiza los elementos internos verticalmente */
        }

        .sidebar {
        overflow-y: scroll; /* Habilita desplazamiento vertical si es necesario */
        height: 82%;
        }

        .col-md-4.border-right {
        height: 100%; /* Asegura que este div use todo el alto disponible */
        }

        .chat-panel {
        flex-grow: 1; /* Permite que este div ocupe el espacio restante */
        overflow-y: scroll;
        height: 91%;
        padding-bottom: 5rem;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        }

        .no-gutters {
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .col-md-4.border-right, .col-md-8 {
                height: 100vh; /* Altura completa */
                width: 100vw; /* Ancho completo */
                position: fixed; /* Posición fija */
                top: 0;
                transition: transform 0.3s ease-in-out; /* Transición suave */
            }
            .col-md-4.border-right {
                z-index: 2; /* Asegura que el sidebar esté sobre el panel de chat */
            }
            .col-md-8 {
                z-index: 1;
                transform: translateX(100%); /* Oculta el panel de chat fuera de la pantalla */
            }
            /* .back-to-sidebar {
                display: block;
                position: absolute;
                top: 10px;
                left: 10px;
                z-index: 5; Asegura que el botón esté visible sobre todo lo demás
            } */
            .container {
                height: 100vh; /* Ajusta el contenedor al 100% de la altura de la ventana */
                max-width: 100vw; /* Asegura que el ancho no se desborde */
                overflow: hidden; /* Evita cualquier desbordamiento del contenedor */
                display: flex; /* Habilita la disposición flexible de los elementos internos */
                flex-direction: column; /* Organiza los elementos internos verticalmente */
                margin: 0 auto !important;
            }
            .back-to-sidebar {

            }
            .material-icons {
                font-size: 3rem !important;
                color: gray;
                margin-right: 1rem;
            }
            .chat-panel {
                flex-grow: 1;
                overflow-y: scroll;
                height: 100%;
                padding-bottom: 8rem;
                background-image: url(https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png);
            }
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper .clear-icon {
            position: absolute;
            right: 10px;
        }
        .back-to-sidebar {
            display: none;
            background: none;
            border: none;
            padding: 0;
        }
        @media (max-width: 768px) {
            .back-to-sidebar {
                display: block;
            }
        }

    </style>

</head>
<body>
    <div class="container">
        <div class="row no-gutters" style="height: inherit;">
            <div class="col-md-4 border-right">
                <div class="settings-tray">
                <img class="profile-image" src="https://i.ibb.co/KDgfpZt/logo-hawkins-oscuro-suite.jpg" alt="Profile img">
                <span class="settings-tray--right">
                    <i class="material-icons">cached</i>
                    <i class="material-icons">message</i>
                    <i class="material-icons">menu</i>
                </span>
                </div>
                <div class="search-box">
                    <div class="search-box">
                        <div class="input-wrapper">
                            <i class="material-icons">search</i>
                            <input placeholder="Search here" type="text" id="search-input">
                            <i class="material-icons clear-icon" style="display: none; cursor: pointer;">close</i>
                        </div>
                    </div>
                </div>
                <div class="sidebar">
                    @php
                    $items = $resultado;
                    function cmp($a, $b)
                    {
                        if ($a == $b) {
                            return 0;
                        }
                        return ($a['created_at'] < $b['created_at']) ? 1 : -1;
                    }
                    // dd($resultado);

                    foreach ($items as $key => $result) {
                    // $items = usort($result, "cmp");
                    $result = usort($result, "cmp");

                    }

                    // dd($items);

                    @endphp
                    @foreach ($items as $key => $item)
                    {{-- {{dd($items)}} --}}
                        <div class="friend-drawer friend-drawer--onhover" data-id="{{$key}}">
                            <img class="profile-image" src="https://media.istockphoto.com/id/1337144146/es/vector/vector-de-icono-de-perfil-de-avatar-predeterminado.jpg?s=612x612&w=0&k=20&c=YiNB64vwYQnKqp-bWd5mB_9QARD3tSpIosg-3kuQ_CI=" alt="">
                            <div class="text">
                                <h6>{{$item[0]->nombre_remitente}}</h6>
                                <small>{{$key}}</small>
                                <p class="text-muted">{{ \Illuminate\Support\Str::limit($item[0]->mensaje, 20, '...') }}</p>
                            </div>
                            <span class="time text-muted small">{{$item[0]->created_at}}</span>
                        </div>
                @endforeach
                </div>
            </div>
          <div id="chat-mensajes" class="col-md-8" style="display:none;height: 100%;">

          </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
        var data = @json($resultado);
        var datas = JSON.stringify(data)
        var messageInput = $('#message-input');

        // EVENTOS
        // $('.volver').on('click', function() {
        //     console.log('volver')
        //     $('.col-md-4.border-right').css('transform', 'translateX(0)');
        //     $('.col-md-8').css('transform', 'translateX(100%)');
        // });

        $( '.friend-drawer--onhover' ).on( 'click',  function() {
            if ($(window).width() <= 768) {
                    $('.col-md-4.border-right').css('transform', 'translateX(-100%)');
                    $('.col-md-8').css('transform', 'translateX(0)');
            }
            var remitenteId = $(this).attr('data-id'); // Obtén el ID del remitente del atributo data-id.
            var nombreRemitente = data[remitenteId][0]['nombre_remitente'];
            // data.forEach(function(item){
            //     item.remitente == remitenteId ? nombreRemitente = item.nombre_remitente : ''
            // })

            var template = `
            <div class="settings-tray">
                <div class="friend-drawer no-gutters friend-drawer--grey">
                    <button  onclick="volver()" class="back-to-sidebar">
                        <i class="material-icons volver">arrow_back</i>
                    </button >
                    <img class="profile-image" src="https://media.istockphoto.com/id/1337144146/es/vector/vector-de-icono-de-perfil-de-avatar-predeterminado.jpg?s=612x612&w=0&k=20&c=YiNB64vwYQnKqp-bWd5mB_9QARD3tSpIosg-3kuQ_CI=" alt="">
                    <div class="text">
                        <h6>${nombreRemitente}</h6>
                        <small>${remitenteId}</small>
                        <p style="display:none"class="text-muted">Layin' down the law since like before Christ...</p>
                    </div>
                </div>
            </div>
            <div class="chat-panel" id="contenedorChat">`
                // <span class="settings-tray--right">
                //   <i class="material-icons">cached</i>
                //   <i class="material-icons">message</i>
                //   <i class="material-icons">menu</i>
                // </span>

            var recorrer = data[$(this).attr('data-id')]
            function unicodeToChar(text) {
                return text.replace(/\\u[\dA-F]{4}/gi,
                        function (match) {
                            return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16));
                        });
            }

            var dataMensaje = [];


            $('#chat-mensajes').empty()
            $('#chat-mensajes').append(template).show()
            // var dataMensaje = [];
            // recorrer.sort(function(a, b){return b['created_at'] - a['created_at']})

            const sortedActivities = recorrer.sort((a, b) => {
              const date1 = new Date(a.created_at)
              const date2 = new Date(b.created_at)

              return date1 - date2;
            })

            // console.log(sortedActivities)


            Object.entries(sortedActivities).forEach(([key, value]) => {
                console.log(value)
                if(value.type == 'image'){
                    var templateChat = `
                        <div class="row no-gutters">
                            <div class="col-md-6">
                                <div class="chat-bubble chat-bubble--left">
                                    <img src="{{ asset('/imagenesWhatsapp/${value.mensaje}.jpeg') }}" style="width: -webkit-fill-available;">
                                </div>
                            </div>
                        </div>`
                }else {
                    if (value.mensaje != null) {
                        var templateChat = `
                        <div class="row no-gutters">
                            <div class="col-md-6">
                                <div class="chat-bubble chat-bubble--left">
                                    ${value.mensaje}
                                    <p class="fecha_mensaje">
                                        <small>
                                        ${formatDate(value.created_at)}
                                    </small>
                                    </p>
                                </div>
                            </div>
                        </div>`
                    }

                }
                if (value.respuesta != null) {
                    var templateChatRespuesta = `
                    <div class="row no-gutters" style="justify-content: end;">
                        <div class="col-md-6">
                            <div class="chat-bubble2 chat-bubble2--right" >
                                ${unicodeToChar(value.respuesta)}
                                <p class="fecha_mensaje">
                                    <small>
                                    ${formatDate(value.created_at)}
                                    </small>
                                    <br>
                                    <span class="estado_mensaje">${renderEstado(value.whatsapp_mensaje?.estado)}</span>
                                </p>
                            </div>
                        </div>
                    </div>`
                }

                console.log(value.whatsapp_mensaje?.estado)
                console.log(value)
                dataMensaje.push(templateChat)
                $('#contenedorChat').append(templateChat)
                $('#contenedorChat').append(templateChatRespuesta)
            })

            var templateFinal = `
            </div>
            </div>
            <div class="chat-box-tray">
                <textarea id="message-input" placeholder="Escribe un mensaje aquí..." class="form-control" rows="1" style="overflow:hidden;"></textarea>
                <button  id="send-icon" class="border-0 bg-transparent"><i class="material-icons send-icon" style="cursor: pointer;">send</i></button>
                <input type="file" id="image-upload" style="display: none;" accept="image/*">
                <img id="image-preview" src="#" alt="Vista previa de la imagen" style="display: none;">
            </div>`

            $('#contenedorChat').append(templateFinal)

            // console.log(dataMensaje)
            // console.log($(this).attr('data-id'))
            // console.log(data['34622440984'])

            // $( '.chat-bubble' ).hide('slow').show('slow');
            // Una vez que se han agregado los mensajes al contenedor del chat, haz scroll hasta el final.
            var chatPanel = $('#contenedorChat'); // Selecciona el contenedor del chat.
            var scrollHeight = chatPanel.prop('scrollHeight'); // Obtiene la altura total del contenedor, incluyendo el contenido no visible.
            chatPanel.scrollTop(scrollHeight); // Establece la posición del scroll al final.
            initializeDynamicTextarea();
        });

        $(document).ready(function() {
            // Manejar entrada en el campo de búsqueda
            $('#search-input').on('input', function() {
                var searchValue = $(this).val().toLowerCase();
                // Muestra el ícono de "x" cuando hay texto
                if (searchValue) {
                    $('.clear-icon').show();
                } else {
                    $('.clear-icon').hide();
                }

                // Filtrar la lista de amigos
                $(".friend-drawer").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(searchValue) > -1);
                });
            });

            // Manejar clic en el ícono de "x"
            $('.clear-icon').click(function() {
                $('#search-input').val(''); // Borra el texto del input
                $(this).hide(); // Oculta el ícono de "x"
                $(".friend-drawer").show(); // Muestra todos los amigos nuevamente
            });
        });

        $(document).ready(function() {

            $(document).on('drop', '.chat-box-tray', function(event) {
                event.preventDefault();
                var files = event.originalEvent.dataTransfer.files;
                if (files.length) {
                    // Asigna el archivo al input de tipo 'file'
                    $('#image-upload').prop('files', files);

                    // Llama a readURL para mostrar la vista previa
                    readURL(files[0]);
                }
            });

            // Mostrar vista previa de la imagen cargada
            // Función para mostrar vista previa de la imagen
            function readURL(file) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    $('#image-preview').attr('src', e.target.result).show();
                };

                reader.readAsDataURL(file);
            }

            // Delegar evento click para el botón de envío que se añade dinámicamente
            $(document).on('click', '#send-icon', function() {
                console.log('click boton envio');

                // Aquí iría tu lógica para enviar el mensaje o la imagen
                var messageText = $('#message-input').val();
                var imageFile = $('#image-upload').prop('files')[0]; // Si es que hay una imagen seleccionada
                console.log(imageFile);
                console.log(messageText);

                // Comprueba si se ha ingresado texto o seleccionado una imagen
                if(messageText || imageFile) {
                    // Lógica para enviar mensaje o imagen
                    console.log("Enviar mensaje o imagen");

                    // Limpiar input y campo de archivo después de enviar
                    $('#message-input').val('');
                    $('#image-upload').val('');
                    // Opcional: Resetear la vista previa de la imagen
                    $('#image-preview').attr('src', '#').hide();

                }
                $('#message-input').css('height', 'auto');

                initializeDynamicTextarea();

            });
        });

        /* FUNCIONES */
        function initializeDynamicTextarea() {
            // Selecciona dinámicamente el textarea en caso de que haya sido agregado recientemente
            var messageInput = $('#message-input');

            // Función para ajustar la altura automáticamente
            function adjustTextareaHeight() {
                // Solo ajusta si el elemento existe
                if (messageInput.length) {
                    messageInput.css('height', 'auto'); // Resetea la altura
                    messageInput.css('height', messageInput.prop('scrollHeight') + 'px'); // Ajusta la altura
                }
            }

            // Asegúrate de ajustar la altura inicialmente en caso de que haya contenido
            adjustTextareaHeight();

            // Adjunta el evento input para ajustar dinámicamente
            messageInput.off('input').on('input', function() {
                adjustTextareaHeight();
            });
        }

        function formatDate(dateString) {
            const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            const date = new Date(dateString);
            const dayOfWeek = days[date.getDay()];
            const formattedDate = dayOfWeek + ': ' +
                                ('0' + date.getDate()).slice(-2) + '-' +
                                ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                                date.getFullYear() + ' - ' +
                                ('0' + date.getHours()).slice(-2) + ':' +
                                ('0' + date.getMinutes()).slice(-2) + ':' +
                                ('0' + date.getSeconds()).slice(-2);
            return formattedDate;
        }

        // function volver(){
        //     console.log('volver')
        //     $('.col-md-4.border-right').css('transform', 'translateX(0)');
        //     $('.col-md-8').css('transform', 'translateX(100%)');
        // }

        function renderEstado(estado) {
            switch (estado) {
                case 'read': return '✅ Leído';
                case 'delivered': return '📬 Entregado';
                case 'sent': return '📤 Enviado';
                case 'failed': return '❌ Fallido';
                default: return '⏳ Pendiente';
            }
        }

        $('.friend-drawer--onhover').on('click', function () {
            const remitenteId = $(this).data('id');
            const nombreRemitente = data[remitenteId][0]['nombre_remitente'];

            let offset = 0;
            const limit = 20;
            let cargando = false;

            // Mostrar cabecera y contenedor
            const header = `<div class="settings-tray">
                <div class="friend-drawer no-gutters friend-drawer--grey">
                    <button  onclick="volver()" class="back-to-sidebar">
                        <i class="material-icons volver">arrow_back</i>
                    </button >
                    <img class="profile-image" src="https://media.istockphoto.com/id/1337144146/es/vector/vector-de-icono-de-perfil-de-avatar-predeterminado.jpg?s=612x612&w=0&k=20&c=YiNB64vwYQnKqp-bWd5mB_9QARD3tSpIosg-3kuQ_CI=" alt="">
                    <div class="text">
                        <h6>${nombreRemitente}</h6>
                        <small>${remitenteId}</small>
                        <p style="display:none"class="text-muted">Layin' down the law since like before Christ...</p>
                    </div>
                </div>
            </div>`;
            $('#chat-mensajes').html(header + `<div class="chat-panel" id="contenedorChat"></div>`).show();

            cargarMensajes(remitenteId);

            function cargarMensajes(remitenteId) {
                if (cargando) return;
                cargando = true;

                fetch(`/whatsapp/mensajes/${remitenteId}?limit=${limit}&offset=${offset}`)
                    .then(response => response.json())
                    .then(mensajes => {
                        mensajes.forEach(value => {
                            // Renderizar mensaje y respuesta igual que hacías antes
                            const mensajeHtml = renderMensaje(value);
                            $('#contenedorChat').append(mensajeHtml);
                        });

                        offset += mensajes.length;
                        cargando = false;

                        if (mensajes.length === limit) {
                            $('#contenedorChat').append(`<button id="loadMore" class="btn btn-sm btn-secondary btn-block mt-2">Cargar más</button>`);
                        }
                    });
            }

            $(document).on('click', '#loadMore', function () {
                $(this).remove(); // Quita el botón antes de recargar
                cargarMensajes(remitenteId);
            });
        });
        function renderMensaje(value) {
            const msgDate = formatDate(value.created_at);
            const estado = renderEstado(value.whatsapp_mensaje?.estado || '');

            let html = '';

            if (value.mensaje) {
                html += `
                    <div class="row no-gutters">
                        <div class="col-md-6">
                            <div class="chat-bubble chat-bubble--left">
                                ${value.mensaje}
                                <p class="fecha_mensaje"><small>${msgDate}</small></p>
                            </div>
                        </div>
                    </div>`;
            }

            if (value.respuesta) {
                html += `
                    <div class="row no-gutters" style="justify-content: end;">
                        <div class="col-md-6">
                            <div class="chat-bubble2 chat-bubble2--right">
                                ${value.respuesta}
                                <p class="fecha_mensaje"><small>${msgDate}<br><span class="estado_mensaje">${estado}</span></small></p>
                            </div>
                        </div>
                    </div>`;
            }

            return html;
        }
        function volver() {
            $('.col-md-4.border-right').css('transform', 'translateX(0)');
            $('.col-md-8').css('transform', 'translateX(100%)');
            $('#chat-mensajes').hide().empty(); // Oculta y limpia la conversación al volver
        }

    </script>
</body>
</html>









