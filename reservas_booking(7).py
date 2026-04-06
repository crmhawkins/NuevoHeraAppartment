import json
import os
import platform
import re
import subprocess
import time
from datetime import datetime

import requests
from colorama import Fore
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from urllib.parse import urlparse, parse_qs

x2 = 955
y2 = 470

# Log in Email


def login_email(driver):
    user_booking = WebDriverWait(driver, 30).until(
        EC.presence_of_element_located((By.NAME, "loginname"))
    )
    user = "ivan@lchawkins.com"
    user_booking.send_keys(user)

    prueba_css = "whxYYRnvyHGyGqxO4ici"
    boton = driver.find_element("css selector", "." + prueba_css)
    boton.click()
    time.sleep(1)


# Log in Password


def login_password(driver):
    password_booking = WebDriverWait(driver, 30).until(
        EC.visibility_of_element_located((By.NAME, "password"))
    )
    password = "H4Kins4p4rtamento2024"
    password_booking.send_keys(password)

    prueba_css = "whxYYRnvyHGyGqxO4ici"
    boton = driver.find_element("css selector", "." + prueba_css)
    boton.click()
    time.sleep(1)


# Aceptar Cookies


def accept_cookies(driver):
    cookies = driver.find_element(By.ID, "onetrust-accept-btn-handler")
    cookies.click()
    time.sleep(1)

def get_hotel_id_from_url(url):
    parsed_url = urlparse(url)
    query_params = parse_qs(parsed_url.query)
    hotel_id = query_params.get('hotel_id', [None])[0]
    return hotel_id

def confimation_code(driver):
    filename = "sms.py"

    if platform.system() == "Windows":
        subprocess.Popen(["start", "cmd", "/k", "python", filename], shell=True)

    elif platform.system() == "Darwin":
        subprocess.Popen(
            [
                "osascript",
                "-e",
                f'tell app "Terminal" to do script "python3 {filename}"',
            ]
        )

    elif platform.system() == "Linux":
        subprocess.Popen(["gnome-terminal", "--", "python3", filename])

    time.sleep(15)

    send_sms = WebDriverWait(driver, 30).until(
        EC.presence_of_element_located((By.XPATH,"/html/body/div[1]/div/div/div/div[2]/div[1]/div/div/div/div/div/div/div[2]/a[2]",))
    )

    send_sms.click()

    what_number = WebDriverWait(driver, 30).until(
        EC.presence_of_element_located((By.XPATH,"/html/body/div[1]/div/div/div/div[2]/div[1]/div/div/div/div/div/div/div/div[2]/div/div/select",))
    )
    what_number.click()
    select_number = WebDriverWait(driver, 30).until(
        EC.presence_of_element_located((By.XPATH,"/html/body/div[1]/div/div/div/div[2]/div[1]/div/div/div/div/div/div/div/div[2]/div/div/select/option[4]",))
    )
    select_number.click()

    continue_botton = WebDriverWait(driver, 30).until(
        EC.presence_of_element_located((By.XPATH,"/html/body/div[1]/div/div/div/div[2]/div[1]/div/div/div/div/div/div/div/div[3]/button",))
    )
    continue_botton.click()
    time.sleep(15)

    with open("c:/sms_received.txt", "r") as file:
        fa_code = file.read()

    sms_input = WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.XPATH,"/html/body/div[1]/div/div/div/div[2]/div[1]/div/div/div/div/div/div/form/div[1]/div/div/div/div/input",))
    )
    sms_input.send_keys(fa_code)

    verify_now = WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.XPATH,"/html/body/div[1]/div/div/div/div[2]/div[1]/div/div/div/div/div/div/form/div[2]/button",))
    )
    verify_now.click()


# Enviar datos traves de webhook

