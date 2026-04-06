# Informe de Error - Integración MIR Hospedajes

**Fecha:** 2025-12-05  
**Entorno:** Sandbox (pre-ses.mir.es)  
**Error:** HTTP 500 Internal Server Error

## Credenciales Utilizadas

- **Código Arrendador:** 0000004735
- **Código Establecimiento:** 0000003984
- **Usuario:** B56927809WS
- **Contraseña:** Temporal1
- **Endpoint:** https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion

## Detalles de la Petición

### Headers HTTP
```
POST /hospedajes-web/ws/v1/comunicacion HTTP/1.1
Host: hospedajes.pre-ses.mir.es
Accept: */*
Authorization: Basic QjU2OTI3ODA5V1M6VGVtcG9yYWwx
Content-Type: text/xml; charset=utf-8
Content-Length: 1243
```

### XML Request Enviado
```xml
<comunicacionRequest>
  <cabecera>
    <arrendador>0000004735</arrendador>
    <aplicacion>Hawkins Suite</aplicacion>
    <tipoOperacion>A</tipoOperacion>
    <tipoComunicacion>PV</tipoComunicacion>
  </cabecera>
  <solicitud>BASE64_DEL_ZIP_AQUI</solicitud>
</comunicacionRequest>
```

## Respuesta del Servidor

**Status Code:** 500 Internal Server Error  
**Content-Type:** application/json

```json
{
  "timestamp": "2025-12-05 10:51:44",
  "status": 500,
  "error": "Internal Server Error",
  "message": "",
  "path": "/hospedajes-web/ws/v1/comunicacion"
}
```

## XML Interno (contenido del ZIP)

El XML interno que se envía dentro del ZIP codificado en Base64 tiene la siguiente estructura:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<solicitud>
  <codigoEstablecimiento>0000003984</codigoEstablecimiento>
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
      <apellido2>Carrascoso</apellido2>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>48859573Y</numeroDocumento>
      <fechaNacimiento>1980-04-28</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>M</sexo>
      <direccion>
        <pais>ESP</pais>
        <via>Zurbaran 10</via>
      </direccion>
      <telefono>34622168855</telefono>
      <correo>juliagarciac05@gmail.com</correo>
    </persona>
    <!-- Más personas (huéspedes) -->
  </comunicacion>
</solicitud>
```

## Proceso de Envío

1. Se genera el XML interno con la estructura `<solicitud>` → `<comunicacion>` → `<contrato>` + `<persona>` (para cada viajero)
2. El XML se comprime en un archivo ZIP
3. El ZIP se codifica en Base64
4. El Base64 se incluye en el campo `<solicitud>` del XML request
5. Se envía mediante POST con autenticación Basic Auth

## Validaciones Realizadas

- ✅ XML interno válido (validado con `simplexml_load_string`)
- ✅ Estructura correcta según documentación
- ✅ Credenciales correctas (autenticación exitosa, no hay error 401)
- ✅ Formato idéntico al archivo de prueba proporcionado
- ✅ Sin SOAP envelope (formato directo XML)

## Preguntas para el Soporte MIR

1. ¿Hay algún campo obligatorio que estemos omitiendo en el XML interno?
2. ¿Hay alguna validación específica que esté fallando?
3. ¿El código de establecimiento 0000003984 está correctamente asociado al arrendador 0000004735 en el entorno de pruebas?
4. ¿Hay algún problema conocido con el servidor en este momento?
5. ¿Podrían proporcionar logs más detallados del error 500 para identificar la causa específica?

## Información Adicional

- **Lenguaje:** PHP 8.3.24
- **Método de envío:** cURL
- **SSL:** Verificación deshabilitada en sandbox (CURLOPT_SSL_VERIFYPEER = false)
- **Timeout:** 60 segundos

## XML Interno Completo

```xml
<?xml version="1.0" encoding="UTF-8"?>
<solicitud>
  <codigoEstablecimiento>0000003984</codigoEstablecimiento>
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
      <apellido2>Carrascoso</apellido2>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>48859573Y</numeroDocumento>
      <fechaNacimiento>1980-04-28</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>M</sexo>
      <direccion>
        <pais>ESP</pais>
        <via>Zurbaran 10</via>
      </direccion>
      <telefono>34622168855</telefono>
      <correo>juliagarciac05@gmail.com</correo>
    </persona>
    <persona>
      <rol>VI</rol>
      <nombre>Jose Antonio</nombre>
      <apellido1>Medina</apellido1>
      <apellido2>Troya</apellido2>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>48858011P</numeroDocumento>
      <fechaNacimiento>1975-10-07</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>H</sexo>
      <correo>juliagarciac05@gmail.com</correo>
    </persona>
    <persona>
      <rol>VI</rol>
      <nombre>Lucia</nombre>
      <apellido1>Medina</apellido1>
      <apellido2>Reina</apellido2>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>49127730Y</numeroDocumento>
      <fechaNacimiento>2004-09-29</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>M</sexo>
      <correo>juliagarciac05@gmail.com</correo>
    </persona>
    <persona>
      <rol>VI</rol>
      <nombre>Myrian</nombre>
      <apellido1>Lobo</apellido1>
      <apellido2>Reina</apellido2>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>47406330H</numeroDocumento>
      <fechaNacimiento>1996-11-07</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>M</sexo>
      <correo>juliagarciac05@gmail.com</correo>
    </persona>
  </comunicacion>
