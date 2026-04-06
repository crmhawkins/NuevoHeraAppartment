# Código de Integración MIR Hospedajes - Para Revisión con IA

## Contexto
Estamos integrando el envío de reservas al servicio web del Ministerio del Interior (MIR) según el Real Decreto 933/2021. Estamos recibiendo un error 502 del servidor y necesitamos verificar si hay un endpoint de login/autenticación que debamos usar primero.

## Información de Credenciales (Entorno de Pruebas)
- **Código Arrendador**: 0000004735
- **Código Establecimiento**: 0000003984
- **Usuario**: B56927809WS
- **Contraseña**: Temporal1
- **Endpoint Sandbox**: https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion
- **Endpoint Producción**: https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion

## Estructura XML que Enviamos

### XML Request (sin SOAP envelope):
```xml
<comunicacionRequest>
  <cabecera>
    <arrendador>0000060524</arrendador>
    <aplicacion>Hawkins Suite</aplicacion>
    <tipoOperacion>A</tipoOperacion>
    <tipoComunicacion>PV</tipoComunicacion>
  </cabecera>
  <solicitud>BASE64_DEL_ZIP_CON_XML</solicitud>
</comunicacionRequest>
```

### XML Interno (que va dentro del ZIP en Base64):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<solicitud>
  <codigoEstablecimiento>0000119330</codigoEstablecimiento>
  <comunicacion>
    <contrato>
      <referencia>5395547059</referencia>
      <fechaContrato>2025-12-05</fechaContrato>
      <fechaEntrada>2025-12-05T14:00:00</fechaEntrada>
      <fechaSalida>2025-12-06T12:00:00</fechaSalida>
      <numPersonas>4</numPersonas>
      <pago>
        <importe>100.00</importe>
        <moneda>EUR</moneda>
        <metodo>Efectivo</metodo>
      </pago>
    </contrato>
    <persona>
      <rol>VI</rol>
      <nombre>Maribel</nombre>
      <apellido1>Reina</apellido1>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>48859573Y</numeroDocumento>
      <fechaNacimiento>1990-01-01</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>M</sexo>
      <!-- más personas... -->
    </persona>
  </comunicacion>
</solicitud>
```

## Código PHP Actual

### Método de Envío (usando cURL):
```php
// Preparar autenticación
$credentials = base64_encode($usuario . ':' . $password);

// Headers
$headers = [
    'Authorization: Basic ' . $credentials,
    'Content-Type: text/xml; charset=utf-8'
];

// Configurar cURL
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $requestXml, // XML sin SOAP envelope
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // Solo en sandbox
    CURLOPT_SSL_VERIFYHOST => false, // Solo en sandbox
]);

// Ejecutar
$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
```

## Error Actual
- **HTTP Status**: 502 Proxy Error
- **Mensaje**: "The proxy server received an invalid response from an upstream server. Error reading from remote server"
- **Respuesta**: HTML de error del proxy, no XML

## Preguntas para la IA
1. ¿Existe un endpoint de login/autenticación previo que debamos usar antes de enviar las comunicaciones?
2. ¿La autenticación Basic Auth es correcta o necesitamos otro método?
3. ¿El formato del XML es correcto según la documentación oficial?
4. ¿Hay algún header adicional requerido que no estemos enviando?
5. ¿El error 502 puede ser por credenciales incorrectas o falta de autenticación previa?

## Referencias
- Documentación mencionada en correo: https://pre-sede.interior.gob.es/portal/sede/tramites?idAgrupacion=11
- Ejemplo de XML según correo oficial (con SOAP, pero nuestro archivo de prueba no lo usa):
```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
   <soapenv:Header/>
   <soapenv:Body>
      <com:comunicacionRequest>
         <peticion>
            <cabecera>
               <codigoArrendador>0000060524</codigoArrendador>
               <aplicacion>Hawkins Suite</aplicacion>
               <tipoOperacion>A</tipoOperacion>
               <tipoComunicacion>PV</tipoComunicacion>
            </cabecera>
            <solicitud>TEXTO EN BASE64 DEL FICHERO ZIP</solicitud>
         </peticion>
      </com:comunicacionRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

## Nota Importante
Tenemos un archivo de prueba (`test_mir_sandbox.php`) que NO usa SOAP envelope, solo XML directo. Pero el correo oficial menciona SOAP. Necesitamos aclarar cuál es el formato correcto.

## ✅ CORRECCIONES APLICADAS (según respuesta de IA)
1. **SOAP Envelope completo añadido**: Ahora incluye `<soapenv:Envelope>`, `<soapenv:Body>`, namespaces y nodo `<peticion>`
2. **Campo corregido**: Cambiado de `<arrendador>` a `<codigoArrendador>` (según XSD)
3. **Headers adicionales**: Añadidos `SOAPAction: ""`, `Accept: text/xml`, `User-Agent: PHP-cURL/8.2`