def datos_reserva(driver):
    ubis = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Location"]'))
    )
    clients = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Guest name"]'))
    )
    checkins = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Check-in"]'))
    )
    checkouts = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Check-out"]'))
    )
    states = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Status"]'))
    )
    id_res = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Reservation number"]'))
    )
    precio_res = WebDriverWait(driver, 30).until(
        EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Total payment"]'))
    )

   

    # for ubi, client, checkin, checkout, state, idres, precio, phone in zip(ubis, clients, checkins, checkouts, states, id_res, phone_res, precio_res):
    for i, (ubi, client, checkin, checkout, state, idres, precio) in enumerate(zip(ubis, clients, checkins, checkouts, states, id_res, precio_res), start=1):
        try:
            checkin_object = datetime.strptime(checkin.text, "%d %b %Y")
            checkin_format = checkin_object.strftime("%Y-%m-%d")

            checkout_object = datetime.strptime(checkout.text, "%d %b %Y")
            checkout_format = checkout_object.strftime("%Y-%m-%d")
        except ValueError as e:
            print(f"Error al analizar la fecha: {e}")
            continue

        client_text = client.text
        client_pattern = re.compile(r'(.+?)\s*(\d+)\s*(persona|adulto|personas|adultos)?', re.IGNORECASE)
        match = client_pattern.match(client_text)
        if match:
            name_client, num_people, _ = match.groups()
        else:
            name_client = client_text
            num_people = None

        # phone_res = WebDriverWait(driver, 30).until(
        #     EC.presence_of_all_elements_located((By.CSS_SELECTOR, '[data-heading="Reservation number"]'))
        # )   
        # Esperar a que el elemento esté presente en la página y obtener el enlace
        xpath_id = f'(//td[@data-heading="Property ID"]/a/span)[{i}]'

        link_element = WebDriverWait(driver, 30).until(
            EC.presence_of_element_located((By.XPATH, xpath_id))
        )

        # Obtener el atributo href (URL) del enlace

        #url = link_element.get_attribute('href')

        hotel_id = link_element.text

        #print(url)
        print(hotel_id)

        # Paso 1: Obtén todas las pestañas abiertas antes de hacer clic
        original_window = driver.current_window_handle
        assert len(driver.window_handles) == 1

        # Paso 2: Haz clic en el enlace para abrir la nueva pestaña
        # Espera a que el elemento esté presente en la página y sea clickeable
        xpath_enlace = f'(//td[@data-heading="Guest name"]/div/div/a)[{i}]'

        elemento = WebDriverWait(driver, 30).until(
            EC.element_to_be_clickable((By.XPATH, xpath_enlace))
        )

        # Haz clic en el enlace
        elemento.click()

        # Paso 3: Espera a que la nueva pestaña se abra
        time.sleep(7)  # Mejor usar WebDriverWait en lugar de sleep

        # Paso 4: Encuentra la nueva ventana
        new_window = None
        for window_handle in driver.window_handles:
            if window_handle != original_window:
                new_window = window_handle
                break

        assert new_window is not None

        # Paso 5: Cambia el foco a la nueva pestaña
        driver.switch_to.window(new_window)

        # Paso 6: Interactúa con los elementos en la nueva pestaña
        # Aquí tu código para interactuar con la nueva pestaña
        boton = WebDriverWait(driver, 50).until(
            EC.element_to_be_clickable((By.XPATH, '/html/body/div[1]/div/div/div/main/div/div[2]/div[1]/div[1]/div[2]/div/div/div[2]/div/address/p[2]/div/div/span[2]/button'))
        )
        boton.click()
        # Espera 3 segundos
        time.sleep(3)

        # Encuentra el elemento y obtén el valor del atributo href
        span_element = WebDriverWait(driver, 30).until(
            EC.presence_of_element_located((By.XPATH, '/html/body/div[1]/div/div/div/main/div/div[2]/div[1]/div[1]/div[2]/div/div/div[2]/div/address/p[2]/div/div/span[2]/a'))
        )
        #print(span_element)
        #a_element = span_element.find_element(By.TAG_NAME, 'a')

        numero = span_element.get_attribute('href')
        phone = ''.join(c for c in numero if c.isdigit() or c == '+')

        

        # Paso 7: (Opcional) Cierra la nueva pestaña y vuelve a la original
        driver.close()
        driver.switch_to.window(original_window)


        update = {
            "origen": "Booking",
            "alias": name_client,
            "idiomas": None,
            "telefono": phone,
            "codigo_reserva": idres.text,
            "status": state.text,
            "fecha_entrada": checkin_format,
            "fecha_salida": checkout_format,
            "precio": precio.text,  # O alguna otra propiedad o método que devuelva el valor deseado
            "apartamento": hotel_id,
            "email": None,
            "numero_personas": num_people
        }

        # Mensaje JSON

        url_get = f"https://crm.apartamentosalgeciras.com/verificar-reserva/{idres}"
        url_post = "https://crm.apartamentosalgeciras.com/agregar-reserva"

        response = requests.get(url_get)
        headers = {"Content-Type": "application/json"}
        payload_json = json.dumps(update)

        if response.status_code == 200:
            print(f"\n{Fore.RED} > {Fore.RESET} La reserva {idres.text} ya esta añadida")

        else:
            response2 = requests.post(url_post, data=payload_json, headers=headers)
            print(f"\n{Fore.RED} > {Fore.RESET} Reserva {idres.text} añadida")
            print(response2)

# Funcion principal

def booking():
    username = os.getlogin()
    perfil_chrome = os.path.join(
        r"C:\Users", username, r"AppData\Local\Google\Chrome\User Data"
    )

    options = webdriver.ChromeOptions()
    user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    options.add_argument(f"user-agent={user_agent}")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument(f"--user-data-dir={perfil_chrome}")

    driver = webdriver.Chrome(options=options)
    driver.get("https://admin.booking.com/hotel/hoteladmin/groups/reservations/index.html")

    login_email(driver)
    login_password(driver)

    try:
        confimation_code(driver)
    except:
        print(" > No es necesario realizar la autentificaion en 2 pasos")
        
    datos_reserva(driver)
    time.sleep(5)
    driver.close()

# Ejecucion funcion booking

if __name__ == "__main__":
    booking()
