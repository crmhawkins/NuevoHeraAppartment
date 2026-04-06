<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Pruebas con la IA</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap-grid.min.css" integrity="sha512-i1b/nzkVo97VN5WbEtaPebBG8REvjWeqNclJ6AItj7msdVcaveKrlIIByDpvjk5nwHjXkIqGZscVxOrTb9tsMA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Estilo para la conversación */
    .chat-container {
      height: 70vh;
      overflow-y: scroll;
      padding-bottom: 20px;
      display: flex;
      flex-direction: column;
      padding: 0 38px 30px;
    }

    .message {
      padding: 10px 15px;
      border-radius: 20px;
      margin-bottom: 10px;
      display: inline-block;
      word-wrap: break-word;
    }

    .message.user {
      background-color: #dcf8c6;
      align-self: flex-end;
      text-align: right;
      border-bottom-right-radius: 0;
    }

    .message.bot {
      background-color: #fff;
      border: 1px solid #ddd;
      align-self: flex-start;
      text-align: left;
      border-bottom-left-radius: 0;
    }

    .input-container {
      display: flex;
      align-items: center;
      position: fixed;
      bottom: 0;
      width: 100%;
      padding: 10px;
      background-color: #f8f8f8;
      border-top: 1px solid #ddd;
      width: 97%;
      left: 0;
    }

    .input-container input {
      border: none;
      border-radius: 30px;
      padding: 10px;
      flex: 1;
      margin-right: 10px;
      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
    }

    .input-container button {
      background-color: #25d366;
      border: none;
      border-radius: 50%;
      padding: 10px;
      color: white;
      font-size: 18px;
      height: 45px;
      width: 45px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .input-container button i {
      font-size: 24px;
    }

    /* Quitar estilos de los inputs en focus */
    input:focus {
      outline: none;
    }
    .modal-dialog-scrollable .modal-content {
      height: 90%;
  }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

</head>
<body>
  <h3 class="text-center mt-4 mb-5 text-uppercase">Prueba del ChatBot de los Apartamentos</h3>
  
  <!-- Botón para abrir el modal -->
  <button class="btn btn-primary text-center m-auto d-flex" data-bs-toggle="modal" data-bs-target="#instruccionesModal">Instrucciones</button>

  <!-- Modal para mostrar y editar instrucciones -->
  <div class="modal fade " id="instruccionesModal" tabindex="-1" aria-labelledby="instruccionesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="instruccionesModalLabel">Instrucciones del ChatBot</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="instruccionesForm">
            @csrf
            <div class="form-group">
              <label for="instruccionesTextarea">Instrucciones:</label>
              <textarea class="form-control" id="instruccionesTextarea" rows="10" style="height: 573px;
}"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary" id="guardarInstruccionesBtn">Guardar Cambios</button>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-3" style="background-image: url(https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png); padding-right: 0; border-radius: 20px; padding-top: 30px;">
    <!-- Contenedor de la conversación -->
    <div class="chat-container">
      <ul class="list-group">
        @if (!empty($conversation))
          @foreach ($conversation as $intercambio)
            <!-- Preguntas (a la derecha) -->
            <li class="message user">
              <span>{{ $intercambio['pregunta'] }}</span>
            </li>
            <!-- Respuestas (a la izquierda) -->
            <li class="message bot">
              <span>{{ $intercambio['respuesta'] }}</span>
            </li>
          @endforeach
        @else
          <li class="message bot">Aún no hay conversación.</li>
        @endif
      </ul>
    </div>

    <!-- Área de entrada de texto con el botón de envío -->
    <form action="{{route('probarIA')}}" method="GET" class="input-container">
      @csrf
      <input name="texto" id="texto" placeholder="Escribe tu mensaje" class="form-control">
      <button type="submit">
        <i class="fas fa-paper-plane"></i> <!-- Icono de envío -->
      </button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

  <script>
    // Cargar instrucciones al abrir el modal
    document.getElementById('instruccionesModal').addEventListener('show.bs.modal', function () {
      fetch('{{ route("mostrarInstrucciones") }}')
        .then(response => response.json())
        .then(data => {
          document.getElementById('instruccionesTextarea').value = data.instrucciones;
        });
    });

    // Guardar las instrucciones modificadas
    document.getElementById('guardarInstruccionesBtn').addEventListener('click', function () {
      const instrucciones = document.getElementById('instruccionesTextarea').value;

      fetch('{{ route("guardarInstrucciones") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ instrucciones })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.status);
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('instruccionesModal'));
        modal.hide();
      });
    });
  </script>
</body>
</html>