</solicitud>
```

## XML Request Completo (con Base64 del ZIP)

```xml
<comunicacionRequest>
  <cabecera>
    <arrendador>0000004735</arrendador>
    <aplicacion>Hawkins Suite</aplicacion>
    <tipoOperacion>A</tipoOperacion>
    <tipoComunicacion>PV</tipoComunicacion>
  </cabecera>
  <solicitud>UEsDBBQAAgAIAJRWhVv/WMjzWgIAAPYIAAAdAAAAcGFydGVfdmlhamVyb3NfNTM5NTU0NzA1OS54bWy9Vl1vmzAUfe+vQHkn2HwEmKi7qc3UTU0Vtemk7c0BN/MEvpGBqP33vUD4yqZO0bJEkcDnnntt33OuRHT1kqXGTuhcgrqc0CmZGELFkEi1uZw8rT6bweSKXUQ5pDKWRZmwC8OIqvgG5nnB16mIZSaFKoCR+ueEgRtZf2Y0uVmpZMxj3LACakgVmjfxGtDiWWg8huTMc0LPc33ihZE1gFvms4h/8us23ya2Z1LbJF5kjSMj/rwCEz6gr6j7gRD87/NaxijtkadymDVbUXuUtSe0SarMlthYUDxn2JLhsqVs+aY7Gy5ltgVdCEYJmVZV23XPyEAJ3GL+9BBZ+/dBUBSQAJvjYQq5A2Q0QLub1W9XKTRsTbRtjtZLACn79gV7js/uQpCttWALruVapHijZt2G+Vak2ACg7EFIxSOrBw4pNrvmWvM8hhx6nt3xCrmFG4jLrLbNzT0eZAwNeiz0AHeDwAs93/le93sUGml5zztT0jAgJnFNO9jLOIh129R2reVN2PxxidWHSEvLxQuwRWTVzxZMpBZx7/a97jJv6tRvfWCH3v5R6jXXXBkULbAbmN36rVRUiFQ8gwLmuDPbpjO8Pnq/Q1taDFoLYL/KVPIN1zhBMfE+bjIu0ykOZOWGmrD3xsgMx1jjK+TC+ISdUxLe8cdCJH81yErDKz+5NwJC6fIYb/g462gP/zTeuD3wxrl0uSux9D8KcjDUpxAkpLbvO+SIYbUJTioJTTv8P8N6LkEWr1py9Y4id7CGs+vhu2TmOOT2mAEJZyalJxuQ0+lRhYZfGli4+4Z5A1BLAQI/AxQAAgAIAJRWhVv/WMjzWgIAAPYIAAAdAAAAAAAAAAAAAAC2gQAAAABwYXJ0ZV92aWFqZXJvc181Mzk1NTQ3MDU5LnhtbFBLBQYAAAAAAQABAEsAAACVAgAAAAA=</solicitud>
</comunicacionRequest>
```

## Detalles Técnicos de la Petición cURL

**Comando cURL equivalente:**
```bash
curl -X POST https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion \
  -H "Authorization: Basic QjU2OTI3ODA5V1M6VGVtcG9yYWwx" \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "Accept: */*" \
  --data-binary @xml_request_completo.xml \
  --insecure \
  --verbose
```

**Opciones cURL utilizadas:**
- `CURLOPT_RETURNTRANSFER`: true
- `CURLOPT_POST`: true
- `CURLOPT_POSTFIELDS`: XML request completo
- `CURLOPT_HTTPHEADER`: Authorization (Basic Auth), Content-Type
- `CURLOPT_TIMEOUT`: 60 segundos
- `CURLOPT_CONNECTTIMEOUT`: 30 segundos
- `CURLOPT_SSL_VERIFYPEER`: false (solo en sandbox)
- `CURLOPT_SSL_VERIFYHOST`: false (solo en sandbox)

## Estadísticas

- **XML interno:** 2,294 bytes
- **ZIP comprimido:** ~758 bytes (estimado)
- **Base64 del ZIP:** 1,012 caracteres
- **XML Request completo:** 1,243 bytes
- **Total petición HTTP:** ~1,243 bytes

## Archivos Generados

Los archivos XML completos están disponibles en:
- `storage/logs/xml_interno_completo.xml` - XML interno sin comprimir
- `storage/logs/xml_request_completo.xml` - XML request completo con Base64

