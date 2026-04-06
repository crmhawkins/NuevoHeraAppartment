<?php
/**
 * Test conexión MIR Hospedajes Sandbox
 * Autor: Hawkins
 * Versión: 2025-11
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* 🔧 CONFIGURACIÓN */
$endpoint2 = "https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion";
$endpoint = "https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion";
$usuario = "75900659S";     // <- Reemplaza con tus credenciales MIR sandbox
$contrasena = "vc52t@6U4VXwXSP";
$arrendador = "0000060524";          // HAWKINS REAL STATE SL
$establecimiento = "0000119331";     // HAWKINS COSTA

/* 🧩 1. Generamos el XML mínimo del parte de viajero */
$xml_minimo = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<solicitud>
  <codigoEstablecimiento>$establecimiento</codigoEstablecimiento>
  <comunicacion>
    <contrato>
      <referencia>TEST-RES-001</referencia>
      <fechaContrato>2025-11-06</fechaContrato>
      <fechaEntrada>2025-11-07T14:00:00</fechaEntrada>
      <fechaSalida>2025-11-08T10:00:00</fechaSalida>
      <numPersonas>1</numPersonas>
      <pago>
        <importe>100.00</importe>
        <moneda>EUR</moneda>
        <metodo>Efectivo</metodo>
      </pago>
    </contrato>
    <persona>
      <rol>VI</rol>
      <nombre>Test</nombre>
      <apellido1>Prueba</apellido1>
      <tipoDocumento>DNI</tipoDocumento>
      <numeroDocumento>12345678Z</numeroDocumento>
      <fechaNacimiento>1990-01-01</fechaNacimiento>
      <nacionalidad>ESP</nacionalidad>
      <sexo>H</sexo>
      <direccion>
        <pais>ESP</pais>
        <provincia>CA</provincia>
        <municipio>La Linea de la Concepción</municipio>
        <via>Av. España</via>
        <numero>12</numero>
        <codigoPostal>11300</codigoPostal>
      </direccion>
      <telefono>600000000</telefono>
      <correo>test@hawkins.es</correo>
    </persona>
  </comunicacion>
</solicitud>
XML;

/* 📦 2. Guardamos y comprimimos el XML */
file_put_contents("solicitud.xml", $xml_minimo);
$zip = new ZipArchive();
$zip->open("solicitud.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile("solicitud.xml", "solicitud.xml");
$zip->close();

/* 🔐 3. Codificamos el ZIP en Base64 */
$xml_base64 = base64_encode(file_get_contents("solicitud.zip"));

/* 🧠 4. Armamos la petición completa */
$peticion = <<<XML
<comunicacionRequest>
  <cabecera>
    <arrendador>$arrendador</arrendador>
    <aplicacion>HeraRent</aplicacion>
    <tipoOperacion>A</tipoOperacion>
    <tipoComunicacion>PV</tipoComunicacion>
  </cabecera>
  <solicitud>$xml_base64</solicitud>
</comunicacionRequest>
XML;

/* 🌐 5. Enviamos la petición */
$token = base64_encode("$usuario:$contrasena");
$headers = [
  "Authorization: Basic $token",
  "Content-Type: text/xml; charset=utf-8"
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $peticion,
  CURLOPT_HTTPHEADER => $headers,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_SSL_VERIFYHOST => false,
]);

$respuesta = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);

/* 📊 6. Resultado */
echo "=== ESTADO DE CONEXIÓN ===\n";
echo "HTTP CODE: " . $info['http_code'] . "\n\n";

if ($error) {
    echo "Error CURL: $error\n";
} else {
    echo "Respuesta del servidor:\n------------------------\n";
    echo htmlspecialchars($respuesta) . "\n\n";
}

/* 🧾 7. Guardar log */
file_put_contents("mir_test_log.xml", $respuesta);
echo "Log guardado en mir_test_log.xml\n";
?>
