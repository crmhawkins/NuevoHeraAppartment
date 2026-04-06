@extends('layouts.appAdmin')

@section('content')
<style>
  .drop-zone {
      max-width: 100%;
      padding: 50px;
      border: 2px dashed #cccccc;
      border-radius: 10px;
      text-align: center;
      color: #cccccc;
      font-family: Arial, sans-serif;
      cursor: pointer;
  }
  .drop-zone.dragover {
      border-color: #6666ff;
      color: #6666ff;
  }
</style>
<div class="container-fluid">
    <div class="d-flex flex-colum mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Subir Archivo de Banco') }}</h2>
    </div>
    <hr class="mb-5">
    <div class="row justify-content-center">
      <div class="drop-zone" id="drop-zone">
        Arrastra y suelta tu archivo aquí, o haz clic para seleccionarlo.
      </div>

      <!-- Input oculto para soportar clic en la zona de drop -->
      <input type="file" name="file" id="fileInput" accept=".xlsx" style="display:none;"

    </div>
</div>
@endsection

{{-- @include('sweetalert::alert') --}}

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('fileInput');

  // Abrir el input de archivos al hacer clic en la zona de drop
  dropZone.addEventListener('click', () => fileInput.click());

  // Añadir eventos de drag-and-drop
  dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.classList.add('dragover');
  });

  dropZone.addEventListener('dragleave', () => {
      dropZone.classList.remove('dragover');
  });

  dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.classList.remove('dragover');

      if (e.dataTransfer.files.length) {
          fileInput.files = e.dataTransfer.files;
          uploadFile(fileInput.files[0]);  // Subir el archivo arrastrado
      }
  });

  // Si selecciona archivo con el input
  fileInput.addEventListener('change', () => {
      if (fileInput.files.length) {
          uploadFile(fileInput.files[0]);
      }
  });

  // Función para subir el archivo con fetch
  function uploadFile(file) {
      let formData = new FormData();
      formData.append('file', file);

      fetch("{{ route('upload.excel') }}", {
          method: 'POST',
          body: formData,
          headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
      })
      .then(response => response.json())
      .then(data => {
        console.log(data)
          if (data.message) {
              // Preparar el mensaje con el resumen
              let mensaje = data.message;
              if (data.resumen) {
                  mensaje += `\n\n📊 Resumen del procesamiento:\n`;
                  mensaje += `• Total de filas: ${data.resumen.total_filas}\n`;
                  mensaje += `• Procesadas: ${data.resumen.procesados}\n`;
                  mensaje += `• Ingresos creados: ${data.resumen.ingresos_creados}\n`;
                  mensaje += `• Gastos creados: ${data.resumen.gastos_creados}\n`;
                  mensaje += `• Duplicados: ${data.resumen.duplicados}\n`;
                  mensaje += `• Errores: ${data.resumen.errores}`;
              }

              // Mostrar SweetAlert con información detallada
              Swal.fire({
                  title: 'Archivo Procesado!',
                  html: `
                      <div style="text-align: left;">
                          <p><strong>${data.message}</strong></p>
                      </div>
                  `,
                  icon: 'success',
                  confirmButtonText: 'Ver Detalles',
                  showCancelButton: true,
                  cancelButtonText: 'Cerrar'
              }).then((result) => {
                  if (result.isConfirmed) {
                      // Mostrar detalles en una nueva ventana
                      mostrarDetalles(data);
                  }
              });
          }
      })
      .catch(error => {
         console.log(error)

          // Mostrar un error si la subida falla
          Swal.fire({
              title: 'Error!',
              text: 'Ocurrió un error al procesar el archivo.',
              icon: 'error',
              confirmButtonText: 'Aceptar'
          });
      });
  }

  // Función para mostrar detalles del procesamiento
  function mostrarDetalles(data) {
      let html = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
      
      // Resumen
      if (data.resumen) {
          html += '<h5>📊 Resumen del Procesamiento</h5>';
          html += '<ul>';
          html += `<li><strong>Total de filas:</strong> ${data.resumen.total_filas}</li>`;
          html += `<li><strong>Procesadas:</strong> ${data.resumen.procesados}</li>`;
          html += `<li><strong>Ingresos creados:</strong> ${data.resumen.ingresos_creados}</li>`;
          html += `<li><strong>Gastos creados:</strong> ${data.resumen.gastos_creados}</li>`;
          html += `<li><strong>Duplicados:</strong> ${data.resumen.duplicados}</li>`;
          html += `<li><strong>Errores:</strong> ${data.resumen.errores}</li>`;
          if (data.resumen.hashes_huérfanos_eliminados > 0) {
              html += `<li style="color: #ffc107;"><strong>🔧 Hashes huérfanos eliminados:</strong> ${data.resumen.hashes_huérfanos_eliminados}</li>`;
          }
          html += '</ul>';
      }

      // Duplicados
      if (data.duplicados_detalle && data.duplicados_detalle.length > 0) {
          html += '<h5>⚠️ Registros Duplicados (No procesados)</h5>';
          html += '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0;">';
          data.duplicados_detalle.forEach(dup => {
              html += `<div style="border-bottom: 1px solid #eee; padding: 5px 0;">`;
              html += `<strong>Fila ${dup.fila}:</strong> ${dup.fecha} - ${dup.descripcion}<br>`;
              html += `<small>Debe: ${dup.debe} | Haber: ${dup.haber} | Saldo: ${dup.saldo}</small><br>`;
              html += `<small style="color: #666;">${dup.razon}</small><br>`;
              html += `<small style="color: #007bff;"><strong>Hash ID:</strong> ${dup.hash_id} | <strong>Hash:</strong> ${dup.hash}</small><br>`;
              if (dup.hash_created_at) {
                  html += `<small style="color: #28a745;"><strong>Hash Creado:</strong> ${dup.hash_created_at}</small><br>`;
              }
              
              // Mostrar información del registro original
              if (dup.registro_original) {
                  const tipo = dup.registro_original.tipo;
                  const color = tipo === 'ingreso' ? '#28a745' : '#dc3545';
                  const icon = tipo === 'ingreso' ? '💰' : '💸';
                  html += `<div style="background: #f8f9fa; padding: 8px; margin: 5px 0; border-left: 3px solid ${color};">`;
                  html += `<small style="color: ${color};"><strong>${icon} Registro Original (${tipo.toUpperCase()}):</strong></small><br>`;
                  html += `<small><strong>ID:</strong> ${dup.registro_original.id} | <strong>Fecha:</strong> ${dup.registro_original.fecha}</small><br>`;
                  html += `<small><strong>Concepto:</strong> ${dup.registro_original.concepto}</small><br>`;
                  html += `<small><strong>Importe:</strong> ${dup.registro_original.importe} € | <strong>Categoría ID:</strong> ${dup.registro_original.categoria_id}</small><br>`;
                  if (dup.registro_original.created_at) {
                      html += `<small><strong>Creado:</strong> ${dup.registro_original.created_at}</small>`;
                  }
                  html += `</div>`;
              } else {
                  html += `<small style="color: #ffc107;">⚠️ No se encontró el registro original</small><br>`;
              }
              
              html += '</div>';
          });
          html += '</div>';
      }

      // Errores
      if (data.errores_detalle && data.errores_detalle.length > 0) {
          html += '<h5>❌ Errores de Procesamiento</h5>';
          html += '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0;">';
          data.errores_detalle.forEach(error => {
              html += `<div style="border-bottom: 1px solid #eee; padding: 5px 0;">`;
              html += `<strong>Fila ${error.fila}:</strong> ${error.error}<br>`;
              html += `<small>Datos: ${JSON.stringify(error.datos)}</small>`;
              html += '</div>';
          });
          html += '</div>';
      }

      html += '</div>';

      Swal.fire({
          title: 'Detalles del Procesamiento',
          html: html,
          width: '800px',
          confirmButtonText: 'Cerrar'
      });
  }
</script>

@endsection

