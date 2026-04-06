# Script para renombrar migraciones a formato de 3 dígitos

# Migraciones con 1 dígito
$oneDigit = @(
    "05_create_personal_access_tokens_table.php",
    "07_create_apartamentos.php",
    "08_create_estado.php",
    "09_create_clientes.php"
)

# Migraciones con 2 dígitos
$twoDigits = @(
    "10_create_reservas.php",
    "11_create_estado_realizar_apartamento.php",
    "12_create_realizar_apartamento.php",
    "13_create_photo_categoria.php",
    "14_create_photos.php",
    "15_create_clientes_update.php",
    "16_update_reserva.php",
    "17_update_reserva.php",
    "18_update_reserva.php",
    "19_update_photo.php",
    "20_update_realizar_table.php",
    "21_update_user.php",
    "22_update_ photos.php",
    "23_update_photos.php",
    "24_create_whatsapp.php",
    "25_create_chat_gpt.php",
    "26_create_mensaje.php",
    "27_create_mensaje_auto_categoria.php",
    "28_create_mensaje_auto.php",
    "29_create_huesped.php",
    "30_update_photos.php",
    "31_update_clientes.php",
    "32_update_apartamentos.php",
    "33_update_clientes.php",
    "34_update_clientes.php",
    "35_update_cliente.php",
    "36_update_clientes.php",
    "37_update_huesped.php",
    "38_update_huesped.php",
    "39_create_comprobacion.php",
    "40_create_estados_mensajes.php",
    "41_update_apartamentos_edificio.php",
    "42_update_apartamentos.php",
    "43_update_chatgpt.php",
    "44_create_configuracion.php",
    "45_update_configuracion.php",
    "46_create_reparaciones.php",
    "47_create_fichajes.php",
    "48_create_pausas.php",
    "49_create_bancos.php",
    "50_create_categoria_gastos.php",
    "51_create_gasto.php",
    "52_update_gastos.php",
    "53_create_estados_gastos.php",
    "54_create_estados_ingresos.php",
    "55_create_categoria_ingresos.php",
    "56_create_ingresos.php",
    "57_update_reservas.php",
    "58_create_cuentas_contable.php",
    "59_create_grupo_contable.php",
    "60_create_sub_cuentas_contable.php",
    "61_create_sub_cuenta_hija.php",
    "62_create_sub_grupo_contable.php",
    "63_create_formas_pago.php",
    "64_create_diario_caja.php",
    "65_update_limpieza.php",
    "66_update_user.php",
    "67_create_anio.php",
    "68_update_anio.php",
    "69_update_anio.php",
    "70_create_estados_diario.php",
    "71_update_diario_caja.php",
    "72_create_prompt_asistente.php",
    "73_create_email_notificaciones.php",
    "74_update_tecnico.php",
    "75_update_tecnico.php",
    "76_create_limpiadora_guardia.php",
    "77_create_edificio.php",
    "78_update_apartamentos.php",
    "79_limpieza_fondo.php",
    "80_create_checklists.php",
    "81_create_items_checklists.php",
    "82_create_controles_limpieza.php",
    "83_update_limpieza.php",
    "84_create_proveedores.php",
    "85_create_apartamento_item_checklist.php",
    "86_create_invoices_status.php",
    "87_create_invoices.php",
    "88_create_invoice_reference_autoincrement.php",
    "89_update_invoices.php",
    "90_create_temporadas.php",
    "91_create_tarifas.php",
    "92_create_invoice_concepts.php",
    "93_create_emails.php",
    "94_create_status_email.php",
    "95_create_category_email.php",
    "96_update_email.php",
    "97_create_checklist_photo_requirements.php",
    "98_update_photo.php",
    "99_create_photo_requirements.php"
)

# Renombrar migraciones con 1 dígito
foreach ($migration in $oneDigit) {
    if (Test-Path $migration) {
        $newName = $migration -replace '^(\d)_', '00$1_'
        Write-Host "Renombrando $migration a $newName"
        Rename-Item $migration $newName
    }
}

# Renombrar migraciones con 2 dígitos
foreach ($migration in $twoDigits) {
    if (Test-Path $migration) {
        $newName = $migration -replace '^(\d{2})_', '0$1_'
        Write-Host "Renombrando $migration a $newName"
        Rename-Item $migration $newName
    }
}

Write-Host "Renombrado completado!"
